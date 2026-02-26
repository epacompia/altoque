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
        Schema::create('menu_item_topping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('topping_id')->constrained('toppings')->onDelete('cascade');
            $table->boolean('required')->default(false); // Si es obligatorio seleccionar una crema
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['menu_item_id', 'topping_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_topping');
    }
};
