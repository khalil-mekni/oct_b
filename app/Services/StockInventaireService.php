<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\StockInventaire;
use Illuminate\Support\Facades\DB;

class StockInventaireService
{
    public function createInventaire(array $input): StockInventaire
    {
        return DB::transaction(function () use ($input) {
            $theorique = app(StockService::class)->getTheoriqueAt(
                entrepotId: (int) $input['entrepot_id'],
                emballageId: (int) $input['emballage_id'],
                lotId: null,
                dateTime: $input['date_inventaire']
            );

            $physique = (float) $input['stock_physique'];
            $ecart = $physique - $theorique;

            return StockInventaire::create([
                'entrepot_id'      => (int) $input['entrepot_id'],
                'emballage_id'     => (int) $input['emballage_id'],
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

    public function removeInventairesFromLot(Lot $lot): void
    {
        StockInventaire::where('lot_id', $lot->id)->delete();
    }
}