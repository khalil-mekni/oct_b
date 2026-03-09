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
    $table->enum('statut', ['BROUILLON','VALIDE','ANNULE','LIVRE'])->default('BROUILLON');
    $table->foreignId('emballage_id')->nullable()->constrained('emballages');
    $table->decimal('quantite', 15,2)->nullable();
    $table->foreignId('fournisseur_id')->constrained();
    $table->foreignId('contrat_id')->nullable()->constrained();
    $table->foreignId('entrepot_id')->constrained();
    $table->foreignId('created_by')->constrained('users');
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
