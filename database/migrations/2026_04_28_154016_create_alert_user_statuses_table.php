<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_user_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alert_id')
                ->constrained('alerts')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status')->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->unique(['alert_id', 'user_id']);

            $table->index(['user_id', 'status']);
            $table->index(['alert_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_user_statuses');
    }
};