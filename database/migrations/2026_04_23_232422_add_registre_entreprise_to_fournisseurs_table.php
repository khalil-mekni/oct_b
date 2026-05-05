<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('fournisseurs', function (Blueprint $table) {
        $table->string('registre_entreprise')->nullable()->after('matricule_fiscale');
    });
}

public function down()
{
    Schema::table('fournisseurs', function (Blueprint $table) {
        $table->dropColumn('registre_entreprise');
    });
}
};
