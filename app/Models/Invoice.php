<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'seller_id', 'customer_name', 'customer_document_type', 'customer_document_number', 'type',
        'subtotal', 'igv', 'total', 'pdf_path', 'xml_path', 'ose_ticket', 'ose_hash', 'status', 'attempts', 'error_message'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
