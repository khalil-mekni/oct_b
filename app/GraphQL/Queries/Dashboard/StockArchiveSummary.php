<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Stock;
use Carbon\Carbon;

final class StockArchiveSummary
{
    public function __invoke($_, array $args): array
    {
        $start = Carbon::now()->subDays(6)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $totalIn = Stock::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('sens', 'ENTREE')
            ->sum('quantite');

        $totalOut = Stock::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('sens', 'SORTIE')
            ->sum('quantite');

        $todayIn = Stock::query()
            ->whereDate('created_at', Carbon::today())
            ->where('sens', 'ENTREE')
            ->sum('quantite');

        $todayOut = Stock::query()
            ->whereDate('created_at', Carbon::today())
            ->where('sens', 'SORTIE')
            ->sum('quantite');

        $days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            $in = Stock::query()
                ->whereDate('created_at', $date)
                ->where('sens', 'ENTREE')
                ->sum('quantite');

            $out = Stock::query()
                ->whereDate('created_at', $date)
                ->where('sens', 'SORTIE')
                ->sum('quantite');

            $days[] = [
                'date' => $date->format('d/m'),
                'in' => (float) $in,
                'out' => (float) $out,
                'balance' => (float) $in - (float) $out,
            ];
        }

        return [
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'today_in' => (float) $todayIn,
            'today_out' => (float) $todayOut,
            'balance' => (float) $totalIn - (float) $totalOut,
            'days' => $days,
        ];
    }
}