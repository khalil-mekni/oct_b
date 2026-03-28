<?php

namespace App\Services;

use App\Models\BonLivraison;
use App\Models\Commande;
use App\Models\Entrepot;
use App\Models\Emballage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\StockService;
use App\Services\MouvementStockService;

class BonLivraisonService
{
    public function __construct(
        private StockService $stockService,
        private MouvementStockService $mouvementStockService
    ) {}

    private const STATUTS = ['EN_ATTENTE', 'VALIDE'];

    public function create(array $data, $file)
    {
        $userId = auth()->id() ?? 1;

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw ValidationException::withMessages([
                'document_bl' => 'Fichier invalide.'
            ]);
        }

        $commande = Commande::where('numero_commande', $data['numero_commande'])->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'numero_commande' => 'Commande non trouvée.'
            ]);
        }

        if (!Emballage::find($data['emballage_id'])) {
            throw ValidationException::withMessages([
                'emballage_id' => 'Emballage introuvable.'
            ]);
        }

        if (!Entrepot::find($data['entrepot_id'])) {
            throw ValidationException::withMessages([
                'entrepot_id' => 'Entrepôt introuvable.'
            ]);
        }

        if ((float) $data['quantite_recue'] <= 0) {
            throw ValidationException::withMessages([
                'quantite_recue' => 'Quantité reçue doit être positive.'
            ]);
        }

        $totalAvant = BonLivraison::where('commande_id', $commande->id)->sum('quantite_recue');
        $totalApres = $totalAvant + (float) $data['quantite_recue'];

        if ($totalApres > (float) $commande->quantite) {
            throw ValidationException::withMessages([
                'quantite_recue' => 'La quantité reçue dépasse la quantité commandée.'
            ]);
        }

        return DB::transaction(function () use ($data, $file, $commande, $userId) {
            $path = $file->store('bon_livraisons', 'public');

            $bl = BonLivraison::create([
                ...$data,
                'numero_bl' => 'BL-' . now()->format('Ymd-His'),
                'commande_id' => $commande->id,
                'statut' => 'VALIDE',
                'document_bl' => $path,
                'date_validation' => now(),
                'validated_by' => $userId,
                'receptionne_par' => $userId,
            ]);

            $draft = $this->mouvementStockService->createDraft([
                'type_mouvement' => 'ENT',
                'emballage_id' => (int) $bl->emballage_id,
                'entrepot_destination_id' => (int) $bl->entrepot_id,
                'quantite' => (float) $bl->quantite_recue,
                'date_mouvement' => $bl->date_reception,
                'user_id' => $userId,
            ]);

            $this->mouvementStockService->validateMovement($draft);

            $total = BonLivraison::where('commande_id', $commande->id)->sum('quantite_recue');

            if ($total < $commande->quantite) {
                $commande->statut = 'PARTIELLEMENT_RECEPTIONNEE';
            } else {
                $commande->statut = 'RECEPTIONNEE';
            }

            $commande->save();

            return $bl->refresh();
        });
    }

    public function update(BonLivraison $bonLivraison, array $data): BonLivraison
    {
        if ($bonLivraison->statut !== 'EN_ATTENTE') {
            throw new \InvalidArgumentException("Update allowed only if statut is EN_ATTENTE.");
        }

        $allowed = ['date_reception', 'emballage_id', 'quantite_recue', 'numero_commande', 'entrepot_id', 'statut'];
        $data = array_intersect_key($data, array_flip($allowed));

        if (isset($data['statut'])) {
            $data['statut'] = strtoupper($data['statut']);

            if (!in_array($data['statut'], self::STATUTS, true)) {
                throw new \InvalidArgumentException("Invalid statut.");
            }
        }

        if (isset($data['quantite_recue']) && $data['quantite_recue'] <= 0) {
            throw new \InvalidArgumentException("quantite_recue must be > 0.");
        }

        if (isset($data['numero_commande'])) {
            $commande = Commande::where('numero_commande', $data['numero_commande'])->first();

            if (!$commande) {
                throw new \InvalidArgumentException("Commande not found.");
            }

            $data['commande_id'] = $commande->id;
        }

        if (isset($data['entrepot_id'])) {
            $entrepot = Entrepot::find($data['entrepot_id']);

            if (!$entrepot) {
                throw new \InvalidArgumentException("Entrepot not found.");
            }
        }

        if (isset($data['emballage_id'])) {
            $emballage = Emballage::find($data['emballage_id']);

            if (!$emballage) {
                throw new \InvalidArgumentException("Emballage not found.");
            }
        }

        $bonLivraison->update($data);

        return $bonLivraison->refresh();
    }

    public function delete(BonLivraison $bonLivraison): BonLivraison
    {
        if ($bonLivraison->statut !== 'EN_ATTENTE') {
            throw new \InvalidArgumentException("Delete allowed only if statut is EN_ATTENTE.");
        }

        $bonLivraison->delete();

        return $bonLivraison;
    }

    private function generateLotCode(BonLivraison $bonLivraison): string
    {
        return 'LOT-' . now()->format('YmdHis') . '-' . $bonLivraison->id;
    }

    private function generateMovementCode(): string
    {
        return 'MVT-' . now()->format('YmdHis');
    }
}