<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {

            // 1) Rename colonnes entrepôt
            if (Schema::hasColumn('mouvement_stocks', 'entrepot_source')
                && !Schema::hasColumn('mouvement_stocks', 'entrepot_source_id')
            ) {
                $table->renameColumn('entrepot_source', 'entrepot_source_id');
            }

            if (Schema::hasColumn('mouvement_stocks', 'entrepot_destination')
                && !Schema::hasColumn('mouvement_stocks', 'entrepot_destination_id')
            ) {
                $table->renameColumn('entrepot_destination', 'entrepot_destination_id');
            }

            // 2) Add lot_id
            if (!Schema::hasColumn('mouvement_stocks', 'lot_id')) {
                $table->unsignedBigInteger('lot_id')->nullable()->after('emballage_id');
                $table->index('lot_id');
            }

            // 3) Add statut
            if (!Schema::hasColumn('mouvement_stocks', 'statut')) {
                $table->enum('statut', ['BROUILLON', 'VALIDE'])->default('BROUILLON')->after('user_id');
                $table->index('statut');
            }

            // 4) Add date_mouvement
            if (!Schema::hasColumn('mouvement_stocks', 'date_mouvement')) {
                $table->dateTime('date_mouvement')->nullable()->after('quantite');
                $table->index('date_mouvement');
            }

            // Optionnel: code_mouvement unique
            if (!Schema::hasColumn('mouvement_stocks', 'code_mouvement')) {
                $table->string('code_mouvement', 191)->nullable()->after('id');
                $table->unique('code_mouvement');
            }

            // Index dépôts (utile)
            $table->index('entrepot_source_id');
            $table->index('entrepot_destination_id');
        });

        // 5) Modifier ENUM type_mouvement (MySQL: DB::statement)
        DB::statement("ALTER TABLE mouvement_stocks 
            MODIFY type_mouvement ENUM('ENT','PRD','CDD','PTE','SPL') NOT NULL");
    }

    public function down(): void
    {
        // rollback simple (optionnel)
        // DB::statement(\"ALTER TABLE mouvement_stocks MODIFY type_mouvement ENUM('ENTREE','SORTIE','TRANSFERT','AJUSTEMENT','INVENTAIRE') NOT NULL\");
    }
};