<?php

namespace App\Console\Commands;

use App\Services\Mobile\MobilePersonalDataRetention;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class PruneMobilePersonalData extends Command
{
    protected $signature = 'mobile:prune-personal-data {--dry-run : Preview only (the default)} {--execute : Apply the approved retention settings}';
    protected $description = 'Preview or prune expired mobile-only personal data for the configured company.';

    public function handle(MobilePersonalDataRetention $retention)
    {
        if ($this->option('dry-run') && $this->option('execute')) {
            $this->error('Choose --dry-run or --execute, not both.');
            return 1;
        }
        $lock = Cache::lock('mobile-personal-data-retention:'.(int) config('mobile.company_id'), 3600);
        if (!$lock->get()) {
            $this->warn('Mobile personal data retention is already running.');
            return 0;
        }
        // Share the notification producer's lock; queued workers also use row
        // leases, which the service preserves until their processing window ends.
        $notifications = Cache::lock('mobile-notifications:'.(int) config('mobile.company_id'), 3600);
        try {
            if (!$notifications->get()) {
                $this->warn('Notification processing is active; retry retention after it finishes.');
                return 0;
            }
            $result = $retention->run((bool) $this->option('execute'));
            $this->info($result['dry_run'] ? 'Dry run: no data changed. Counts are eligible items per stage and may overlap.' : 'Mobile personal data retention completed.');
            $rows = [];
            foreach ($result['counts'] as $label => $count) $rows[] = [str_replace('_', ' ', $label), $count];
            $this->table(['Category', $result['dry_run'] ? 'Eligible' : 'Processed'], $rows);
            foreach ($result['skipped'] as $reason) $this->warn($reason);
            return 0;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return 1;
        } finally {
            $notifications->release();
            $lock->release();
        }
    }
}
