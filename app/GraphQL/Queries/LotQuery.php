<?php

namespace App\GraphQL\Queries;

use App\Models\Lot;

class LotQuery
{
    public function byEmballage($_, array $args)
    {
        return Lot::query()
            ->where('emballage_id', $args['emballage_id'])
            ->orderBy('code_lot')
            ->get();
    }
}