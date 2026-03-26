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
        Schema::create('entrepots', function (Blueprint $table) {
    $table->id();    
    $table->string('nom');

    $table->string('adresse');
    $table->decimal('capacite_totale', 15,2)->nullable();
    $table->decimal('capacite_disponible', 15,2)->nullable();
    $table->enum('statut', ['ACTIF', 'INACTIF'])->default('ACTIF');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrepots');
    }
};
