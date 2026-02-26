<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderCommission;
use App\Jobs\ExecuteTransferJob;

class TransferPendingCommissions extends Command
{
    protected $signature = 'payments:transfer-pending {--vendor_id=} {--method=mock}';
    protected $description = 'Dispatch transfers for pending order commissions (schedulable)';

    public function handle()
    {
        $vendorId = $this->option('vendor_id');
        $method = $this->option('method') ?? 'mock';

        $query = OrderCommission::where('status', 'pending');
        if ($vendorId) {
            $query->whereHas('order.stall', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            });
        }

        $commissions = $query->get();
        if ($commissions->isEmpty()) {
            $this->info('No pending commissions found.');
            return 0;
        }

        foreach ($commissions as $c) {
            ExecuteTransferJob::dispatch($c->id, $method);
            $this->info('Dispatched transfer job for commission ' . $c->id);
        }

        return 0;
    }
}
