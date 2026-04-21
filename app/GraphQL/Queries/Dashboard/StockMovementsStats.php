<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\MouvementStock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

final class StockMovementsStats
{
    public function __invoke($_, array $args): array
    {
        $period = $args['period'] ?? '7d';

        [$startDate, $endDate] = $this->resolvePeriod($period);

        $rows = MouvementStock::query()
            ->selectRaw('DATE(created_at) as movement_date, type_mouvement, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupByRaw('DATE(created_at), type_mouvement')
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
            $result[] = [
                'label' => $date->format('d M'),
                'in_count' => (int) ($grouped[$key]['ENT'] ?? 0),
                'out_count' => (int) ($grouped[$key]['PTE'] ?? 0),
                'transfer_count' => (int) ($grouped[$key]['CDD'] ?? 0),
                'loss_count' => (int) (($grouped[$key]['PRD'] ?? 0) + ($grouped[$key]['SPL'] ?? 0)),
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