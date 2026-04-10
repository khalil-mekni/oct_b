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
        Schema::table('emballages', function (Blueprint $table) {
            // Ajout du seuil critique (min_stock)
            // On le place après la colonne 'type' ou 'description'
            $table->integer('min_stock')->default(0)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emballages', function (Blueprint $table) {
            $table->dropColumn('min_stock');
        });
    }
};