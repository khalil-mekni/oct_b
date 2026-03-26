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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();

            $table->string('numero_commande')->unique();
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();

            $table->enum('statut', ['EN_ATTENTE','VALIDEE','EN_ATTENTE_BL','RECEPTIONNEE','ANNULEE'])
                  ->default('EN_ATTENTE');

            $table->foreignId('emballage_id')
                  ->constrained('emballages')
                  ->cascadeOnDelete();

            $table->decimal('quantite', 15, 2)->nullable();

            $table->foreignId('fournisseur_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('contrat_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('entrepot_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};