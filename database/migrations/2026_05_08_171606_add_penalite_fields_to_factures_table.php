<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {

            // Montant total des pénalités
            $table->decimal('montant_penalites', 10, 2)
                  ->default(0)
                  ->after('montant_ht');

            // Nombre total de jours de retard
            $table->integer('jours_retard_total')
                  ->default(0)
                  ->after('montant_penalites');

            // Montant HT après pénalités
            $table->decimal('montant_ht_net', 10, 2)
                  ->default(0)
                  ->after('jours_retard_total');

            // Détails du calcul des pénalités
            $table->text('details_calcul_penalite')
                  ->nullable()
                  ->after('montant_ht_net');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {

            $table->dropColumn([
                'montant_penalites',
                'jours_retard_total',
                'montant_ht_net',
                'details_calcul_penalite'
            ]);

        });
    }
};