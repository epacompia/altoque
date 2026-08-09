<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CommissionService;
use App\Models\CompanyAccountEntry;
use App\Models\Notification;
use App\Services\InvoiceService;

class PaymentService
{
    /**
     * Procesar pago de un pedido
     */
    public function processPayment(Order $order, string $method): array
    {
        try {
            DB::beginTransaction();

            // Validar que el pedido está en estado pending
            if ($order->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => "El pedido no está en estado pending. Estado actual: {$order->status}",
                ];
            }

            // Simular procesamiento de pago (mock)
            $paymentProcessed = $this->simulatePaymentGateway($method, $order->total);

            if (!$paymentProcessed['success']) {
                return [
                    'success' => false,
                    'message' => 'El pago fue rechazado por el banco',
                    'error_detail' => $paymentProcessed['error'],
                ];
            }

            // Crear registro de pago
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'amount' => $order->total,
                'status' => 'completed',
                'transaction_id' => $paymentProcessed['transaction_id'],
                'response_json' => json_encode($paymentProcessed),
            ]);

            // Actualizar estado del pedido
            $order->update([
                'status' => 'confirmed',
                'payment_id' => $payment->id,
                'confirmed_at' => now(),
            ]);

            // Notificar al vendedor sobre nuevo pedido
            $order->load('client', 'stall');
            Notification::create([
                'user_id' => $order->stall->seller_id,
                'type' => 'new_order',
                'title' => 'Nuevo pedido recibido',
                'body' => "Pedido #{$order->id} de {$order->client->name} por S/ {$order->total}",
                'notifiable_id' => $order->id,
                'notifiable_type' => Order::class,
            ]);
            \Illuminate\Support\Facades\Cache::forget("user.{$order->stall->seller_id}.notificaciones_no_leidas");

            // Registrar entrada en cuenta institucional (company holds the payment temporarily)
            CompanyAccountEntry::create([
                'type' => 'credit',
                'amount' => $order->total,
                'description' => 'Payment received for order #' . $order->id,
                'order_id' => $order->id,
                'reference' => $paymentProcessed['transaction_id'] ?? null
            ]);

            // Calcular comisión para el pedido
            $commissionService = app(CommissionService::class);
            $orderCommission = $commissionService->calculateCommissionForOrder($order);

            // Transferir en tiempo real si está configurado
            $transferMode = config('payments.transfer_mode', 'scheduled'); // 'real_time' or 'scheduled'
            if ($transferMode === 'real_time') {
                // Dispatch transfer job to execute actual vendor transfer
                \App\Jobs\ExecuteTransferJob::dispatch($orderCommission->id, config('payments.default_transfer_method', 'mock'));
            }

            // Emitir comprobante electrónico (si el cliente lo solicitó)
            if (!empty($order->invoice_requested)) {
                $invoiceService = app(InvoiceService::class);
                $invoiceService->createForOrder($order);
            }

            DB::commit();

            Log::info("Pago procesado exitosamente", [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $order->total,
            ]);

            return [
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'payment' => $payment,
                'order' => $order->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error al procesar pago", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error_detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Simular gateway de pago (mock para desarrollo/testing)
     * En producción, integrar con Stripe, Yape, Plin, etc.
     */
    private function simulatePaymentGateway(string $method, float $amount): array
    {
        // Simular respuesta exitosa
        $transactionId = 'TXN-' . time() . '-' . rand(1000, 9999);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'method' => $method,
            'amount' => $amount,
            'timestamp' => now(),
            'status' => 'approved',
        ];
    }
}
