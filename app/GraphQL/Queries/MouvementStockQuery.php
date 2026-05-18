<?php

namespace App\GraphQL\Queries;

use App\Models\MouvementStock;
use Illuminate\Database\Eloquent\Builder;

class MouvementStockQuery
{
    public function ordered($_, array $args): Builder
    {
        return MouvementStock::query()
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id');
    }
}