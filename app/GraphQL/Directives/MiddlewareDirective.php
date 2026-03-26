<?php

namespace App\GraphQL\Directives;


use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class MiddlewareDirective extends BaseDirective implements FieldMiddleware
{
    public function name(): string
    {
        return 'middleware';
    }

    public static function definition(): string
    {
        return <<<'SDL'
            directive @middleware on FIELD_DEFINITION
            SDL;
    }

    public function handleField(FieldValue $fieldValue): void
    {
        // Exemple simple : vérifier si utilisateur connecté
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }
    }
}