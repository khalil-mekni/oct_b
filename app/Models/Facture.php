<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $fillable = [
        'numero_facture',
        'date_facture',
        'montant_ht',            // Montant Brut
        'montant_penalites',     // NOUVEAU
        'montant_ht_net',        // NOUVEAU
        'montant_ttc',
        'statut',
        'emballage_id',
        'quantite_facturee',
        'fournisseur_id',
        'contrat_id',
        'commande_id',
        'bon_livraison_id',      // Gardé pour compatibilité
        'valide_par',
        'jours_retard_total',    // NOUVEAU
        'details_calcul_penalite' // NOUVEAU
    ];

    protected $casts = [
        'date_facture' => 'datetime',
        'montant_ht' => 'float',
        'montant_penalites' => 'float',
        'montant_ttc' => 'float',
    ];

    // --- RELATIONS ---

    /**
     * Relation vers PLUSIEURS Bons de Livraison (Groupement)
     */
  public function bon_livraisons() // Change bonLivraisons en bon_livraisons
{
    return $this->hasMany(BonLivraison::class);
}
    /**
     * Gardé pour ton code actuel
     */
  public function bonLivraison()
{
    return $this->belongsTo(BonLivraison::class, 'bon_livraison_id');
}

    public function emballage()
    {
        return $this->belongsTo(Emballage::class);
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
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