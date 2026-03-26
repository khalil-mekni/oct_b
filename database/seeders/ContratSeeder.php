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
            $data[] = [
                'numero_contrat' => "CTR-2026-0$i",
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',
                'quantite_contractuelle' => rand(1000,5000),
                'taux_depassement_autorise' => 0.20,
                'quantite_realisee' => rand(100,1000),
                'prix' => rand(50, 500),
                'statut' => $i % 2 == 0 ? 'ACTIF' : 'SUSPENDU',
                'fournisseur_id' => $i,   
                'emballage_id' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('contrats')->insert($data);
    }
}