<?php

namespace App\GraphQL\Queries;

use App\Models\Stock;

class StockQuery
{
    /**
     * Retourne l'historique du stock entre deux dates
     */
    public function history($_, array $args)
    {
        return Stock::query()
            ->where('entrepot_id', $args['entrepot_id'])
            ->where('emballage_id', $args['emballage_id'])
            ->whereBetween('date_stock', [
                $args['from'],
                $args['to']
            ])
            ->orderBy('date_stock', 'asc')
            ->get();
    }

    /**
     * Calcule le stock théorique à une date donnée
     */
    public function theoriqueAt($_, array $args)
    {
        $stock = Stock::query()
            ->where('entrepot_id', $args['entrepot_id'])
            ->where('emballage_id', $args['emballage_id'])
            ->where('date_stock', '<=', $args['at'])
            ->orderBy('date_stock', 'desc')
            ->first();

        if (!$stock) {
            return 0;
        }

        return $stock->quantite_finale;
    }
}