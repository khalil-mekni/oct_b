<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntrepotSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('entrepots')->insert([
            [
                'nom' => 'Entrepot Central',
                'adresse' => 'Zone Industrielle Tunis',
                'capacite_totale' => 10000.00,
                'stock_existant' => 2500.00,
                'capacite_disponible' => 7500.00,
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Entrepot Sfax',
                'adresse' => 'Route de Sidi Mansour, Sfax',
                'capacite_totale' => 8000.00,
                'stock_existant' => 3200.00,
                'capacite_disponible' => 4800.00,
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Entrepot Sousse',
                'adresse' => 'Zone Logistique Sousse',
                'capacite_totale' => 6000.00,
                'stock_existant' => 1500.00,
                'capacite_disponible' => 4500.00,
                'statut' => 'INACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}