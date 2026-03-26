<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stocks')->insert([
            'entrepot_id' => 1,
            'emballage_id' => 1,
            'lot_id' => null,
            'date_stock' => '2026-03-01 10:00:00',


            'quantite' => 200,
            'sens' => 'entree',


            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}