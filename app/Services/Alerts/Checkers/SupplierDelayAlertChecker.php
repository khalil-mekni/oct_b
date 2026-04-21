<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Commande;
use App\Services\Alerts\AlertService;
use Carbon\Carbon;

class SupplierDelayAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $orders = Commande::query()
            ->whereNotNull('date_livraison_prevue')
            ->whereNotIn('statut', ['RECEPTIONNEE', 'ANNULEE'])
            ->get();

        foreach ($orders as $order) {
            $expectedDate = Carbon::parse($order->date_livraison_prevue)->startOfDay();
            $today = now()->startOfDay();
            $remaining = max(0, (float) $order->reste);

            if ($today->lte($expectedDate) || $remaining <= 0) {
                $this->alertService->resolve(
                    AlertType::SUPPLIER_DELAY,
                    'commande',
                    $order->id
                );
                continue;
            }

            $delayDays = $expectedDate->diffInDays($today);

            $severity = $delayDays >= 3
                ? AlertSeverity::CRITICAL
                : AlertSeverity::WARNING;

            $reference = $order->numero_commande ?? ('CMD-' . $order->id);

            $this->alertService->createOrUpdate([
                'type' => AlertType::SUPPLIER_DELAY,
                'title' => 'Retard fournisseur',
                'message' => "La commande {$reference} a un retard de {$delayDays} jour(s).",
                'severity' => $severity,
                'entity_type' => 'commande',
                'entity_id' => $order->id,
                'action_url' => "/commandes?highlight={$order->id}",
                'metadata' => [
                    'numero_commande' => $reference,
                    'date_livraison_prevue' => $expectedDate->toDateString(),
                    'delay_days' => $delayDays,
                    'quantite_commandee' => (float) $order->quantite,
                    'quantite_recue_total' => (float) $order->quantite_recue_total,
                    'reste' => $remaining,
                    'fournisseur_id' => $order->fournisseur_id ?? null,
                ],
            ]);
        }
    }
}