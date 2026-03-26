<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrepot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emballage_id')->constrained()->cascadeOnDelete();

            $table->foreignId('lot_id')->nullable()
                ->constrained('lots')->nullOnDelete();

            $table->dateTime('date_stock');

            //$table->decimal('quantite_init', 15, 2)->default(0);
            $table->decimal('quantite', 15, 2)->default(0);
            $table->string('sens', 20); 
            //$table->decimal('quantite_finale', 15, 2)->default(0);

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

           /// $table->unique(['entrepot_id', 'emballage_id', 'lot_id']);

            $table->index(['entrepot_id', 'emballage_id', 'date_stock']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};