<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Contrat;
use Illuminate\Support\Str;
use App\Models\BonLivraison;
use Illuminate\Support\Facades\DB;

class CommandeService
{
private const STATUTS = [
    'EN_ATTENTE',
    'VALIDEE',
    'PARTIELLEMENT_RECEPTIONNEE',
    'RECEPTIONNEE',
    'ANNULEE'
];
    public function create(array $data): Commande
{
    foreach (['date_livraison_prevue', 'emballage_id', 'quantite', 'fournisseur_id', 'entrepot_id'] as $field) {
        if (empty($data[$field])) {
            throw new \InvalidArgumentException("$field is required.");
        }
    }

    $data['numero_commande'] = 'CMD-' . strtoupper(Str::random(8));
    $data['date_commande'] = now()->toDateString();
    $data['statut'] = 'EN_ATTENTE'; // ✅ changé
    $data['created_by'] = 1;

    if ($data['quantite'] <= 0) {
        throw new \InvalidArgumentException("quantite must be > 0.");
    }

    $contrat = Contrat::query()
        ->where('fournisseur_id', $data['fournisseur_id'])
        ->latest('id')
        ->first();

    if (!$contrat) {
        throw new \InvalidArgumentException("No contract found for this fournisseur.");
    }

    $data['contrat_id'] = $contrat->id;

    return Commande::create($data); // ✅ simple
}


  public function update(Commande $commande, array $data): Commande
{
    if ($commande->statut !== 'EN_ATTENTE') {
        unset(
            $data['emballage_id'],
            $data['quantite'],
            $data['fournisseur_id'],
            $data['contrat_id'],
            $data['date_livraison_prevue'],
            $data['entrepot_id']
        );
    }

    if (isset($data['statut'])) {
        $data['statut'] = strtoupper($data['statut']);

        if (!in_array($data['statut'], self::STATUTS, true)) {
            throw new \InvalidArgumentException("Invalid statut.");
        }

        // ✅ SI la commande devient VALIDEE
        /*if ($data['statut'] === 'VALIDEE' && $commande->statut !== 'VALIDEE') {

            $contrat = $commande->contrat;

            $nouvelleQuantite = $contrat->quantite_realisee + $commande->quantite;

            $quantiteMax = $contrat->quantite_contractuelle * (1 + $contrat->taux_depassement_autorise);

            if ($nouvelleQuantite > $quantiteMax) {
                throw new \InvalidArgumentException("Quantité dépasse la limite autorisée du contrat.");
            }

            $contrat->update([
                'quantite_realisee' => $nouvelleQuantite
            ]);
        }*/
    }

    if (isset($data['quantite']) && $data['quantite'] <= 0) {
        throw new \InvalidArgumentException("quantite must be > 0.");
    }

    $commande->update($data);

    return $commande->refresh();
}

    public function cancel(Commande $commande): Commande
    {
        $commande->update(['statut' => 'ANNULEE']);
        return $commande->refresh();
    }
    public function drop(Commande $commande): bool
    {
        if ($commande->statut !== 'EN_ATTENTE') {
            throw new \InvalidArgumentException("Only EN_ATTENTE commandes can be dropped.");
            }
        return DB::transaction(function () use ($commande) {
            BonLivraison::where('commande_id', $commande->id)->delete();
            return (bool) $commande->delete();
            }
        );
    }
}