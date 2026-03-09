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
        Schema::create('bon_livraisons', function (Blueprint $table) {
    $table->id();
    $table->string('numero_bl')->unique();
    $table->date('date_reception');
    $table->enum('statut', ['EN_ATTENTE','VALIDE'])->default('EN_ATTENTE');
    $table->foreignId('emballage_id')->nullable()->constrained('emballages');
    $table->decimal('quantite_recue', 15,2)->nullable();
    $table->foreignId('commande_id')->constrained();
    $table->string('numero_commande');
    $table->foreignId('entrepot_id')->constrained();
    $table->foreignId('receptionne_par')->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_livraisons');
    }
};
