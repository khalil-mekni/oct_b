<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'code_lot',
        'emballage_id',
        'quantite',
        'date_mvt',
        'user_id',
        'commentaire',
    ];

    protected $casts = [
        'quantite' => 'float',
        'date_mvt' => 'datetime',
    ];

    public function emballage(): BelongsTo
    {
        return $this->belongsTo(Emballage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function entrepotLots(): HasMany
    {
        return $this->hasMany(EntrepotLot::class);
    }
}