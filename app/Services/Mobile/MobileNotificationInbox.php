<?php
namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileNotificationInbox
{
    public static function installed(): bool
    {
        return Schema::hasTable('mobile_notifications') && Schema::hasTable('mobile_push_devices')
            && Schema::hasTable('mobile_notification_runtime') && Schema::hasTable('mobile_notification_deliveries')
            && Schema::hasTable('mobile_notification_booking_states');
    }
    public static function enabled(): bool
    {
        if (!config('mobile_notifications.enabled', true) || !self::installed()) return false;
        $features = Schema::hasTable('mobile_app_configs') ? DB::table('mobile_app_configs')->where('company_id', config('mobile.company_id'))->value('features') : null;
        $features = $features ? (json_decode($features, true) ?: []) : [];
        return !array_key_exists('notifications', $features) || (bool) $features['notifications'];
    }
    public static function query(PassengerAccount $account)
    {
        return DB::table('mobile_notifications')->where('company_id', $account->company_id)->where('passenger_account_id', $account->id);
    }
    public static function record(int $company, int $account, string $key, string $kind, string $title, string $body, ?int $invoice = null): void
    {
        DB::table('mobile_notifications')->insertOrIgnore(['company_id' => $company, 'passenger_account_id' => $account,
            'event_key' => $key, 'kind' => $kind, 'title' => $title, 'body' => $body, 'invoice_id' => $invoice, 'created_at' => now()]);
    }
    public static function present($row): array
    {
        return ['id' => (int) $row->id, 'kind' => $row->kind, 'title' => $row->title, 'body' => $row->body,
            'booking_id' => $row->invoice_id ? (int) $row->invoice_id : null,
            'read_at' => $row->read_at ? \Carbon\Carbon::parse($row->read_at)->toIso8601String() : null,
            'created_at' => \Carbon\Carbon::parse($row->created_at)->toIso8601String()];
    }
}
