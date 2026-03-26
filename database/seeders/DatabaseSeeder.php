<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Création utilisateur principal
        User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]
);

        // Appel des seeders métier (ordre important)
        $this->call([
            EntrepotSeeder::class,
            FournisseurSeeder::class,
            EmballageSeeder::class,
            ContratSeeder::class,
            StockSeeder::class,            LotSeeder::class,

            //StockInventaireSeeder::class,
        ]);
    }
}