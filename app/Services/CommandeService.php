<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Contrat;
use App\Models\BonLivraison;
use App\Services\Alerts\AlertScanTriggerService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CommandeService
{
    private const STATUTS = ['EN_ATTENTE','VALIDEE','PARTIELLEMENT_RECEPTIONNEE','RECEPTIONNEE','ANNULEE'];

    public function __construct(
        //private AlertScanTriggerService $alertScanTrigger
    ) {
    }

     public function create(array $data): Commande
    {
        foreach (['date_livraison_prevue', 'emballage_id', 'quantite', 'fournisseur_id', 'entrepot_id'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ $field est requis.");
            }
        }

        if ((float) $data['quantite'] <= 0) {
            throw new \InvalidArgumentException("La quantité doit être supérieure à 0.");
        }

        $contrat = Contrat::where('fournisseur_id', $data['fournisseur_id'])->latest('id')->first();
        if (!$contrat) {
            throw new \InvalidArgumentException("Aucun contrat trouvé pour ce fournisseur.");
        }

        $data['numero_commande'] = 'CMD-' . strtoupper(Str::random(8));
        $data['date_commande'] = now()->toDateString();
        $data['statut'] = 'EN_ATTENTE';
        $data['contrat_id'] = $contrat->id;
        $data['created_by'] = auth()->id() ?? 1;

        $commande = Commande::create($data);
        //$this->alertScanTrigger->dispatch();
        return $commande->refresh();


    }

    public function update(Commande $commande, array $data): Commande
    {
        // 1. Si le statut change vers "VALIDEE", on vérifie le contrat
        if (isset($data['statut']) && strtoupper($data['statut']) === 'VALIDEE' && $commande->statut !== 'VALIDEE') {
            
            $contrat = $commande->contrat;
            if (!$contrat) {
                throw new \InvalidArgumentException("Impossible de valider : aucun contrat lié.");
            }

            // Cumul des quantités déjà engagées (Commandes validées ou en cours de réception)
            $dejaEngage = Commande::where('contrat_id', $contrat->id)
                ->whereIn('statut', ['VALIDEE', 'PARTIELLEMENT_RECEPTIONNEE', 'RECEPTIONNEE'])
                ->where('id', '!=', $commande->id)
                ->sum('quantite');

            $totalPrevu = $dejaEngage + $commande->quantite;

            // Calcul du plafond (Quantité contractuelle + Taux de dépassement)
            $taux = $contrat->taux_depassement_autorise ?? 0;
            $maxAutorise = $contrat->quantite_contractuelle * (1 + ($taux / 100));

            if ($totalPrevu > $maxAutorise) {
                throw ValidationException::withMessages([
                    'statut' => "Validation refusée : Le cumul des commandes ({$totalPrevu}) dépasserait le plafond du contrat ({$maxAutorise})."
                ]);
            }
        }

        // 2. Protection des champs si la commande n'est plus en attente
        if ($commande->statut !== 'EN_ATTENTE') {
            unset($data['emballage_id'], $data['quantite'], $data['fournisseur_id']);
        }

        $commande->update($data);
        //$this->alertScanTrigger->dispatch();
        return $commande->refresh();
    }
    public function cancel(Commande $commande): Commande
    {
        $commande->update(['statut' => 'ANNULEE']);

        //$this->alertScanTrigger->dispatch();

        return $commande->refresh();
    }

    public function drop(Commande $commande): bool
    {
        if ($commande->statut !== 'EN_ATTENTE') {
            throw new \InvalidArgumentException("Only EN_ATTENTE commandes can be dropped.");
        }

        $deleted = DB::transaction(function () use ($commande) {
            BonLivraison::where('commande_id', $commande->id)->delete();

            $result = (bool) $commande->delete();

            //$this->alertScanTrigger->dispatch();

            return $result;
        });

        return $deleted;
    }
}