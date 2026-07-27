<?php

namespace App\Jobs;

use App\Models\OrderCommission;
use App\Services\CommissionService;
use App\Services\TransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCommissionTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected OrderCommission $commission;
    protected string $method;

    public function __construct(OrderCommission $commission, string $method = 'mock')
    {
        $this->commission = $commission;
        $this->method = $method;
        $this->tries = 3;
        $this->timeout = 30;
    }

    public function handle(TransferService $transferService, CommissionService $commissionService): void
    {
        Log::info('Iniciando transferencia de comisión', [
            'commission_id' => $this->commission->id,
            'method' => $this->method,
        ]);

        $res = $transferService->transferToVendor($this->commission, $this->method);

        if ($res['success']) {
            $commissionService->markAsCompleted($this->commission, [
                'method' => $this->method,
                'transaction_id' => $res['transaction_id'],
            ]);

            Log::info('Comisión transferida exitosamente', [
                'commission_id' => $this->commission->id,
                'transaction_id' => $res['transaction_id'],
            ]);
        } else {
            Log::error('Fallo al transferir comisión', [
                'commission_id' => $this->commission->id,
                'error' => $res['message'],
            ]);
            throw new \Exception($res['message']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job ProcessCommissionTransfer falló después de reintentos', [
            'commission_id' => $this->commission->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
