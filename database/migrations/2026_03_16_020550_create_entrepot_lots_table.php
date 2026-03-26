<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrepot_lots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrepot_id')
                ->constrained('entrepots')
                ->cascadeOnDelete();

            $table->foreignId('lot_id')
                ->constrained('lots')
                ->cascadeOnDelete();

            $table->foreignId('emballage_id')
                ->constrained('emballages')
                ->cascadeOnDelete();

            $table->decimal('quantite', 15, 3)->default(0);

            $table->timestamps();

            $table->unique(['entrepot_id', 'lot_id'], 'uq_entrepot_lot');
            $table->index(['entrepot_id', 'emballage_id'], 'idx_entrepot_emballage');
            $table->index(['lot_id'], 'idx_lot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrepot_lots');
    }
};