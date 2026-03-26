<?php

namespace App\GraphQL\Mutations;

use App\Models\Commande;
use App\Services\CommandeService;

class CommandeMutator
{
    public function __construct(private CommandeService $service) {}

    public function create($_, array $args): Commande
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args): Commande
    {
        $commande = Commande::findOrFail($args['id']);
        return $this->service->update($commande, $args['input']);
    }

    public function cancel($_, array $args): Commande
    {
        $commande = Commande::findOrFail($args['id']);
        return $this->service->cancel($commande);
    }
    public function drop($_, array $args): bool
    {
        $commande = Commande::findOrFail($args['id']);
        return $this->service->drop($commande);
    }
}