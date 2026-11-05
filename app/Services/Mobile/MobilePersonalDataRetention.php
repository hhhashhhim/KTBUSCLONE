<?php

namespace App\Services\Mobile;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/** Mobile-only retention. Never purges shared booking, payment or ERP records. */
class MobilePersonalDataRetention
{
    public function run(bool $execute = false): array
    {
        $company = $this->positiveInteger(config('mobile.company_id'), 'mobile.company_id', PHP_INT_MAX);
        $batch = $this->positiveInteger(config('mobile.retention.batch_size', 500), 'batch_size', 1000);
        $days = [];
        foreach (['notifications' => 90, 'login_events' => 90, 'push_devices' => 90, 'deletion_audit' => 365] as $key => $default) {
            $days[$key] = $this->positiveInteger(config('mobile.retention.'.$key.'_days', $default), $key.'_days', 3650);
        }
        $backup = config('mobile.retention.backup_max_days');
        $backup = $backup === null || $backup === '' ? null : $this->positiveInteger($backup, 'backup_max_days', 3650);
        if ($backup !== null && $days['deletion_audit'] <= $backup) {
            throw new InvalidArgumentException('deletion_audit_days must exceed the confirmed backup_max_days.');
        }
        if ($execute && config('mobile.retention.enabled') !== true) {
            throw new InvalidArgumentException('Execution is disabled until mobile.retention.enabled is explicitly true.');
        }

        $now = Carbon::now();
        $counts = ['deleted_accounts_checked' => 0, 'deleted_account_private_rows' => 0,
            'notifications' => 0, 'push_devices' => 0, 'login_events' => 0,
            'expired_account_credentials' => 0, 'expired_registration_codes' => 0, 'deletion_audits' => 0];
        $skipped = [];

        // An explicit deletion marker is required: absence alone is not evidence
        // that an account was deleted. Retain markers until restored data has
        // passed the same cleanup and the longest backup horizon has elapsed.
        if (Schema::hasTable('mobile_account_deletions') && Schema::hasTable('passenger_accounts')) {
            $audits = DB::table('mobile_account_deletions')->where('company_id', $company);
            $this->each($audits, $batch, function ($audit) use ($execute, $company, $now, $days, $backup, &$counts) {
                if (DB::table('passenger_accounts')->where('id', $audit->passenger_account_id)->lockForUpdate()->first()) {
                    return; // Also protects an inconsistent marker pointing to another company's live account.
                }
                $private = app(MobileAccountDataDeletion::class)->deletePrivateData($company, (int) $audit->passenger_account_id, $execute);
                $counts['deleted_accounts_checked']++;
                $counts['deleted_account_private_rows'] += array_sum($private);
                if ($backup !== null && Carbon::parse($audit->deleted_at)->lt($now->copy()->subDays($days['deletion_audit']))) {
                    if ($execute) DB::table('mobile_account_deletions')->where('id', $audit->id)->delete();
                    $counts['deletion_audits']++;
                }
            });
        }
        if ($backup === null) $skipped[] = 'Deletion audit expiry is paused until backup_max_days is confirmed.';

        if (Schema::hasTable('mobile_notifications')) {
            $query = DB::table('mobile_notifications')->where('company_id', $company)->where('created_at', '<', $now->copy()->subDays($days['notifications']));
            $this->each($query, $batch, function ($notice) use ($execute, $now, &$counts) {
                if (!$this->removeDeliveries('notification_id', (int) $notice->id, $execute, $now)) return;
                if ($execute) DB::table('mobile_notifications')->where('id', $notice->id)->delete();
                $counts['notifications']++;
            });
        }
        if (Schema::hasTable('mobile_push_devices')) {
            $query = DB::table('mobile_push_devices')->where('company_id', $company)->where('updated_at', '<', $now->copy()->subDays($days['push_devices']));
            $this->each($query, $batch, function ($device) use ($execute, $now, &$counts) {
                if (!$this->removeDeliveries('device_id', (int) $device->id, $execute, $now)) return;
                if ($execute) DB::table('mobile_push_devices')->where('id', $device->id)->delete();
                $counts['push_devices']++;
            });
        }

        if (Schema::hasTable('mobile_app_login_events')) {
            if (!Schema::hasColumn('passenger_accounts', 'first_mobile_login_at')) {
                $skipped[] = 'Login history expiry is paused until the first_mobile_login_at migration is installed.';
            } else {
                // Telemetry stores UTC. The retained first-login marker prevents
                // pruning from making a returning passenger look newly registered.
                $query = DB::table('mobile_app_login_events')->where('company_id', $company)
                    ->where('occurred_at', '<', Carbon::now('UTC')->subDays($days['login_events']))
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')->from('passenger_accounts')
                            ->whereColumn('passenger_accounts.id', 'mobile_app_login_events.passenger_account_id')
                            ->whereColumn('passenger_accounts.company_id', 'mobile_app_login_events.company_id')
                            ->whereNotNull('passenger_accounts.first_mobile_login_at');
                    });
                $this->each($query, $batch, function ($event) use ($execute, &$counts) {
                    if ($execute) DB::table('mobile_app_login_events')->where('id', $event->id)->delete();
                    $counts['login_events']++;
                });
            }
        }

        // Clear only expired material; do not reset attempt counters or the OTP
        // budget. Row locks match issue/verify/reset so newly issued secrets survive.
        foreach (['otp', 'password_reset_otp', 'password_reset_token'] as $prefix) {
            $counts['expired_account_credentials'] += $this->clearExpiredCredential('passenger_accounts', $prefix, $company, $batch, $execute, $now);
        }
        $counts['expired_registration_codes'] = $this->clearExpiredCredential('pending_passenger_registrations', 'otp', $company, $batch, $execute, $now);

        return ['dry_run' => !$execute, 'counts' => $counts, 'skipped' => $skipped];
    }

    private function clearExpiredCredential(string $table, string $prefix, int $company, int $batch, bool $execute, Carbon $now): int
    {
        $digest = $prefix.'_digest';
        $expiry = $prefix.'_expires_at';
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $digest) || !Schema::hasColumn($table, $expiry)) return 0;
        $query = DB::table($table)->where('company_id', $company)->whereNotNull($expiry)->where($expiry, '<', $now);
        $count = 0;
        $this->each($query, $batch, function ($row) use ($table, $digest, $expiry, $execute, &$count) {
            if ($execute) DB::table($table)->where('id', $row->id)->update([$digest => null, $expiry => null]);
            $count++;
        });
        return $count;
    }

    /** Lock all linked deliveries before checking the worker's five-minute lease. */
    private function removeDeliveries(string $column, int $id, bool $execute, Carbon $now): bool
    {
        if (!Schema::hasTable('mobile_notification_deliveries')) return true;
        $query = DB::table('mobile_notification_deliveries')->where($column, $id);
        $processing = false;
        (clone $query)->orderBy('id')->lockForUpdate()->select(['id', 'status', 'next_attempt_at'])
            ->chunkById(200, function ($rows) use ($now, &$processing) {
                foreach ($rows as $row) {
                    if ($row->status === 'processing' && $row->next_attempt_at && Carbon::parse($row->next_attempt_at)->gt($now)) {
                        $processing = true;
                        return false;
                    }
                }
            });
        if ($processing) return false;
        if ($execute) $query->delete();
        return true;
    }

    /** Bounded memory, finite ID snapshot, and eligibility rechecked under a row lock. */
    private function each(Builder $query, int $batch, callable $action): void
    {
        $maximum = (clone $query)->max('id');
        if (!$maximum) return;
        (clone $query)->where('id', '<=', $maximum)->orderBy('id')->chunkById($batch, function ($rows) use ($query, $action) {
            foreach ($rows as $row) {
                DB::transaction(function () use ($query, $row, $action) {
                    $fresh = (clone $query)->where('id', $row->id)->lockForUpdate()->first();
                    if ($fresh) $action($fresh);
                }, 3);
            }
        });
    }

    private function positiveInteger($value, string $name, int $maximum): int
    {
        $valid = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maximum]]);
        if ($valid === false || is_bool($value)) throw new InvalidArgumentException($name.' must be a positive integer no greater than '.$maximum.'.');
        return $valid;
    }
}
