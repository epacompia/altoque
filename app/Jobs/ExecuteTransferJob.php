<?php

namespace App\Jobs;

use App\Models\OrderCommission;
use App\Services\TransferService;
use App\Services\CommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\TransferAttempt;
use App\Models\CompanyAccountEntry;

class ExecuteTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $commissionId;
    protected $method;

    public function __construct(int $commissionId, string $method = 'mock')
    {
        $this->commissionId = $commissionId;
        $this->method = $method;
        $this->tries = 3;
        $this->timeout = 60;
    }

    public function handle(TransferService $transferService, CommissionService $commissionService)
    {
        $commission = OrderCommission::find($this->commissionId);
        if (!$commission) {
            Log::warning('ExecuteTransferJob: commission not found', ['id' => $this->commissionId]);
            return;
        }
        // Crear o actualizar intento
        $attemptCount = TransferAttempt::where('order_commission_id', $commission->id)->count() + 1;
        $attempt = TransferAttempt::create([
            'order_commission_id' => $commission->id,
            'method' => $this->method,
            'status' => 'pending',
            'attempt' => $attemptCount
        ]);

        $result = $transferService->transferToVendor($commission, $this->method);

        if ($result['success']) {
            // Marcar attempt success
            $attempt->update([
                'status' => 'success',
                'transaction_id' => $result['transaction_id'],
                'response' => json_encode($result)
            ]);

            // Registrar débito en cuenta de la empresa (se transfiere al vendedor)
            CompanyAccountEntry::create([
                'type' => 'debit',
                'amount' => $commission->net_amount,
                'description' => 'Transfer to vendor for commission #' . $commission->id,
                'order_id' => $commission->order_id,
                'reference' => $result['transaction_id'] ?? null
            ]);

            // Marcar como completada y guardar datos de transferencia
            $commissionService->markAsCompleted($commission, [
                'method' => $this->method,
                'transaction_id' => $result['transaction_id']
            ]);

            Log::info('ExecuteTransferJob: transferencia exitosa', ['commission_id' => $commission->id, 'txn' => $result['transaction_id']]);
        } else {
            // Registrar intento fallido
            $attempt->update([
                'status' => 'failed',
                'response' => json_encode($result)
            ]);

            Log::error('ExecuteTransferJob: transferencia fallida', ['commission_id' => $commission->id, 'message' => $result['message']]);
            // Notificar admin via mail si existe
            if (config('mail.admin_address')) {
                try {
                    \Mail::raw('Transfer failed for commission ' . $commission->id . '\nReason: ' . $result['message'], function ($m) {
                        $m->to(config('mail.admin_address'))->subject('Transfer Failed');
                    });
                } catch (\Exception $e) {
                    Log::error('ExecuteTransferJob: failed to send admin mail ' . $e->getMessage());
                }
            }
            // dejar que el job reintente según $this->tries
            throw new \Exception('Transfer failed: ' . $result['message']);
        }
    }
}
