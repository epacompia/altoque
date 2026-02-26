<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->dropColumn('category'); // elimina la columna string previa
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->dropConstrainedForeignId('category_id');
        });
    }
};