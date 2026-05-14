<?php

namespace App\GraphQL\Mutations;

use App\Models\StockInventaire;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockInventaireMutator
{
    public function create($_, array $args): StockInventaire
    {
        $input = $args['input'];

        return DB::transaction(function () use ($input) {
            $entrepotId  = (int) $input['entrepot_id'];
            $emballageId = (int) $input['emballage_id'];
            $userId      = $input['user_id'] ?? null;
            $dateInv     = Carbon::parse($input['date_inventaire']);
            $physique    = (float) $input['stock_physique'];

            $theorique = app(StockService::class)->getTheoriqueAt(
                $entrepotId,
                $emballageId,
                null,
                $dateInv
            );

            return StockInventaire::create([
                'entrepot_id'      => $entrepotId,
                'emballage_id'     => $emballageId,
                'stock_physique'   => $physique,
                'stock_theorique'  => $theorique,
                'ecart'            => $physique - $theorique,
                'user_id'          => $userId,
                'date_inventaire'  => $dateInv,
                'periode_debut'    => isset($input['periode_debut']) ? Carbon::parse($input['periode_debut']) : null,
                'periode_fin'      => isset($input['periode_fin']) ? Carbon::parse($input['periode_fin']) : null,
            ])->load(['entrepot', 'emballage']);
        });
    }

    public function update($_, array $args): StockInventaire
    {
        $id = (int) $args['id'];
        $input = $args['input'];

        return DB::transaction(function () use ($id, $input) {
            $inv = StockInventaire::query()->findOrFail($id);

            $dateInv = isset($input['date_inventaire'])
                ? Carbon::parse($input['date_inventaire'])
                : $inv->date_inventaire;

            $physique = array_key_exists('stock_physique', $input)
                ? (float) $input['stock_physique']
                : (float) $inv->stock_physique;

            $theorique = app(StockService::class)->getTheoriqueAt(
                (int) $inv->entrepot_id,
                (int) $inv->emballage_id,
                null,
                $dateInv
            );

            $inv->fill([
                'stock_physique'  => $physique,
                'stock_theorique' => $theorique,
                'ecart'           => $physique - $theorique,
                'user_id'         => $input['user_id'] ?? $inv->user_id,
                'date_inventaire' => $dateInv,
                'periode_debut'   => array_key_exists('periode_debut', $input)
                    ? ($input['periode_debut'] ? Carbon::parse($input['periode_debut']) : null)
                    : $inv->periode_debut,
                'periode_fin'     => array_key_exists('periode_fin', $input)
                    ? ($input['periode_fin'] ? Carbon::parse($input['periode_fin']) : null)
                    : $inv->periode_fin,
            ]);

            $inv->save();

            return $inv->fresh()->load(['entrepot', 'emballage']);
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