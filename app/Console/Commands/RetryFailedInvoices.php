<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class RetryFailedInvoices extends Command
{
    protected $signature = 'invoicing:retry-failed {--limit=50}';
    protected $description = 'Reintentar generación de comprobantes fallidos';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $failed = Invoice::where('status', 'failed')->limit($limit)->get();
        foreach ($failed as $inv) {
            \App\Jobs\GenerateElectronicInvoiceJob::dispatch($inv->id)->onQueue('invoices');
            $this->info('Re-enqueued invoice ' . $inv->id);
        }
        $this->info('Done. Dispatched ' . $failed->count() . ' jobs.');
        return 0;
    }
}
