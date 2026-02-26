<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodStall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'seller_id',
        'slug',
        'qr_path',
        'active',
        'address',
        'phone',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'description'
    ];

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'stall_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Relación: Un puesto tiene muchas pausas
     */
    public function pauses()
    {
        return $this->hasMany(StallPause::class, 'stall_id');
    }

    /**
     * Verificar si el puesto está abierto ahora
     */
    public function isOpenNow()
    {
        $now = now()->format('H:i');
        $opening = $this->opening_time;
        $closing = $this->closing_time;

        // Verificar si está dentro del horario
        if ($now < $opening || $now >= $closing) {
            return false;
        }

        // Verificar si hay pausas activas
        if ($this->pauses()->active()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si el puesto está en pausa ahora
     */
    public function isInPauseNow()
    {
        return $this->pauses()->active()->exists();
    }
}