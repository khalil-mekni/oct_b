<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emballage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'capacity_value',
        'capacity_unit',
        'material',
        'status',
    ];

    public function contrats()
{
    return $this->hasMany(Contrat::class, 'emballage_id');
}
}
