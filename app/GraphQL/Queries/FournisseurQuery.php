<?php

namespace App\GraphQL\Queries;

use App\Models\Fournisseur;

class FournisseurQuery
{
    public function map($_, array $args)
    {
        return Fournisseur::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('raison_sociale')
            ->get();
    }
}