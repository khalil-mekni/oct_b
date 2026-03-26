<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInventaire extends Model
{
    protected $table = 'stock_inventaires';

    protected $fillable = [
        'entrepot_id',
        'emballage_id',
        'lot_id',
        'stock_theorique',
        'stock_physique',
        'ecart',
        'user_id',
        'date_inventaire',
        'periode_debut',
        'periode_fin',
    ];

    protected $casts = [
        'stock_theorique' => 'decimal:2',
        'stock_physique' => 'decimal:2',
        'ecart' => 'decimal:2',
        'date_inventaire' => 'datetime',
        'periode_debut' => 'datetime',
        'periode_fin' => 'datetime',
    ];

    public $timestamps = true;

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class, 'entrepot_id');
    }

    public function emballage(): BelongsTo
    {
        return $this->belongsTo(Emballage::class, 'emballage_id');
    }



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}