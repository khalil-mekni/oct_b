<?php

namespace App\GraphQL\Mutations;

use App\Models\BonLivraison;
use App\Services\BonLivraisonService;

class BonLivraisonMutator
{
    public function __construct(private BonLivraisonService $service) {}

    public function create($_, array $args): BonLivraison
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args): BonLivraison
    {
        $bonLivraison = BonLivraison::findOrFail($args['id']);
        return $this->service->update($bonLivraison, $args['input']);
    }

    public function delete($_, array $args): BonLivraison
    {
        $bonLivraison = BonLivraison::findOrFail($args['id']);
        return $this->service->delete($bonLivraison);
    }
}