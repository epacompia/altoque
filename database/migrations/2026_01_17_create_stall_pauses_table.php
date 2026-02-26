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
        Schema::create('stall_pauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('food_stalls')->onDelete('cascade');
            $table->string('reason')->nullable(); // Razón de la pausa
            $table->dateTime('start_at'); // Inicio de la pausa
            $table->dateTime('end_at'); // Fin de la pausa
            $table->boolean('is_active')->default(true); // Indicador si está vigente
            $table->timestamps();

            $table->index('stall_id');
            $table->index('start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stall_pauses');
    }
};
