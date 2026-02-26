<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function createForOrder(Order $order): Invoice
    {
        $type = $order->invoice_type ?? 'boleta';

        $subtotal = $order->subtotal ?? $order->total; // assume order has subtotal
        $igv = round(($subtotal * 0.18), 2);
        $total = $subtotal + $igv;

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'seller_id' => $order->stall_id ?? null,
            'customer_name' => $order->customer_name ?? ($order->user->name ?? null),
            'customer_document_type' => $order->customer_document_type ?? null,
            'customer_document_number' => $order->customer_document_number ?? null,
            'type' => $type,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'status' => 'pending',
        ]);

        // Dispatch job to generate electronic invoice with OSE
        \App\Jobs\GenerateElectronicInvoiceJob::dispatch($invoice->id)->onQueue('invoices');

        Log::info('Invoice created and job dispatched', ['invoice_id' => $invoice->id, 'order_id' => $order->id]);

        return $invoice;
    }

    public function generateImmediately(Invoice $invoice): Invoice
    {
        $job = new \App\Jobs\GenerateElectronicInvoiceJob($invoice->id);
        $job->handle();

        return $invoice->fresh();
    }
}
