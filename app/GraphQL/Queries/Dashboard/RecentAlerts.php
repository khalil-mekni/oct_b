<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;

final class RecentAlerts
{
    public function __invoke($_, array $args)
    {
        $limit = max(1, min((int) ($args['limit'] ?? 5), 20));

        return Alert::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'type',
                'title',
                'message',
                'severity',
                'status',
                'entity_type',
                'entity_id',
                'action_url',
                'is_active',
                'read_at',
                'created_at',
                'updated_at',
            ]);
    }
}