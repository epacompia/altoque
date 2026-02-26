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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('method'); // mock, yape, plin
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->string('transaction_id')->nullable();
            $table->text('response')->nullable(); // JSON de respuesta de gateway
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
