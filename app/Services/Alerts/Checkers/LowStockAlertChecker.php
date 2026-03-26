<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Lot;
use App\Services\Alerts\AlertService;

class LowStockAlertChecker
{
    private const DEFAULT_MINIMUM_STOCK = 10;

    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $lots = Lot::query()->get();

        foreach ($lots as $lot) {
            $currentStock = (float) ($lot->quantite ?? 0);
            $minimumStock = self::DEFAULT_MINIMUM_STOCK;

            if ($currentStock > 0 && $currentStock <= $minimumStock) {
                $severity = $currentStock <= ($minimumStock * 0.5)
                    ? AlertSeverity::CRITICAL
                    : AlertSeverity::WARNING;

                $lotCode = $lot->code_lot ?? ('LOT-' . $lot->id);

                $this->alertService->createOrUpdate([
                    'type' => AlertType::LOW_STOCK,
                    'title' => 'Stock faible',
                    'message' => "Le lot {$lotCode} est sous le seuil minimal.",
                    'severity' => $severity,
                    'entity_type' => 'lot',
                    'entity_id' => $lot->id,
                    'action_url' => "/lots/{$lot->id}",
                    'metadata' => [
                        'lot_code' => $lotCode,
                        'current_stock' => $currentStock,
                        'minimum_stock' => $minimumStock,
                    ],
                ]);
            } else {
                $this->alertService->resolve(
                    AlertType::LOW_STOCK,
                    'lot',
                    $lot->id
                );
            }
        }
    }
}