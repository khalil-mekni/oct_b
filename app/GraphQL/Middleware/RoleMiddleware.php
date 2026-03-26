<?php

namespace App\GraphQL\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($resolve, $root, $args, $context, $info, $next)
    {
        $user = auth()->user();
        $allowedRoles = $info->fieldDefinition->directive('middleware')->arguments['checkRole'] ?? [];

        if (!$user || !in_array($user->role, $allowedRoles)) {
            throw new \Exception('Unauthorized');
        }

        return $next($root, $args, $context, $info);
    }
}