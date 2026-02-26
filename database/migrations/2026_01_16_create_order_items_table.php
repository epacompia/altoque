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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('menu_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2); // Precio al momento de compra
            $table->decimal('subtotal', 10, 2); // quantity * price_per_unit
            $table->json('toppings')->nullable(); // Cremas seleccionadas (JSON)
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
