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
        Schema::create('contrats', function (Blueprint $table) {
    $table->id();
    $table->string('numero_contrat')->unique();
    $table->date('date_debut');
    $table->date('date_fin');
    $table->decimal('quantite_contractuelle', 15,2);
    $table->decimal('taux_depassement_autorise', 5,2)->default(0.20);
    $table->decimal('quantite_realisee', 15,2)->default(0);
    $table->decimal('prix', 12,2)->default(0);
    $table->enum('statut', ['ACTIF','EXPIRE','SUSPENDU'])->default('ACTIF');
    $table->foreignId('fournisseur_id')->constrained()->onDelete('cascade');
    //$table->foreignId('emballage_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
