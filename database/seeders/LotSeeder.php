<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LotSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('lots')->insert([
            [
                'code_lot' => 'LOT-001',
                'emballage_id' => 1,
                'quantite' => 1000,
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Réception fournisseur',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code_lot' => 'LOT-002',
                'emballage_id' => 2,
                'quantite' => 500,
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Livraison initiale',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code_lot' => 'LOT-003',
                'emballage_id' => 3,
                'quantite' => 200,
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Réception lot emballage',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code_lot' => 'LOT-004',
                'emballage_id' => 4,
                'quantite' => 300,
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Entrée stock initiale',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code_lot' => 'LOT-005',
                'emballage_id' => 5,
                'quantite' => 50,
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Création manuelle du lot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        for ($i = 6; $i <= 10; $i++) {
            DB::table('lots')->insert([
                'code_lot' => 'LOT-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'emballage_id' => $i,
                'quantite' => rand(100, 800),
                'user_id' => 1,
                'date_mvt' => $now,
                'commentaire' => 'Lot généré automatiquement',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}