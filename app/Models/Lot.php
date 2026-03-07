<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'numero_lot',
        'date_production',
        'date_expiration',
        'quantite',
    ];

    protected $casts = [
        'date_production' => 'date',
        'date_expiration' => 'date',
        'quantite' => 'decimal:2',
    ];

    // Cas A: relation vers Stock (table stocks)
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'lot_id');
    }

    // Si ton model s'appelle StockEmballage au lieu de Stock:
    // public function stockEmballages(): HasMany
    // {
    //     return $this->hasMany(StockEmballage::class, 'lot_id');
    // }
    public function createAutoLot(): Lot
    {
        $lastLot = Lot::orderBy('id', 'desc')->first();

        $nextNumber = 1;

        if ($lastLot && preg_match('/L(\d+)/', $lastLot->numero_lot, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        $numeroLot = 'L' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return Lot::create([
            'numero_lot' => $numeroLot,
            'quantite' => 0
        ]);
    }
}