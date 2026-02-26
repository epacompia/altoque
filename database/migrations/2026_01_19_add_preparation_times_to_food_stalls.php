<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_stalls', function (Blueprint $table) {
            // Configuración de tiempos de preparación (en minutos)
            $table->integer('base_preparation_time')->default(15)->after('description'); // Tiempo base
            $table->integer('time_per_product')->default(5)->after('base_preparation_time'); // Por cada producto
            $table->integer('time_per_active_order')->default(5)->after('time_per_product'); // Por cada pedido activo
            $table->integer('time_for_delivery')->default(10)->after('time_per_active_order'); // Si hay delivery
        });
    }

    public function down(): void
    {
        Schema::table('food_stalls', function (Blueprint $table) {
            $table->dropColumn([
                'base_preparation_time',
                'time_per_product',
                'time_per_active_order',
                'time_for_delivery'
            ]);
        });
    }
};
