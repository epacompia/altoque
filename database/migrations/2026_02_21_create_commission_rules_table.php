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
        // Tabla de reglas de comisión
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Comisión base", "Bebidas 15%"
            $table->string('type')->default('base'); // base, category, price_range, vendor_type
            $table->decimal('commission_percentage', 5, 2); // 10.00 a 25.00
            $table->string('category_id')->nullable(); // Para tipo categoria
            $table->decimal('min_amount', 10, 2)->nullable(); // Para rango de precio
            $table->decimal('max_amount', 10, 2)->nullable(); // Para rango de precio
            $table->string('vendor_type')->nullable(); // normal, premium, etc
            $table->date('valid_from')->nullable(); // Fecha de inicio de vigencia
            $table->date('valid_until')->nullable(); // Fecha de fin de vigencia
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable(); // Usuario que creó la regla
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
            $table->index('valid_from');
        });

        // Tabla de auditoría de cambios en comisiones
        Schema::create('commission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules')->onDelete('cascade');
            $table->string('action'); // created, updated, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('changed_by'); // Usuario que hizo el cambio
            $table->string('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('commission_rule_id');
            $table->index('created_at');
        });

        // Tabla de comisiones aplicadas por pedido
        Schema::create('order_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->onDelete('restrict');
            $table->decimal('order_total', 10, 2); // Total del pedido
            $table->decimal('commission_percentage', 5, 2); // Porcentaje aplicado
            $table->decimal('commission_amount', 10, 2); // Monto calculado
            $table->decimal('net_amount', 10, 2); // Monto para el vendedor (total - comisión)
            $table->string('status')->default('pending'); // pending, completed, refunded
            $table->string('rule_applied')->nullable(); // Descripción de qué regla se aplicó
            $table->timestamp('calculated_at'); // Cuándo se calculó
            $table->timestamp('transferred_at')->nullable(); // Cuándo se transfirió al vendedor
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_commissions');
        Schema::dropIfExists('commission_audit_logs');
        Schema::dropIfExists('commission_rules');
    }
};
