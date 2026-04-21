<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Alerts\Checkers\LowStockAlertChecker;
use App\Services\Alerts\Checkers\InventoryAnomalyAlertChecker;
use App\Services\Alerts\Checkers\WarehouseCapacityAlertChecker;
use App\Services\Alerts\Checkers\ContractAlertChecker;
use App\Services\Alerts\Checkers\SupplierDelayAlertChecker;
use App\Services\Alerts\Checkers\DeliveryImminentAlertChecker;
use App\Services\Alerts\Checkers\OrderNotReceivedOnTimeAlertChecker;

class ScanAlerts extends Command
{
    protected $signature = 'alerts:scan';
    protected $description = 'Scan business data and generate alerts';

    public function handle(): int
    {
        app(LowStockAlertChecker::class)->check();
        app(InventoryAnomalyAlertChecker::class)->check();
        app(WarehouseCapacityAlertChecker::class)->check();

        // Contrats : toutes les alertes contrat dans un seul checker
        app(ContractAlertChecker::class)->check();

        // Commandes
        app(SupplierDelayAlertChecker::class)->check();
        app(DeliveryImminentAlertChecker::class)->check();
        app(OrderNotReceivedOnTimeAlertChecker::class)->check();

        $this->info('Alerts scanned successfully.');

        return self::SUCCESS;
    }
}