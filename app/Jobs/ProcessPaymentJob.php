<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;
    protected string $paymentMethod;

    /**
     * Crear nueva instancia del job
     */
    public function __construct(Order $order, string $paymentMethod)
    {
        $this->order = $order;
        $this->paymentMethod = $paymentMethod;
        
        // Configurar reintentos y timeout
        $this->tries = 3;
        $this->timeout = 30;
    }

    /**
     * Ejecutar el job
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::info("Iniciando procesamiento de pago en queue", [
            'order_id' => $this->order->id,
            'method' => $this->paymentMethod,
        ]);

        $result = $paymentService->processPayment(
            $this->order,
            $this->paymentMethod
        );

        if (!$result['success']) {
            Log::error("Fallo al procesar pago en queue", [
                'order_id' => $this->order->id,
                'error' => $result['message'],
            ]);

            // El framework reintentará automáticamente (hasta 3 veces)
            throw new \Exception($result['message']);
        }

        Log::info("Pago procesado exitosamente en queue", [
            'order_id' => $this->order->id,
            'payment_id' => $result['payment']->id,
        ]);
    }

    /**
     * Manejar fallos después de agotar reintentos
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ProcessPaymentJob falló después de reintentos", [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // Aquí se podría notificar al cliente o admin
        // Ejemplo: enviar email al cliente informando del fallo
    }
}
