<?php

namespace App\GraphQL\Directives;


use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class MiddlewareDirective extends BaseDirective implements FieldMiddleware
{
    public function name(): string
    {
        return 'middleware';
    }

    public function handleField($fieldValue)
    {
        // Exemple simple : vérifier si utilisateur connecté
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        return $fieldValue;
    }
}