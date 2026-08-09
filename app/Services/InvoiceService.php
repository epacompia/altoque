<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function createForOrder(Order $order): Invoice
    {
        // Si ya existe un comprobante para el pedido, devolverlo sin duplicar
        $existente = Invoice::where('order_id', $order->id)->first();
        if ($existente) {
            Log::info('Invoice ya existente para el pedido, no se duplica', [
                'invoice_id' => $existente->id,
                'order_id' => $order->id,
            ]);
            return $existente;
        }

        $type = $order->invoice_type ?? 'boleta';

        // Montos del comprobante = montos reales pagados del pedido.
        // El subtotal del comprobante incluye items + delivery (total gravado);
        // el IGV ya fue calculado al crear el pedido (18% solo para factura,
        // 0 para boleta); el total coincide exactamente con lo pagado.
        $subtotal = round((float) $order->subtotal + (float) $order->delivery_cost, 2);
        $igv = (float) $order->igv;
        $total = round($subtotal + $igv, 2);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'seller_id' => $order->stall?->seller_id,
            'customer_name' => $order->customer_name ?? ($order->client->name ?? null),
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
