<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'numero_commande',
        'date_commande',
        'date_livraison_prevue',
        'statut',
        'emballage_id',
        'quantite',
        'fournisseur_id',
        'contrat_id',
        'entrepot_id',
        'created_by',
    ];
    protected $casts = [
        'date_commande' => 'date',
        'date_livraison_prevue' => 'date',
        'quantite' => 'float',
    ];
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }
    public function entrepot()
    {
        return $this->belongsTo(Entrepot::class);
    }

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
