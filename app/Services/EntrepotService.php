<?php

namespace App\Services;

use App\Models\Entrepot;
use App\Services\Alerts\AlertScanTriggerService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EntrepotService
{
    public function __construct(
        private AlertScanTriggerService $alertScanTrigger
    ) {
    }

    public function addStock(int $entrepotId, float $quantite, bool $triggerAlerts = true): Entrepot
    {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        $entrepot = DB::transaction(function () use ($entrepotId, $quantite) {
            $entrepot = Entrepot::query()
                ->lockForUpdate()
                ->findOrFail($entrepotId);

            $stockExistant = (float) $entrepot->stock_existant + $quantite;
            $capaciteTotale = (float) $entrepot->capacite_totale;
            $capaciteDisponible = $capaciteTotale - $stockExistant;

            if ($capaciteDisponible < 0) {
                throw new RuntimeException("Capacité disponible insuffisante dans l'entrepôt.");
            }

            $entrepot->update([
                'stock_existant' => $stockExistant,
                'capacite_disponible' => $capaciteDisponible,
            ]);

            return $entrepot->refresh();
        });

        if ($triggerAlerts) {
            $this->alertScanTrigger->dispatchWarehouseCapacityCheck($entrepotId);
        }

        return $entrepot;
    }

    public function removeStock(int $entrepotId, float $quantite, bool $triggerAlerts = true): Entrepot
    {
        if ($quantite <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        $entrepot = DB::transaction(function () use ($entrepotId, $quantite) {
            $entrepot = Entrepot::query()
                ->lockForUpdate()
                ->findOrFail($entrepotId);

            $stockExistant = (float) $entrepot->stock_existant - $quantite;

            if ($stockExistant < 0) {
                throw new RuntimeException("Stock existant insuffisant dans l'entrepôt.");
            }

            $capaciteTotale = (float) $entrepot->capacite_totale;
            $capaciteDisponible = $capaciteTotale - $stockExistant;

            if ($capaciteDisponible > $capaciteTotale) {
                throw new RuntimeException("La capacité disponible ne peut pas dépasser la capacité totale.");
            }

            $entrepot->update([
                'stock_existant' => $stockExistant,
                'capacite_disponible' => $capaciteDisponible,
            ]);

            return $entrepot->refresh();
        });

        if ($triggerAlerts) {
            $this->alertScanTrigger->dispatchWarehouseCapacityCheck($entrepotId);
        }

        return $entrepot;
    }

    public function syncCapacity(int $entrepotId, bool $triggerAlerts = true): Entrepot
    {
        $entrepot = DB::transaction(function () use ($entrepotId) {
            $entrepot = Entrepot::query()
                ->lockForUpdate()
                ->findOrFail($entrepotId);

            $stockExistant = (float) $entrepot->stock_existant;
            $capaciteTotale = (float) $entrepot->capacite_totale;
            $capaciteDisponible = $capaciteTotale - $stockExistant;

            $entrepot->update([
                'capacite_disponible' => $capaciteDisponible,
            ]);

            return $entrepot->refresh();
        });

        if ($triggerAlerts) {
            $this->alertScanTrigger->dispatchWarehouseCapacityCheck($entrepotId);
        }

        return $entrepot;
    }

    public function syncStockFromLots(int $entrepotId, bool $triggerAlerts = true): Entrepot
    {
        $entrepot = DB::transaction(function () use ($entrepotId) {
            $entrepot = Entrepot::query()
                ->lockForUpdate()
                ->findOrFail($entrepotId);

            $stockExistant = (float) DB::table('entrepot_lots')
                ->where('entrepot_id', $entrepotId)
                ->sum('quantite');

            $capaciteTotale = (float) $entrepot->capacite_totale;
            $capaciteDisponible = $capaciteTotale - $stockExistant;

            $entrepot->update([
                'stock_existant' => $stockExistant,
                'capacite_disponible' => $capaciteDisponible,
            ]);

            return $entrepot->refresh();
        });

        if ($triggerAlerts) {
            $this->alertScanTrigger->dispatchWarehouseCapacityCheck($entrepotId);
        }

        return $entrepot;
    }
}