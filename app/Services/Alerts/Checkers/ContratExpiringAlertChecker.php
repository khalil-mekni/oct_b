<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Contrat;
use App\Services\Alerts\AlertService;
use Carbon\Carbon;

class ContratExpiringAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $contrats = Contrat::query()
            ->whereNotNull('date_fin')
            ->get();

        foreach ($contrats as $contrat) {
            $endDate = Carbon::parse($contrat->date_fin)->startOfDay();
            $today = now()->startOfDay();
            $daysRemaining = $today->diffInDays($endDate, false);

            if ($daysRemaining < 0) {
                $this->alertService->resolve(
                    AlertType::CONTRAT_EXPIRING,
                    'contrat',
                    $contrat->id
                );
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
                $reference = $contrat->reference ?? ('CTR-' . $contrat->id);

                $this->alertService->createOrUpdate([
                    'type' => AlertType::CONTRAT_EXPIRING,
                    'title' => 'Contrat proche expiration',
                    'message' => "Le contrat {$reference} expire dans {$daysRemaining} jour(s).",
                    'severity' => $severity,
                    'entity_type' => 'contrat',
                    'entity_id' => $contrat->id,
                    'action_url' => "/contrats/{$contrat->id}",
                    'metadata' => [
                        'contrat_reference' => $reference,
                        'date_fin' => $endDate->toDateString(),
                        'days_remaining' => $daysRemaining,
                    ],
                ]);
            } else {
                $this->alertService->resolve(
                    AlertType::CONTRAT_EXPIRING,
                    'contrat',
                    $contrat->id
                );
            }
        }
    }
}