<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->string('document_bl')->nullable()->after('receptionne_par');
            $table->dateTime('date_validation')->nullable()->after('document_bl');
            $table->unsignedBigInteger('validated_by')->nullable()->after('date_validation');
        });
    }

    public function down(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->dropColumn([
                'document_bl',
                'date_validation',
                'validated_by'
            ]);
        });
    }
};