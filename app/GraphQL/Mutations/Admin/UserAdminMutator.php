<?php

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use App\Services\Alerts\AlertService;
use Illuminate\Support\Facades\Auth;

class UserAdminMutator
{
    public function __construct(
        private AlertService $alertService
    ) {
    }

    private function checkAdmin()
    {
        $admin = Auth::guard('api')->user();

        if (! $admin || $admin->role !== 'ADMIN') {
            throw new \Exception('Unauthorized.');
        }

        return $admin;
    }

    public function updateUserRole($_, array $args)
    {
        $this->checkAdmin();

        $allowedRoles = [
            'PENDING',
            'ADMIN',
            'RESPONSABLE_APPROVISIONNEMENT',
            'RESPONSABLE_STOCKAGE',
        ];

        if (! in_array($args['role'], $allowedRoles)) {
            throw new \Exception('Invalid role.');
        }

        $user = User::findOrFail($args['id']);

        $oldRole = $user->role;

        $user->role = $args['role'];
        $user->save();

        if ($oldRole === 'PENDING' && $user->role !== 'PENDING') {
            $this->alertService->attachExistingAlertsToUser($user);
        }

        return $user;
    }

    public function updateUserStatus($_, array $args)
    {
        $this->checkAdmin();

        $user = User::findOrFail($args['id']);

        $wasInactive = ! $user->is_active;

        $user->is_active = $args['is_active'];
        $user->save();

        if ($wasInactive && $user->is_active && $user->role !== 'PENDING') {
            $this->alertService->attachExistingAlertsToUser($user);
        }

        return $user;
    }
}