<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['stall_id', 'status'], 'orders_stall_status_idx');
            $table->index(['user_id', 'status'], 'orders_user_status_idx');
        });

        Schema::table('food_stalls', function (Blueprint $table) {
            $table->index('active', 'food_stalls_active_idx');
            $table->index(['latitude', 'longitude'], 'food_stalls_lat_lng_idx');
        });

        Schema::table('stall_pauses', function (Blueprint $table) {
            $table->index(['stall_id', 'is_active', 'start_at', 'end_at'], 'stall_pauses_active_range_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['product_id', 'order_id'], 'order_items_product_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_stall_status_idx');
            $table->dropIndex('orders_user_status_idx');
        });

        Schema::table('food_stalls', function (Blueprint $table) {
            $table->dropIndex('food_stalls_active_idx');
            $table->dropIndex('food_stalls_lat_lng_idx');
        });

        Schema::table('stall_pauses', function (Blueprint $table) {
            $table->dropIndex('stall_pauses_active_range_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_order_idx');
        });
    }
};
