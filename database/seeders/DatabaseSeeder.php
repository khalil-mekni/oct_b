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
        'first_name' => 'Admin',
        'last_name' => 'User',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
        'phone' => null,
        'birth_date' => null,
        'address' => null,
        'is_active' => true,
        'email_verified_at' => now(),
    ]
);

        // Appel des seeders métier (ordre important)
        $this->call([
            EntrepotSeeder::class,
            FournisseurSeeder::class,
            EmballageSeeder::class,
            ContratSeeder::class,
            StockSeeder::class,            
            //LotSeeder::class,

            //StockInventaireSeeder::class,
        ]);
    }
}