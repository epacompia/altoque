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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Cliente
            $table->foreignId('stall_id')->constrained('food_stalls')->onDelete('cascade'); // Puesto
            $table->string('status')->default('pending'); // pending, confirmed, preparing, ready, delivered, cancelled
            $table->decimal('subtotal', 10, 2); // Total sin IGV ni delivery
            $table->decimal('igv', 10, 2)->default(0); // IGV 18%
            $table->decimal('delivery_cost', 10, 2)->default(0); // Costo delivery
            $table->decimal('total', 10, 2); // Total final
            $table->string('payment_method')->nullable(); // mock, yape, plin
            $table->string('payment_id')->nullable(); // ID de transacción
            $table->string('delivery_address')->nullable(); // Dirección de entrega
            $table->string('client_notes')->nullable(); // Notas del cliente
            $table->datetime('estimated_delivery_at')->nullable(); // Hora estimada entrega
            $table->datetime('delivered_at')->nullable(); // Hora real entrega
            $table->timestamps();

            $table->index('user_id');
            $table->index('stall_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
