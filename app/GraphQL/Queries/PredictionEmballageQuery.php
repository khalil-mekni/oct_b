<?php

namespace App\GraphQL\Queries;

use App\Models\Contrat;
use App\Models\Emballage;
use App\Models\Entrepot;
use App\Models\EntrepotLot;
use App\Models\MouvementStock;
use App\Services\PredictionEmballageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PredictionEmballageQuery
{
    public function __construct(
        private PredictionEmballageService $predictionService
    ) {
    }

    public function __invoke($_, array $args): array
    {
        $emballage = Emballage::findOrFail($args['emballage_id']);
        $entrepotId = $args['entrepot_id'] ?? null;

        if (!empty($entrepotId)) {
            $entrepots = [Entrepot::findOrFail($entrepotId)];
        } else {
            $entrepots = Entrepot::all();
        }

        // 1. Calcul du stock actuel
        $stockActuelQuery = EntrepotLot::where('emballage_id', $emballage->id);
        if (!empty($entrepotId)) {
            $stockActuelQuery->where('entrepot_id', $entrepotId);
        }
        $stockActuelInitial = (float) $stockActuelQuery->sum('quantite');

        // 2. Capacité totale combinée des entrepôts
        $capaciteTotale = collect($entrepots)->sum('capacite_totale') ?: 1000000;

        // 3. Récupération des commandes en cours (Reste à recevoir)
        $commandesEnCoursQuery = \App\Models\Commande::where('emballage_id', $emballage->id)
            ->whereIn('statut', ['VALIDEE', 'PARTIELLEMENT_RECEPTIONNEE']);
        if (!empty($entrepotId)) {
            $commandesEnCoursQuery->where('entrepot_id', $entrepotId);
        }
        $commandesEnCours = $commandesEnCoursQuery->get();

        $granularity = $args['granularity'] ?? 'month';
        $periods = (int) ($args['periods'] ?? 12);
        $startDateInput = Carbon::parse($args['start_date'] ?? now());
        $now = Carbon::now();

        // On utilise la date demandée comme point de départ
        $startDate = $startDateInput->copy();

        $prixUnitaire = (float) $this->getPrixUnitaire($emballage);
        $unite = $this->getBusinessUnit($emballage);

        // --- PHASE 1 : Préparation des Périodes et Prédictions ---
        $periodMetadata = [];
        $allPayloads = [];
        
        // Suppression du mois virtuel forcé au début pour éviter les doublons complexes
        // Le mois actuel sera inclus naturellement s'il fait partie de la boucle

        for ($i = 0; $i < $periods; $i++) {
            $pStart = match ($granularity) {
                'day' => $startDate->copy()->addDays($i),
                'month' => $startDate->copy()->addMonths($i)->startOfMonth(),
                default => $startDate->copy()->addMonths($i)->startOfMonth(),
            };

            $meta = $this->buildPeriodMeta($pStart, $granularity, false, $entrepots, $emballage, $now);
            $periodMetadata[] = $meta;
            foreach ($meta['payloads'] as $p) $allPayloads[] = $p;
        }

        $predictionsRaw = count($allPayloads) > 0
            ? $this->predictionService->predictBatch($allPayloads)
            : [];

        // --- PHASE 2 : Simulation et Formatage ---
        $virtualStock = $stockActuelInitial;
        $results = [];
        $minStock = $emballage->min_stock ?: 500; 
        $currentIndex = 0;

        foreach ($periodMetadata as $meta) {
            $isCurrentMonth = $meta['is_current_month'];
            $isDayView = $granularity === 'day';
            
            // Check if this period is strictly in the past
            $isPast = false;
            if ($isDayView) {
                $isPast = $meta['start_date']->isBefore($now->copy()->startOfDay());
            } else {
                // Pour les mois, on considère passé ce qui se finit avant le début du mois actuel
                $isPast = $meta['end_date']->isBefore($now->copy()->startOfMonth());
            }

            // Récupération des prédictions (elles sont dans le bon ordre dans predictionsRaw)
            $count = count($meta['payloads']);
            $periodPredictions = array_slice($predictionsRaw, $currentIndex, $count);
            $currentIndex += $count;

            $totalQuantityPrediteRaw = collect($periodPredictions)->sum(fn ($item) => (float) ($item['quantite_predite'] ?? 0));
            $totalQuantityPredite = $totalQuantityPrediteRaw;

            // Ajustement des échelles
            if ($isDayView) {
                // Si le ML retourne un agrégat mensuel pour un jour donné (cas fréquent),
                // on divise par le nombre de jours du mois pour avoir une estimation journalière.
                $daysInMonth = Carbon::parse($meta['periode'])->daysInMonth;
                $totalQuantityPredite = $totalQuantityPrediteRaw / $daysInMonth;
            } elseif ($granularity === 'month' && $isCurrentMonth && $meta['days_in_period'] > 0) {
                // En vue mensuelle pour le mois actuel, on proratise selon les jours restants
                $daysRemaining = max(1, $meta['end_date']->day - $now->day);
                $totalQuantityPredite = ($totalQuantityPrediteRaw / $meta['days_in_period']) * $daysRemaining;
            }

            if ($isPast) {
                // Si la période est passée, on ne simule pas de mouvement de stock
                // car le stock actuel reflète déjà le passé.
                $results[] = [
                    'periode' => $meta['periode'],
                    'quantite_predite' => ceil($totalQuantityPredite),
                    'prix_unitaire' => round($prixUnitaire, 3),
                    'cout_predite' => round(ceil($totalQuantityPredite) * $prixUnitaire, 2),
                    'unite' => $unite,
                    'stock_actuel' => round($virtualStock, 2),
                    'stock_securite' => round($minStock, 2),
                    'stock_restant_prevu' => round($virtualStock, 2),
                    'quantite_recommandee' => 0,
                    'cout_recommande' => 0,
                    'alerte_rupture' => false,
                    'consommation_restante_mois' => 0,
                    'receptions_futures_mois' => 0,
                    'recommandations_plan' => [],
                ];
                continue;
            }

            // Réceptions prévues (uniquement pour le présent et le futur)
            $receptionsFutures = $commandesEnCours->filter(function ($cmd) use ($meta) {
                $d = Carbon::parse($cmd->date_livraison_prevue);
                return $d >= $meta['start_date'] && $d <= $meta['end_date'];
            })->sum('reste');

            $virtualStockAvant = $virtualStock;
            $virtualStock += $receptionsFutures;
            
            $besoinGlobal = ($totalQuantityPredite + $minStock) - $virtualStock;
            $recommandationsPeriode = [];
            $quantiteCommandeeCeMois = 0;

            if ($besoinGlobal > 0) {
                if ($isCurrentMonth || $isDayView) {
                    $recommandationsPeriode[] = [
                        'date_suggeree' => $now->copy()->addDay()->format('Y-m-d'),
                        'quantite' => ceil($besoinGlobal),
                        'description' => "Commande urgente",
                    ];
                    $quantiteCommandeeCeMois = $besoinGlobal;
                } else {
                    $nbCommandes = ($besoinGlobal > $minStock * 5) ? 3 : 2;
                    $qteParCommande = $besoinGlobal / $nbCommandes;
                    for ($j = 0; $j < $nbCommandes; $j++) {
                        $jour = ($j === 0) ? 5 : (($j === 1) ? 15 : 25);
                        $recommandationsPeriode[] = [
                            'date_suggeree' => $meta['start_date']->copy()->addDays($jour - 1)->format('Y-m-d'),
                            'quantite' => ceil($qteParCommande),
                            'description' => "Approvisionnement échelonné (" . ($j+1) . "/$nbCommandes)",
                        ];
                    }
                    $quantiteCommandeeCeMois = $besoinGlobal;
                }
            }

            $virtualStock += $quantiteCommandeeCeMois;
            $virtualStock -= $totalQuantityPredite;

            $results[] = [
                'periode' => $meta['periode'],
                'quantite_predite' => ceil($totalQuantityPredite),
                'prix_unitaire' => round($prixUnitaire, 3),
                'cout_predite' => round(ceil($totalQuantityPredite) * $prixUnitaire, 2),
                'unite' => $unite,
                'stock_actuel' => round($virtualStockAvant, 2),
                'stock_securite' => round($minStock, 2),
                'stock_restant_prevu' => round($virtualStock, 2),
                'quantite_recommandee' => ceil($quantiteCommandeeCeMois),
                'cout_recommande' => round(ceil($quantiteCommandeeCeMois) * $prixUnitaire, 2),
                'alerte_rupture' => $virtualStock <= $minStock,
                'consommation_restante_mois' => round($totalQuantityPredite, 2),
                'receptions_futures_mois' => round($receptionsFutures, 2),
                'recommandations_plan' => $recommandationsPeriode,
            ];
        }

        return $results;
    }

    private function buildPayload($emballage, $entrepot, Carbon $date): array
    {
        return [
            'date_prediction' => $date->format('Y-m-d'),
            'emballage_id' => (int) $emballage->id,
            'entrepot_id' => (int) $entrepot->id,
            'type_emballage' => $emballage->name ?? $emballage->type ?? 'UNKNOWN',
            'prix_unitaire' => (float) $this->getPrixUnitaire($emballage),
            'capacite_totale' => (float) ($entrepot->capacite_totale ?? 0),
            
            // Les champs suivants sont optionnels pour le nouveau modèle
            // mais gardés vides pour maintenir la structure attendue par api_prediction.py
            'region' => $entrepot->nom ?? 'UNKNOWN',
            'stock_initial' => (float) ($entrepot->stock_existant ?? 0),
        ];
    }

    private function buildPeriodMeta($pStart, $granularity, $isVirtual, $entrepots, $emballage, $now)
    {
        $isCurrentMonth = $pStart->isSameMonth($now);
        $daysInPeriod = ($granularity === 'month') ? $pStart->daysInMonth : 1;
        $dateKey = $pStart->format('Y-m-d');

        $payloads = [];
        // Pour la vue journalière, on utilise la date exacte du jour (pStart)
        // Pour la vue mensuelle, on utilise le premier jour de chaque mois pour que le ML voit la différence
        $predictionDate = $pStart->copy();

        foreach ($entrepots as $entrepot) {
            $payloads[] = $this->buildPayload($emballage, $entrepot, $predictionDate);
        }

        return [
            'periode' => $dateKey,
            'is_current_month' => $isCurrentMonth,
            'start_date' => $pStart->copy(),
            'end_date' => ($granularity === 'month') ? $pStart->copy()->endOfMonth() : $pStart->copy(),
            'is_virtual' => $isVirtual,
            'days_in_period' => $daysInPeriod,
            'payloads' => $payloads
        ];
    }

    private function getBusinessUnit($emballage): string
    {
        $type = strtolower($emballage->type ?? $emballage->name ?? '');

        if (str_contains($type, 'sac')) {
            return 'sacs';
        }

        if (str_contains($type, 'carton')) {
            return 'cartons';
        }

        if (str_contains($type, 'palette')) {
            return 'palettes';
        }

        if (str_contains($type, 'bidon')) {
            return 'bidons';
        }

        return 'unités';
    }

    private function getPrixUnitaire($emballage): float
    {
        // 1. Chercher un contrat ACTIF du même emballage avec prix_unitaire > 0
        $contratActif = Contrat::query()
            ->where('emballage_id', $emballage->id)
            ->where('statut', 'ACTIF')
            ->where('prix_unitaire', '>', 0)
            ->latest('id')
            ->first();

        if ($contratActif) {
            return (float) $contratActif->prix_unitaire;
        }

        // 2. Sinon chercher le dernier contrat du même emballage avec prix_unitaire > 0
        $dernierContratAvecPrix = Contrat::query()
            ->where('emballage_id', $emballage->id)
            ->where('prix_unitaire', '>', 0)
            ->latest('id')
            ->first();

        if ($dernierContratAvecPrix) {
            return (float) $dernierContratAvecPrix->prix_unitaire;
        }

        // 3. Sinon utiliser emballage.prix_unitaire si existe et > 0
        if (isset($emballage->prix_unitaire) && $emballage->prix_unitaire > 0) {
            return (float) $emballage->prix_unitaire;
        }

        // 4. Sinon calculer prix_unitaire = montant_ht / quantite_contractuelle si possible
        $dernierContrat = Contrat::query()
            ->where('emballage_id', $emballage->id)
            ->where('montant_ht', '>', 0)
            ->where('quantite_contractuelle', '>', 0)
            ->latest('id')
            ->first();

        if ($dernierContrat) {
            return (float) ($dernierContrat->montant_ht / $dernierContrat->quantite_contractuelle);
        }

        // 5. Sinon retourner 0
        return 0;
    }
}