<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonLivraison extends Model
{
    protected $fillable = [
        'numero_bl',
        'date_reception',
        'statut',
        'emballage_id',
        'quantite_recue',
        'numero_commande',
        'commande_id',
        'entrepot_id',
        'receptionne_par',
    ];

    protected $casts = [
        'date_reception' => 'datetime',
        'quantite_recue' => 'float',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }

    public function entrepot()
    {
        return $this->belongsTo(Entrepot::class);
    }
}