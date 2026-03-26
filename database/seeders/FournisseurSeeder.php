<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $data = [];

        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'raison_sociale' => "Fournisseur $i",
                'matricule_fiscale' => "MF000$i",
                'telephone' => "+2167000000$i",
                'adresse' => "Zone Industrielle $i",
                'statut' => $i % 3 == 0 ? 'INACTIF' : 'ACTIF',   'latitude' => 36.80 + ($i * 0.01),
    'longitude' => 10.18 + ($i * 0.01),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('fournisseurs')->insert($data);
    }
}