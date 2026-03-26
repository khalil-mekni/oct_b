<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emballages', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();     
            $table->string('name', 255);               
            $table->string('type', 50);               

            $table->decimal('capacity_value', 12, 3)->nullable(); 
            $table->string('capacity_unit', 20)->nullable();      

            $table->string('material', 100)->nullable();          
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emballages');
    }
};
