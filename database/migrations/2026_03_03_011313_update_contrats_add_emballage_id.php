<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            // si colonne déjà existante, ne pas la recréer
            if (!Schema::hasColumn('contrats', 'emballage_id')) {
                $table->unsignedBigInteger('emballage_id')->after('fournisseur_id');
            }

            // FK + index (protégé si déjà présent)
            // MySQL: si tu as des noms auto, garde ces noms fixes
            $table->index('emballage_id', 'contrats_emballage_id_index');

            $table->foreign('emballage_id', 'contrats_emballage_id_foreign')
                ->references('id')->on('emballages')
                ->onDelete('restrict'); // ou cascade selon ton besoin
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            // drop FK / index si existent
            try { $table->dropForeign('contrats_emballage_id_foreign'); } catch (\Throwable $e) {}
            try { $table->dropIndex('contrats_emballage_id_index'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('contrats', 'emballage_id')) {
                $table->dropColumn('emballage_id');
            }
        });
    }
};