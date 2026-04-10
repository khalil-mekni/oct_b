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

            // Montants
            $table->decimal('montant_ht', 15, 2);
            $table->decimal('montant_penalites', 15, 3)->default(0);
            $table->integer('jours_retard_total')->default(0);
            $table->decimal('montant_ht_net', 15, 3)->default(0);
            $table->decimal('montant_ttc', 15, 2);

            // Statut
            $table->enum('statut', ['BROUILLON','VALIDE','PAYE'])
                  ->default('BROUILLON');

            // Détails pénalités
            $table->text('details_calcul_penalite')->nullable();

            // Relations
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

            // ⚠️ tu peux garder ou supprimer ça selon ton besoin
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

        // 🔥 Ajout dans bon_livraisons (relation inverse)
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->foreignId('facture_id')
                  ->nullable()
                  ->constrained('factures')
                  ->nullOnDelete();

            $table->boolean('is_factured')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->dropForeign(['facture_id']);
            $table->dropColumn(['facture_id', 'is_factured']);
        });

        Schema::dropIfExists('factures');
    }
};