<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_commission_id',
        'method',
        'transaction_id',
        'status',
        'response',
        'attempt'
    ];

    protected $casts = [
        'attempt' => 'integer'
    ];

    public function commission()
    {
        return $this->belongsTo(OrderCommission::class, 'order_commission_id');
    }
}
