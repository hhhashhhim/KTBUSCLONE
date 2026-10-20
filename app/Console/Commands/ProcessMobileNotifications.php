<?php
namespace App\Console\Commands;

use App\Services\Mobile\MobileBookingNotifications;
use App\Services\Mobile\MobileNotificationDelivery;
use App\Services\Mobile\MobileNotificationInbox;
use App\Services\Mobile\MobilePushGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessMobileNotifications extends Command
{
    protected $signature = 'mobile:notifications';
    protected $description = 'Record booking alerts and process pending passenger push deliveries';
    public function handle(MobileBookingNotifications $bookings, MobileNotificationDelivery $delivery, MobilePushGateway $gateway): int
    {
        if (!MobileNotificationInbox::enabled() || !(int) config('mobile.company_id')) { $this->info('Mobile notifications are disabled or not installed.'); return 0; }
        $lock = Cache::lock('mobile-notifications:' . config('mobile.company_id'), 3600);
        if (!$lock->get()) return 0;
        try {
            DB::table('mobile_notification_runtime')->insertOrIgnore(['company_id' => config('mobile.company_id'), 'started_at' => now()]);
            $bookings->run();
            $delivery->run($gateway);
            DB::table('mobile_notification_runtime')->updateOrInsert(['company_id' => config('mobile.company_id')],
                ['last_run_at' => now(), 'push_configured' => $gateway->configured()]);
            $this->info('Mobile notification processing completed.');
        } finally { $lock->release(); }
        return 0;
    }
}
