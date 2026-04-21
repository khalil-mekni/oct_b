<?php

namespace App\GraphQL\Queries\Dashboard;

use App\Models\Alert;

final class AlertsSummary
{
    public function __invoke($_, array $args): array
    {
        $base = Alert::query();

        return [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->where('status', 'unread')->count(),
            'read' => (clone $base)->where('status', 'read')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'info' => (clone $base)->where('severity', 'info')->count(),
            'warning' => (clone $base)->where('severity', 'warning')->count(),
            'critical' => (clone $base)->where('severity', 'critical')->count(),
        ];
    }
}