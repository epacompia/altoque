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
        Schema::create('toppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('food_stalls')->onDelete('cascade');
            $table->string('name'); // Queso, Guacamole, Crema, etc
            $table->decimal('price', 8, 2)->default(0); // Precio adicional
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('stall_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toppings');
    }
};
