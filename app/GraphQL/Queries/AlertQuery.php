<?php

namespace App\GraphQL\Queries;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertUserStatus;
use Illuminate\Support\Facades\Auth;

class AlertQuery
{
    public function list($_, array $args)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return [];
        }

        $query = Alert::query()
            ->with([
                'userStatuses' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
            ->where('is_active', true)
            ->whereHas('userStatuses', function ($q) use ($user, $args) {
                $q->where('user_id', $user->id);

                if (isset($args['status'])) {
                    $q->where('status', $args['status']);
                } else {
                    $q->where('status', '!=', AlertStatus::ARCHIVED);
                }
            })
            ->latest();

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
        $user = Auth::guard('api')->user();

        if (!$user) {
            return 0;
        }

        return AlertUserStatus::where('user_id', $user->id)
            ->where('status', AlertStatus::UNREAD)
            ->count();
    }
}