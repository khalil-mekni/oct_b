<?php

namespace App\GraphQL\Mutations;

use App\Models\Facture;
use App\Services\FactureService;

class FactureMutator
{
    public function __construct(private FactureService $service) {}

    public function create($_, array $args): Facture
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args): Facture
    {
        $facture = Facture::findOrFail($args['id']);
        return $this->service->update($facture, $args['input']);
    }

    public function delete($_, array $args): bool
    {
        $facture = Facture::findOrFail($args['id']);
        return $this->service->delete($facture);
    }
}