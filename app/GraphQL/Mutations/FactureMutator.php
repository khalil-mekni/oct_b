<?php

namespace App\GraphQL\Mutations;

use App\Models\Facture;
use App\Services\FactureService;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class FactureMutator
{
    protected $factureService;

    public function __construct(FactureService $factureService)
    {
        $this->factureService = $factureService;
    }

    public function create($root, array $args) {
    return $this->factureService->create($args); 
}

    /**
     * Met à jour une facture
     */
    public function update($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Facture
    {
        $facture = Facture::findOrFail($args['id']);
        return $this->factureService->update($facture, $args['input']);
    }

    /**
     * Supprime une facture
     */
    public function delete($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): bool
    {
        $facture = Facture::findOrFail($args['id']);
        return $this->factureService->delete($facture);
    }
}