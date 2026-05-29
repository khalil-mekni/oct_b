<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emballages', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();      // ex: EMB-001
            $table->string('name', 255);               // ex: Sachet 1kg
            $table->string('type', 50);                // ex: SACHET, CARTON

            $table->decimal('capacity_value', 12, 3)->nullable(); // 1.000
            $table->string('capacity_unit', 20)->nullable();      // kg, g, L...

            $table->string('material', 100)->nullable();          // Plastic, Paper...
            $table->enum('statut', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emballages');
    }
};
