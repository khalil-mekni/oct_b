<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Contrat;
use App\Services\Alerts\AlertService;
use Carbon\Carbon;

class ContractAlertChecker
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
            $this->checkExpiringAlert($contrat);
            $this->checkExpiredAlert($contrat);
            $this->checkConsumptionHighAlert($contrat);
            $this->checkQuantityExceededAlert($contrat);
        }
    }

    private function checkExpiringAlert($contrat): void
    {
        $endDate = Carbon::parse($contrat->date_fin)->startOfDay();
        $today = now()->startOfDay();
        $daysRemaining = $today->diffInDays($endDate, false);

        if ($daysRemaining < 0) {
            $this->alertService->resolve(
                AlertType::CONTRAT_EXPIRING,
                'contrat',
                $contrat->id
            );
            return;
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
            $reference = $contrat->numero_contrat ?? ('CTR-' . $contrat->id);

            $this->alertService->createOrUpdate([
                'type' => AlertType::CONTRAT_EXPIRING,
                'title' => 'Contrat proche expiration',
                'message' => "Le contrat {$reference} expire dans {$daysRemaining} jour(s).",
                'severity' => $severity,
                'entity_type' => 'contrat',
                'entity_id' => $contrat->id,
                'action_url' => "/contrats?highlight={$contrat->id}",
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

    private function checkExpiredAlert($contrat): void
    {
        $endDate = Carbon::parse($contrat->date_fin)->startOfDay();
        $today = now()->startOfDay();

        if ($endDate->lt($today)) {
            $daysExpired = $endDate->diffInDays($today);
            $reference = $contrat->numero_contrat ?? ('CTR-' . $contrat->id);

            $this->alertService->createOrUpdate([
                'type' => AlertType::CONTRACT_EXPIRED,
                'title' => 'Contrat expiré',
                'message' => "Le contrat {$reference} a expiré depuis {$daysExpired} jour(s).",
                'severity' => AlertSeverity::CRITICAL,
                'entity_type' => 'contrat',
                'entity_id' => $contrat->id,
                'action_url' => "/contrats?highlight={$contrat->id}",
                'metadata' => [
                    'contrat_reference' => $reference,
                    'date_fin' => $endDate->toDateString(),
                    'days_expired' => $daysExpired,
                ],
            ]);
        } else {
            $this->alertService->resolve(
                AlertType::CONTRACT_EXPIRED,
                'contrat',
                $contrat->id
            );
        }
    }

    private function checkConsumptionHighAlert($contrat): void
    {
        $quantiteContractuelle = (float) ($contrat->quantite_contractuelle ?? 0);
        $quantiteRealisee = (float) ($contrat->quantite_realisee ?? 0);

        if ($quantiteContractuelle <= 0) {
            $this->alertService->resolve(
                AlertType::CONTRACT_CONSUMPTION_HIGH,
                'contrat',
                $contrat->id
            );
            return;
        }

        $consumptionRate = ($quantiteRealisee / $quantiteContractuelle) * 100;
        $severity = null;

        if ($consumptionRate >= 95) {
            $severity = AlertSeverity::CRITICAL;
        } elseif ($consumptionRate >= 80) {
            $severity = AlertSeverity::WARNING;
        }

        if ($severity) {
            $reference = $contrat->numero_contrat ?? ('CTR-' . $contrat->id);

            $this->alertService->createOrUpdate([
                'type' => AlertType::CONTRACT_CONSUMPTION_HIGH,
                'title' => 'Consommation du contrat élevée',
                'message' => "Le contrat {$reference} est consommé à " . round($consumptionRate, 2) . "%.",
                'severity' => $severity,
                'entity_type' => 'contrat',
                'entity_id' => $contrat->id,
                'action_url' => "/contrats?highlight={$contrat->id}",
                'metadata' => [
                    'contrat_reference' => $reference,
                    'quantite_contractuelle' => $quantiteContractuelle,
                    'quantite_realisee' => $quantiteRealisee,
                    'consumption_rate' => round($consumptionRate, 2),
                ],
            ]);
        } else {
            $this->alertService->resolve(
                AlertType::CONTRACT_CONSUMPTION_HIGH,
                'contrat',
                $contrat->id
            );
        }
    }

    private function checkQuantityExceededAlert($contrat): void
    {
        $quantiteContractuelle = (float) ($contrat->quantite_contractuelle ?? 0);
        $tauxDepassementAutorise = (float) ($contrat->taux_depassement_autorise ?? 0);
        $quantiteRealisee = (float) ($contrat->quantite_realisee ?? 0);

        if ($quantiteContractuelle <= 0) {
            $this->alertService->resolve(
                AlertType::CONTRACT_QUANTITY_EXCEEDED,
                'contrat',
                $contrat->id
            );
            return;
        }

        $maxAutorise = $quantiteContractuelle + (($quantiteContractuelle * $tauxDepassementAutorise) / 100);

        if ($quantiteRealisee > $maxAutorise) {
            $reference = $contrat->numero_contrat ?? ('CTR-' . $contrat->id);
            $quantiteDepassee = $quantiteRealisee - $maxAutorise;

            $this->alertService->createOrUpdate([
                'type' => AlertType::CONTRACT_QUANTITY_EXCEEDED,
                'title' => 'Quantité contractuelle dépassée',
                'message' => "Le contrat {$reference} a dépassé la quantité autorisée.",
                'severity' => AlertSeverity::CRITICAL,
                'entity_type' => 'contrat',
                'entity_id' => $contrat->id,
                'action_url' => "/contrats?highlight={$contrat->id}",
                'metadata' => [
                    'contrat_reference' => $reference,
                    'quantite_contractuelle' => $quantiteContractuelle,
                    'taux_depassement_autorise' => $tauxDepassementAutorise,
                    'quantite_realisee' => $quantiteRealisee,
                    'quantite_max_autorisee' => round($maxAutorise, 2),
                    'quantite_depassee' => round($quantiteDepassee, 2),
                ],
            ]);
        } else {
            $this->alertService->resolve(
                AlertType::CONTRACT_QUANTITY_EXCEEDED,
                'contrat',
                $contrat->id
            );
        }
    }
}