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
    Schema::create('fournisseurs', function (Blueprint $table) {
    $table->id();
    $table->string('raison_sociale');
    $table->string('matricule_fiscale')->unique();
    $table->string('telephone')->nullable();
    $table->string('adresse')->nullable();
    $table->enum('statut', ['ACTIF', 'INACTIF'])->default('ACTIF');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
