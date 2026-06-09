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

    protected $casts = [
        'date_signature' => 'datetime',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'quantite_contractuelle' => 'float',
        'quantite_realisee' => 'float',
        'taux_depassement_autorise' => 'float',
        'montant_ht' => 'float',
        'montant_tva' => 'float',
        'taux_cautionnement' => 'float',
        'taux_penalite_retard' => 'float',
        'plafond_penalite' => 'float',
        'prix_unitaire' => 'float',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function bonLivraisons()
    {
        return $this->hasManyThrough(BonLivraison::class, Commande::class);
    }
}