<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;
use App\Models\Entrepot;

final class DashboardOverview
{
    public function __invoke($_, array $args): array
    {
        $totalWarehouses = Entrepot::query()->count();

        $totals = Entrepot::query()
            ->selectRaw('
                COALESCE(SUM(capacite_totale), 0) as total_capacity,
                COALESCE(SUM(stock_existant), 0) as total_stock,
                COALESCE(SUM(capacite_disponible), 0) as total_available_capacity
            ')
            ->first();

        $totalCapacity = (float) ($totals->total_capacity ?? 0);
        $totalStock = (float) ($totals->total_stock ?? 0);
        $totalAvailableCapacity = (float) ($totals->total_available_capacity ?? 0);

        $capacityUsageRate = $totalCapacity > 0
            ? round(($totalStock / $totalCapacity) * 100, 2)
            : 0.0;

        $activeAlertsCount = Alert::query()
            ->where('is_active', true)
            ->count();

        $criticalAlertsCount = Alert::query()
            ->where('is_active', true)
            ->where('severity', 'critical')
            ->count();

        $warningAlertsCount = Alert::query()
            ->where('is_active', true)
            ->where('severity', 'warning')
            ->count();

        $unreadAlertsCount = Alert::query()
            ->where('is_active', true)
            ->where('status', 'unread')
            ->count();

        return [
            'totalWarehouses' => $totalWarehouses,
            'totalStock' => $totalStock,
            'totalCapacity' => $totalCapacity,
            'totalAvailableCapacity' => $totalAvailableCapacity,
            'capacityUsageRate' => $capacityUsageRate,
            'activeAlertsCount' => $activeAlertsCount,
            'criticalAlertsCount' => $criticalAlertsCount,
            'unreadAlertsCount' => $unreadAlertsCount,
            'warningAlertsCount' => $warningAlertsCount,
        ];
    }
}