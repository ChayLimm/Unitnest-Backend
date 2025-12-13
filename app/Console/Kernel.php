<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendMonthlyPaymentJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //
        $schedule->call(function () {

            // Dispatch job to send monthly payment reminders
            $tenants = Tenant::whereNotNull('telegram_id')
                ->whereNotNull('landlord_id')
                ->get();

            foreach ($tenants as $tenant) {
                SendMonthlyPaymentJob::dispatch($tenant->id)
                    ->onQueue('payments');
            }
            
            Log::info('Monthly payment reminders dispatched', [
                'tenant_count' => $tenants->count(),
                'timestamp' => now()
            ]);
        })->monthlyOn(1, '08:00') // Schedule to run on the 1st of every month at 8 AM
          ->timezone('Asia/Phnom_Penh')
          ->onQueue('payments');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
