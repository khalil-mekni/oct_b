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
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->foreignId('bon_livraison_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('bon_livraisons')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {
            $table->dropForeign(['bon_livraison_id']);
            $table->dropColumn('bon_livraison_id');
        });
    }
};
