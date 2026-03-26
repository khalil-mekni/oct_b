<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\StockInventaire;
use App\Services\Alerts\AlertService;

class InventoryAnomalyAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $records = StockInventaire::query()->get();

        foreach ($records as $record) {
            $theoretical = (float) ($record->stock_theorique ?? 0);
            $actual = (float) ($record->stock_physique ?? 0);
            $difference = abs((float) ($record->ecart ?? ($theoretical - $actual)));

            $percent = $theoretical > 0
                ? ($difference / $theoretical) * 100
                : ($difference > 0 ? 100 : 0);

            $hasAnomaly = $difference > 10 || $percent > 5;

            if ($hasAnomaly) {
                $severity = ($difference > 20 || $percent > 10)
                    ? AlertSeverity::CRITICAL
                    : AlertSeverity::WARNING;

                $reference = 'INV-' . $record->id;

                $this->alertService->createOrUpdate([
                    'type' => AlertType::INVENTORY_ANOMALY,
                    'title' => 'Anomalie inventaire',
                    'message' => "Un écart inhabituel a été détecté sur {$reference}.",
                    'severity' => $severity,
                    'entity_type' => 'stock_inventaire',
                    'entity_id' => $record->id,
                    'action_url' => "/stock-inventaires/{$record->id}",
                    'metadata' => [
                        'reference' => $reference,
                        'entrepot_id' => $record->entrepot_id ?? null,
                        'emballage_id' => $record->emballage_id ?? null,
                        'stock_theorique' => $theoretical,
                        'stock_physique' => $actual,
                        'ecart' => $difference,
                        'difference_percent' => round($percent, 2),
                        'date_inventaire' => $record->date_inventaire ?? null,
                    ],
                ]);
            } else {
                $this->alertService->resolve(
                    AlertType::INVENTORY_ANOMALY,
                    'stock_inventaire',
                    $record->id
                );
            }
        }
    }
}