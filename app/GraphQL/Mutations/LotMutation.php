<?php

namespace App\GraphQL\Mutations;

use App\Services\LotService;

class LotMutation
{
    public function __construct(private LotService $lotService) {}

    public function create($_, array $args)
    {
        return $this->lotService->createLotAndApply($args['input']);
    }

    public function update($_, array $args)
    {
        return $this->lotService->updateLotWithHistory(
            (int) $args['id'],
            $args['input']
        );
    }

    public function delete($_, array $args)
    {
        return $this->lotService->deleteLot((int) $args['id']);
    }
}