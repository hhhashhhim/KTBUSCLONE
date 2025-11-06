<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        \App\Console\Commands\OnlineReservedCancelTicket::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('reserved:cancel')->everyFifteenMinutes();

        if (app()->environment('local')) {
            // Run every minute locally for testing
            $schedule->command('daily:report')->everyMinute()->withoutOverlapping()->sendOutputTo(storage_path('logs/daily_report.log'));

        } else {
            // Run daily at 11:00 AM in production
            $schedule->command('daily:report')->dailyAt('11:00');
        }
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
