<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Alerts\Checkers\LowStockAlertChecker;
use App\Services\Alerts\Checkers\InventoryAnomalyAlertChecker;
use App\Services\Alerts\Checkers\WarehouseCapacityAlertChecker;
use App\Services\Alerts\Checkers\ContratExpiringAlertChecker;
use App\Services\Alerts\Checkers\SupplierDelayAlertChecker;

class ScanAlerts extends Command
{
    protected $signature = 'alerts:scan';
    protected $description = 'Scan business data and generate alerts';

    public function handle(): int
    {
        app(LowStockAlertChecker::class)->check();
        app(InventoryAnomalyAlertChecker::class)->check();
        app(WarehouseCapacityAlertChecker::class)->check();

        // Active seulement si la partie contrats est prête
        // app(ContratExpiringAlertChecker::class)->check();

        // Active seulement si la partie commandes est prête
        app(SupplierDelayAlertChecker::class)->check();

        $this->info('Alerts scanned successfully.');

        return self::SUCCESS;
    }
}