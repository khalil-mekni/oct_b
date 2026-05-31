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

        $late = BonLivraison::query()
            ->whereIn('statut', ['EN_ATTENTE', 'PENDING', 'en attente'])
            ->where('created_at', '<', now()->subDays(2))
            ->count();

        $totalQuantityReceived = BonLivraison::query()->sum('quantite_recue');

        $warehousesInvolved = BonLivraison::query()
            ->whereNotNull('entrepot_id')
            ->distinct('entrepot_id')
            ->count('entrepot_id');

        $recentDeliveryNotes = BonLivraison::query()
            ->with(['commande', 'entrepot'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'numero_bl' => $note->numero_bl,
                    'commandeReference' => $note->commande->numero_commande ?? null,
                    'entrepotName' => $note->entrepot->nom ?? null,
                    'date_reception' => $note->date_reception,
                    'statut' => $note->statut ?? 'inconnu',
                    'quantite_commandee' => (float) ($note->commande->quantite ?? 0),
                    'quantite_recue' => (float) ($note->quantite_recue ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'validated' => $validated,
            'pending' => $pending,
            'late' => $late,
            'totalQuantityReceived' => $totalQuantityReceived,
            'warehousesInvolved' => $warehousesInvolved,
            'recentDeliveryNotes' => $recentDeliveryNotes,
        ];
    }
}