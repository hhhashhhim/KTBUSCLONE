<?php

namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileAppTelemetry
{
    public function syncRuntime(): bool
    {
        $company = (int) config('mobile.company_id');
        if (!$company || !Schema::hasTable('mobile_app_runtime') || !Schema::hasTable('mobile_app_login_events')) { return false; }
        $now = now('UTC');
        DB::table('mobile_app_runtime')->insertOrIgnore([
            'company_id' => $company, 'terminal_id' => (int) config('mobile.terminal_id') ?: null,
            'booking_user_id' => (int) config('mobile.booking_user_id') ?: null,
            'tracking_started_at' => $now, 'last_seen_at' => $now,
        ]);
        DB::table('mobile_app_runtime')->where('company_id', $company)->update([
            'terminal_id' => (int) config('mobile.terminal_id') ?: null,
            'booking_user_id' => (int) config('mobile.booking_user_id') ?: null, 'last_seen_at' => $now,
        ]);
        return true;
    }

    public function recordLogin(PassengerAccount $account, string $kind): void
    {
        if ((int) $account->company_id !== (int) config('mobile.company_id') || !$this->syncRuntime()) { return; }
        DB::transaction(function () use ($account, $kind) {
            // Serialize first-login classification with login/token changes for this account.
            PassengerAccount::whereKey($account->id)->where('company_id', $account->company_id)->lockForUpdate()->firstOrFail();
            $previous = DB::table('mobile_app_login_events')->where('company_id', $account->company_id)
                ->where('passenger_account_id', $account->id)->exists();
            DB::table('mobile_app_login_events')->insert([
                'company_id' => $account->company_id, 'passenger_account_id' => $account->id,
                'kind' => $kind, 'first_login' => !$previous, 'occurred_at' => now('UTC'),
            ]);
        });
    }
}
