<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Emballage extends Model
{
    use SoftDeletes; // ← AJOUTÉ : nécessaire pour softDelete/restore/forceDelete

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

    protected $attributes = [
        'status' => 'ACTIVE', // ← valeur par défaut
    ];

    public function contrats()
    {
        return $this->hasMany(Contrat::class, 'emballage_id');
    }
}