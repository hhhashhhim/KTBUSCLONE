<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreserveMobileFirstLogin extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('passenger_accounts')) return;
        if (!Schema::hasColumn('passenger_accounts', 'first_mobile_login_at')) {
            Schema::table('passenger_accounts', function (Blueprint $table) {
                $table->dateTime('first_mobile_login_at')->nullable(); // UTC, account lifetime only.
            });
        }
        if (!Schema::hasTable('mobile_app_login_events')) return;

        // Resume safely after interruption; never invent dates for accounts without history.
        DB::table('passenger_accounts')->select('id', 'company_id')->whereNull('first_mobile_login_at')
            ->chunkById(500, function ($accounts) {
                $firstLogins = DB::table('mobile_app_login_events')
                    ->whereIn('passenger_account_id', $accounts->pluck('id'))
                    ->select('company_id', 'passenger_account_id')->selectRaw('MIN(occurred_at) as first_at')
                    ->groupBy('company_id', 'passenger_account_id')->get()
                    ->keyBy(function ($row) { return $row->company_id . ':' . $row->passenger_account_id; });
                foreach ($accounts as $account) {
                    $first = $firstLogins->get($account->company_id . ':' . $account->id);
                    if ($first) DB::table('passenger_accounts')->where('id', $account->id)
                        ->where('company_id', $account->company_id)->whereNull('first_mobile_login_at')
                        ->update(['first_mobile_login_at' => $first->first_at]);
                }
            });
    }

    public function down()
    {
        // Preserve the account-lifetime marker after event history has expired.
    }
}
