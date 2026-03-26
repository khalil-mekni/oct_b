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
        Schema::create('mouvement_stocks', function (Blueprint $table) {
            $table->id();

            $table->enum('type_mouvement', [
                'ENTREE',
                'SORTIE',
                'TRANSFERT',
                'AJUSTEMENT',
                'INVENTAIRE'
            ]);

            // 🔹 Remplacement de article_ref par emballage_id
            $table->foreignId('emballage_id')
                  ->constrained('emballages')
                  ->cascadeOnDelete();

            $table->decimal('quantite', 15, 2);

            $table->foreignId('entrepot_source')
                  ->nullable()
                  ->constrained('entrepots')
                  ->nullOnDelete();

            $table->foreignId('entrepot_destination')
                  ->nullable()
                  ->constrained('entrepots')
                  ->nullOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvement_stocks');
    }
};