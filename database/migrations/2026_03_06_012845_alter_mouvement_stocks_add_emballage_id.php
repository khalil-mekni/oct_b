<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {

            $table->unsignedBigInteger('emballage_id')->after('type_mouvement');

            $table->foreign('emballage_id')
                  ->references('id')
                  ->on('emballages');

            $table->dropColumn('article_ref');

        });
    }

    public function down(): void
    {
        Schema::table('mouvement_stocks', function (Blueprint $table) {

            $table->dropForeign(['emballage_id']);
            $table->dropColumn('emballage_id');

            $table->string('article_ref');
        });
    }
};
