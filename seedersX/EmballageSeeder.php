<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmballageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('emballages')->insert([

            // =========================
            // THE VERT - COMPLEXE
            // =========================

            [
                "code" => "TV-CX-100",
                "name" => "Thé Vert Supérieur 100g",
                "type" => "COMPLEXE",
                "min_stock" => 100,
                "description" => "Complexe Thé Vert 100g",
                "capacity_value" => 100,
                "capacity_unit" => "g",
                "poids" => 0.10,
                "epaisseur_pp" => 0.05,
                "epaisseur_ppc" => 0.07,
                "largeur" => 12.50,
                "material" => "Polypropylène",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "TV-CX-250",
                "name" => "Thé Vert Supérieur 250g",
                "type" => "COMPLEXE",
                "min_stock" => 100,
                "description" => "Complexe Thé Vert 250g",
                "capacity_value" => 250,
                "capacity_unit" => "g",
                "poids" => 0.25,
                "epaisseur_pp" => 0.06,
                "epaisseur_ppc" => 0.08,
                "largeur" => 14.00,
                "material" => "Polypropylène",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // THE VERT - CARTON
            // =========================

            [
                "code" => "TV-CRT-100",
                "name" => "Carton Thé Vert 100g",
                "type" => "CARTON",
                "min_stock" => 80,
                "description" => "Carton Thé Vert",
                "capacity_value" => 100,
                "capacity_unit" => "g",
                "poids" => 0.30,
                "epaisseur_pp" => 0.10,
                "epaisseur_ppc" => 0.12,
                "largeur" => 20.00,
                "material" => "Carton Kraft",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "TV-CRT-250",
                "name" => "Carton Thé Vert 250g",
                "type" => "CARTON",
                "min_stock" => 80,
                "description" => "Carton Thé Vert",
                "capacity_value" => 250,
                "capacity_unit" => "g",
                "poids" => 0.45,
                "epaisseur_pp" => 0.11,
                "epaisseur_ppc" => 0.13,
                "largeur" => 24.00,
                "material" => "Carton Kraft",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // THE NOIR - COMPLEXE
            // =========================

            [
                "code" => "TN-CX-100",
                "name" => "Thé Noir Extra Plus 100g",
                "type" => "COMPLEXE",
                "min_stock" => 100,
                "description" => "Complexe Thé Noir",
                "capacity_value" => 100,
                "capacity_unit" => "g",
                "poids" => 0.11,
                "epaisseur_pp" => 0.05,
                "epaisseur_ppc" => 0.07,
                "largeur" => 12.00,
                "material" => "Polypropylène",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "TN-CX-250",
                "name" => "Thé Noir Extra 250g",
                "type" => "COMPLEXE",
                "min_stock" => 100,
                "description" => "Complexe Thé Noir",
                "capacity_value" => 250,
                "capacity_unit" => "g",
                "poids" => 0.26,
                "epaisseur_pp" => 0.06,
                "epaisseur_ppc" => 0.08,
                "largeur" => 14.50,
                "material" => "Polypropylène",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // THE NOIR - CARTON
            // =========================

            [
                "code" => "TN-CRT-100",
                "name" => "Carton Thé Noir 100g",
                "type" => "CARTON",
                "min_stock" => 80,
                "description" => "Carton Thé Noir",
                "capacity_value" => 100,
                "capacity_unit" => "g",
                "poids" => 0.32,
                "epaisseur_pp" => 0.10,
                "epaisseur_ppc" => 0.12,
                "largeur" => 20.50,
                "material" => "Carton Kraft",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "TN-CRT-250",
                "name" => "Carton Thé Noir 250g",
                "type" => "CARTON",
                "min_stock" => 80,
                "description" => "Carton Thé Noir",
                "capacity_value" => 250,
                "capacity_unit" => "g",
                "poids" => 0.48,
                "epaisseur_pp" => 0.11,
                "epaisseur_ppc" => 0.13,
                "largeur" => 24.50,
                "material" => "Carton Kraft",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // RIZ
            // =========================

            [
                "code" => "RIZ-CRT-ETV",
                "name" => "Carton Riz Étuvé",
                "type" => "CARTON",
                "min_stock" => 120,
                "description" => "Carton Riz Étuvé",
                "capacity_value" => 1,
                "capacity_unit" => "piece",
                "poids" => 1.00,
                "epaisseur_pp" => 0.15,
                "epaisseur_ppc" => 0.18,
                "largeur" => 35.00,
                "material" => "Carton recyclé",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "RIZ-CX-BSM",
                "name" => "Complexe Riz Basmati",
                "type" => "COMPLEXE",
                "min_stock" => 100,
                "description" => "Complexe Riz Basmati",
                "capacity_value" => 1,
                "capacity_unit" => "piece",
                "poids" => 0.80,
                "epaisseur_pp" => 0.09,
                "epaisseur_ppc" => 0.11,
                "largeur" => 28.00,
                "material" => "Film multicouche",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // SUCRE
            // =========================

            [
                "code" => "SCR-CRT",
                "name" => "Carton Sucre Blanc",
                "type" => "CARTON",
                "min_stock" => 150,
                "description" => "Carton Sucre",
                "capacity_value" => 1,
                "capacity_unit" => "piece",
                "poids" => 0.90,
                "epaisseur_pp" => 0.12,
                "epaisseur_ppc" => 0.15,
                "largeur" => 30.00,
                "material" => "Carton",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "SCR-ETIR",
                "name" => "Étirable Sucre Blanc",
                "type" => "ETIRABLE",
                "min_stock" => 120,
                "description" => "Film étirable sucre",
                "capacity_value" => 1,
                "capacity_unit" => "piece",
                "poids" => 0.50,
                "epaisseur_pp" => 0.03,
                "epaisseur_ppc" => 0.04,
                "largeur" => 25.00,
                "material" => "PVC",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // FILMS
            // =========================

            [
                "code" => "ETIR-GINOR",
                "name" => "Étirable Ginor",
                "type" => "FILM",
                "min_stock" => 50,
                "description" => "Film étirable Ginor",
                "capacity_value" => 1,
                "capacity_unit" => "rouleau",
                "poids" => 1.20,
                "epaisseur_pp" => 0.02,
                "epaisseur_ppc" => 0.03,
                "largeur" => 45.00,
                "material" => "PVC",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "THERMO-200",
                "name" => "Thermo 200",
                "type" => "FILM",
                "min_stock" => 50,
                "description" => "Thermo 200",
                "capacity_value" => 200,
                "capacity_unit" => "mm",
                "poids" => 0.70,
                "epaisseur_pp" => 0.04,
                "epaisseur_ppc" => 0.05,
                "largeur" => 20.00,
                "material" => "Polyoléfine",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            [
                "code" => "THERMO-500",
                "name" => "Thermo 500",
                "type" => "FILM",
                "min_stock" => 50,
                "description" => "Thermo 500",
                "capacity_value" => 500,
                "capacity_unit" => "mm",
                "poids" => 1.50,
                "epaisseur_pp" => 0.05,
                "epaisseur_ppc" => 0.06,
                "largeur" => 50.00,
                "material" => "Polyoléfine",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

            // =========================
            // ACCESSOIRES
            // =========================

            [
                "code" => "ACC-RADH",
                "name" => "Rouleaux Adhésifs",
                "type" => "ACCESSOIRE",
                "min_stock" => 60,
                "description" => "Rouleaux adhésifs emballage",
                "capacity_value" => 1,
                "capacity_unit" => "rouleau",
                "poids" => 0.20,
                "epaisseur_pp" => 0.01,
                "epaisseur_ppc" => 0.02,
                "largeur" => 5.00,
                "material" => "Adhésif",
                "statut" => "ACTIVE",
                "created_at" => now(),
                "updated_at" => now(),
            ],

        ]);
    }
}
