<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Entrepot;
use App\Services\Alerts\AlertService;

class WarehouseCapacityAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $warehouses = Entrepot::query()->get();

        foreach ($warehouses as $warehouse) {
            $capacityTotal = (float) ($warehouse->capacite_totale ?? 0);
            $capacityUsed = (float) ($warehouse->stock_existant ?? 0);

            if ($capacityTotal <= 0) {
                $this->alertService->resolve(
                    AlertType::WAREHOUSE_CAPACITY_HIGH,
                    'entrepot',
                    $warehouse->id
                );
                continue;
            }

            $fillRate = ($capacityUsed / $capacityTotal) * 100;
            $severity = null;

            if ($fillRate > 95) {
                $severity = AlertSeverity::CRITICAL;
            } elseif ($fillRate > 85) {
                $severity = AlertSeverity::WARNING;
            }

            if ($severity) {
                $name = $warehouse->nom ?? ('ENT-' . $warehouse->id);

                $this->alertService->createOrUpdate([
                    'type' => AlertType::WAREHOUSE_CAPACITY_HIGH,
                    'title' => 'Capacité entrepôt élevée',
                    'message' => "L'entrepôt {$name} est rempli à " . round($fillRate, 2) . "%.",
                    'severity' => $severity,
                    'entity_type' => 'entrepot',
                    'entity_id' => $warehouse->id,
                    'action_url' => "/entrepots/{$warehouse->id}",
                    'metadata' => [
                        'entrepot_nom' => $name,
                        'capacite_totale' => $capacityTotal,
                        'stock_existant' => $capacityUsed,
                        'capacite_disponible' => $warehouse->capacite_disponible ?? null,
                        'fill_rate' => round($fillRate, 2),
                    ],
                ]);
            } else {
                $this->alertService->resolve(
                    AlertType::WAREHOUSE_CAPACITY_HIGH,
                    'entrepot',
                    $warehouse->id
                );
            }
        }
    }
}