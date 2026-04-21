<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;
use App\Models\Commande;
use Carbon\Carbon;

final class OrdersWidget
{
    public function __invoke($_, array $args): array
    {
        $total = Commande::query()->count();

        $pending = Commande::query()
            ->whereIn('statut', ['EN_ATTENTE', 'PENDING', 'pending'])
            ->count();

        $partiallyReceived = Commande::query()
            ->whereIn('statut', ['PARTIAL', 'PARTIELLE', 'PARTIELLEMENT_RECUE'])
            ->count();

        $lateByStatus = Commande::query()
            ->whereIn('statut', ['LATE', 'RETARD'])
            ->count();

        $lateByAlert = Alert::query()
            ->where('is_active', true)
            ->whereIn('type', [
                'SUPPLIER_DELAY',
                'ORDER_NOT_RECEIVED_ON_TIME',
            ])
            ->count();

        $late = max($lateByStatus, $lateByAlert);

        $recentOrders = Commande::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'reference' => $order->reference ?? ('CMD-' . $order->id),
                    'supplierName' => $order->fournisseur->nom ?? null,
                    'date' => $order->date_commande ?? $order->created_at,
                    'status' => $order->statut ?? 'inconnu',
                    'totalLabel' => isset($order->montant_total)
                        ? number_format((float) $order->montant_total, 2, '.', ' ') . ' MAD'
                        : null,
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'pending' => $pending,
            'partiallyReceived' => $partiallyReceived,
            'late' => $late,
            'recentOrders' => $recentOrders,
        ];
    }
}