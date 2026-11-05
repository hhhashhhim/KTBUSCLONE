<?php

namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileAccountDataDeletion
{
    /**
     * Delete mobile-only personal data inside the caller's transaction.
     *
     * The caller must first lock the matching passenger account, or lock its
     * deletion audit and confirm that the account no longer exists when
     * repairing a historical deletion. Keep that lock until commit.
     * Shared financial records, booked passenger snapshots and anti-abuse
     * identity hashes are intentionally retained under their separate policies.
     */
    public function deletePrivateData(int $company, int $account, bool $execute = true): array
    {
        $counts = [];
        if (Schema::hasTable('mobile_notification_deliveries')) {
            $hasNotices = Schema::hasTable('mobile_notifications');
            $hasDevices = Schema::hasTable('mobile_push_devices');
            if ($hasNotices || $hasDevices) {
                $deliveries = DB::table('mobile_notification_deliveries')
                    ->where(function ($query) use ($company, $account, $hasNotices, $hasDevices) {
                        if ($hasNotices) {
                            $query->whereIn('notification_id', $this->owned('mobile_notifications', $company, $account)->select('id'));
                        }
                        if ($hasDevices) {
                            $query->orWhereIn('device_id', $this->owned('mobile_push_devices', $company, $account)->select('id'));
                        }
                    });
                $counts['mobile_notification_deliveries'] = $execute ? $deliveries->delete() : $deliveries->count();
            }
        }
        if (Schema::hasTable('mobile_biometric_sessions') && Schema::hasTable('mobile_biometric_tokens')) {
            $tokens = DB::table('mobile_biometric_tokens')
                ->whereIn('session_id', $this->owned('mobile_biometric_sessions', $company, $account)->select('id'));
            $counts['mobile_biometric_tokens'] = $execute ? $tokens->delete() : $tokens->count();
        }
        foreach (['mobile_notifications', 'mobile_push_devices', 'mobile_biometric_sessions',
            'mobile_app_login_events', 'mobile_app_passenger_controls', 'mobile_app_seat_usage', 'saved_passengers'] as $table) {
            if (Schema::hasTable($table)) {
                $rows = $this->owned($table, $company, $account);
                $counts[$table] = $execute ? $rows->delete() : $rows->count();
            }
        }
        if (Schema::hasTable('mobile_booking_quotes')) {
            // A used quote is the immutable passenger snapshot for a booking,
            // including older bookings created before the invoice link existed.
            $drafts = $this->owned('mobile_booking_quotes', $company, $account)->whereNull('used_at');
            if (Schema::hasColumn('mobile_booking_quotes', 'invoice_id')) {
                $drafts->whereNull('invoice_id');
            }
            $counts['mobile_booking_quotes'] = $execute ? $drafts->delete() : $drafts->count();
        }
        if (Schema::hasTable('personal_access_tokens')) {
            $owner = new PassengerAccount;
            $owner->id = $account;
            $counts['personal_access_tokens'] = $execute ? $owner->tokens()->delete() : $owner->tokens()->count();
        }

        return $counts;
    }

    private function owned(string $table, int $company, int $account)
    {
        return DB::table($table)->where('company_id', $company)->where('passenger_account_id', $account);
    }
}
