<?php

namespace App\GraphQL\Queries;

use App\Models\Stock;
use App\Services\StockService;
use Carbon\Carbon;

class StockQuery
{
    public function __construct(private StockService $stockService) {}

    public function all($_, array $args)
    {
        return Stock::with(['entrepot', 'emballage', 'lot', 'user'])
            ->orderBy('date_stock', 'desc')
            ->orderBy('id', 'desc');
    }

    public function history($_, array $args)
    {
        return Stock::with(['entrepot', 'emballage', 'lot', 'user'])
            ->where('entrepot_id', $args['entrepot_id'])
            ->where('emballage_id', $args['emballage_id'])
            ->whereBetween('date_stock', [
                Carbon::parse($args['from']),
                Carbon::parse($args['to']),
            ])
            ->orderBy('date_stock', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function theoriqueAt($_, array $args): float
    {
        return $this->stockService->getTheoriqueAt(
            (int) $args['entrepot_id'],
            (int) $args['emballage_id'],
            $args['at']
        );
    }

    public function lotsDisponibles($_, array $args)
    {
        return Stock::query()
            ->leftJoin('lots', 'lots.id', '=', 'stocks.lot_id')
            ->where('stocks.entrepot_id', $args['entrepot_id'])
            ->where('stocks.emballage_id', $args['emballage_id'])
            ->whereNotNull('stocks.lot_id')
            ->groupBy(
                'stocks.lot_id',
                'stocks.emballage_id',
                'stocks.entrepot_id',
                'lots.code_lot'
            )
            ->selectRaw('
                stocks.lot_id as lot_id,
                stocks.emballage_id as emballage_id,
                stocks.entrepot_id as entrepot_id,
                lots.code_lot as code_lot,
                SUM(CASE WHEN stocks.sens = "ENTREE" THEN stocks.quantite ELSE -stocks.quantite END) as stock_disponible
            ')
            ->havingRaw('SUM(CASE WHEN stocks.sens = "ENTREE" THEN stocks.quantite ELSE -stocks.quantite END) > 0')
            ->orderBy('lots.code_lot')
            ->get();
    }
}