<?php

namespace App\Services\Alerts;

use App\Services\Alerts\Checkers\WarehouseCapacityAlertChecker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertScanTriggerService
{
    public function __construct(
        private WarehouseCapacityAlertChecker $warehouseCapacityAlertChecker
    ) {
    }

    public function dispatch(): void
    {
        Log::info('AlertScanTriggerService::dispatch appelé', [
            'transaction_level' => DB::transactionLevel(),
            'mode' => 'global_scan',
        ]);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function () {
                $this->runGlobalScan();
            });

            return;
        }

        $this->runGlobalScan();
    }

    public function dispatchWarehouseCapacityCheck(int $warehouseId): void
    {
        Log::info('AlertScanTriggerService::dispatchWarehouseCapacityCheck appelé', [
            'transaction_level' => DB::transactionLevel(),
            'warehouse_id' => $warehouseId,
        ]);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function () use ($warehouseId) {
                $this->runWarehouseCapacityCheck($warehouseId);
            });

            return;
        }

        $this->runWarehouseCapacityCheck($warehouseId);
    }

    private function runWarehouseCapacityCheck(int $warehouseId): void
    {
        try {
            Log::info('Warehouse capacity targeted check lancé automatiquement', [
                'warehouse_id' => $warehouseId,
            ]);

            $this->warehouseCapacityAlertChecker->checkWarehouse($warehouseId);

            Log::info('Warehouse capacity targeted check terminé', [
                'warehouse_id' => $warehouseId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors du targeted warehouse capacity check', [
                'warehouse_id' => $warehouseId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function runGlobalScan(): void
    {
        try {
            Log::info('alerts:scan lancé automatiquement');

            $exitCode = Artisan::call('alerts:scan');

            Log::info('alerts:scan terminé', [
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors du déclenchement automatique de alerts:scan', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}