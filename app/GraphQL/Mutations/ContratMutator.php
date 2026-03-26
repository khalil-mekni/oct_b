<?php

namespace App\GraphQL\Mutations;

use App\Services\ContratService;

class ContratMutator
{
    public function __construct(private ContratService $service) {}

    public function list($_, array $args)
    {
        return $this->service->list();
    }

    public function find($_, array $args)
    {
        return $this->service->find((int) $args['id']);
    }

    public function create($_, array $args)
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args)
    {
        return $this->service->update((int) $args['id'], $args['input']);
    }

    public function delete($_, array $args)
    {
        return $this->service->delete((int) $args['id']);
    }
}