<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'commission_rule_id',
        'order_total',
        'commission_percentage',
        'commission_amount',
        'net_amount',
        'status',
        'rule_applied',
        'calculated_at',
        'transferred_at',
        'transfer_method',
        'transfer_transaction_id'
    ];

    protected $casts = [
        'order_total' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    /**
     * Relación: Una comisión pertenece a un pedido
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación: Una comisión se calcula por una regla
     */
    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class);
    }

    /**
     * Obtener comisiones por rango de fechas
     */
    public static function getByDateRange($from, $to)
    {
        return self::whereBetween('calculated_at', [$from, $to])
            ->where('status', 'completed')
            ->get();
    }

    /**
     * Obtener comisiones pendientes de transferencia
     */
    public static function getPending()
    {
        return self::where('status', 'pending')
            ->whereNotNull('calculated_at')
            ->get();
    }
}
