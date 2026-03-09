<?php

namespace App\Services;

use App\Models\BonLivraison;
use App\Models\Commande;
use App\Models\Entrepot;
use App\Models\Emballage;
use Illuminate\Support\Str;

class BonLivraisonService
{
    private const STATUTS = ['EN_ATTENTE', 'VALIDE'];

    public function create(array $data)
    {
        foreach (['date_reception', 'emballage_id', 'quantite_recue', 'numero_commande', 'entrepot_id'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("$field is required.");
            }
        }

        $entrepot = Entrepot::find($data['entrepot_id']);
        if (!$entrepot) {
            throw new \Exception("Entrepot with ID {$data['entrepot_id']} not found.");
        }

        $emballage = Emballage::find($data['emballage_id']);
        if (!$emballage) {
            throw new \Exception("Emballage with ID {$data['emballage_id']} not found.");
        }

        if (empty($data['numero_bl'])) {
            $data['numero_bl'] = 'BL-' . strtoupper(Str::random(8));
        }

        $data['statut'] = strtoupper($data['statut'] ?? 'EN_ATTENTE');
        if (!in_array($data['statut'], self::STATUTS, true)) {
            throw new \InvalidArgumentException("Invalid statut.");
        }

        $data['receptionne_par'] = 1;

        if ($data['quantite_recue'] <= 0) {
            throw new \InvalidArgumentException("quantite_recue must be > 0.");
        }

        $commande = Commande::where('numero_commande', $data['numero_commande'])->first();

        if (!$commande) {
            throw new \InvalidArgumentException("Commande not found.");
        }

        $data['commande_id'] = $commande->id;

        return BonLivraison::create($data);
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
}