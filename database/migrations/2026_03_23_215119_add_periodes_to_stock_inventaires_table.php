<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_inventaires', function (Blueprint $table) {
            $table->dateTime('periode_debut')->nullable()->after('date_inventaire');
            $table->dateTime('periode_fin')->nullable()->after('periode_debut');
            
$table->index(
    ['entrepot_id', 'emballage_id', 'periode_debut', 'periode_fin'],
    'stk_inv_ent_emp_per_idx'
);
        });
    }

    public function down(): void
    {
        Schema::table('stock_inventaires', function (Blueprint $table) {
            $table->dropIndex(['entrepot_id', 'emballage_id', 'periode_debut', 'periode_fin']);
            $table->dropColumn(['periode_debut', 'periode_fin']);
        });
    }
};

