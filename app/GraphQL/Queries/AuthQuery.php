<?php

namespace App\GraphQL\Queries;

use App\Services\AuthService;

class AuthQuery
{

 public function me($_, array $args)
    {
        return auth()->user();
    }
}