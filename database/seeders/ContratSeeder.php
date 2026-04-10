<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContratSeeder extends Seeder
{
    public function run(): void
    {
        $data = [];

        for ($i = 1; $i <= 10; $i++) {
            $quantite = rand(1000, 5000);
            $realisee = rand(100, 1000);
            $prixUnitaire = rand(10, 50);

            $montantHT = $quantite * $prixUnitaire;
            $montantTVA = $montantHT * 0.19;

            $data[] = [
                'numero_contrat' => "CTR-2026-0$i",
                'objet' => "Contrat fourniture emballage $i",

                'date_signature' => '2025-12-15',
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',

                'quantite_contractuelle' => $quantite,
                'quantite_realisee' => $realisee,

                'taux_depassement_autorise' => 20,
                'montant_ht' => $montantHT,
                'montant_tva' => $montantTVA,

                'taux_cautionnement' => 5.00,
                'taux_penalite_retard' => 0.0020,
                'plafond_penalite' => 5.00,

                'prix_unitaire' => $prixUnitaire,

                'statut' => $i % 3 == 0 ? 'EXPIRE' : ($i % 2 == 0 ? 'ACTIF' : 'SUSPENDU'),

                'fournisseur_id' => $i,
                'emballage_id' => $i,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('contrats')->insert($data);
    }
}