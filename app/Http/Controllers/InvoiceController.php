<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function generateForOrder(Request $request, $orderId)
    {
        $order = \App\Models\Order::findOrFail($orderId);

        // Basic validation: only the owner or admin can request
        // For simplicity, allow authenticated user

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->createForOrder($order);

        return response()->json(['success' => true, 'invoice_id' => $invoice->id]);
    }

    public function downloadPdf($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
            return response()->json(['success' => false, 'message' => 'PDF no disponible'], 404);
        }

        return response()->download(storage_path('app/' . $invoice->pdf_path));
    }

    public function downloadXml($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!$invoice->xml_path || !Storage::exists($invoice->xml_path)) {
            return response()->json(['success' => false, 'message' => 'XML no disponible'], 404);
        }

        return response()->download(storage_path('app/' . $invoice->xml_path));
    }

    public function resend($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if ($invoice->status === 'generated') {
            return response()->json(['success' => false, 'message' => 'Comprobante ya generado']);
        }

        \App\Jobs\GenerateElectronicInvoiceJob::dispatch($invoice->id)->onQueue('invoices');

        return response()->json(['success' => true, 'message' => 'Reenvío encolado']);
    }

    public function index()
    {
        $user = request()->user();
        // Admin can list all; vendors maybe list their invoices; clients list their invoices
        if ($user->hasRole('admin')) {
            $invoices = Invoice::orderBy('created_at', 'desc')->limit(200)->get();
        } else {
            $invoices = Invoice::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orderBy('created_at', 'desc')->get();
        }

        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    public function show($invoiceId)
    {
        $invoice = Invoice::with('order')->findOrFail($invoiceId);
        $user = request()->user();

        // Allow owner or admin or seller
        if ($invoice->order->user_id !== $user->id && !$user->hasRole('admin') && ($invoice->seller_id !== $user->id)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }
}
