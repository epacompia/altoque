<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stall_id',
        'status',
        'subtotal',
        'igv',
        'delivery_cost',
        'with_delivery',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_distance_km',
        'delivery_provider',
        'total',
        'payment_method',
        'payment_id',
        'delivery_address',
        'client_notes',
        'estimated_delivery_at',
        'delivered_at'
    ];

    protected $casts = [
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'delivery_distance_km' => 'decimal:2',
    ];

    /**
     * Relación: Una orden pertenece a un cliente
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una orden pertenece a un puesto
     */
    public function stall()
    {
        return $this->belongsTo(FoodStall::class, 'stall_id');
    }

    /**
     * Relación: Una orden tiene muchos items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Relación: Una orden tiene un pago
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
}
