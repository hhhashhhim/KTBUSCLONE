<?php

namespace App\Services\Mobile;

use App\Models\PassengerAccount;
use Illuminate\Http\Request;
use RuntimeException;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobilePassengerControls
{
    public const DEFAULTS = [
        'registrations_enabled' => true, 'logins_enabled' => true, 'bookings_enabled' => true, 'otp_enabled' => true,
        'daily_seat_limit' => null, 'monthly_seat_limit' => null,
        'otp_per_minute' => 3, 'otp_per_hour' => 10, 'otp_per_day' => 20, 'otp_per_month' => 100,
        'login_per_minute' => 5, 'otp_verify_per_ten_minutes' => 5, 'password_reset_per_minute' => 5,
    ];
    public const USER_DEFAULTS = ['daily_seat_limit' => null, 'monthly_seat_limit' => null,
        'otp_daily_limit' => null, 'otp_monthly_limit' => null, 'bookings_enabled' => true, 'otp_enabled' => true];

    public static function policy(int $company): array
    {
        $row = Schema::hasTable('mobile_app_policies') ? DB::table('mobile_app_policies')->where('company_id', $company)->first() : null;
        $settings = array_replace(self::DEFAULTS, $row ? (json_decode($row->settings, true) ?: []) : []);
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = is_bool($default) ? (bool) $settings[$key] : ($settings[$key] === null ? null : (int) $settings[$key]);
        }
        return $settings;
    }

    public static function mobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (preg_match('/^03[0-9]{9}$/', $mobile)) { return '92' . substr($mobile, 1); }
        if (preg_match('/^3[0-9]{9}$/', $mobile)) { return '92' . $mobile; }
        if (preg_match('/^00923[0-9]{9}$/', $mobile)) { return substr($mobile, 2); }
        return $mobile;
    }

    public static function identity(string $kind, string $value): string
    {
        return hash('sha256', $kind . ':' . ($kind === 'mobile' ? self::mobile($value) : preg_replace('/\D/', '', $value)));
    }

    public static function usage(int $company, int $account): array
    {
        $now = Carbon::now('Asia/Karachi'); $counts = [];
        foreach (['daily' => $now->copy()->startOfDay(), 'monthly' => $now->copy()->startOfMonth()] as $period => $start) {
            // Stored Laravel timestamps use the application timezone; period boundaries are Pakistan calendar time.
            $from = $start->setTimezone(config('app.timezone', 'Asia/Karachi'))->toDateTimeString();
            $to = ($period === 'daily' ? $now->copy()->startOfDay()->addDay() : $now->copy()->startOfMonth()->addMonth())
                ->setTimezone(config('app.timezone', 'Asia/Karachi'))->toDateTimeString();
            $count = Schema::hasTable('mobile_app_seat_usage') ? (int) DB::table('mobile_app_seat_usage')->where('company_id', $company)
                ->where('passenger_account_id', $account)->where('booked_at', '>=', $from)->where('booked_at', '<', $to)->sum('seats') : 0;
            if (Schema::hasTable('invoices') && Schema::hasTable('tickets') && Schema::hasColumn('invoices', 'passenger_account_id')) {
                $legacy = DB::table('invoices as i')->join('tickets as t', function ($join) { $join->on('t.invoice_id', '=', 'i.id')->on('t.company_id', '=', 'i.company_id'); })
                    ->where('i.company_id', $company)->where('i.passenger_account_id', $account)->where('i.created_at', '>=', $from)->where('i.created_at', '<', $to);
                if (Schema::hasTable('mobile_app_seat_usage')) { $legacy->whereNotExists(function ($q) { $q->selectRaw('1')->from('mobile_app_seat_usage as u')->whereColumn('u.invoice_id', 'i.id'); }); }
                $count += $legacy->count('t.id');
            }
            $counts[$period] = $count;
        }
        return $counts;
    }

    public static function installed(): bool
    {
        return Schema::hasTable('mobile_app_passenger_controls');
    }

    public static function controls(PassengerAccount $account): array
    {
        $row = self::installed() ? DB::table('mobile_app_passenger_controls')->where('company_id', $account->company_id)
            ->where('passenger_account_id', $account->id)->first() : null;
        return array_replace(self::USER_DEFAULTS, ['status' => 'active', 'password_reset_required' => false], (array) $row);
    }

    public static function mobileVariants(string $mobile): array
    {
        $digits = preg_replace('/\D/', '', $mobile); $canonical = self::mobile($mobile);
        $variants = [$digits, $canonical];
        if (preg_match('/^923[0-9]{9}$/', $canonical)) {
            $variants = array_merge($variants, ['0' . substr($canonical, 2), substr($canonical, 2), '00' . $canonical]);
        }
        return array_unique($variants);
    }

    public static function requestAccount(Request $request): ?PassengerAccount
    {
        if ($request->user() instanceof PassengerAccount && $request->is('api/mobile/v1/auth/resend-otp', 'api/mobile/v1/auth/verify-otp')) {
            return $request->user();
        }
        if (!self::installed() || !Schema::hasTable('passenger_accounts')) { return null; }
        $mobile = $request->input('mobile', '');
        if (!is_string($mobile) || strlen($mobile) > 100) { return null; }
        return PassengerAccount::where('company_id', (int) config('mobile.company_id'))
            ->whereIn('mobile', self::mobileVariants($mobile))->first();
    }

    public static function assertIdentity(int $company, string $mobile, ?string $cnic = null): void
    {
        if (!Schema::hasTable('mobile_app_blacklist_entries')) { return; }
        $hashes = [];
        if ($mobile !== '') { $hashes[] = self::identity('mobile', $mobile); }
        if ($cnic) { $hashes[] = self::identity('cnic', $cnic); }
        if ($hashes && DB::table('mobile_app_blacklist_entries')->where('company_id', $company)->whereIn('identity_hash', $hashes)->exists()) {
            throw new RuntimeException('This account is restricted. Please contact passenger support.', 403);
        }
    }

    public static function assertRegistration(int $company, string $mobile, ?string $cnic): void
    {
        if (!self::policy($company)['registrations_enabled']) {
            throw new RuntimeException('New registrations are temporarily unavailable.', 403);
        }
        self::assertIdentity($company, $mobile, $cnic);
    }

    public static function assertAccount(PassengerAccount $account, bool $allowPasswordReset = false): void
    {
        $control = self::controls($account);
        if ($control['status'] !== 'active') {
            throw new RuntimeException('This account is restricted. Please contact passenger support.', 403);
        }
        $cnic = null;
        if (Schema::hasTable('mobile_app_blacklist_entries') && $account->customer_id && Schema::hasTable('customers')) {
            $cnic = DB::table('customers')->where('company_id', $account->company_id)->where('id', $account->customer_id)->value('cnic');
        }
        self::assertIdentity((int) $account->company_id, (string) $account->mobile, $cnic);
        if (!$allowPasswordReset && $control['password_reset_required']) {
            throw new RuntimeException('A password reset is required. Use Forgot password to verify your mobile number and set a new password.', 403);
        }
    }

    public static function assertOtp(?PassengerAccount $account, int $company): void
    {
        $policy = self::policy($company); $control = $account ? self::controls($account) : self::USER_DEFAULTS;
        if ($account) { self::assertAccount($account, true); }
        if (!$policy['otp_enabled'] || !$control['otp_enabled'] || $control['otp_daily_limit'] === 0 || $control['otp_monthly_limit'] === 0
            || $control['otp_daily_limit'] === '0' || $control['otp_monthly_limit'] === '0') {
            throw new RuntimeException('Verification codes are disabled for this account. Please contact passenger support.', 403);
        }
    }

    public static function otpLimits(Request $request): array
    {
        $account = self::requestAccount($request);
        $policy = self::policy($account ? (int) $account->company_id : (int) config('mobile.company_id'));
        $control = $account ? self::controls($account) : self::USER_DEFAULTS;
        return ['minute' => (int) $policy['otp_per_minute'], 'hour' => (int) $policy['otp_per_hour'],
            'day' => (int) ($control['otp_daily_limit'] ?? $policy['otp_per_day']),
            'month' => (int) ($control['otp_monthly_limit'] ?? $policy['otp_per_month'])];
    }

    public static function assertBooking(PassengerAccount $account, int $seats): void
    {
        self::assertAccount($account);
        $policy = self::policy((int) $account->company_id); $control = self::controls($account);
        if (!$policy['bookings_enabled'] || !$control['bookings_enabled']) {
            throw new RuntimeException('New mobile bookings are disabled. Please contact passenger support.', 403);
        }
        $limits = ['daily' => $control['daily_seat_limit'] ?? $policy['daily_seat_limit'],
            'monthly' => $control['monthly_seat_limit'] ?? $policy['monthly_seat_limit']];
        if ($limits['daily'] === null && $limits['monthly'] === null) { return; }
        $usage = self::usage((int) $account->company_id, (int) $account->id);
        foreach ($limits as $period => $limit) {
            if ($limit !== null && $usage[$period] + $seats > (int) $limit) {
                throw new RuntimeException('Your ' . $period . ' seat limit has been reached. Please contact passenger support.', 403);
            }
        }
    }

    // Must be the first read in the booking transaction: concurrent schedules share this account lock.
    public static function lockForBooking(PassengerAccount $account, int $seats): void
    {
        if (self::installed()) {
            $account = PassengerAccount::where('company_id', $account->company_id)->whereKey($account->id)->lockForUpdate()->first();
            if (!$account) { throw new RuntimeException('Passenger account not found.', 403); }
        }
        self::assertBooking($account, $seats);
    }

    public static function recordSeats(PassengerAccount $account, int $invoice, int $seats): void
    {
        if (!Schema::hasTable('mobile_app_seat_usage')) { return; }
        DB::table('mobile_app_seat_usage')->insert(['company_id' => $account->company_id, 'passenger_account_id' => $account->id,
            'invoice_id' => $invoice, 'seats' => $seats, 'booked_at' => now()]);
    }
}
