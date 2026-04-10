<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrat extends Model
{
   protected $table = 'contrats';

    protected $fillable = [
        'numero_contrat',
    'objet',
    'date_signature',
    'date_debut',
    'date_fin',
    'quantite_contractuelle',
    'quantite_realisee',
    'taux_depassement_autorise',
    'montant_ht',
    'montant_tva',
    'taux_cautionnement',
    'taux_penalite_retard',
    'plafond_penalite',
    'prix_unitaire',
    'statut',
    'fournisseur_id',
    'emballage_id',

    ];

    

        public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }
}
