<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $fillable = [
        'numero_facture',
        'date_facture',
        'montant_ht',
        'montant_ttc',
        'statut',
        'emballage_id',
        'quantite_facturee',
        'fournisseur_id',
        'contrat_id',
        'commande_id',
        'bon_livraison_id',
        'valide_par',
    ];

    protected $casts = [
        'date_facture' => 'datetime',
    ];

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function bonLivraison()
    {
        return $this->belongsTo(BonLivraison::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}