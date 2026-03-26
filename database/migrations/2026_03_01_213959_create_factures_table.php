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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();

            $table->string('numero_facture');
            $table->date('date_facture');

            $table->decimal('montant_ht', 15, 2);
            $table->decimal('montant_ttc', 15, 2);

            $table->enum('statut', ['BROUILLON','VALIDE','PAYE'])
                  ->default('BROUILLON');

            $table->foreignId('emballage_id')
                  ->constrained('emballages')
                  ->cascadeOnDelete();

            $table->decimal('quantite_facturee', 15, 2)->nullable();

            $table->foreignId('fournisseur_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('contrat_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('commande_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('bon_livraison_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('valide_par')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};