<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_items', 'active')) {
                $table->boolean('active')->default(true)->after('category_id');
            }
            if (!Schema::hasColumn('menu_items', 'featured')) {
                $table->boolean('featured')->default(false)->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['active', 'featured']);
        });
    }
};
