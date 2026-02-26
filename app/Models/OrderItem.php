<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'subtotal',
        'toppings'
    ];

    protected $casts = [
        'toppings' => 'array',
    ];

    /**
     * Relación: Un item pertenece a una orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relación: Un item es un producto
     */
    public function product()
    {
        return $this->belongsTo(MenuItem::class, 'product_id');
    }
}
