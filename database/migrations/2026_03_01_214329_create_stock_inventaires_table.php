<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('stock_inventaires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrepot_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('emballage_id')
                ->constrained('emballages')
                ->cascadeOnDelete();

           

            $table->decimal('stock_theorique', 15, 2)->default(0);
            $table->decimal('stock_physique', 15, 2)->default(0);
            $table->decimal('ecart', 15, 2)->default(0);

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('date_inventaire');

            $table->timestamps();


            $table->unique(['entrepot_id', 'emballage_id', 'date_inventaire'], 'uniq_inv_entrepot_emballage_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventaires');
    }
};