<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();

            // 🧾 Infos générales
            $table->string('numero_contrat')->unique();
            $table->text('objet')->nullable();
            $table->date('date_signature')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');

            // 📦 Quantités
            $table->decimal('quantite_contractuelle', 15, 2);
            $table->decimal('quantite_realisee', 15, 2)->default(0);
            $table->decimal('taux_depassement_autorise', 5, 2)->default(20.00); // %

            // 💰 Financier (minimal)
            $table->decimal('montant_ht', 15, 3)->nullable();
            $table->decimal('montant_tva', 15, 3)->default(0.000);
            $table->decimal('taux_cautionnement', 5, 2)->default(3.00); // %

            // ⚠️ Pénalités
            $table->decimal('taux_penalite_retard', 5, 4)->default(0.0020); // 2‰
            $table->decimal('plafond_penalite', 5, 2)->default(5.00); // %

            // 🔗 Relations
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emballage_id')->constrained()->restrictOnDelete();

            // 🔄 Statut
            $table->enum('statut', ['ACTIF', 'EXPIRE', 'SUSPENDU'])->default('ACTIF');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};