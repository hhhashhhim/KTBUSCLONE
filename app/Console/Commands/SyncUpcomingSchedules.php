<?php

namespace App\Console\Commands;

use App\Services\UpcomingScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUpcomingSchedules extends Command
{
    protected $signature = 'schedules:sync-upcoming {--company= : Sync one company only}';
    protected $description = 'Refresh the 60-day schedule copy used by external booking APIs';

    public function handle(UpcomingScheduleService $service)
    {
        if ($this->option('company') !== null && (!ctype_digit($this->option('company')) || (int) $this->option('company') < 1)) {
            $this->error('Company must be a positive integer.');
            return 1;
        }
        $companyIds = $this->option('company') !== null
            ? [(int) $this->option('company')]
            : DB::table('companies')->pluck('id')->all();
        $failed = false;
        foreach ($companyIds as $companyId) {
            try {
                $state = $service->sync((int) $companyId);
                $this->info("Company {$companyId}: {$state['row_count']} rows, {$state['window_start']} to {$state['window_end']}.");
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("Company {$companyId}: " . $exception->getMessage());
                $failed = true;
            }
        }
        return $failed ? 1 : 0;
    }
}
