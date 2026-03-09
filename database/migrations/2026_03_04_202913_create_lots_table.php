<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('numero_lot', 191)->unique();
            $table->date('date_production')->nullable();
            $table->date('date_expiration')->nullable();
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