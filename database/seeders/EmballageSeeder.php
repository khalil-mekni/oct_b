<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmballageSeeder extends Seeder
{
    public function run(): void
    {
        // Désactivation des contraintes pour le truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('emballages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            ['id' => 1,  'name' => 'Cartons',            'code' => 'EMB-CAR-001', 'type' => 'CARTON',   'material' => 'Carton'],
            ['id' => 2,  'name' => 'Riz Blanc',          'code' => 'EMB-RIZ-001', 'type' => 'SAC',      'material' => 'Polypropylène'],
            ['id' => 3,  'name' => 'Sucre Blanc',        'code' => 'EMB-SUC-001', 'type' => 'SAC',      'material' => 'Papier'],
            ['id' => 4,  'name' => 'Riz Étuvé',          'code' => 'EMB-RIZ-002', 'type' => 'SAC',      'material' => 'Polypropylène'],
            ['id' => 5,  'name' => 'Riz Basmati',        'code' => 'EMB-RIZ-003', 'type' => 'SAC',      'material' => 'Polypropylène'],
            ['id' => 6,  'name' => 'Complexe',           'code' => 'EMB-COM-001', 'type' => 'SAC',      'material' => 'Plastique'],
            ['id' => 7,  'name' => 'Rouleaux Adhésifs',  'code' => 'EMB-ADH-001', 'type' => 'AUTRE',    'material' => 'Plastique'],
            ['id' => 8,  'name' => 'TNCeylon 150 G',     'code' => 'EMB-THE-001', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 9,  'name' => 'TNExtra 250 G',      'code' => 'EMB-THE-002', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 10, 'name' => 'TNExtra Plus 100 G', 'code' => 'EMB-THE-003', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 11, 'name' => 'TNExtra Plus 250 G', 'code' => 'EMB-THE-004', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 12, 'name' => 'TVBourgeon 250 G',   'code' => 'EMB-THE-005', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 13, 'name' => 'TVSuperieur 100 G',  'code' => 'EMB-THE-006', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 14, 'name' => 'TVSuperieur 250 G',  'code' => 'EMB-THE-007', 'type' => 'BOITE',    'material' => 'Carton'],
            ['id' => 15, 'name' => 'Thermo 200µ',        'code' => 'EMB-FIL-001', 'type' => 'FILM',     'material' => 'Plastique'],
            ['id' => 16, 'name' => 'Thermo 500µ',        'code' => 'EMB-FIL-002', 'type' => 'FILM',     'material' => 'Plastique'],
            ['id' => 17, 'name' => 'Étirable',           'code' => 'EMB-FIL-003', 'type' => 'FILM',     'material' => 'Plastique'],
            ['id' => 18, 'name' => 'Étirable GINOR',     'code' => 'EMB-FIL-004', 'type' => 'FILM',     'material' => 'Plastique'],
        ];

        $now = now();
        foreach ($data as &$item) {
            $item['description'] = 'Emballage pour ' . $item['name'];
            $item['min_stock'] = 1000;
            $item['capacity_value'] = 50;
            $item['capacity_unit'] = 'KG';
            $item['status'] = 'ACTIVE';
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }

        DB::table('emballages')->insert($data);
    }
}
