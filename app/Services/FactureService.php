<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Commande;
use App\Models\BonLivraison;
use App\Models\Emballage;
use App\Models\Contrat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FactureService
{
    private const STATUTS = ['BROUILLON','VALIDE','PAYE'];
    private const TVA = 19;

    public function create(array $data): Facture
    {
        foreach (['numero_facture','date_facture','montant_ht','emballage_id','quantite_facturee','commande_id'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \InvalidArgumentException("$field is required.");
            }
        }

        if ($data['montant_ht'] <= 0) {
            throw new \InvalidArgumentException("montant_ht must be > 0.");
        }

        if ($data['quantite_facturee'] <= 0) {
            throw new \InvalidArgumentException("quantite_facturee must be > 0.");
        }

        $emballage = Emballage::find($data['emballage_id']);
        if (!$emballage) {
            throw new \InvalidArgumentException("Emballage not found.");
        }

        return DB::transaction(function () use ($data) {
            $commande = Commande::findOrFail($data['commande_id']);

            $bonLivraison = BonLivraison::query()
                ->where('commande_id', $commande->id)
                ->latest('id')
                ->first();

            if (!$bonLivraison) {
                throw new \InvalidArgumentException("No bon de livraison found for this commande.");
            }

            $data['statut'] = isset($data['statut']) ? strtoupper($data['statut']) : 'BROUILLON';
            if (!in_array($data['statut'], self::STATUTS, true)) {
                throw new \InvalidArgumentException("Invalid statut.");
            }

            $data['valide_par'] = 1;
            $data['fournisseur_id'] = $commande->fournisseur_id;
            $data['contrat_id'] = $commande->contrat_id;
            $data['bon_livraison_id'] = $bonLivraison->id;
            $data['montant_ttc'] = $this->calculateTtc($data['montant_ht']);

            return Facture::create($data);
        });
    }

    public function update(Facture $facture, array $data): Facture
    {
        $allowed = ['numero_facture','date_facture','montant_ht','emballage_id','quantite_facturee','commande_id','statut'];
        $data = array_intersect_key($data, array_flip($allowed));

        if (isset($data['statut'])) {
            $data['statut'] = strtoupper($data['statut']);
            if (!in_array($data['statut'], self::STATUTS, true)) {
                throw new \InvalidArgumentException("Invalid statut.");
            }
        }

        if (isset($data['montant_ht'])) {
            if ($data['montant_ht'] <= 0) {
                throw new \InvalidArgumentException("montant_ht must be > 0.");
            }
            $data['montant_ttc'] = $this->calculateTtc($data['montant_ht']);
        }

        if (isset($data['quantite_facturee']) && $data['quantite_facturee'] <= 0) {
            throw new \InvalidArgumentException("quantite_facturee must be > 0.");
        }

        if (isset($data['emballage_id'])) {
            $emballage = Emballage::find($data['emballage_id']);
            if (!$emballage) {
                throw new \InvalidArgumentException("Emballage not found.");
            }
        }

        if (isset($data['commande_id'])) {
            $commande = Commande::findOrFail($data['commande_id']);

            $bonLivraison = BonLivraison::query()
                ->where('commande_id', $commande->id)
                ->latest('id')
                ->first();

            if (!$bonLivraison) {
                throw new \InvalidArgumentException("No bon de livraison found for this commande.");
            }

            $data['fournisseur_id'] = $commande->fournisseur_id;
            $data['contrat_id'] = $commande->contrat_id;
            $data['bon_livraison_id'] = $bonLivraison->id;
        }

        $facture->update($data);
        return $facture->refresh();
    }

    public function delete(Facture $facture): bool
    {
        return (bool) $facture->delete();
    }

    // AJOUT PRINCIPAL
    public function createFromBonLivraisons(array $bonLivraisonIds): array
    {
        if (empty($bonLivraisonIds)) {
            throw ValidationException::withMessages([
                'bon_livraison_ids' => ['Aucun bon de livraison sélectionné.']
            ]);
        }

        return DB::transaction(function () use ($bonLivraisonIds) {
            $bonLivraisons = BonLivraison::query()
                ->whereIn('id', $bonLivraisonIds)
                ->orderBy('id')
                ->get();

            if ($bonLivraisons->count() !== count($bonLivraisonIds)) {
                throw ValidationException::withMessages([
                    'bon_livraison_ids' => ['Un ou plusieurs bons de livraison sont introuvables.']
                ]);
            }

            $numeroFacture = $this->generateNumeroFacture();
            $factures = [];

            foreach ($bonLivraisons as $bl) {
                $commande = Commande::findOrFail($bl->commande_id);

                if (!$commande->fournisseur_id) {
                    throw ValidationException::withMessages([
                        'commande' => ["La commande liée au BL #{$bl->id} n'a pas de fournisseur."]
                    ]);
                }

                $contrat = Contrat::query()
                    ->where('fournisseur_id', $commande->fournisseur_id)
                    ->where('emballage_id', $bl->emballage_id)
                    ->where('statut', 'ACTIF')
                    ->whereDate('date_debut', '<=', $bl->date_reception)
                    ->whereDate('date_fin', '>=', $bl->date_reception)
                    ->orderByDesc('id')
                    ->first();

                if (!$contrat) {
                    throw ValidationException::withMessages([
                        'contrat' => ["Aucun contrat actif trouvé pour le BL #{$bl->id}."]
                    ]);
                }

                $quantite = (float) $bl->quantite_recue;
                $prix = (float) $contrat->prix;
                $montantHt = round($quantite * $prix, 2);
                $montantTtc = $this->calculateTtc($montantHt);

                $factures[] = Facture::create([
                    'numero_facture' => $numeroFacture,
                    'date_facture' => now()->toDateString(),
                    'montant_ht' => $montantHt,
                    'montant_ttc' => $montantTtc,
                    'statut' => 'BROUILLON',
                    'emballage_id' => $bl->emballage_id,
                    'quantite_facturee' => $quantite,
                    'fournisseur_id' => $commande->fournisseur_id,
                    'contrat_id' => $contrat->id,
                    'commande_id' => $bl->commande_id,
                    'bon_livraison_id' => $bl->id,
                    'valide_par' => 1, // à remplacer plus tard par l'utilisateur connecté si besoin
                ]);
            }

            return [
                'numero_facture' => $numeroFacture,
                'factures' => $factures,
            ];
        });
    }

    private function generateNumeroFacture(): string
    {
        $prefix = 'FAC-' . now()->format('Ymd');

        $lastFacture = Facture::query()
            ->where('numero_facture', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->first();

        if (!$lastFacture) {
            return $prefix . '-0001';
        }

        $lastNumero = $lastFacture->numero_facture;
        $parts = explode('-', $lastNumero);
        $lastSequence = (int) end($parts);
        $nextSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextSequence;
    }

    private function calculateTtc($montantHt): float
    {
        return round(((float) $montantHt) * (1 + (self::TVA / 100)), 2);
    }
}