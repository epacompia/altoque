<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'commission_percentage',
        'category_id',
        'min_amount',
        'max_amount',
        'vendor_type',
        'valid_from',
        'valid_until',
        'is_active',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'commission_percentage' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    /**
     * Relación con OrderCommission
     */
    public function orderCommissions()
    {
        return $this->hasMany(OrderCommission::class);
    }

    /**
     * Obtener todas las reglas activas
     */
    public static function getActiveRules()
    {
        return self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->orderBy('type')
            ->get();
    }

    /**
     * Obtener la regla por defecto (comisión base)
     */
    public static function getBaseRule()
    {
        return self::where('type', 'base')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->first() ?? self::createDefaultRule();
    }

    /**
     * Crear regla por defecto si no existe
     */
    public static function createDefaultRule()
    {
        return self::create([
            'name' => 'Comisión Base',
            'type' => 'base',
            'commission_percentage' => 10.00,
            'is_active' => true,
            'created_by' => 'SYSTEM',
            'notes' => 'Comisión base del sistema'
        ]);
    }

    /**
     * Validar que el porcentaje esté en el rango permitido
     */
    public function isValidPercentage(): bool
    {
        return $this->commission_percentage >= 10 && $this->commission_percentage <= 25;
    }
}
