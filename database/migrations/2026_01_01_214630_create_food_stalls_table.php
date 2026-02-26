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
         Schema::create('food_stalls', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('seller_id')->nullable(); // FK a users si aplica
        $table->string('slug')->unique()->nullable();
        $table->string('qr_path')->nullable(); // ruta del PNG del QR en storage
        $table->boolean('active')->default(true);
        $table->timestamps();
        // $table->foreign('seller_id')->references('id')->on('users')->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_stalls');
    }
};
