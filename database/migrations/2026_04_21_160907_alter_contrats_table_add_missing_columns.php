<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            if (!Schema::hasColumn('contrats', 'objet')) {
                $table->text('objet')->nullable()->after('numero_contrat');
            }

            if (!Schema::hasColumn('contrats', 'date_signature')) {
                $table->dateTime('date_signature')->nullable()->after('objet');
            }

            if (!Schema::hasColumn('contrats', 'date_debut')) {
                $table->dateTime('date_debut')->nullable()->after('date_signature');
            }

            if (!Schema::hasColumn('contrats', 'date_fin')) {
                $table->dateTime('date_fin')->nullable()->after('date_debut');
            }

            if (!Schema::hasColumn('contrats', 'quantite_contractuelle')) {
                $table->decimal('quantite_contractuelle', 15, 3)->default(0)->after('date_fin');
            }

            if (!Schema::hasColumn('contrats', 'quantite_realisee')) {
                $table->decimal('quantite_realisee', 15, 3)->default(0)->after('quantite_contractuelle');
            }

            if (!Schema::hasColumn('contrats', 'taux_depassement_autorise')) {
                $table->decimal('taux_depassement_autorise', 10, 4)->default(0.2)->after('quantite_realisee');
            }

            if (!Schema::hasColumn('contrats', 'montant_ht')) {
                $table->decimal('montant_ht', 15, 3)->nullable()->after('taux_depassement_autorise');
            }

            if (!Schema::hasColumn('contrats', 'montant_tva')) {
                $table->decimal('montant_tva', 15, 3)->default(0)->after('montant_ht');
            }

            if (!Schema::hasColumn('contrats', 'taux_cautionnement')) {
                $table->decimal('taux_cautionnement', 10, 4)->default(3)->after('montant_tva');
            }

            if (!Schema::hasColumn('contrats', 'taux_penalite_retard')) {
                $table->decimal('taux_penalite_retard', 10, 4)->default(0.002)->after('taux_cautionnement');
            }

            if (!Schema::hasColumn('contrats', 'plafond_penalite')) {
                $table->decimal('plafond_penalite', 10, 4)->default(5)->after('taux_penalite_retard');
            }

            if (!Schema::hasColumn('contrats', 'prix_unitaire')) {
                $table->decimal('prix_unitaire', 15, 3)->nullable()->after('plafond_penalite');
            }

            if (!Schema::hasColumn('contrats', 'statut')) {
                $table->string('statut', 50)->default('ACTIF')->after('prix_unitaire');
            }

            if (!Schema::hasColumn('contrats', 'fournisseur_id')) {
                $table->unsignedBigInteger('fournisseur_id')->nullable()->after('statut');
            }

            if (!Schema::hasColumn('contrats', 'emballage_id')) {
                $table->unsignedBigInteger('emballage_id')->nullable()->after('fournisseur_id');
            }
        });

        if (Schema::hasColumn('contrats', 'fournisseur_id')) {
            try {
                Schema::table('contrats', function (Blueprint $table) {
                    $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasColumn('contrats', 'emballage_id')) {
            try {
                Schema::table('contrats', function (Blueprint $table) {
                    $table->foreign('emballage_id')->references('id')->on('emballages')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            try {
                $table->dropForeign(['fournisseur_id']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropForeign(['emballage_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('contrats', function (Blueprint $table) {
            $columns = [
                'objet',
                'date_signature',
                'date_debut',
                'date_fin',
                'quantite_contractuelle',
                'quantite_realisee',
                'taux_depassement_autorise',
                'montant_ht',
                'montant_tva',
                'taux_cautionnement',
                'taux_penalite_retard',
                'plafond_penalite',
                'prix_unitaire',
                'statut',
                'fournisseur_id',
                'emballage_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contrats', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};