<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('fournisseurs')->insert([

            [
                'raison_sociale' => 'Tunisie Matières Premières',
                'logo' => null,
                'matricule_fiscale' => 'TMP12345A',
                'registre_entreprise' => 'B2000012025',
                'telephone' => '+216 71 450 300',
                'email' => 'contact@tmp.tn',
                'adresse' => 'Zone Industrielle Charguia, Tunis',
                'representant_nom' => 'Hichem Ben Salah',
                'representant_role' => 'Directeur Achat',
                'latitude' => 36.8500,
                'longitude' => 10.2200,
                'adresse_geocodee' => 'Charguia, Tunis',
                'geocoded_at' => now(),
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'raison_sociale' => 'Agro Packaging Supply',
                'logo' => null,
                'matricule_fiscale' => 'APS45678B',
                'registre_entreprise' => 'B2000022025',
                'telephone' => '+216 72 410 500',
                'email' => 'info@agropack.tn',
                'adresse' => 'Nabeul, Tunisie',
                'representant_nom' => 'Sami Trabelsi',
                'representant_role' => 'Responsable Commercial',
                'latitude' => 36.4510,
                'longitude' => 10.7350,
                'adresse_geocodee' => 'Nabeul, Tunisie',
                'geocoded_at' => now(),
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'raison_sociale' => 'Société Tunisienne du Carton',
                'logo' => null,
                'matricule_fiscale' => 'STC78945C',
                'registre_entreprise' => 'B2000032025',
                'telephone' => '+216 73 250 400',
                'email' => 'contact@stc.tn',
                'adresse' => 'Sfax, Tunisie',
                'representant_nom' => 'Walid Karray',
                'representant_role' => 'Chef Production',
                'latitude' => 34.7406,
                'longitude' => 10.7603,
                'adresse_geocodee' => 'Sfax, Tunisie',
                'geocoded_at' => now(),
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'raison_sociale' => 'PlastPack Industrie',
                'logo' => null,
                'matricule_fiscale' => 'PPI96325D',
                'registre_entreprise' => 'B2000042025',
                'telephone' => '+216 74 500 600',
                'email' => 'commercial@plastpack.tn',
                'adresse' => 'Gabès, Tunisie',
                'representant_nom' => 'Moez Jebali',
                'representant_role' => 'Manager Industriel',
                'latitude' => 33.8815,
                'longitude' => 10.0982,
                'adresse_geocodee' => 'Gabès, Tunisie',
                'geocoded_at' => now(),
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'raison_sociale' => 'Eco Raw Materials',
                'logo' => null,
                'matricule_fiscale' => 'ERM85214E',
                'registre_entreprise' => 'B2000052025',
                'telephone' => '+216 70 120 700',
                'email' => 'contact@ecoraw.tn',
                'adresse' => 'Ariana, Tunisie',
                'representant_nom' => 'Ahmed Gharbi',
                'representant_role' => 'Responsable Logistique',
                'latitude' => 36.8663,
                'longitude' => 10.1647,
                'adresse_geocodee' => 'Ariana, Tunisie',
                'geocoded_at' => now(),
                'statut' => 'ACTIF',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}