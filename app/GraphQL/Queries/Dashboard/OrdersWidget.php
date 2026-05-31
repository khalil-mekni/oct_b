<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;
use App\Models\Commande;
use Carbon\Carbon;

final class OrdersWidget
{
    public function __invoke($_, array $args): array
    {
        $pending = Commande::query()
            ->where('statut', 'EN_ATTENTE')
            ->count();

        $partiallyReceived = Commande::query()
            ->where('statut', 'PARTIELLEMENT_RECEPTIONNEE')
            ->count();

        $received = Commande::query()
            ->whereIn('statut', ['RECEPTIONNEE', 'VALIDEE'])
            ->count();
        
        $total = $pending + $partiallyReceived + $received;

        $lateByAlert = Alert::query()
            ->where('is_active', true)
            ->whereIn('type', [
                'SUPPLIER_DELAY',
                'ORDER_NOT_RECEIVED_ON_TIME',
            ])
            ->count();
        
        $late = $lateByAlert;

        $recentOrders = Commande::query()
            ->with('fournisseur')
            ->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_RECEPTIONNEE', 'RECEPTIONNEE', 'VALIDEE'])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($order) {
                $status = $order->statut;
                if ($status === 'VALIDEE') $status = 'RECEPTIONNEE';
                
                return [
                    'id' => $order->id,
                    'reference' => $order->numero_commande ?? ('CMD-' . $order->id),
                    'supplierName' => $order->fournisseur->raison_sociale ?? null,
                    'date' => $order->date_commande ?? $order->created_at,
                    'status' => $status,
                    'totalLabel' => $order->quantite . ' unités',
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'pending' => $pending,
            'validated_count' => 0, // Now merged into received
            'partiallyReceived' => $partiallyReceived,
            'received_count' => $received,
            'late' => $late,
            'recentOrders' => $recentOrders,
        ];
    }
}