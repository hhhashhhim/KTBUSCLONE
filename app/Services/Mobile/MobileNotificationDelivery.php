<?php
namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class MobileNotificationDelivery
{
    public function run(MobilePushGateway $gateway): void
    {
        $company = (int) config('mobile.company_id');
        if (!$gateway->configured()) return;
        DB::table('mobile_notifications')->where('company_id', $company)->whereNull('push_queued_at')
            ->orderBy('id')->limit(500)->get()->each(function ($notice) {
                DB::transaction(function () use ($notice) {
                    $current = DB::table('mobile_notifications')->where('id', $notice->id)->lockForUpdate()->first();
                    if ($current->push_queued_at) return;
                    if (Carbon::parse($current->created_at)->gt(now()->subDay()) && !$current->read_at) {
                        $devices = DB::table('mobile_push_devices')->where('company_id', $notice->company_id)
                            ->where('passenger_account_id', $notice->passenger_account_id)->get();
                        foreach ($devices as $device) DB::table('mobile_notification_deliveries')->insertOrIgnore([
                            'notification_id' => $notice->id, 'device_id' => $device->id, 'next_attempt_at' => now()]);
                    }
                    DB::table('mobile_notifications')->where('id', $notice->id)->update(['push_queued_at' => now()]);
                });
            });
        $ids = DB::table('mobile_notification_deliveries as d')->join('mobile_notifications as n', 'n.id', '=', 'd.notification_id')
            ->where('n.company_id', $company)->whereIn('d.status', ['pending', 'retry', 'processing'])
            ->where('d.next_attempt_at', '<=', now())->orderBy('d.id')->limit(100)->pluck('d.id');
        foreach ($ids as $id) {
            $delivery = DB::transaction(function () use ($id) {
                $row = DB::table('mobile_notification_deliveries')->where('id', $id)->lockForUpdate()->first();
                if (!in_array($row->status, ['pending', 'retry', 'processing']) || Carbon::parse($row->next_attempt_at)->isFuture()) return null;
                DB::table('mobile_notification_deliveries')->where('id', $id)->update(['status' => 'processing', 'attempts' => $row->attempts + 1, 'next_attempt_at' => now()->addMinutes(5)]);
                return $row;
            });
            if (!$delivery) continue;
            $notice = DB::table('mobile_notifications')->where('id', $delivery->notification_id)->first();
            $device = DB::table('mobile_push_devices')->where('id', $delivery->device_id)->first();
            $result = 'skipped';
            if ($notice && $device && !$notice->read_at && Carbon::parse($notice->created_at)->gt(now()->subDay()) && $this->eligible($notice, $device)) {
                try { $result = $gateway->send(Crypt::decryptString($device->token), (int) $notice->id); }
                catch (Throwable $error) { $result = 'retry'; }
            }
            if ($result === 'unregistered') DB::table('mobile_push_devices')->where('id', $device->id)->where('token_hash', $device->token_hash)->delete();
            $retry = $result === 'retry' && $delivery->attempts < 4;
            DB::table('mobile_notification_deliveries')->where('id', $id)->update([
                'status' => $result === 'accepted' ? 'accepted' : ($retry ? 'retry' : ($result === 'skipped' ? 'skipped' : 'failed')),
                'accepted_at' => $result === 'accepted' ? now() : null,
                'error_code' => in_array($result, ['accepted', 'skipped']) ? null : $result,
                'next_attempt_at' => $retry ? now()->addMinutes(pow(2, $delivery->attempts + 1)) : null]);
        }
    }
    private function eligible($notice, $device): bool
    {
        if ((int) $notice->company_id !== (int) $device->company_id || (int) $notice->passenger_account_id !== (int) $device->passenger_account_id) return false;
        $account = PassengerAccount::where('company_id', $device->company_id)->find($device->passenger_account_id);
        if (!$account || !$account->mobile_verified_at) return false;
        $access = $account->tokens()->where('id', $device->access_token_id)->first();
        if (!$access || (config('sanctum.expiration') && $access->created_at->lte(now()->subMinutes(config('sanctum.expiration'))))) return false;
        try { MobilePassengerControls::assertAccount($account); } catch (\RuntimeException $error) { return false; }
        return true;
    }
}
