<?php

namespace App\GraphQL\Mutations;

use App\Models\StockInventaire;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class StockInventaireMutator
{
    public function create($_, array $args): StockInventaire
    {
        $input = $args['input'];

        return DB::transaction(function () use ($input) {
            $entrepotId = (int) $input['entrepot_id'];
            $emballageId = (int) $input['emballage_id'];
            $userId = $input['user_id'] ?? null;
            $dateInv = $input['date_inventaire'];
            $physique = (float) $input['stock_physique'];

            $periodeDebut = $input['periode_debut'] ?? null;
            $periodeFin = $input['periode_fin'] ?? null;

            $stockService = app(StockService::class);

            $theorique = ($periodeDebut && $periodeFin)
                ? $stockService->getStockSumPeriode(
                    $entrepotId,
                    $emballageId,
                    $periodeDebut,
                    $periodeFin
                )
                : $stockService->getTheoriqueAt(
                    $entrepotId,
                    $emballageId,
                    $dateInv
                );

            return StockInventaire::create([
                'entrepot_id' => $entrepotId,
                'emballage_id' => $emballageId,
                'stock_physique' => $physique,
                'stock_theorique' => $theorique,
                'ecart' => $physique - $theorique,
                'user_id' => $userId,
                'date_inventaire' => $dateInv,
                'periode_debut' => $periodeDebut,
                'periode_fin' => $periodeFin,
            ]);
        });
    }

    public function update($_, array $args): StockInventaire
    {
        $id = (int) $args['id'];
        $input = $args['input'];

        return DB::transaction(function () use ($id, $input) {
            $inv = StockInventaire::query()->findOrFail($id);

            $entrepotId = array_key_exists('entrepot_id', $input)
                ? (int) $input['entrepot_id']
                : (int) $inv->entrepot_id;

            $emballageId = array_key_exists('emballage_id', $input)
                ? (int) $input['emballage_id']
                : (int) $inv->emballage_id;

            $dateInv = $input['date_inventaire'] ?? $inv->date_inventaire;
            $physique = array_key_exists('stock_physique', $input)
                ? (float) $input['stock_physique']
                : (float) $inv->stock_physique;

            $periodeDebut = $input['periode_debut'] ?? $inv->periode_debut;
            $periodeFin = $input['periode_fin'] ?? $inv->periode_fin;

            $stockService = app(StockService::class);

            $theorique = ($periodeDebut && $periodeFin)
                ? $stockService->getStockSumPeriode(
                    $entrepotId,
                    $emballageId,
                    (string) $periodeDebut,
                    (string) $periodeFin
                )
                : $stockService->getTheoriqueAt(
                    $entrepotId,
                    $emballageId,
                    (string) $dateInv
                );

            $inv->fill([
                'entrepot_id' => $entrepotId,
                'emballage_id' => $emballageId,
                'stock_physique' => $physique,
                'stock_theorique' => $theorique,
                'ecart' => $physique - $theorique,
                'user_id' => $input['user_id'] ?? $inv->user_id,
                'date_inventaire' => $dateInv,
                'periode_debut' => $periodeDebut,
                'periode_fin' => $periodeFin,
            ]);

            $inv->save();

            return $inv->fresh();
        });
    }

    public function delete($_, array $args): StockInventaire
    {
        $id = (int) $args['id'];

        return DB::transaction(function () use ($id) {
            $inv = StockInventaire::query()->findOrFail($id);
            $inv->delete();
            return $inv;
        });
    }
}