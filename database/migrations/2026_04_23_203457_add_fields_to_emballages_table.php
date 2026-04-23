<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emballages', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type');
            $table->decimal('poids', 8, 2)->nullable()->after('capacity_unit');
            $table->decimal('epaisseur_pp', 5, 3)->nullable()->after('poids');
            $table->decimal('epaisseur_ppc', 5, 3)->nullable()->after('epaisseur_pp');
            $table->decimal('largeur', 6, 2)->nullable()->after('epaisseur_ppc');
        });
    }

    public function down(): void
    {
        Schema::table('emballages', function (Blueprint $table) {
            $table->dropColumn(['description', 'poids', 'epaisseur_pp', 'epaisseur_ppc', 'largeur']);
        });
    }
};