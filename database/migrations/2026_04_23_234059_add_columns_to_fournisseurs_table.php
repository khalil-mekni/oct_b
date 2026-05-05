<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('email')->nullable()->after('telephone');
            $table->string('representant_nom')->nullable()->after('adresse');
            $table->string('representant_role')->nullable()->after('representant_nom');
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['email', 'representant_nom', 'representant_role']);
        });
    }
};