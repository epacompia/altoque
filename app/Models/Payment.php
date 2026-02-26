<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'transaction_id',
        'response',
        'error_message'
    ];

    protected $casts = [
        'response' => 'array',
    ];

    /**
     * Relación: Un pago pertenece a una orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
