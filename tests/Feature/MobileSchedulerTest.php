<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class MobileSchedulerTest extends TestCase
{
    public function test_dedicated_mobile_schedule_does_not_duplicate_erp_jobs(): void
    {
        config(['mobile.scheduler_only' => true, 'mobile.retention.enabled' => false]);
        $events = $this->events();
        $commands = implode("\n", array_map(function ($event) { return $event->command; }, $events));
        $this->assertCount(6, $events);
        foreach (['reconcile-payments', 'prune-biometric-sessions', 'prune-pending-registrations',
            'prune-booking-quotes', 'prune-personal-data', 'notifications'] as $command) {
            $this->assertStringContainsString('mobile:' . $command, $commands);
        }
        foreach (['reserved:cancel', 'jazzcash:check-pending-payment', 'daily:report'] as $command) {
            $this->assertStringNotContainsString($command, $commands);
        }
        $retention = collect($events)->first(function ($event) {
            return strpos($event->command, 'mobile:prune-personal-data') !== false;
        });
        $this->assertFalse($retention->filtersPass($this->app));
        config(['mobile.retention.enabled' => true]);
        $this->assertTrue($retention->filtersPass($this->app));
    }

    public function test_default_schedule_preserves_existing_erp_jobs(): void
    {
        config(['mobile.scheduler_only' => false]);
        $events = $this->events();
        $commands = implode("\n", array_map(function ($event) { return $event->command; }, $events));
        $this->assertCount(9, $events);
        foreach (['reserved:cancel', 'jazzcash:check-pending-payment', 'daily:report'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }

    private function events(): array
    {
        $this->app->forgetInstance(Schedule::class);
        return $this->app->make(Schedule::class)->events();
    }
}
