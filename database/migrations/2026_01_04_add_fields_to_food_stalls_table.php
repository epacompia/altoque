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
        Schema::table('food_stalls', function (Blueprint $table) {
            // Agregar campos faltantes solo si no existen
            if (!Schema::hasColumn('food_stalls', 'address')) {
                $table->string('address')->nullable()->after('name');
            }
            if (!Schema::hasColumn('food_stalls', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
            if (!Schema::hasColumn('food_stalls', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('food_stalls', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
            if (!Schema::hasColumn('food_stalls', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('closing_time');
            }
            if (!Schema::hasColumn('food_stalls', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('food_stalls', 'description')) {
                $table->text('description')->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_stalls', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'phone',
                'opening_time',
                'closing_time',
                'latitude',
                'longitude',
                'description'
            ]);
        });
    }
};
