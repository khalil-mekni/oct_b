<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrepots', function (Blueprint $table) {

            $table->decimal('stock_existant', 15, 2)
                ->default(0)
                ->after('capacite_totale');

        });

        /*
        initialisation des valeurs existantes
        */

        DB::statement("
            UPDATE entrepots
            SET stock_existant = 0,
                capacite_disponible = capacite_totale
        ");
    }

    public function down(): void
    {
        Schema::table('entrepots', function (Blueprint $table) {

            $table->dropColumn('stock_existant');

        });
    }
};