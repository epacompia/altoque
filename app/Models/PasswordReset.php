<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    // Campos que pueden ser llenados masivamente
    protected $fillable = [
        'phone',
        'otp',
        'expires_at',
    ];

    // Deshabilitar las marcas de tiempo automáticas (porque la tabla no tiene `created_at` ni `updated_at`)
    public $timestamps = false;
}