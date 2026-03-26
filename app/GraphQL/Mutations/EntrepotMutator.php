<?php

namespace App\GraphQL\Mutations;

use App\Models\Entrepot;
use App\Services\EntrepotService;

class EntrepotMutator
{
    public function __construct(private EntrepotService $service) {}

    public function create($root, array $args): Entrepot
    {
        return $this->service->create($args['input']);
    }

    public function update($root, array $args): Entrepot
    {
        $data = $args['input'];
        $entrepot = Entrepot::findOrFail($data['id']);
        unset($data['id']);

        return $this->service->update($entrepot, $data);
    }

    public function delete($root, array $args): bool
    {
        $entrepot = Entrepot::findOrFail($args['id']);
        return $this->service->delete($entrepot);
    }
}