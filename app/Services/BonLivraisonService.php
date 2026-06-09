<?php

namespace App\Services;

use App\Models\BonLivraison;
use App\Models\Commande;
use App\Models\Entrepot;
use App\Models\Emballage;
use App\Services\Alerts\AlertScanTriggerService;
use App\Services\StockService;
use App\Services\MouvementStockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BonLivraisonService
{
    
    public function __construct(
        private StockService $stockService,
        private MouvementStockService $mouvementStockService,
        //private AlertScanTriggerService $alertScanTrigger
    ) {
    }

    public function create(array $data, $file = null)
    {
        $userId = auth()->id() ?? 1;

        if ($file && (!$file instanceof UploadedFile || !$file->isValid())) {
            throw ValidationException::withMessages([
                'document_bl' => 'Fichier invalide.',
            ]);
        }

        $commande = Commande::where('numero_commande', $data['numero_commande'])->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'numero_commande' => 'Commande non trouvée.',
            ]);
        }

        if (!Emballage::find($data['emballage_id'])) {
            throw ValidationException::withMessages([
                'emballage_id' => 'Emballage introuvable.',
            ]);
        }

        if (!Entrepot::find($data['entrepot_id'])) {
            throw ValidationException::withMessages([
                'entrepot_id' => 'Entrepôt introuvable.',
            ]);
        }

        if ((float) $data['quantite_recue'] <= 0) {
            throw ValidationException::withMessages([
                'quantite_recue' => 'Quantité reçue doit être positive.',
            ]);
        }

        $totalAvant = BonLivraison::where('commande_id', $commande->id)->sum('quantite_recue');
        $totalApres = $totalAvant + (float) $data['quantite_recue'];

        if ($totalApres > (float) $commande->quantite) {
            throw ValidationException::withMessages([
                'quantite_recue' => 'La quantité reçue dépasse la quantité commandée.',
            ]);
        }
        

        $dateReception = isset($data['date_reception']) 
            ? \Illuminate\Support\Carbon::parse($data['date_reception'])->setTimeFrom(now()) 
            : now();

        return DB::transaction(function () use ($data, $file, $commande, $userId, $dateReception) {
            $path = $file ? $file->store('bon_livraisons', 'public') : null;

            $bl = BonLivraison::create([
                ...$data,
                'date_reception' => $dateReception,
                'numero_bl' => 'BL-' . now()->format('Ymd-His'),
                'commande_id' => $commande->id,
                'statut' => 'EN_ATTENTE',
                'document_bl' => $path,
                'receptionne_par' => $userId,
            ]);

            // Création du mouvement d'entrée en BROUILLON
            $this->mouvementStockService->createDraft([
                'type_mouvement' => 'ENT',
                'emballage_id' => (int) $bl->emballage_id,
                'entrepot_destination_id' => (int) $bl->entrepot_id,
                'quantite' => (float) $bl->quantite_recue,
                'date_mouvement' => $dateReception,
                'user_id' => $userId,
                'bon_livraison_id' => $bl->id,
            ]);

            return $bl->refresh();
        });
    }

    /**
     * Finalise la validation du BL (appelé quand le mouvement de stock est validé)
     */
    public function finalizeValidation(BonLivraison $bl): void
    {
        if ($bl->statut === 'VALIDE') {
            return;
        }

        $bl->update([
            'statut' => 'VALIDE',
            'date_validation' => now(),
            'validated_by' => auth()->id() ?? $bl->receptionne_par,
        ]);

        $commande = $bl->commande;
        if ($commande) {
            $totalValide = BonLivraison::where('commande_id', $commande->id)
                ->where('statut', 'VALIDE')
                ->sum('quantite_recue');

            if ($totalValide <= 0) {
                $commande->statut = 'EN_ATTENTE';
            } elseif ($totalValide < $commande->quantite) {
                $commande->statut = 'PARTIELLEMENT_RECEPTIONNEE';
            } else {
                $commande->statut = 'RECEPTIONNEE';
            }
            $commande->save();

            $this->incrementerQuantiteContrat($commande, (float) $bl->quantite_recue);
        }
    }

    public function update(BonLivraison $bonLivraison, array $data): BonLivraison
    {
        $allowed = ['date_reception', 'emballage_id', 'quantite_recue', 'numero_commande', 'entrepot_id', 'statut'];
        $data = array_intersect_key($data, array_flip($allowed));
        $commande = $bonLivraison->commande;
        

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
        if (isset($data['date_reception'])) {
            $data['date_reception'] = \Illuminate\Support\Carbon::parse($data['date_reception'])->setTimeFrom(now());
        }

        $ancienneQuantite = (float) $bonLivraison->quantite_recue;
        $nouvelleQuantite = isset($data['quantite_recue'])
         ? (float) $data['quantite_recue']
         : $ancienneQuantite;
        
        $diff = $nouvelleQuantite - $ancienneQuantite;
        
        $bonLivraison->update($data);
        
        if ($diff != 0 && $bonLivraison->statut === 'VALIDE') {
            $this->incrementerQuantiteContrat($commande, $diff);
        }

        if ($commande) {
            $totalValide = BonLivraison::where('commande_id', $commande->id)
                ->where('statut', 'VALIDE')
                ->sum('quantite_recue');
                
            if ($totalValide <= 0) {
                $commande->statut = 'EN_ATTENTE';
            } elseif ($totalValide < $commande->quantite) {
                $commande->statut = 'PARTIELLEMENT_RECEPTIONNEE';
            } else {
                $commande->statut = 'RECEPTIONNEE';
            }
            $commande->save();
        }
        
        return $bonLivraison->refresh();
    }

    public function delete(BonLivraison $bonLivraison): BonLivraison
    {
        $commande = $bonLivraison->commande;
        $quantite = (float) $bonLivraison->quantite_recue;
        
        if ($commande && $bonLivraison->statut === 'VALIDE') {
            $this->incrementerQuantiteContrat($commande, -$quantite);
        }

        // Supprimer les mouvements en brouillon associés
        \App\Models\MouvementStock::where('bon_livraison_id', $bonLivraison->id)
            ->where('statut', 'BROUILLON')
            ->delete();

       $bonLivraison->delete();

       if ($commande) {
        $totalValide = BonLivraison::where('commande_id', $commande->id)
            ->where('statut', 'VALIDE')
            ->sum('quantite_recue');
            
        if ($totalValide <= 0) {
            $commande->statut = 'EN_ATTENTE';
        } elseif ($totalValide < $commande->quantite) {
             $commande->statut = 'PARTIELLEMENT_RECEPTIONNEE';
        } else {
            $commande->statut = 'RECEPTIONNEE';
        }

        $commande->save();
    }

        return $bonLivraison;
    }
    private function incrementerQuantiteContrat(Commande $commande, float $quantite): void
    {
        $contrat = $commande->contrat;
        if (!$contrat) {
            return;
        }
        $contrat->quantite_realisee = max(0,(float) ($contrat->quantite_realisee ?? 0) + $quantite);
        
        $contrat->save();
    }
}