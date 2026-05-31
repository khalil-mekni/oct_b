<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncEmballageMLSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ce seeder met à jour les noms des emballages pour qu'ils soient
     * compatibles avec le mapping du service de prédiction.
     */
    public function run(): void
    {
        $mappings = [
            1 => "Thé Vert Supérieur 100g",
            2 => "Thé Vert Supérieur 250g",
            5 => "Thé Noir Extra Plus 100g",
            6 => "Thé Noir Extra 250g",
            9 => "Carton Riz Étuvé",
            10 => "Complexe Riz Basmati",
            11 => "Carton Sucre Blanc",
        ];

        foreach ($mappings as $id => $name) {
            DB::table('emballages')
                ->where('id', $id)
                ->update(['name' => $name, 'updated_at' => now()]);
        }

        echo "Mise à jour des noms d'emballages terminée avec succès.\n";
    }
}
