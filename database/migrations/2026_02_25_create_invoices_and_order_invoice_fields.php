<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add invoice-related fields to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('invoice_requested')->default(false)->after('with_delivery');
            $table->string('invoice_type')->nullable()->after('invoice_requested'); // 'boleta' or 'factura'
            $table->string('customer_document_type')->nullable()->after('invoice_type'); // 'DNI' or 'RUC'
            $table->string('customer_document_number')->nullable()->after('customer_document_type');
            $table->string('customer_name')->nullable()->after('customer_document_number');
        });

        // Create invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('seller_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_document_type')->nullable();
            $table->string('customer_document_number')->nullable();
            $table->enum('type', ['boleta', 'factura'])->default('boleta');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('ose_ticket')->nullable();
            $table->string('ose_hash')->nullable();
            $table->enum('status', ['pending', 'generated', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_requested','invoice_type','customer_document_type','customer_document_number','customer_name']);
        });

        Schema::dropIfExists('invoices');
    }
};
