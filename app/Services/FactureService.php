<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Commande;
use App\Models\BonLivraison;
use App\Models\Emballage;
use Illuminate\Support\Facades\DB;

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

    private function calculateTtc($montantHt): float
    {
        return round(((float) $montantHt) * (1 + (self::TVA / 100)), 2);
    }
}