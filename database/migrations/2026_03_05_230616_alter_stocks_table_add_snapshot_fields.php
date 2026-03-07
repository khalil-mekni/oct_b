<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $res = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$dbName, $table, $indexName]);

        return (bool) $res;
    }

    private function columnExists(string $table, string $column): bool
    {
        $dbName = DB::getDatabaseName();

        $res = DB::selectOne("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ", [$dbName, $table, $column]);

        return (bool) $res;
    }

    public function up(): void
    {
        /**
         * 0) Drop index qui contient article_ref (si existe)
         * (d'après ton SHOW INDEX, l'index s'appelle idx_stocks_entrepot_article_lot)
         */
        if ($this->indexExists('stocks', 'idx_stocks_entrepot_article_lot')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex('idx_stocks_entrepot_article_lot');
            });
        }

        /**
         * 1) Ajouter les colonnes snapshot + emballage_id (en NULLABLE au début)
         * => évite l'erreur "0000-00-00 00:00:00" et permet migration progressive
         */
        Schema::table('stocks', function (Blueprint $table) {

            if (!Schema::hasColumn('stocks', 'emballage_id')) {
                // nullable au début car on va tenter de le remplir depuis article_ref
                $table->unsignedBigInteger('emballage_id')->nullable()->after('entrepot_id');
            }

            if (!Schema::hasColumn('stocks', 'date_stock')) {
                // nullable au début, on remplira ensuite
                $table->dateTime('date_stock')->nullable()->after('lot_id');
            }

            if (!Schema::hasColumn('stocks', 'quantite_init')) {
                $table->decimal('quantite_init', 15, 2)->default(0)->after('date_stock');
            }
            if (!Schema::hasColumn('stocks', 'quantite_entree')) {
                $table->decimal('quantite_entree', 15, 2)->default(0)->after('quantite_init');
            }
            if (!Schema::hasColumn('stocks', 'quantite_sortie')) {
                $table->decimal('quantite_sortie', 15, 2)->default(0)->after('quantite_entree');
            }
            if (!Schema::hasColumn('stocks', 'quantite_finale')) {
                $table->decimal('quantite_finale', 15, 2)->default(0)->after('quantite_sortie');
            }

            if (!Schema::hasColumn('stocks', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('quantite_finale');
            }
        });

        /**
         * 2) Backfill des lignes existantes (très important)
         * - date_stock : on met created_at si existe sinon NOW()
         * - quantite_init et quantite_finale : on copie quantite_actuelle (ton champ existant)
         */
        if ($this->columnExists('stocks', 'date_stock')) {
            // remplir date_stock si null
            DB::statement("
                UPDATE stocks
                SET date_stock = COALESCE(created_at, NOW())
                WHERE date_stock IS NULL
            ");
        }

        if ($this->columnExists('stocks', 'quantite_actuelle')) {
            DB::statement("
                UPDATE stocks
                SET quantite_init = COALESCE(quantite_init, quantite_actuelle),
                    quantite_finale = COALESCE(quantite_finale, quantite_actuelle)
            ");
        }

        /**
         * 3) Tenter de remplir emballage_id depuis article_ref
         * ✅ si article_ref correspond à emballages.code
         */
        if ($this->columnExists('stocks', 'article_ref') && $this->columnExists('emballages', 'code')) {
            // Update JOIN MySQL
            DB::statement("
                UPDATE stocks s
                JOIN emballages e ON e.code = s.article_ref
                SET s.emballage_id = e.id
                WHERE s.emballage_id IS NULL
            ");
        }

        /**
         * 4) Ajouter les Foreign Keys (uniquement si possible)
         * - emballage_id : uniquement si toutes les lignes ont une valeur (sinon FK peut bloquer)
         * - user_id : FK nullable OK
         */
        $missingEmballage = DB::selectOne("SELECT COUNT(*) as c FROM stocks WHERE emballage_id IS NULL");
        if ($missingEmballage && (int)$missingEmballage->c === 0) {
            // ajouter FK seulement si tout est rempli
            if (!$this->indexExists('stocks', 'stocks_emballage_id_foreign')) {
                Schema::table('stocks', function (Blueprint $table) {
                    $table->foreign('emballage_id')->references('id')->on('emballages');
                });
            }

            // rendre emballage_id NOT NULL (optionnel, conseillé)
            Schema::table('stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('emballage_id')->nullable(false)->change();
            });
        }

        // FK user_id (si table users existe)
        if (Schema::hasTable('users') && !$this->indexExists('stocks', 'stocks_user_id_foreign')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        /**
         * 5) Rendre date_stock NOT NULL maintenant qu'on l'a remplie
         */
        Schema::table('stocks', function (Blueprint $table) {
            $table->dateTime('date_stock')->nullable(false)->change();
        });

        /**
         * 6) Ajouter les index de performance (snapshot + lookup)
         * Important: unique snapshot (entrepot, emballage, lot, date_stock)
         * Attention: lot_id est nullable, MySQL permet plusieurs NULL dans un UNIQUE (à connaître).
         */
        if (!$this->indexExists('stocks', 'stocks_unique_snapshot')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->unique(['entrepot_id', 'emballage_id', 'lot_id', 'date_stock'], 'stocks_unique_snapshot');
            });
        }

        if (!$this->indexExists('stocks', 'stocks_fast_lookup')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->index(['entrepot_id', 'emballage_id', 'lot_id'], 'stocks_fast_lookup');
            });
        }

        /**
         * 7) Optionnel : supprimer article_ref quand tu es sûr que tout est migré
         * ⚠️ Je te conseille de le garder pour l’instant si tu n’es pas sûr du mapping.
         *
         * Quand tu confirmes que emballage_id est rempli à 100%, tu peux décommenter :
         */
        /*
        $missingEmballage2 = DB::selectOne("SELECT COUNT(*) as c FROM stocks WHERE emballage_id IS NULL");
        if ($missingEmballage2 && (int)$missingEmballage2->c === 0 && Schema::hasColumn('stocks', 'article_ref')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('article_ref');
            });
        }
        */
    }

    public function down(): void
    {
        // retirer index
        if ($this->indexExists('stocks', 'stocks_unique_snapshot')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique('stocks_unique_snapshot');
            });
        }
        if ($this->indexExists('stocks', 'stocks_fast_lookup')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex('stocks_fast_lookup');
            });
        }

        // retirer FK
        // (si elles existent, MySQL utilise des noms standards; on try/catch)
        try { DB::statement("ALTER TABLE stocks DROP FOREIGN KEY stocks_emballage_id_foreign"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE stocks DROP FOREIGN KEY stocks_user_id_foreign"); } catch (\Throwable $e) {}

        // retirer colonnes ajoutées
        Schema::table('stocks', function (Blueprint $table) {
            if (Schema::hasColumn('stocks', 'user_id')) $table->dropColumn('user_id');
            if (Schema::hasColumn('stocks', 'quantite_finale')) $table->dropColumn('quantite_finale');
            if (Schema::hasColumn('stocks', 'quantite_sortie')) $table->dropColumn('quantite_sortie');
            if (Schema::hasColumn('stocks', 'quantite_entree')) $table->dropColumn('quantite_entree');
            if (Schema::hasColumn('stocks', 'quantite_init')) $table->dropColumn('quantite_init');
            if (Schema::hasColumn('stocks', 'date_stock')) $table->dropColumn('date_stock');

            if (Schema::hasColumn('stocks', 'emballage_id')) $table->dropColumn('emballage_id');
        });

        // NOTE: on ne recrée pas article_ref ici car tu l'as déjà dans ta table actuelle
        // et on n'a pas forcé sa suppression dans up().
    }
};