<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Entrepot;
use App\Services\Alerts\AlertService;
use Illuminate\Support\Facades\Log;

class WarehouseCapacityAlertChecker
{
    public function __construct(
        private AlertService $alertService
    ) {
    }

    public function check(): void
    {
        $warehouses = Entrepot::query()->get();

        foreach ($warehouses as $warehouse) {
            $this->checkEntity($warehouse);
        }
    }

    public function checkWarehouse(int $warehouseId): void
    {
        $warehouse = Entrepot::query()
            ->where('id', $warehouseId)
            ->first();

        Log::info('WarehouseCapacityAlertChecker::checkWarehouse', [
            'warehouse_id' => $warehouseId,
            'found' => (bool) $warehouse,
            'class' => is_object($warehouse) ? get_class($warehouse) : gettype($warehouse),
        ]);

        if (!$warehouse) {
            $this->alertService->resolve(
                AlertType::WAREHOUSE_CAPACITY_HIGH,
                'entrepot',
                $warehouseId
            );

            return;
        }

        $this->checkEntity($warehouse);
    }

    /**
     * Accepte Entrepot, stdClass, array.
     */
    private function checkEntity(mixed $warehouse): void
    {
        if (!$this->isValidWarehousePayload($warehouse)) {
            Log::warning('WarehouseCapacityAlertChecker::checkEntity warehouse invalide', [
                'type' => is_object($warehouse) ? get_class($warehouse) : gettype($warehouse),
                'value' => $warehouse,
            ]);
            return;
        }

        $warehouseId = (int) $this->readValue($warehouse, 'id', 0);
        $warehouseName = (string) $this->readValue($warehouse, 'nom', 'ENT-' . $warehouseId);

        $capacityTotal = (float) $this->readValue($warehouse, 'capacite_totale', 0);
        $capacityUsed = (float) $this->readValue($warehouse, 'stock_existant', 0);

        $rawAvailable = $this->readValue($warehouse, 'capacite_disponible', null);
        $capacityAvailable = $rawAvailable !== null
            ? (float) $rawAvailable
            : ($capacityTotal - $capacityUsed);

        if ($capacityUsed < 0) {
            $capacityUsed = 0;
        }

        $fillRate = $capacityTotal > 0
            ? round(($capacityUsed / $capacityTotal) * 100, 2)
            : 0.0;

        Log::info('WarehouseCapacityAlertChecker::checkEntity values', [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'capacity_total' => $capacityTotal,
            'capacity_used' => $capacityUsed,
            'capacity_available' => $capacityAvailable,
            'fill_rate' => $fillRate,
            'payload_type' => is_object($warehouse) ? get_class($warehouse) : gettype($warehouse),
        ]);

        if ($capacityTotal <= 0) {
            Log::info('WarehouseCapacityAlertChecker::resolve no capacity', [
                'warehouse_id' => $warehouseId,
            ]);

            $this->alertService->resolve(
                AlertType::WAREHOUSE_CAPACITY_HIGH,
                'entrepot',
                $warehouseId
            );

            return;
        }

        $severity = null;

        if ($fillRate >= 95) {
            $severity = AlertSeverity::CRITICAL;
        } elseif ($fillRate >= 85) {
            $severity = AlertSeverity::WARNING;
        }

        if ($severity !== null) {
            Log::info('WarehouseCapacityAlertChecker::createOrUpdate', [
                'warehouse_id' => $warehouseId,
                'severity' => $this->enumValue($severity),
                'fill_rate' => $fillRate,
            ]);

            $this->alertService->createOrUpdate([
                'type' => AlertType::WAREHOUSE_CAPACITY_HIGH,
                'title' => 'Capacité entrepôt élevée',
                'message' => "L'entrepôt {$warehouseName} est rempli à {$fillRate}%.",
                'severity' => $severity,
                'entity_type' => 'entrepot',
                'entity_id' => $warehouseId,
                'action_url' => "/entrepots?highlight={$warehouseId}",
                'metadata' => [
                    'entrepot_nom' => $warehouseName,
                    'capacite_totale' => $capacityTotal,
                    'stock_existant' => $capacityUsed,
                    'capacite_disponible' => $capacityAvailable,
                    'fill_rate' => $fillRate,
                ],
            ]);

            return;
        }

        Log::info('WarehouseCapacityAlertChecker::resolve below threshold', [
            'warehouse_id' => $warehouseId,
            'fill_rate' => $fillRate,
        ]);

        $this->alertService->resolve(
            AlertType::WAREHOUSE_CAPACITY_HIGH,
            'entrepot',
            $warehouseId
        );
    }

    private function isValidWarehousePayload(mixed $warehouse): bool
    {
        if (is_array($warehouse)) {
            return isset($warehouse['id']);
        }

        if (is_object($warehouse)) {
            return isset($warehouse->id);
        }

        return false;
    }

    private function readValue(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return array_key_exists($key, $source) ? $source[$key] : $default;
        }

        if (is_object($source)) {
            return isset($source->{$key}) ? $source->{$key} : $default;
        }

        return $default;
    }

    private function enumValue(mixed $value): mixed
    {
        if (is_object($value) && property_exists($value, 'value')) {
            return $value->value;
        }

        return $value;
    }
}