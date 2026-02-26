<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_account_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // credit, debit
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->string('description')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('reference')->nullable(); // tx id or note
            $table->timestamps();
            $table->index('order_id');
            $table->index('created_at');
        });

        Schema::create('transfer_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_commission_id')->constrained('order_commissions')->onDelete('cascade');
            $table->string('method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->text('response')->nullable();
            $table->integer('attempt')->default(0);
            $table->timestamps();
            $table->index('order_commission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_attempts');
        Schema::dropIfExists('company_account_entries');
    }
};
