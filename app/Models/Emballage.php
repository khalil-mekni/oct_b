<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emballage extends Model
{
    protected $fillable = [
    'code',
        'name',
        'type',
        'description',
        'min_stock',
        'capacity_value',
        'capacity_unit',
        'poids',
        'epaisseur_pp',
        'epaisseur_ppc',
        'largeur',
        'material',
        'status',
    ];

    public function contrats()
{
    return $this->hasMany(Contrat::class, 'emballage_id');
}
}