<?php

namespace App\GraphQL\Mutations;

use App\Models\Emballage;
use App\Services\EmballageService;
use GraphQL\Error\Error;

class EmbballageMutator
{
    public function __construct(private EmballageService $service) {}

    public function create($_, array $args): Emballage
    {
        try {
            return $this->service->create($args['input']);
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage());
        }
    }

    public function update($_, array $args): Emballage
    {
        try {
            $emballage = Emballage::findOrFail($args['id']);
            return $this->service->update($emballage, $args['input']);
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage());
        }
    }

    public function delete($_, array $args): Emballage
    {
        $emballage = Emballage::findOrFail($args['id']);
        return $this->service->softDelete($emballage);
    }

    public function restore($_, array $args): Emballage
    {
        return $this->service->restore((int) $args['id']);
    }

    public function forceDelete($_, array $args): bool
    {
        return $this->service->forceDelete((int) $args['id']);
    }
}