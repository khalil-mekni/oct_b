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
            $table->text('description')->nullable();  

            $table->decimal('capacity_value', 12, 3)->nullable(); 
            $table->string('capacity_unit', 20)->nullable();     

            $table->decimal('poids', 8, 2)->nullable();           
            $table->decimal('epaisseur_pp', 5, 3)->nullable();    
            $table->decimal('epaisseur_ppc', 5, 3)->nullable();   
            $table->decimal('largeur', 6, 2)->nullable();       

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