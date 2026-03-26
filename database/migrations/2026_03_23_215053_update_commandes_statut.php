<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  public function up()
{
    DB::table('commandes')->update(['statut' => 'EN_ATTENTE']);

    Schema::table('commandes', function (Blueprint $table) {
        $table->enum('statut', [
            'EN_ATTENTE',
            'VALIDEE',
            'PARTIELLEMENT_RECEPTIONNEE',
            'RECEPTIONNEE',
            'ANNULEE'
        ])->default('EN_ATTENTE')->change();
    });
}

public function down()
{
    DB::statement("ALTER TABLE commandes MODIFY statut VARCHAR(255)");
}
};
