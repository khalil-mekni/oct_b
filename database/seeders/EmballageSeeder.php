<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmballageSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['SAC','BIG_BAG','FUT','CAISSE','PALETTE'];

        $data = [];

        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'code' => "EMB-00$i",
                'name' => "Emballage $i",
                'type' => $types[$i % 5],
                'capacity_value' => rand(10,1000),
                'capacity_unit' => 'KG',
                'material' => 'Polypropylène',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('emballages')->insert($data);
    }
}