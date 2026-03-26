<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrat extends Model
{
   protected $table = 'contrats';

    protected $fillable = [
        'numero_contrat',
        'date_debut',
        'date_fin',
        'quantite_contractuelle',
        'taux_depassement_autorise',
        'quantite_realisee',
        'prix',
        'statut',
        'fournisseur_id',
        'emballage_id',

    ];

    /*public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }
     public function emballage()
    {
        return $this->belongsTo(Emballage::class, 'emballage_id');
    }*/

        public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }
}
