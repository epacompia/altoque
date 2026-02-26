<?php

namespace App\Services;

use App\Models\OrderCommission;
use App\Models\Order;
use App\Models\CompanyAccountEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransferService
{
    /**
     * Intentar transferir monto neto al vendedor usando adaptador (mock por ahora)
     * Retorna array con keys: success(bool), transaction_id|null, message
     */
    public function transferToVendor(OrderCommission $commission, string $method = 'mock'): array
    {
        try {
            // Aquí se integrarían los adaptadores para Yape/Plin
            if ($method === 'mock') {
                $txn = 'MOCK-TRX-' . time() . '-' . rand(1000, 9999);
                Log::info('TransferService: transferencia simulada', ['commission_id' => $commission->id, 'txn' => $txn]);
                return [
                    'success' => true,
                    'transaction_id' => $txn,
                    'message' => 'Transferencia simulada exitosa'
                ];
            }

            // Si se agregan adaptadores reales, devolverán el mismo formato
            return [
                'success' => false,
                'transaction_id' => null,
                'message' => 'Método de transferencia no soportado'
            ];

        } catch (\Exception $e) {
            Log::error('TransferService error: ' . $e->getMessage(), ['commission_id' => $commission->id]);
            // Notificar admin si mail configurado
            if (config('mail.admin_address')) {
                try {
                    Mail::raw('Transfer failed for commission ' . $commission->id . '\nError: ' . $e->getMessage(), function ($m) {
                        $m->to(config('mail.admin_address'))->subject('Transfer Failed');
                    });
                } catch (\Exception $__) {
                    Log::error('Failed to send admin mail: ' . $__->getMessage());
                }
            }

            return [
                'success' => false,
                'transaction_id' => null,
                'message' => $e->getMessage()
            ];
        }
    }
}
