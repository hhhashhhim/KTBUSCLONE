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
        // The dedicated mobile host shares ERP data but must not duplicate its cron jobs.
        if (!config('mobile.scheduler_only', false)) {
            $schedule->command('reserved:cancel')->everyFifteenMinutes();
            $schedule->command('jazzcash:check-pending-payment')->everyMinute();
            $schedule->command('daily:report')->dailyAt("11:00");
        }
        $schedule->command('mobile:reconcile-payments')->everyMinute()->withoutOverlapping();
        $schedule->command('mobile:prune-biometric-sessions')->daily()->withoutOverlapping();
        $schedule->command('mobile:prune-pending-registrations')->hourly()->withoutOverlapping();
        $schedule->command('mobile:prune-booking-quotes')->daily()->withoutOverlapping();
        $schedule->command('mobile:prune-personal-data --execute')->daily()->withoutOverlapping()
            ->when(function () { return (bool) config('mobile.retention.enabled', false); });
        $schedule->command('mobile:notifications')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
