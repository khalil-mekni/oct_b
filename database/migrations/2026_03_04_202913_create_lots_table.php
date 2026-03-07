<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
    $table->unique('numero_lot');


            // champs Lot (selon ton diagramme)
$table->string('numero_lot', 191)->unique();
            $table->date('date_production')->nullable();

            // optionnel mais utile : expiration
            $table->date('date_expiration')->nullable();

            // si tu veux garder une quantité globale dans lot (optionnel)
            $table->decimal('quantite', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['date_expiration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};