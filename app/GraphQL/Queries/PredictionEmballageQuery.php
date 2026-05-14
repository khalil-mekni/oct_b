<?php

namespace App\GraphQL\Queries;

use App\Models\Emballage;
use App\Models\Entrepot;
use App\Models\MouvementStock;
use App\Services\PredictionEmballageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PredictionEmballageQuery
{
    public function __construct(
        private PredictionEmballageService $predictionService
    ) {
    }

    public function __invoke($_, array $args): array
    {
        $emballage = Emballage::findOrFail($args['emballage_id']);
        $entrepot = Entrepot::findOrFail($args['entrepot_id']);

        $granularity = $args['granularity'] ?? 'month';
        $periods = $args['periods'] ?? 12;
        $startDate = Carbon::parse($args['start_date'] ?? now());

        $results = [];

        for ($i = 0; $i < $periods; $i++) {
            $date = match ($granularity) {
                'day' => $startDate->copy()->addDays($i),
                'month' => $startDate->copy()->addMonths($i),
                'year' => $startDate->copy()->addYears($i),
                default => $startDate->copy()->addMonths($i),
            };

            $payload = $this->buildPayload(
                emballage: $emballage,
                entrepot: $entrepot,
                date: $date
            );

            $prediction = $this->predictionService->predict($payload);

            $results[] = [
                'periode' => $date->format('Y-m-d'),
                'quantite_predite' => $prediction['quantite_predite'] ?? 0,
            ];
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

        $consommations = $historique->pluck('quantite')->map(fn ($q) => (float) $q)->values();

        $consommationJ1 = $consommations->get(0, 0);
        $consommationJ7 = $consommations->take(7)->avg() ?? 0;
        $consommationJ30 = $consommations->take(30)->avg() ?? 0;

        $rollingStd30 = $this->std($consommations->take(30)->toArray());

        return [
            'date_prediction' => $date->format('Y-m-d'),

            'emballage_id' => $emballage->id,
            'entrepot_id' => $entrepot->id,

            'type_emballage' => $emballage->type ?? $emballage->nom ?? 'UNKNOWN',
            'region' => $entrepot->region ?? $entrepot->nom ?? 'UNKNOWN',

            'prix_unitaire' => $emballage->prix_unitaire ?? 0,
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
            'rolling_std_30j' => $rollingStd30,
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
}