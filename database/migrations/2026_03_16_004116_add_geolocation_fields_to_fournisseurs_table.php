<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('adresse');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('adresse_geocodee')->nullable()->after('longitude');
            $table->timestamp('geocoded_at')->nullable()->after('adresse_geocodee');
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'adresse_geocodee',
                'geocoded_at',
            ]);
        });
    }
};