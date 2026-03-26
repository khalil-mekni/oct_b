<?php

namespace App\GraphQL\Queries;

use App\Services\EntrepotLotService;
use App\Services\StockService;

class StockQuery
{
    public function __construct(
        private StockService $stockService,
        private EntrepotLotService $entrepotLotService
    ) {}

    public function history($_, array $args)
    {
        return $this->stockService->history(
            $args['entrepot_id'] ?? null,
            $args['emballage_id'] ?? null,
            $args['lot_id'] ?? null,
            $args['from'] ?? null,
            $args['to'] ?? null
        );
    }

    public function theoriqueAt($_, array $args): float
    {
        return $this->stockService->getTheoriqueAt(
            (int) $args['entrepot_id'],
            (int) $args['emballage_id'],
            isset($args['lot_id']) ? (int) $args['lot_id'] : null,
            $args['at']
        );
    }

    public function availableLotsByEntrepot($_, array $args)
    {
        return $this->entrepotLotService->listLotsByEntrepot(
            entrepotId: (int) $args['entrepot_id'],
            emballageId: isset($args['emballage_id']) ? (int) $args['emballage_id'] : null
        );
    }

    public function availableEntrepotsByLot($_, array $args)
    {
        return $this->entrepotLotService->listEntrepotsByLot(
            lotId: (int) $args['lot_id']
        );
    }

    public function lotDisponibleDansEntrepot($_, array $args): float
    {
        return $this->entrepotLotService->getDisponible(
            entrepotId: (int) $args['entrepot_id'],
            lotId: (int) $args['lot_id'],
            emballageId: (int) $args['emballage_id']
        );
    }
}