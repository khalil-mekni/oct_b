<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users 
            MODIFY role ENUM(
                'PENDING',
                'ADMIN',
                'RESPONSABLE_APPROVISIONNEMENT',
                'RESPONSABLE_STOCKAGE',
                'USER'
            ) NOT NULL DEFAULT 'PENDING'
        ");

        DB::table('users')
            ->where('role', 'USER')
            ->update(['role' => 'PENDING']);
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users 
            MODIFY role ENUM(
                'USER',
                'ADMIN',
                'RESPONSABLE_APPROVISIONNEMENT',
                'RESPONSABLE_STOCKAGE'
            ) NOT NULL DEFAULT 'USER'
        ");
    }
};