<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileAppControlData
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
}
