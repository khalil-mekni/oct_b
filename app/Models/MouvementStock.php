<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementStock extends Model
{
    protected $table = 'mouvement_stocks';

    protected $fillable = [
    'code_mouvement',
    'type_mouvement',
    'emballage_id',
    'lot_id',
    'entrepot_source_id',
    'entrepot_destination_id',
    'quantite',
    'date_mouvement',
    'user_id',
    'statut',
];

    protected $casts = [
        'date_mouvement' => 'datetime',
        'quantite' => 'float',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lot::class, 'lot_id');
    }

    public function entrepotSource(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Entrepot::class, 'entrepot_source_id');
    }

    public function entrepotDestination(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Entrepot::class, 'entrepot_destination_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function emballage(): BelongsTo
{
    return $this->belongsTo(\App\Models\Emballage::class,'emballage_id');
}
}
