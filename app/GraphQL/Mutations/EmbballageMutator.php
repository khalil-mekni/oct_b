<?php

namespace App\GraphQL\Mutations;

use App\Models\Emballage;
use App\Services\EmballageService;

class EmbballageMutator
{
    public function __construct(private EmballageService $service) {}

    public function create($_, array $args): Emballage
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args): Emballage
    {
        $emballage = Emballage::findOrFail($args['id']);
        return $this->service->update($emballage, $args['input']);
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
