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
        Schema::create('bon_livraisons', function (Blueprint $table) {
            $table->id();

            $table->string('numero_bl')->unique();
            $table->date('date_reception');
            $table->string('numero_commande');

            $table->enum('statut', ['EN_ATTENTE','VALIDE'])
                  ->default('EN_ATTENTE');

            $table->foreignId('emballage_id')
                  ->constrained('emballages')
                  ->cascadeOnDelete();

            $table->decimal('quantite_recue', 15, 2)->nullable();

            $table->foreignId('commande_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('entrepot_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('receptionne_par')
                  ->constrained('users')
                  ->cascadeOnDelete();


                  $table->string('document_bl')->nullable();


                  $table->timestamp('date_validation')->nullable();


                  $table->foreignId('validated_by')
                     ->nullable()
                     ->constrained('users')
                   ->nullOnDelete();
            
                   $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_livraisons');
    }
};