<?php

namespace App\GraphQL\Queries;

use App\Models\Contrat;
use App\Models\Emballage;
use App\Models\Entrepot;
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

        if (isset($args['entrepot_id']) && !empty($args['entrepot_id'])) {
            $entrepots = [Entrepot::findOrFail($args['entrepot_id'])];
        } else {
            $entrepots = Entrepot::all();
        }

        $granularity = $args['granularity'] ?? 'month';
        $periods = (int) ($args['periods'] ?? 12);

        $maxPeriods = match ($granularity) {
            'day' => 366,
            'month' => 60,
            'year' => 60,
            default => 60,
        };

        if ($periods > $maxPeriods) {
            Log::warning("Prediction periods capped from {$periods} to {$maxPeriods}");
            $periods = $maxPeriods;
        }

        if ($periods < 1) {
            $periods = 1;
        }

        $startDate = Carbon::parse($args['start_date'] ?? now());

        $unite = $this->getBusinessUnit($emballage);
        $prixUnitaire = $this->getPrixUnitaire($emballage);

        $allPayloads = [];
        $periodMetadata = [];

        for ($i = 0; $i < $periods; $i++) {
            $date = match ($granularity) {
                'day' => $startDate->copy()->addDays($i),
                'month' => $startDate->copy()->addMonths($i)->startOfMonth(),
                'year' => $startDate->copy()->addMonths($i)->startOfMonth(),
                default => $startDate->copy()->addMonths($i)->startOfMonth(),
            };

            $count = 0;

            foreach ($entrepots as $entrepot) {
                $allPayloads[] = $this->buildPayload($emballage, $entrepot, $date);
                $count++;
            }

            $periodMetadata[] = [
                'periode' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }

        Log::info('Sending batch prediction request', [
            'payload_count' => count($allPayloads),
            'periods' => $periods,
            'granularity' => $granularity,
            'entrepots_count' => count($entrepots),
        ]);

        $predictions = count($allPayloads) > 0
            ? $this->predictionService->predictBatch($allPayloads)
            : [];

        $results = [];
        $currentIndex = 0;

        foreach ($periodMetadata as $meta) {
            $periodPredictions = array_slice(
                $predictions,
                $currentIndex,
                $meta['count']
            );

            $totalQuantity = collect($periodPredictions)
                ->sum(fn ($item) => (float) ($item['quantite_predite'] ?? 0));

            $totalCost = $totalQuantity * $prixUnitaire;

            $results[] = [
                'periode' => $meta['periode'],
                'quantite_predite' => round($totalQuantity, 2),
                'prix_unitaire' => round($prixUnitaire, 3),
                'cout_predite' => round($totalCost, 2),
                'unite' => $unite,
            ];

            $currentIndex += $meta['count'];
        }

        return $results;
    }

    private function buildPayload($emballage, $entrepot, Carbon $date): array
    {
        $historique = MouvementStock::query()
            ->where('emballage_id', $emballage->id)
            ->where('entrepot_source_id', $entrepot->id)
            ->where('statut', 'VALIDE')
            ->whereIn('type_mouvement', ['PRD', 'PTE'])
            ->whereDate('date_mouvement', '<', $date)
            ->orderByDesc('date_mouvement')
            ->limit(30)
            ->get();

        $consommations = $historique
            ->pluck('quantite')
            ->map(fn ($q) => (float) $q)
            ->values();

        $consommationJ1 = $consommations->get(0, 0);
        $consommationJ7 = $consommations->take(7)->avg() ?? 0;
        $consommationJ30 = $consommations->take(30)->avg() ?? 0;

        return [
            'date_prediction' => $date->format('Y-m-d'),

            'emballage_id' => $emballage->id,
            'entrepot_id' => $entrepot->id,

            'type_emballage' => $emballage->name ?? $emballage->type ?? 'UNKNOWN',
            'region' => $entrepot->nom ?? 'UNKNOWN',

            'prix_unitaire' => $this->getPrixUnitaire($emballage),
            'capacite_totale' => $entrepot->capacite_totale ?? 1,

            'stock_initial' => $entrepot->stock_existant ?? 0,
            'stock_final' => $entrepot->stock_existant ?? 0,

            'reception_ent' => 0,
            'transfert_in_cdd' => 0,
            'transfert_out_cdd' => 0,

            'contrat_actif' => 1,
            'commandes_en_cours' => 0,

            'consommation_j_1' => $consommationJ1,
            'consommation_j_7' => $consommationJ7,
            'consommation_j_30' => $consommationJ30,

            'rolling_mean_7j' => $consommationJ7,
            'rolling_mean_30j' => $consommationJ30,
            'rolling_std_30j' => $this->std($consommations->take(30)->toArray()),
        ];
    }

    private function std(array $values): float
    {
        $count = count($values);

        if ($count <= 1) {
            return 0;
        }

        $mean = array_sum($values) / $count;

        $variance = array_sum(array_map(
            fn ($value) => pow($value - $mean, 2),
            $values
        )) / ($count - 1);

        return sqrt($variance);
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
        $contrat = Contrat::query()
            ->where('emballage_id', $emballage->id)
            ->where('statut', 'ACTIF')
            ->latest('id')
            ->first();

        if ($contrat && $contrat->prix_unitaire !== null) {
            return (float) $contrat->prix_unitaire;
        }

        return (float) ($emballage->prix_unitaire ?? 0);
    }
}