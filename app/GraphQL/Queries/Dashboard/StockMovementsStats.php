<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\MouvementStock;
use Carbon\CarbonPeriod;

final class StockMovementsStats
{
    public function __invoke($_, array $args): array
    {
        $period = $args['period'] ?? '7d';

        [$startDate, $endDate] = $this->resolvePeriod($period);

        $rows = MouvementStock::withoutGlobalScope('ordered')
    ->selectRaw('DATE(date_mouvement) as movement_date, type_mouvement, SUM(quantite) as total')
    ->where('statut', 'VALIDE')
    ->whereBetween('date_mouvement', [
        $startDate->copy()->startOfDay(),
        $endDate->copy()->endOfDay(),
    ])
    ->groupByRaw('DATE(date_mouvement), type_mouvement')
    ->orderByRaw('DATE(date_mouvement) ASC')
    ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $date = $row->movement_date;
            $grouped[$date][$row->type_mouvement] = (float) $row->total;
        }

        $result = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');

            $entrees = (float) ($grouped[$key]['ENT'] ?? 0);
            $productionSorties = (float) ($grouped[$key]['PRD'] ?? 0);
            $pertes = (float) ($grouped[$key]['PTE'] ?? 0);
            $transferts = (float) ($grouped[$key]['CDD'] ?? 0);
            $surplus = (float) ($grouped[$key]['SPL'] ?? 0);

            $result[] = [
                'label' => $date->format('d M'),
                'in_count' => $entrees,
                'out_count' => $productionSorties,
                'transfer_count' => $transferts,
                'loss_count' => $pertes,
                'surplus_count' => $surplus,
            ];
        }

        return $result;
    }

    private function resolvePeriod(string $period): array
    {
        $end = now();

        return match ($period) {
            '30d' => [$end->copy()->subDays(29), $end],
            '14d' => [$end->copy()->subDays(13), $end],
            '7d' => [$end->copy()->subDays(6), $end],
            default => [$end->copy()->subDays(6), $end],
        };
    }
}