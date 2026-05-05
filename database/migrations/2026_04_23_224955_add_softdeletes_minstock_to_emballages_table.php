<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emballages', function (Blueprint $table) {
            $table->softDeletes(); // ← ajoute deleted_at
            $table->integer('min_stock')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('emballages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('min_stock');
        });
    }
};