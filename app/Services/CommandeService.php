<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Contrat;
use Illuminate\Support\Str;
use App\Models\BonLivraison;
use Illuminate\Support\Facades\DB;

class CommandeService
{
    private const STATUTS = ['BROUILLON','VALIDEE','ANNULEE','LIVREP','LIVREC'];

    public function create(array $data): Commande
    {
        foreach (['date_livraison_prevue', 'emballage_id', 'quantite', 'fournisseur_id', 'entrepot_id'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("$field is required.");
            }
        }

        $data['numero_commande'] = 'CMD-' . strtoupper(Str::random(8));
        $data['date_commande'] = now()->toDateString();
        $data['statut'] = 'BROUILLON';
        $data['created_by'] = 1;

        if ($data['quantite'] <= 0) {
            throw new \InvalidArgumentException("quantite must be > 0.");
        }

        if (strtotime($data['date_livraison_prevue']) < strtotime($data['date_commande'])) {
            throw new \InvalidArgumentException("date_livraison_prevue must be >= date_commande.");
        }

        $contrat = Contrat::query()
            ->where('fournisseur_id', $data['fournisseur_id'])
            ->where('statut', 'ACTIF')
            ->latest('id')
            ->first();

        if (!$contrat) {
            throw new \InvalidArgumentException("No contract found for this fournisseur.");
        }

        $data['contrat_id'] = $contrat->id;

        return DB::transaction(function () use ($data) {
            $commande = Commande::create($data);

            BonLivraison::create([
                'numero_bl'       => 'BL-' . strtoupper(Str::random(8)),
                'date_reception'  => '1111-01-01',
                'entrepot_id'     => $commande->entrepot_id,
                'receptionne_par' => 1,
                'statut'          => 'EN_ATTENTE',
                'commande_id'     => $commande->id,
                'numero_commande' => $commande->numero_commande,
                'emballage_id'    => $commande->emballage_id,
                'quantite_recue'  => $commande->quantite,
            ]);

            return $commande;
        });
    }

    public function update(Commande $commande, array $data): Commande
    {
        if ($commande->statut === 'LIVREC') {
            throw new \InvalidArgumentException("Cannot update a LIVREC commande.");
            
        }

        if (isset($data['statut'])) {
            $data['statut'] = strtoupper($data['statut']);

            if (!in_array($data['statut'], self::STATUTS, true)) {
                throw new \InvalidArgumentException("Invalid statut.");
            }
        }

        if (isset($data['quantite']) && $data['quantite'] <= 0) {
            throw new \InvalidArgumentException("quantite must be > 0.");
        }

        $commande->update($data);

        return $commande->refresh();
    }

    public function cancel(Commande $commande): Commande
    {
        if ($commande->statut !== 'VALIDEE') {
            throw new \InvalidArgumentException("Cannot cancel a delivered commande.");
            }
        $commande->update(['statut' => 'ANNULEE']);
        return $commande->refresh();
    }
    public function drop(Commande $commande): bool
    {
        if ($commande->statut !== 'BROUILLON') {
            throw new \InvalidArgumentException("Only BROUILLON commandes can be dropped.");
            }
        return DB::transaction(function () use ($commande) {
            BonLivraison::where('commande_id', $commande->id)->delete();
            return (bool) $commande->delete();
            }
        );
    }
}