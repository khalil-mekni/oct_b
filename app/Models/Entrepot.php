<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrepot extends Model
{
    protected $table = 'entrepots';

    protected $fillable = [
        'nom',       
         'adresse',

        'capacite_totale',
        'capacite_disponible',
        'statut',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'entrepot_id');
    }
}