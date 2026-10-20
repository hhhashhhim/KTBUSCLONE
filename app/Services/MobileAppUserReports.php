<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileAppUserReports
{
    public const TYPES = ['user_registrations', 'user_verification', 'user_restrictions', 'user_bookings', 'users_without_bookings'];

    private $data;

    public function __construct(MobileAppAdminData $data) { $this->data = $data; }

    public function supports(string $report): bool { return in_array($report, self::TYPES, true); }

    public function query(Request $request): array
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['active', 'blocked', 'blacklisted', 'verified', 'unverified'])],
            'verification' => ['nullable', Rule::in(['verified', 'unverified'])],
        ]);
        $cohort = in_array($request->report, ['user_registrations', 'user_verification'], true);
        $users = $this->data->users($request, $cohort);
        if ($request->verification === 'verified') { $users->whereNotNull('a.mobile_verified_at'); }
        if ($request->verification === 'unverified') { $users->whereNull('a.mobile_verified_at'); }

        if ($request->report === 'user_registrations') {
            $query = DB::query()->fromSub($users, 'u')->selectRaw("DATE(created_at) as date, COUNT(*) as users,
                SUM(CASE WHEN mobile_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_users,
                SUM(CASE WHEN mobile_verified_at IS NULL THEN 1 ELSE 0 END) as unverified_users,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
                SUM(CASE WHEN status = 'blacklisted' THEN 1 ELSE 0 END) as blacklisted_users")
                ->groupByRaw('DATE(created_at)')->orderByDesc('date');
            return [$query, ['date' => 'Registration date', 'users' => 'New accounts', 'verified_users' => 'Currently verified',
                'unverified_users' => 'Currently unverified', 'blocked_users' => 'Currently blocked', 'blacklisted_users' => 'Currently blacklisted']];
        }

        $columns = ['id' => 'Account', 'name' => 'Passenger', 'cnic' => 'CNIC', 'mobile' => 'Mobile', 'email' => 'Email',
            'status' => 'Account status', 'verification' => 'Mobile verification', 'created_at' => 'Joined'];

        if ($request->report === 'user_restrictions') {
            $this->data->requireCapability('controls');
            $users->whereIn('ctl.status', ['blocked', 'blacklisted'])->addSelect('ctl.updated_at as control_updated_at');
            $this->data->dates($request, $users, 'ctl.updated_at');
            return [$users->orderByDesc('ctl.updated_at')->orderByDesc('a.id'), $columns + ['control_updated_at' => 'Last control update']];
        }
        if ($request->report === 'user_verification') {
            return [$users->orderByDesc('a.id'), $columns + ['mobile_verified_at' => 'Verified at']];
        }

        // The period applies to bookings, not registration. Include older accounts and zero-booking users.
        // A user created after the selected period cannot belong to its no-booking population.
        if ($request->filled('to')) { $users->where('a.created_at', '<', Carbon::parse($request->to)->addDay()->toDateString() . ' 00:00:00'); }
        $bookingRequest = $request->duplicate($request->except(['status', 'search', 'verification']), []);
        $bookings = DB::query()->fromSub($this->data->invoices($bookingRequest), 'b')
            ->select('passenger_id')->groupBy('passenger_id')
            ->selectRaw('COUNT(*) as bookings, SUM(seat_count) as seats, SUM(confirmed_seats) as confirmed_seats,
                SUM(cancelled_seats) as cancelled_seats, SUM(confirmed_sales) as confirmed_sales, MAX(created_at) as last_booking_at');
        $users->leftJoinSub($bookings, 'ub', 'ub.passenger_id', '=', 'a.id');
        foreach (['bookings', 'seats', 'confirmed_seats', 'cancelled_seats', 'confirmed_sales'] as $field) {
            $users->selectRaw("COALESCE(ub.$field, 0) as $field");
        }
        $users->addSelect('ub.last_booking_at');
        if ($request->report === 'users_without_bookings') { $users->whereNull('ub.passenger_id'); }
        return [$users->orderByDesc('bookings')->orderBy('a.id'), $columns + ['bookings' => 'Bookings in period',
            'seats' => 'Original seats', 'confirmed_seats' => 'Confirmed seats', 'cancelled_seats' => 'Cancelled seats',
            'confirmed_sales' => 'Active confirmed sales', 'last_booking_at' => 'Last booking in period']];
    }

    public function present(Request $request, $row): array
    {
        $item = (array) $row;
        if ($request->report !== 'user_registrations') {
            $identity = $this->data->presentUser($request, $row);
            $item['mobile'] = $identity['mobile'];
            $item['email'] = $identity['email'];
            $cnic = $identity['cnic'] ?: $identity['cnic_masked'];
            $item['cnic'] = $cnic && preg_match('/^\d{13}$/', $cnic)
                ? substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12) : $cnic;
            $item['verification'] = $row->mobile_verified_at ? 'verified' : 'unverified';
            unset($item['company_id']);
        }
        return $item;
    }

    public function summary(Request $request, $query): array
    {
        $summary = DB::query()->fromSub((clone $query)->reorder(), 'r');
        if ($request->report === 'user_registrations') {
            return (array) $summary->selectRaw('COALESCE(SUM(users),0) as users, COALESCE(SUM(verified_users),0) as verified_users,
                COALESCE(SUM(unverified_users),0) as unverified_users, COALESCE(SUM(blocked_users),0) as blocked_users,
                COALESCE(SUM(blacklisted_users),0) as blacklisted_users')->first();
        }
        if (in_array($request->report, ['user_bookings', 'users_without_bookings'], true)) {
            return (array) $summary->selectRaw('COUNT(*) as users,
                COALESCE(SUM(CASE WHEN bookings > 0 THEN 1 ELSE 0 END),0) as users_with_bookings,
                COALESCE(SUM(CASE WHEN bookings > 1 THEN 1 ELSE 0 END),0) as repeat_bookers,
                COALESCE(SUM(CASE WHEN bookings = 0 THEN 1 ELSE 0 END),0) as users_without_bookings,
                COALESCE(SUM(bookings),0) as bookings, COALESCE(SUM(confirmed_sales),0) as confirmed_sales')->first();
        }
        return (array) $summary->selectRaw("COUNT(*) as users,
            COALESCE(SUM(CASE WHEN mobile_verified_at IS NOT NULL THEN 1 ELSE 0 END),0) as verified_users,
            COALESCE(SUM(CASE WHEN mobile_verified_at IS NULL THEN 1 ELSE 0 END),0) as unverified_users,
            COALESCE(SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END),0) as blocked_users,
            COALESCE(SUM(CASE WHEN status = 'blacklisted' THEN 1 ELSE 0 END),0) as blacklisted_users")->first();
    }

    public function note(Request $request): string
    {
        $notes = [
            'user_registrations' => 'Dates refer to account registration. Verification and restriction totals reflect current account status, not historical status on the registration day.',
            'user_verification' => 'Dates refer to account registration. Verification and account status are current. Use the verification filter to find accounts awaiting mobile verification.',
            'user_restrictions' => 'Currently blocked or blacklisted accounts whose controls were last updated in the selected period. This is a current restriction list, not a history of blocking events.',
            'user_bookings' => 'Dates refer to booking creation. Includes accounts registered before the end of the period, even with zero bookings. Repeat bookers have more than one booking in this period. Cancelled and pending bookings also count.',
            'users_without_bookings' => 'Accounts registered before the end of the period with no visible mobile bookings in this period and payment environment. This does not mean they have never booked or never used the app.',
        ];
        return $notes[$request->report] . ' Only accounts and bookings within your permitted company, terminals and routes are included. Restricted staff cannot see accounts with no booking history in their scope. CNIC and contact details follow your role privileges.';
    }
}
