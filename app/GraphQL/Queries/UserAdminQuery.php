<?php

namespace App\GraphQL\Queries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserAdminQuery
{
    public function users()
    {
        $admin = Auth::guard('api')->user();

        if (! $admin || $admin->role !== 'ADMIN') {
            throw new \Exception('Unauthorized.');
        }

        return User::orderBy('created_at', 'desc')->get();
    }
}