<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\BonLivraison;

final class DeliveryNotesWidget
{
    public function __invoke($_, array $args): array
    {
        $total = BonLivraison::query()->count();

        $validated = BonLivraison::query()
            ->whereIn('statut', ['VALIDE', 'VALIDATED', 'validé', 'valide'])
            ->count();

        $pending = BonLivraison::query()
            ->whereIn('statut', ['EN_ATTENTE', 'PENDING', 'en attente'])
            ->count();

        $warehousesInvolved = BonLivraison::query()
            ->whereNotNull('entrepot_id')
            ->distinct('entrepot_id')
            ->count('entrepot_id');

        $recentDeliveryNotes = BonLivraison::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'numero_bl' => $note->numero_bl,
                    'commandeReference' => $note->commande->reference ?? null,
                    'entrepotName' => $note->entrepot->nom ?? null,
                    'date_reception' => $note->date_reception,
                    'statut' => $note->statut ?? 'inconnu',
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'validated' => $validated,
            'pending' => $pending,
            'warehousesInvolved' => $warehousesInvolved,
            'recentDeliveryNotes' => $recentDeliveryNotes,
        ];
    }
}