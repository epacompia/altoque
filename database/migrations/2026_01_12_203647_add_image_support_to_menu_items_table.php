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
        Schema::table('menu_items', function (Blueprint $table) {
            // Mejorar soporte de imágenes si aún no existen
            if (!Schema::hasColumn('menu_items', 'image_path')) {
                $table->string('image_path')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
