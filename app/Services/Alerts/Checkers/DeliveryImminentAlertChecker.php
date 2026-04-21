<?php

namespace App\Services\Alerts\Checkers;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Commande;
use App\Services\Alerts\AlertService;
use Carbon\Carbon;

class DeliveryImminentAlertChecker
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function check(): void
    {
        $today = now()->startOfDay();
        $tomorrow = now()->copy()->addDay()->startOfDay();

        $orders = Commande::query()
            ->whereNotNull('date_livraison_prevue')
            ->whereNotIn('statut', ['RECEPTIONNEE', 'ANNULEE'])
            ->whereDate('date_livraison_prevue', '>=', $today->toDateString())
            ->whereDate('date_livraison_prevue', '<=', $tomorrow->toDateString())
            ->get();

        foreach ($orders as $order) {
            $expectedDate = Carbon::parse($order->date_livraison_prevue)->startOfDay();
            $remaining = max(0, (float) $order->reste);

            

            if ($remaining <= 0) {
                $this->alertService->resolve(
                    AlertType::DELIVERY_IMMINENT,
                    'commande',
                    $order->id
                );
                continue;
            }

            $reference = $order->numero_commande ?? ('CMD-' . $order->id);
            $dayLabel = $expectedDate->equalTo($today) ? "aujourd'hui" : 'demain';

            $this->alertService->createOrUpdate([
                'type' => AlertType::DELIVERY_IMMINENT,
                'title' => 'Livraison imminente',
                'message' => "La commande {$reference} est prévue {$dayLabel}.",
                'severity' => AlertSeverity::INFO,
                'entity_type' => 'commande',
                'entity_id' => $order->id,
                'action_url' => "/commandes?highlight={$order->id}",
                'metadata' => [
                    'numero_commande' => $reference,
                    'date_livraison_prevue' => $expectedDate->toDateString(),
                    'delivery_day_label' => $dayLabel,
                    'quantite_commandee' => (float) $order->quantite,
                    'quantite_recue_total' => (float) $order->quantite_recue_total,
                    'reste' => $remaining,
                ],
            ]);
        }
    }
}