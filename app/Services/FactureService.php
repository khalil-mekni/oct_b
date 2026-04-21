<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\BonLivraison;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FactureService
{
    private const TVA_DEFAUT = 19;

    public function create(array $args): Facture
    {
        $input = $args['input'];

        // 1. Chargement des relations
        $firstBL = BonLivraison::with(['commande.contrat'])->findOrFail($input['bon_livraison_ids'][0]);
        $commande = $firstBL->commande;
        $contrat  = $commande->contrat;

        // 2. Calcul du prix unitaire
        // Si prix_unitaire existe dans le contrat, on l'utilise.
        // Sinon on garde ta logique actuelle basée sur montant_ht / quantite_contractuelle.
        $prixUnitaire = (float) ($contrat->prix_unitaire ?? 0);

        if ($prixUnitaire <= 0) {
            $montantTotalContrat = (float) ($contrat->montant_ht ?? 0);
            $qteTotaleContrat    = (float) ($contrat->quantite_contractuelle ?? 1);
            $prixUnitaire        = $qteTotaleContrat > 0 ? ($montantTotalContrat / $qteTotaleContrat) : 0;
        }

        // 3. Montant Brut calculé automatiquement
        $bons = BonLivraison::whereIn('id', $input['bon_livraison_ids'])->get();
        $totalQteRecue = $bons->sum('quantite_recue');
        $montantHtBrutCalcule = $totalQteRecue * $prixUnitaire;

        // 4. Calcul des pénalités et jours de retard
        $montantPenalitesCalcule = 0;
        $joursRetardTotalCalcule = 0;
        $detailsCalcul = "";

        $tauxJournalier  = (float) ($contrat->taux_penalite_retard ?? 0.0020);
        $plafondPourcent = (float) ($contrat->plafond_penalite ?? 5.00);
        $datePrevue      = Carbon::parse($commande->date_livraison_prevue);

        foreach ($bons as $bl) {
            $dateReception = Carbon::parse($bl->date_reception);

            if ($dateReception->gt($datePrevue)) {
                $jours = $dateReception->diffInDays($datePrevue);
                $joursRetardTotalCalcule += $jours;

                $valeurBL = (float) $bl->quantite_recue * $prixUnitaire;
                $penaliteBL = $valeurBL * $tauxJournalier * $jours;
                $montantPenalitesCalcule += $penaliteBL;

                $detailsCalcul .= "BL #{$bl->numero_bl}: {$jours} jrs retard (Val: " . round($penaliteBL, 3) . " DT); ";
            }
        }

        // Application du plafond sur la pénalité calculée
        $montantMaxPenalites = $montantHtBrutCalcule * ($plafondPourcent / 100);
        if ($montantPenalitesCalcule > $montantMaxPenalites) {
            $montantPenalitesCalcule = $montantMaxPenalites;
            $detailsCalcul .= " [Plafond de {$plafondPourcent}% appliqué]";
        }

        // 5. Remplissage des champs liés
        $input['emballage_id']      = $firstBL->emballage_id;
        $input['fournisseur_id']    = $commande->fournisseur_id;
        $input['contrat_id']        = $commande->contrat_id;
        $input['commande_id']       = $firstBL->commande_id;
        $input['quantite_facturee'] = $totalQteRecue;

        // 6. Valeurs modifiables par l'utilisateur ou calcul automatique par défaut
        $input['montant_ht'] = isset($input['montant_ht'])
            ? (float) $input['montant_ht']
            : round($montantHtBrutCalcule, 2);

        $input['montant_penalites'] = isset($input['montant_penalites'])
            ? (float) $input['montant_penalites']
            : round($montantPenalitesCalcule, 3);

        $input['jours_retard_total'] = isset($input['jours_retard_total'])
            ? (int) $input['jours_retard_total']
            : (int) $joursRetardTotalCalcule;

        // 7. Net HT recalculé à partir des valeurs finales
        $input['montant_ht_net'] = round(
            ((float) $input['montant_ht']) - ((float) $input['montant_penalites']),
            3
        );

        // Sécurité pour éviter un net négatif
        if ($input['montant_ht_net'] < 0) {
            $input['montant_ht_net'] = 0;
        }

        // 8. Détail pénalités
        $input['details_calcul_penalite'] = $detailsCalcul ?: "Aucun retard constaté";

        // 9. TTC calculé sur le net
        $input['montant_ttc'] = $this->calculateTtc($input['montant_ht_net'], $contrat);

        return DB::transaction(function () use ($input, $contrat, $totalQteRecue) {
            $facture = Facture::create($input);

            // Liaison des BL à la facture
            BonLivraison::whereIn('id', $input['bon_livraison_ids'])
                ->update([
                    'facture_id' => $facture->id,
                    'is_factured' => true,
                ]);

            // Mise à jour de la réalisation du contrat
            $contrat->increment('quantite_realisee', $totalQteRecue);

            return $facture;
        });
    }

    public function update(Facture $facture, array $input): Facture
    {
        if (isset($input['montant_ht'])) {
            $input['montant_ht'] = (float) $input['montant_ht'];
        }

        if (isset($input['montant_penalites'])) {
            $input['montant_penalites'] = (float) $input['montant_penalites'];
        }

        if (isset($input['jours_retard_total'])) {
            $input['jours_retard_total'] = (int) $input['jours_retard_total'];
        }

        $montantHt = array_key_exists('montant_ht', $input)
            ? (float) $input['montant_ht']
            : (float) $facture->montant_ht;

        $montantPenalites = array_key_exists('montant_penalites', $input)
            ? (float) $input['montant_penalites']
            : (float) ($facture->montant_penalites ?? 0);

        $montantHtNet = round($montantHt - $montantPenalites, 3);
        if ($montantHtNet < 0) {
            $montantHtNet = 0;
        }

        $input['montant_ht_net'] = $montantHtNet;

        $contrat = $facture->contrat;
        $input['montant_ttc'] = $this->calculateTtc($montantHtNet, $contrat);

        $facture->update($input);

        return $facture->refresh();
    }

    public function delete(Facture $facture): bool
    {
        return DB::transaction(function () use ($facture) {
            // Délier les BL de la facture
            BonLivraison::where('facture_id', $facture->id)
                ->update([
                    'facture_id' => null,
                    'is_factured' => false,
                ]);

            return (bool) $facture->delete();
        });
    }

    private function calculateTtc($montantHtNet, $contrat): float
    {
        $tauxTva = self::TVA_DEFAUT;
        return round(((float) $montantHtNet) * (1 + ($tauxTva / 100)), 2);
    }
}