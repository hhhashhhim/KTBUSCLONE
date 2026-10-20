<?php

namespace App\Console\Commands;

use App\Models\PassengerAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneMobileBiometricSessions extends Command
{
    protected $signature = 'mobile:prune-biometric-sessions';
    protected $description = 'Remove expired or revoked mobile quick-login credentials.';

    public function handle()
    {
        if (!Schema::hasTable('mobile_biometric_sessions') || !Schema::hasTable('mobile_biometric_tokens')) { return 0; }
        $count = 0;
        DB::table('mobile_biometric_sessions')->where('company_id', (int) config('mobile.company_id'))
            ->orderBy('id')->chunkById(200, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    DB::transaction(function () use ($row, &$count) {
                        // Match renewal's lock order. Keep tombstones until the family
                        // expires so replay detection remains possible.
                        $account = PassengerAccount::whereKey($row->passenger_account_id)
                            ->where('company_id', $row->company_id)->lockForUpdate()->first();
                        $access = $account ? $account->tokens()->whereKey($row->access_token_id)->lockForUpdate()->first() : null;
                        $session = DB::table('mobile_biometric_sessions')->where('id', $row->id)->lockForUpdate()->first();
                        if ($session && (!$account || !$access || \Carbon\Carbon::parse($session->expires_at)->lte(now()))) {
                            DB::table('mobile_biometric_tokens')->where('session_id', $row->id)->delete();
                            DB::table('mobile_biometric_sessions')->where('id', $row->id)->delete();
                            $count++;
                        }
                    });
                }
            });
        $this->info("Removed {$count} expired or revoked quick-login sessions.");
        return 0;
    }
}
