<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Agregar timestamps para cada estado (delivered_at ya existe)
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            $table->timestamp('ready_at')->nullable()->after('preparing_at');
            $table->timestamp('en_camino_at')->nullable()->after('ready_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'confirmed_at',
                'preparing_at',
                'ready_at',
                'en_camino_at'
            ]);
        });
    }
};
