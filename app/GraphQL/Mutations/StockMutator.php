<?php

namespace App\GraphQL\Mutations;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMutator
{
    public function update($_, array $args): Stock
    {
        return DB::transaction(function () use ($args) {
            $id = (int) $args['id'];
            $input = $args['input'];

            $stock = Stock::find($id);

            if (!$stock) {
                throw ValidationException::withMessages([
                    'id' => "Stock #{$id} introuvable."
                ]);
            }

            $stock->fill([
                'entrepot_id' => $input['entrepot_id'] ?? $stock->entrepot_id,
                'emballage_id' => $input['emballage_id'] ?? $stock->emballage_id,
                'lot_id' => $input['lot_id'] ?? $stock->lot_id,
                'date_stock' => $input['date_stock'] ?? $stock->date_stock,
                'quantite' => $input['quantite'] ?? $stock->quantite,
                'sens' => $input['sens'] ?? $stock->sens,
                'user_id' => $input['user_id'] ?? $stock->user_id,
            ]);

            $stock->save();

            return $stock->fresh(['entrepot', 'emballage', 'lot', 'user']);
        });
    }

    public function delete($_, array $args): Stock
    {
        return DB::transaction(function () use ($args) {
            $id = (int) $args['id'];

            $stock = Stock::find($id);

            if (!$stock) {
                throw ValidationException::withMessages([
                    'id' => "Stock #{$id} introuvable."
                ]);
            }

            $stockCopy = clone $stock;
            $stock->delete();

            return $stockCopy;
        });
    }

    
    private function rebuildScope(int $entrepotId, int $emballageId, Carbon $fromDate): void
    {
        $previousStock = Stock::where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId)
            ->where('date_stock', '<', $fromDate)
            ->orderBy('date_stock', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $runningFinale = $previousStock ? (float) $previousStock->quantite_finale : 0;

        $stocks = Stock::where('entrepot_id', $entrepotId)
            ->where('emballage_id', $emballageId)
            ->where('date_stock', '>=', $fromDate)
            ->orderBy('date_stock', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $item) {
            $item->quantite_init = $runningFinale;

            $item->quantite_finale = match ($item->sens) {
                'entree' => $runningFinale + (float) $item->quantite,
                'sortie' => $runningFinale - (float) $item->quantite,
                default => throw ValidationException::withMessages([
                    'sens' => 'Valeur de sens invalide.'
                ]),
            };

            if ($item->quantite_finale < 0) {
                throw ValidationException::withMessages([
                    'stock' => 'Stock insuffisant après recalcul.'
                ]);
            }

            $item->save();

            $runningFinale = (float) $item->quantite_finale;
        }
    }
}


