<?php

namespace App\GraphQL\Mutations;

use App\Models\MouvementStock;
use App\Services\MouvementStockService;

class MouvementStockMutator
{
    public function __construct(private MouvementStockService $service)
    {
    }

    public function createDraft($_, array $args): MouvementStock
    {
        return $this->service->createDraft($args['input']);
    }

    public function validate($_, array $args): MouvementStock
    {
        $mouvement = MouvementStock::findOrFail($args['input']['id']);

        return $this->service->validateMovement($mouvement);
    }

    public function deleteDraft($_, array $args): bool
    {
        $mouvement = MouvementStock::findOrFail($args['id']);

        return $this->service->deleteDraft($mouvement);
    }
}