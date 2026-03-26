<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepotLot extends Model
{
    protected $table = 'entrepot_lots';

    protected $fillable = [
        'entrepot_id',
        'lot_id',
        'emballage_id',
        'quantite',
    ];

    protected $casts = [
        'entrepot_id' => 'integer',
        'lot_id' => 'integer',
        'emballage_id' => 'integer',
        'quantite' => 'float',
    ];

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function emballage(): BelongsTo
    {
        return $this->belongsTo(Emballage::class);
    }
}