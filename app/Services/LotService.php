<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LotService
{
    /**
     * Crée un nouveau lot automatique : L001, L002, L003...
     * Transaction + lock pour éviter doublon si 2 users créent en même temps.
     */
    public function createAutoLot(array $data = []): Lot
    {
        return DB::transaction(function () use ($data) {

            // Chercher le dernier numero_lot du format L###
            $last = Lot::select('numero_lot')
                ->where('numero_lot', 'regexp', '^L[0-9]+$')
                ->orderByRaw("CAST(SUBSTRING(numero_lot, 2) AS UNSIGNED) DESC")
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;

            if ($last && preg_match('/^L(\d+)$/', $last->numero_lot, $m)) {
                $nextNumber = ((int) $m[1]) + 1;
            }

            $numeroLot = 'L' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

            // Optionnel : validations dates
            if (!empty($data['date_production']) && !empty($data['date_expiration'])) {
                if ($data['date_expiration'] < $data['date_production']) {
                    throw new InvalidArgumentException("date_expiration doit être >= date_production");
                }
            }

            return Lot::create([
    'numero_lot' => $numeroLot,
    'date_production' => $data['date_production'] ?? null,
    'date_expiration' => $data['date_expiration'] ?? null,
    'quantite' => isset($data['quantite']) ? (float) $data['quantite'] : 0,
])->refresh();
        });
    }
}