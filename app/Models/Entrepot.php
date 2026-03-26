<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrepot extends Model
{
    protected $fillable = [
        'nom',
        'adresse',
        'capacite_totale',
        'stock_existant',
        'capacite_disponible',
        'statut',
    ];

    protected $casts = [
        'capacite_totale' => 'float',
        'stock_existant' => 'float',
        'capacite_disponible' => 'float',
    ];

    public function entrepotLots(): HasMany
    {
        return $this->hasMany(EntrepotLot::class);
    }
}