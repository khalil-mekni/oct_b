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
            ->where('statut', 'EN_ATTENTE')
            ->count();

        $validated = Commande::query()
            ->where('statut', 'VALIDEE')
            ->count();

        $partiallyReceived = Commande::query()
            ->where('statut', 'PARTIELLEMENT_RECEPTIONNEE')
            ->count();

        $received = Commande::query()
            ->where('statut', 'RECEPTIONNEE')
            ->count();

        $lateByAlert = Alert::query()
            ->where('is_active', true)
            ->whereIn('type', [
                'SUPPLIER_DELAY',
                'ORDER_NOT_RECEIVED_ON_TIME',
            ])
            ->count();
        
        $late = $lateByAlert; // Use alerts for real operational delay tracking

        $recentOrders = Commande::query()
            ->with('fournisseur')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'reference' => $order->numero_commande ?? ('CMD-' . $order->id),
                    'supplierName' => $order->fournisseur->raison_sociale ?? null,
                    'date' => $order->date_commande ?? $order->created_at,
                    'status' => $order->statut ?? 'inconnu',
                    'totalLabel' => $order->quantite . ' unités',
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'pending' => $pending,
            'validated_count' => $validated,
            'partiallyReceived' => $partiallyReceived,
            'received_count' => $received,
            'late' => $late,
            'recentOrders' => $recentOrders,
        ];
    }
}