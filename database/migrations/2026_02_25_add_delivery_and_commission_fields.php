<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'with_delivery')) {
                $table->boolean('with_delivery')->default(false)->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
            }
            if (!Schema::hasColumn('orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 6, 2)->nullable()->after('delivery_longitude');
            }
            if (!Schema::hasColumn('orders', 'delivery_provider')) {
                $table->string('delivery_provider')->nullable()->after('delivery_distance_km');
            }
        });

        Schema::table('food_stalls', function (Blueprint $table) {
            if (!Schema::hasColumn('food_stalls', 'delivery_rate_per_km')) {
                $table->decimal('delivery_rate_per_km', 6, 2)->default(3.00)->after('time_for_delivery');
            }
            if (!Schema::hasColumn('food_stalls', 'delivery_min_cost')) {
                $table->decimal('delivery_min_cost', 6, 2)->default(3.00)->after('delivery_rate_per_km');
            }
        });

        Schema::table('order_commissions', function (Blueprint $table) {
            if (!Schema::hasColumn('order_commissions', 'transfer_method')) {
                $table->string('transfer_method')->nullable()->after('transferred_at');
            }
            if (!Schema::hasColumn('order_commissions', 'transfer_transaction_id')) {
                $table->string('transfer_transaction_id')->nullable()->after('transfer_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['with_delivery', 'delivery_latitude', 'delivery_longitude', 'delivery_distance_km', 'delivery_provider']);
        });
        Schema::table('food_stalls', function (Blueprint $table) {
            $table->dropColumn(['delivery_rate_per_km', 'delivery_min_cost']);
        });
        Schema::table('order_commissions', function (Blueprint $table) {
            $table->dropColumn(['transfer_method', 'transfer_transaction_id']);
        });
    }
};
