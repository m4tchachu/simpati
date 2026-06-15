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
        // Check due dates and send reminders daily at 08:00
        // H-3 (3 days before) and H-1 (1 day before) reminders
        // Status: active only
        $schedule->command('debt:check-due-dates')->dailyAt('08:00');

        // Alternative: Send debt reminders (can be used for testing)
        // $schedule->command('debt:send-reminders')->daily();
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
