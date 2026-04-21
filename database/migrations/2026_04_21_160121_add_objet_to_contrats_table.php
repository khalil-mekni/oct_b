use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            if (!Schema::hasColumn('contrats', 'objet')) {
                $table->text('objet')->nullable();
            }

            if (!Schema::hasColumn('contrats', 'date_signature')) {
                $table->dateTime('date_signature')->nullable();
            }

            if (!Schema::hasColumn('contrats', 'date_debut')) {
                $table->dateTime('date_debut')->nullable();
            }

            if (!Schema::hasColumn('contrats', 'date_fin')) {
                $table->dateTime('date_fin')->nullable();
            }

            if (!Schema::hasColumn('contrats', 'quantite_contractuelle')) {
                $table->decimal('quantite_contractuelle', 15, 3)->default(0);
            }

            if (!Schema::hasColumn('contrats', 'quantite_realisee')) {
                $table->decimal('quantite_realisee', 15, 3)->default(0);
            }

            if (!Schema::hasColumn('contrats', 'taux_depassement_autorise')) {
                $table->decimal('taux_depassement_autorise', 10, 4)->default(0.2);
            }

            if (!Schema::hasColumn('contrats', 'montant_ht')) {
                $table->decimal('montant_ht', 15, 3)->nullable();
            }

            if (!Schema::hasColumn('contrats', 'montant_tva')) {
                $table->decimal('montant_tva', 15, 3)->default(0);
            }

            if (!Schema::hasColumn('contrats', 'taux_cautionnement')) {
                $table->decimal('taux_cautionnement', 10, 4)->default(3);
            }

            if (!Schema::hasColumn('contrats', 'taux_penalite_retard')) {
                $table->decimal('taux_penalite_retard', 10, 4)->default(0.002);
            }

            if (!Schema::hasColumn('contrats', 'plafond_penalite')) {
                $table->decimal('plafond_penalite', 10, 4)->default(5);
            }

            if (!Schema::hasColumn('contrats', 'prix_unitaire')) {
                $table->decimal('prix_unitaire', 15, 3)->nullable();
            }

            if (!Schema::hasColumn('contrats', 'statut')) {
                $table->string('statut')->default('ACTIF');
            }
        });
    }

    public function down(): void
    {
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
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contrats', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};