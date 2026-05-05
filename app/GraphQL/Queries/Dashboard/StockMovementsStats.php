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

        $rows = MouvementStock::query()
            ->selectRaw('DATE(date_mouvement) as movement_date, type_mouvement, COUNT(*) as total')
            ->where('statut', 'VALIDE')
            ->whereBetween('date_mouvement', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->groupByRaw('DATE(date_mouvement), type_mouvement')
            ->orderBy('movement_date')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $date = $row->movement_date;
            $grouped[$date][$row->type_mouvement] = (int) $row->total;
        }

        $result = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');

            $entrees = (int) ($grouped[$key]['ENT'] ?? 0);
            $productionSorties = (int) ($grouped[$key]['PRD'] ?? 0);
            $pertes = (int) ($grouped[$key]['PTE'] ?? 0);
            $transferts = (int) ($grouped[$key]['CDD'] ?? 0);
            $splits = (int) ($grouped[$key]['SPL'] ?? 0);

            $result[] = [
                'label' => $date->format('d M'),
                'in_count' => $entrees,
                'out_count' => $productionSorties + $pertes,
                'transfer_count' => $transferts,
                'loss_count' => $pertes,
                'split_count' => $splits,
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