<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\OseAdapterMock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceGenerated;
use App\Mail\InvoiceFailedNotification;

class GenerateElectronicInvoiceJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $invoiceId;

    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
        $this->onQueue('invoices');
    }

    public function handle(): void
    {
        $invoice = Invoice::find($this->invoiceId);
        if (!$invoice) {
            Log::error('Invoice not found for job', ['invoice_id' => $this->invoiceId]);
            return;
        }

        try {
            $adapter = new OseAdapterMock();

            $payload = [
                'order_id' => $invoice->order_id,
                'type' => $invoice->type,
                'subtotal' => $invoice->subtotal,
                'igv' => $invoice->igv,
                'total' => $invoice->total,
                'customer_name' => $invoice->customer_name,
                'customer_document_type' => $invoice->customer_document_type,
                'customer_document_number' => $invoice->customer_document_number,
            ];

            $response = $adapter->sendInvoice($payload);

            if (!$response['success']) {
                throw new \Exception('OSE error');
            }

            // Save files
            $xmlPath = 'invoices/' . $invoice->id . '.xml';
            $pdfPath = 'invoices/' . $invoice->id . '.pdf';
            Storage::put($xmlPath, $response['xml']);
            Storage::put($pdfPath, $response['pdf']);

            $invoice->update([
                'xml_path' => $xmlPath,
                'pdf_path' => $pdfPath,
                'ose_ticket' => $response['ose_ticket'] ?? null,
                'ose_hash' => $response['ose_hash'] ?? null,
                'status' => 'generated',
                'attempts' => $invoice->attempts + 1,
            ]);

            Log::info('Invoice generated successfully', ['invoice_id' => $invoice->id]);

            // Send to customer if email available
            $order = $invoice->order;
            $customerEmail = $order->user->email ?? null;
            if ($customerEmail) {
                Mail::to($customerEmail)->queue(new InvoiceGenerated($invoice->fresh()));
            }
        } catch (\Exception $e) {
            $invoice->increment('attempts');
            $invoice->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            Log::error('Error generating invoice', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            // Retry policy: if attempts < config limit, re-dispatch
            $max = config('invoicing.max_retries', 3);
            if ($invoice->attempts < $max) {
                self::dispatch($invoice->id)->delay(now()->addMinutes(1));
            } else {
                // Notify admin via mail and log
                Log::critical('Invoice generation failed after retries', ['invoice_id' => $invoice->id]);
                $admin = config('invoicing.admin_email');
                if ($admin) {
                    Mail::to($admin)->queue(new InvoiceFailedNotification($invoice));
                }
            }
        }
    }
}
