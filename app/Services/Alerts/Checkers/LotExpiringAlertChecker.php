<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Lot;
use App\Services\Alerts\AlertService;
use Carbon\Carbon;

class LotExpiringAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $lots = Lot::query()
            ->whereNotNull('expiration_date')
            ->get();

        foreach ($lots as $lot) {
            $expirationDate = Carbon::parse($lot->expiration_date)->startOfDay();
            $today = now()->startOfDay();
            $daysRemaining = $today->diffInDays($expirationDate, false);

            if ($daysRemaining < 0) {
                $this->alertService->resolve(AlertType::LOT_EXPIRING, 'lot', $lot->id);
                continue;
            }

            $severity = null;

            if ($daysRemaining <= 7) {
                $severity = AlertSeverity::CRITICAL;
            } elseif ($daysRemaining <= 15) {
                $severity = AlertSeverity::WARNING;
            } elseif ($daysRemaining <= 30) {
                $severity = AlertSeverity::INFO;
            }

            if ($severity) {
                $lotCode = $lot->code ?? ('LOT-' . $lot->id);

                $this->alertService->createOrUpdate([
                    'type' => AlertType::LOT_EXPIRING,
                    'title' => 'Lot proche expiration',
                    'message' => "Le lot {$lotCode} expire dans {$daysRemaining} jour(s).",
                    'severity' => $severity,
                    'entity_type' => 'lot',
                    'entity_id' => $lot->id,
                    'action_url' => "/lots/{$lot->id}",
                    'metadata' => [
                        'lot_code' => $lotCode,
                        'expiration_date' => $expirationDate->toDateString(),
                        'days_remaining' => $daysRemaining,
                    ],
                ]);
            } else {
                $this->alertService->resolve(AlertType::LOT_EXPIRING, 'lot', $lot->id);
            }
        }
    }
}