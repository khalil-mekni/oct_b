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
                'nom' => 'Entrepôt OCT Sousse',
                'adresse' => 'Zone industrielle Sidi Abdelhamid, Sousse 4000',
                'capacite_totale' => 22000,
                'stock_existant' => 1115,
                'capacite_disponible' => 20885,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Béja',
                'adresse' => 'Route de Nefza, Béja 9000',
                'capacite_totale' => 18000,
                'stock_existant' => 749,
                'capacite_disponible' => 17251,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Gabès',
                'adresse' => 'Bouchemma BP70, Gabès 6031',
                'capacite_totale' => 15000,
                'stock_existant' => 1135,
                'capacite_disponible' => 13865,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Kairouan',
                'adresse' => 'BP 68 El Okba, Kairouan 3140',
                'capacite_totale' => 16000,
                'stock_existant' => 610,
                'capacite_disponible' => 15390,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Kasserine',
                'adresse' => 'Zone Industrielle Rue Sbeitla, Kasserine 1200',
                'capacite_totale' => 14000,
                'stock_existant' => 5900,
                'capacite_disponible' => 8100,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Gafsa',
                'adresse' => 'BP 41 Hached Lella CP 2121',
                'capacite_totale' => 13500,
                'stock_existant' => 5000,
                'capacite_disponible' => 8500,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Médenine',
                'adresse' => 'Route Ben Guerdane, Médenine 4100',
                'capacite_totale' => 14500,
                'stock_existant' => 6500,
                'capacite_disponible' => 8000,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Zarzis',
                'adresse' => 'Route Ben Guerdane Km 6, Zarzis 4170',
                'capacite_totale' => 12000,
                'stock_existant' => 4800,
                'capacite_disponible' => 7200,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Tozeur',
                'adresse' => 'Station Star Oil Ali Rejeb Dgueche',
                'capacite_totale' => 10000,
                'stock_existant' => 3900,
                'capacite_disponible' => 6100,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Tataouine',
                'adresse' => 'Avenue Habib Bourguiba, Tataouine 3200',
                'capacite_totale' => 9500,
                'stock_existant' => 3500,
                'capacite_disponible' => 6000,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Le Kef',
                'adresse' => 'Kef Gharbia Zone Industrielle Barnoussa',
                'capacite_totale' => 11000,
                'stock_existant' => 4600,
                'capacite_disponible' => 6400,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Kébili',
                'adresse' => 'Route Tozeur Km 2, Kébili 4200',
                'capacite_totale' => 9000,
                'stock_existant' => 3000,
                'capacite_disponible' => 6000,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Makthar',
                'adresse' => 'BP 62, Makthar 6140',
                'capacite_totale' => 8500,
                'stock_existant' => 2800,
                'capacite_disponible' => 5700,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Sidi Bouzid',
                'adresse' => 'BP 305, Sidi Bouzid',
                'capacite_totale' => 12500,
                'stock_existant' => 5100,
                'capacite_disponible' => 7400,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT La Goulette',
                'adresse' => 'Immeuble La Goulette, Tunis 2060',
                'capacite_totale' => 17000,
                'stock_existant' => 7900,
                'capacite_disponible' => 9100,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Radès',
                'adresse' => 'Route du Bac, Radès 2040',
                'capacite_totale' => 25000,
                'stock_existant' => 9200,
                'capacite_disponible' => 15800,
                'statut' => 'ACTIF',
            ],

            [
                'nom' => 'Entrepôt OCT Sfax',
                'adresse' => 'Route Sidi Mansour Km 3, Sfax 3002',
                'capacite_totale' => 20000,
                'stock_existant' => 8100,
                'capacite_disponible' => 11900,
                'statut' => 'ACTIF',
            ],

        ]);
    }
}