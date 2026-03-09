<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // USER
        DB::table('users')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // FOURNISSEUR
        DB::table('fournisseurs')->updateOrInsert(
            ['id' => 1],
            [
                'raison_sociale' => 'Fournisseur Test',
                'matricule_fiscale' => 'MF123456',
                'telephone' => '12345678',
                'adresse' => 'Tunis',
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // CONTRAT
        DB::table('contrats')->updateOrInsert(
            ['id' => 1],
            [
                'numero_contrat' => 'CTR-001',
                'date_debut' => '2026-03-01',
                'date_fin' => '2026-12-31',
                'quantite_contractuelle' => 1000.00,
                'taux_depassement_autorise' => 0.20,
                'quantite_realisee' => 0.00,
                'statut' => 'ACTIF',
                'fournisseur_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ENTREPOT
        DB::table('entrepots')->updateOrInsert(
            ['id' => 1],
            [
                'adresse' => 'Entrepot Principal',
                'capacite_totale' => 10000.00,
                'capacite_disponible' => 10000.00,
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // EMBALLAGE
        DB::table('emballages')->updateOrInsert(
            ['id' => 1],
            [
                'code' => 'EMB-001',
                'name' => 'Palette Standard',
                'type' => 'PALETTE',
                'capacity_value' => 1000,
                'capacity_unit' => 'KG',
                'material' => 'BOIS',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}