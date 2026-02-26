<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StallPause extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_id',
        'reason',
        'start_at',
        'end_at',
        'is_active'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relación: Una pausa pertenece a un puesto
     */
    public function stall()
    {
        return $this->belongsTo(FoodStall::class, 'stall_id');
    }

    /**
     * Scope para pausas activas en el presente
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('start_at', '<=', now())
                     ->where('end_at', '>', now());
    }
}
