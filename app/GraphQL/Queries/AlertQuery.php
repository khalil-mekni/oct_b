<?php
namespace App\GraphQL\Queries;

use App\Enums\AlertStatus;
use App\Models\Alert;

class AlertQuery
{
    public function list($_, array $args)
    {
        $query = Alert::query()->latest();

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        if (isset($args['severity'])) {
            $query->where('severity', $args['severity']);
        }

        if (isset($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (array_key_exists('is_active', $args)) {
            $query->where('is_active', $args['is_active']);
        }

        return $query->get();
    }

    public function unreadCount()
    {
        return Alert::where('status', AlertStatus::UNREAD)->count();
    }
}