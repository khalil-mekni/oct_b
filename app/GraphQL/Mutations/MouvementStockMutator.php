<?php

namespace App\GraphQL\Mutations;

use App\Models\MouvementStock;
use App\Services\MouvementStockService;

class MouvementStockMutator
{
    public function __construct(private MouvementStockService $service) {}

    public function createDraft($_, array $args): MouvementStock
    {
        return $this->service->createDraft($args['input']);
    }

    public function validate($_, array $args): MouvementStock
    {
        $m = MouvementStock::findOrFail($args['input']['id']);
        return $this->service->validateMovement($m);
    }

    public function deleteDraft($_, array $args): bool
    {
        $m = MouvementStock::findOrFail($args['id']);
        return $this->service->deleteDraft($m);
    }
}