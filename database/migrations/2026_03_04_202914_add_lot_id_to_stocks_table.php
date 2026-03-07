<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('lot_id')
                ->nullable() // si tu veux autoriser stock sans lot (pas recommandé si stock par lot)
                ->after('article_ref')
                ->constrained('lots')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['entrepot_id', 'article_ref', 'lot_id'], 'idx_stocks_entrepot_article_lot');
            // recommandé si tu veux empêcher doublons
            // $table->unique(['entrepot_id', 'article_ref', 'lot_id'], 'uq_stocks_entrepot_article_lot');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_entrepot_article_lot');
            // $table->dropUnique('uq_stocks_entrepot_article_lot');
            $table->dropConstrainedForeignId('lot_id');
        });
    }
};