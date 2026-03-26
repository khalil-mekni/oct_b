<?php

namespace App\GraphQL\Mutations;

use App\Models\BonLivraison;
use App\Services\BonLivraisonService;

class BonLivraisonMutator
{
    public function __construct(private BonLivraisonService $service) {}

public function create($_, array $args): BonLivraison
{
    return $this->service->create(
        $args['input'],
        $args['document_bl']
    );
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
    
   /*public function validate($_, array $args)
{
    return $this->service->validateBonLivraison(
        (int) $args['id'],
        [
            'document_bl' => $args['document_bl'],
        ]
    );
}*/

}