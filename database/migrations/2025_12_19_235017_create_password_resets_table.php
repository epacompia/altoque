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
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();           // Número de celular del usuario
            $table->string('otp');                      // Código OTP generado
            $table->timestamp('expires_at');            // Fecha y hora de expiración del código OTP
            $table->timestamps();                               // Para seguimiento de creación/modificación del registro
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
