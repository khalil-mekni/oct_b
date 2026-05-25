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
        $capaciteTotale = collect($entrepots)->sum('capacite_totale') ?: 1000000; // Valeur par défaut si non renseigné

        // 3. Récupération des commandes en cours (Reste à recevoir)
        $commandesEnCoursQuery = \App\Models\Commande::where('emballage_id', $emballage->id)
            ->whereIn('statut', ['VALIDEE', 'PARTIELLEMENT_RECEPTIONNEE']);
        if (!empty($entrepotId)) {
            $commandesEnCoursQuery->where('entrepot_id', $entrepotId);
        }
        $commandesEnCours = $commandesEnCoursQuery->get();

        $granularity = $args['granularity'] ?? 'month';
        $periods = (int) ($args['periods'] ?? 12);
        $startDate = Carbon::parse($args['start_date'] ?? now());
        $now = Carbon::now();

        $prixUnitaire = $this->getPrixUnitaire($emballage);
        $unite = $this->getBusinessUnit($emballage);

        // --- PHASE 1 : Préparation des Périodes et Prédictions ---
        $periodMetadata = [];
        $allPayloads = [];
        
        // NOUVEAU : Si la date de début est dans le futur, on ajoute d'abord le reste du mois actuel
        if ($startDate->isAfter($now->copy()->endOfMonth())) {
            $currentMonthStart = $now->copy()->startOfMonth();
            $daysToPredict = max(0, $now->daysInMonth - $now->day);
            
            if ($daysToPredict > 0) {
                $count = 0;
                for ($d = 1; $d <= $daysToPredict; $d++) {
                    $currentDate = $now->copy()->addDays($d);
                    foreach ($entrepots as $entrepot) {
                        $allPayloads[] = $this->buildPayload($emballage, $entrepot, $currentDate);
                        $count++;
                    }
                }
                $periodMetadata[] = [
                    'periode' => $currentMonthStart->format('Y-m-d'),
                    'is_current_month' => true,
                    'count' => $count,
                    'start_date' => $currentMonthStart,
                    'end_date' => $now->copy()->endOfMonth(),
                    'is_virtual' => true, // Pour ne pas l'afficher dans le graph si besoin, mais ici on veut les recs
                ];
            }
        }

        for ($i = 0; $i < $periods; $i++) {
            $periodStartDate = match ($granularity) {
                'day' => $startDate->copy()->addDays($i),
                'month' => $startDate->copy()->addMonths($i)->startOfMonth(),
                default => $startDate->copy()->addMonths($i)->startOfMonth(),
            };

            // Éviter de doubler le mois actuel si déjà ajouté
            if (count($periodMetadata) > 0 && $periodStartDate->isSameMonth($periodMetadata[0]['start_date'])) {
                continue;
            }

            $isCurrentMonth = $periodStartDate->isSameMonth($now);
            $daysToPredict = 1;
            $startDayOffset = 0;

            if ($granularity === 'month') {
                $daysToPredict = $periodStartDate->daysInMonth;
                if ($isCurrentMonth) {
                    $startDayOffset = $now->day; 
                    $daysToPredict = max(0, $periodStartDate->daysInMonth - $startDayOffset);
                }
            }

            $count = 0;
            for ($d = 0; $d < $daysToPredict; $d++) {
                $currentDate = $periodStartDate->copy()->addDays($startDayOffset + $d);
                foreach ($entrepots as $entrepot) {
                    $allPayloads[] = $this->buildPayload($emballage, $entrepot, $currentDate);
                    $count++;
                }
            }

            $periodMetadata[] = [
                'periode' => $periodStartDate->format('Y-m-d'),
                'is_current_month' => $isCurrentMonth,
                'count' => $count,
                'start_date' => $periodStartDate->copy(),
                'end_date' => ($granularity === 'month') ? $periodStartDate->copy()->endOfMonth() : $periodStartDate->copy(),
                'is_virtual' => false,
            ];
        }

        $predictions = count($allPayloads) > 0
            ? $this->predictionService->predictBatch($allPayloads)
            : [];

        // --- PHASE 2 : Simulation Mensuelle Robuste (2-3 commandes max / mois) ---
        $virtualStock = $stockActuelInitial;
        $results = [];
        $minStock = $emballage->min_stock ?: 500; 
        $currentIndex = 0;

        foreach ($periodMetadata as $i => $meta) {
            $isCurrentMonth = $meta['is_current_month'];
            
            // 1. Prédiction pour la période
            $periodPredictions = array_slice($predictions, $currentIndex, $meta['count']);
            $totalQuantityPredite = collect($periodPredictions)->sum(fn ($item) => (float) ($item['quantite_predite'] ?? 0));

            // 2. Réceptions déjà prévues par l'utilisateur
            $receptionsFutures = $commandesEnCours->filter(function ($cmd) use ($meta) {
                $d = Carbon::parse($cmd->date_livraison_prevue);
                return $d >= $meta['start_date'] && $d <= $meta['end_date'];
            })->sum('reste');

            // 3. Calcul du besoin
            $virtualStockAvant = $virtualStock;
            $virtualStock += $receptionsFutures;
            
            $besoinGlobal = ($totalQuantityPredite + $minStock) - $virtualStock;
            $recommandationsPeriode = [];
            $quantiteCommandeeCeMois = 0;

            if ($besoinGlobal > 0) {
                if ($isCurrentMonth) {
                    // Mois actuel : 1 seule commande immédiate pour l'écart
                    $recommandationsPeriode[] = [
                        'date_suggeree' => $now->copy()->addDay()->format('Y-m-d'),
                        'quantite' => round($besoinGlobal, 2),
                        'description' => "Commande urgente (Reste du mois)",
                    ];
                    $quantiteCommandeeCeMois = $besoinGlobal;
                } else {
                    // Mois futurs : Split intelligent en 2 ou 3 commandes
                    $nbCommandes = ($besoinGlobal > $minStock * 5) ? 3 : 2;
                    $qteParCommande = $besoinGlobal / $nbCommandes;
                    
                    for ($j = 0; $j < $nbCommandes; $j++) {
                        $jourSuggere = ($j === 0) ? 5 : (($j === 1) ? 15 : 25);
                        $recommandationsPeriode[] = [
                            'date_suggeree' => $meta['start_date']->copy()->addDays($jourSuggere - 1)->format('Y-m-d'),
                            'quantite' => round($qteParCommande, 2),
                            'description' => "Approvisionnement échelonné (" . ($j+1) . "/$nbCommandes)",
                        ];
                    }
                    $quantiteCommandeeCeMois = $besoinGlobal;
                }
            }

            // Mise à jour du stock virtuel pour le mois suivant
            $virtualStock += $quantiteCommandeeCeMois;
            $virtualStock -= $totalQuantityPredite;

            $results[] = [
                'periode' => $meta['periode'],
                'quantite_predite' => round($totalQuantityPredite, 2),
                'prix_unitaire' => round($prixUnitaire, 3),
                'cout_predite' => round($totalQuantityPredite * $prixUnitaire, 2),
                'unite' => $unite,
                'stock_actuel' => round($virtualStockAvant, 2),
                'stock_securite' => round($minStock, 2),
                'stock_restant_prevu' => round($virtualStock, 2),
                'quantite_recommandee' => round($quantiteCommandeeCeMois, 2),
                'cout_recommande' => round($quantiteCommandeeCeMois * $prixUnitaire, 2),
                'alerte_rupture' => $virtualStock <= $minStock,
                
                'consommation_restante_mois' => round($totalQuantityPredite, 2),
                'receptions_futures_mois' => round($receptionsFutures, 2),
                'recommandations_plan' => $recommandationsPeriode,
            ];

            $currentIndex += $meta['count'];
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