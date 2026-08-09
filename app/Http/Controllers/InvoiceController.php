<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function generateForOrder(Request $request, $orderId)
    {
        $order = Order::with('stall')->findOrFail($orderId);

        // Solo el dueño del pedido o un admin pueden solicitar el comprobante
        $user = $request->user();
        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // Evitar duplicados: si ya existe comprobante, devolverlo
        $existente = Invoice::where('order_id', $order->id)->first();
        if ($existente) {
            return response()->json(['success' => true, 'invoice_id' => $existente->id, 'duplicado' => true]);
        }

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->createForOrder($order);

        return response()->json(['success' => true, 'invoice_id' => $invoice->id]);
    }

    public function downloadPdf(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!$this->puedeAcceder($request->user(), $invoice)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
            return response()->json(['success' => false, 'message' => 'PDF no disponible'], 404);
        }

        return response()->download(storage_path('app/' . $invoice->pdf_path));
    }

    public function downloadXml(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!$this->puedeAcceder($request->user(), $invoice)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        if (!$invoice->xml_path || !Storage::exists($invoice->xml_path)) {
            return response()->json(['success' => false, 'message' => 'XML no disponible'], 404);
        }

        return response()->download(storage_path('app/' . $invoice->xml_path));
    }

    public function resend(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $user = $request->user();
        if ($invoice->order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        if ($invoice->status === 'generated') {
            return response()->json(['success' => false, 'message' => 'Comprobante ya generado']);
        }

        \App\Jobs\GenerateElectronicInvoiceJob::dispatch($invoice->id)->onQueue('invoices');

        return response()->json(['success' => true, 'message' => 'Reenvío encolado']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $invoices = Invoice::orderBy('created_at', 'desc')->limit(200)->get();
        } elseif ($user->role === 'vendor') {
            $invoices = Invoice::where('seller_id', $user->id)
                ->orWhereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->orderBy('created_at', 'desc')->get();
        } else {
            $invoices = Invoice::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orderBy('created_at', 'desc')->get();
        }

        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    public function show(Request $request, $invoiceId)
    {
        $invoice = Invoice::with('order')->findOrFail($invoiceId);
        $user = $request->user();

        if (!$this->puedeAcceder($user, $invoice)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    /**
     * Obtener el comprobante de un pedido específico (para polling del frontend).
     * Devuelve 404 si el pedido aún no tiene comprobante (aún en proceso de pago).
     */
    public function porPedido(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        $user = $request->user();

        // Misma regla de acceso: dueño del pedido, vendedor del puesto o admin
        $invoice = Invoice::where('order_id', $order->id)->first();
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'El pedido aún no tiene comprobante'], 404);
        }

        if (!$this->puedeAcceder($user, $invoice)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    /**
     * Verifica si el usuario puede acceder al comprobante:
     * dueño del pedido, vendedor del puesto o admin.
     */
    private function puedeAcceder($user, Invoice $invoice): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $order = $invoice->order()->select('id', 'user_id', 'stall_id')->first();

        if ($order && $order->user_id === $user->id) {
            return true;
        }

        if ($invoice->seller_id === $user->id) {
            return true;
        }

        if ($order && $user->role === 'vendor') {
            $stall = \App\Models\FoodStall::where('id', $order->stall_id)->where('seller_id', $user->id)->first();
            if ($stall) {
                return true;
            }
        }

        return false;
    }
}
