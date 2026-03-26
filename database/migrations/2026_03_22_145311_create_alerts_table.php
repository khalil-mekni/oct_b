<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
    $table->id();

    $table->string('type', 100);
    $table->string('title');
    $table->text('message');

    $table->string('severity', 20); // au lieu de 191
    $table->string('status', 20)->default('unread');

    $table->string('entity_type', 100)->nullable(); // lot, contract, supplier, warehouse...
    $table->unsignedBigInteger('entity_id')->nullable();

    $table->string('action_url')->nullable();
    $table->json('metadata')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamp('read_at')->nullable();
    $table->timestamp('archived_at')->nullable();

    $table->timestamps();

    $table->index(['type', 'entity_type', 'entity_id', 'is_active']);
    $table->index(['status', 'severity']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
