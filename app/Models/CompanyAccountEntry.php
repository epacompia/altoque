<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAccountEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', // credit or debit
        'amount',
        'balance_after',
        'description',
        'order_id',
        'reference'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
