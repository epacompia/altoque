<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Register invoicing commands
        if (class_exists(\App\Console\Commands\RetryFailedInvoices::class)) {
            $this->commands([
                \App\Console\Commands\RetryFailedInvoices::class,
            ]);
        }

        require base_path('routes/console.php');
    }
}
