<?php

namespace App\GraphQL\Mutations;

use App\Models\Stock;
use App\Services\StockService;

class StockMutator
{
    public function __construct(private StockService $service) {}




    public function createWithAutoLot($_, array $args): Stock
{
    return $this->service->createWithAutoLot($args['input']);
}

    public function create($_, array $args): Stock
    {
        return $this->service->create($args['input']);
    }

    public function update($_, array $args): Stock
    {
        $input = $args['input'];
        $stock = Stock::findOrFail($input['id']);

        return $this->service->update($stock, $input);
    }

    public function delete($_, array $args): bool
    {
        $stock = Stock::findOrFail($args['id']);
        return $this->service->delete($stock);
    }

    
}