<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
    $table->string('numero_lot', 20)->change();
});
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropUnique(['numero_lot']);
        });
    }
};