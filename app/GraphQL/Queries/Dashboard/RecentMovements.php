<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\MouvementStock;

final class RecentMovements
{
    public function __invoke($_, array $args): array
    {
        $limit = max(1, min((int) ($args['limit'] ?? 10), 30));

        $mouvements = MouvementStock::query()
            ->with([
                'entrepotSource:id,nom',
                'entrepotDestination:id,nom',
                'lot:id,code_lot',
                'emballage:id,code,name',
                'user:id,name',
            ])
            ->latest('date_mouvement')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return $mouvements->map(function (MouvementStock $m): array {
            return [
                'id' => $m->id,
                'code_mouvement' => $m->code_mouvement,
                'type' => $this->normalizeMovementType($m->type_mouvement),
                'type_mouvement' => $m->type_mouvement,
                'quantite' => (float) $m->quantite,
                'statut' => $m->statut,
                'date_mouvement' => $m->date_mouvement,
                'created_at' => $m->created_at,
                'sourceWarehouseName' => $m->entrepotSource?->nom,
                'destinationWarehouseName' => $m->entrepotDestination?->nom,
                'lot_code' => $m->lot?->code_lot,
                'emballage_code' => $m->emballage?->code,
                'emballage_name' => $m->emballage?->name,
                'user_name' => $m->user?->name,
            ];
        })->values()->all();
    }

    private function normalizeMovementType(?string $type): string
    {
        return match ($type) {
            'ENT' => 'IN',
            'PRD' => 'OUT',
            'PTE' => 'LOSS',
            'CDD' => 'TRANSFER',
            'SPL' => 'SPLIT',
            default => (string) $type,
        };
    }
}