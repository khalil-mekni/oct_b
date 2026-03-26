<?php

namespace App\Services;

use App\Models\StockInventaire;
use Illuminate\Support\Facades\DB;

class StockInventaireService
{
    public function createInventaire(array $input): StockInventaire
    {
        return DB::transaction(function () use ($input) {
            $stockService = app(StockService::class);

            $entrepotId = $input['entrepot_id'];
            $emballageId = $input['emballage_id'];

            $theorique = isset($input['periode_debut']) && isset($input['periode_fin'])
    ? $stockService->getStockSumPeriode(
        $entrepotId,
        $emballageId,
        $input['periode_debut'],
        $input['periode_fin']
    )
    : $stockService->getTheoriqueAt(
        $entrepotId,
        $emballageId,
        $input['date_inventaire']
    );
            $physique = (float) $input['stock_physique'];
            $ecart = $physique - $theorique;

            return StockInventaire::create([
                'entrepot_id'      => $entrepotId,
                'emballage_id'     => $emballageId,
                'stock_theorique'  => $theorique,
                'stock_physique'   => $physique,
                'ecart'            => $ecart,
                'user_id'          => $input['user_id'] ?? null,
                'date_inventaire'  => $input['date_inventaire'],
                'periode_debut'    => $input['periode_debut'] ?? null,
                'periode_fin'      => $input['periode_fin'] ?? null,
            ]);
        });
    }
   

}
