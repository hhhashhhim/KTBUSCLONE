<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MobileAppAdminData;
use App\Services\MobileAppUserReports;
use App\Services\MobileAppControlData as Controls;
use App\Services\TicketCancellationService;
use App\Support\MobileAppPermissions;
use App\Support\MobileAppScope as Scope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MobileAppAdminController extends Controller
{
    private $data;
    private $userReports;

    public function __construct(MobileAppAdminData $data, MobileAppUserReports $userReports)
    {
        $this->data = $data;
        $this->userReports = $userReports;
    }

    private function authorizeAccess(Request $request, ?string $action = null): void
    {
        abort_unless($request->user() instanceof User && $request->user()->company_id, 403, 'ERP staff access is required.');
        abort_unless(Scope::allows($request->user(), 'mobile-app'), 403, 'Mobile App access denied.');
        if ($action) { abort_unless(Scope::allows($request->user(), 'mobile-app-' . $action), 403, 'Your role does not allow this Mobile App action.'); }
    }

    private function response($data) { return response()->json(['status' => true, 'data' => $data]); }

    private function filters(Request $request): void
    {
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'search' => 'nullable|string|max:100', 'status' => 'nullable|string|max:40',
            'terminal_id' => 'nullable|integer|min:1', 'route_id' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1|max:100000', 'per_page' => 'nullable|integer|min:1|max:100',
            'exceptions' => 'nullable|boolean', 'environment' => ['nullable', Rule::in(['live', 'sandbox', 'all'])],
        ]);
        Scope::terminal($request);
    }

    private function reason(Request $request): string
    {
        $request->merge(['reason' => trim((string) $request->reason)]);
        $request->validate(['reason' => 'required|string|min:3|max:1000']);
        return $request->reason;
    }

    private function permissions(Request $request): array
    {
        $map = [
            'overview' => 'view-dashboard', 'users_view' => 'view-users', 'users_update' => 'manage-users',
            'users_blacklist' => 'blacklist-users', 'users_limits' => 'manage-user-limits', 'users_password_reset' => 'force-password-reset', 'users_wallet' => 'manage-wallet',
            'app_controls_update' => 'manage-app-controls', 'users_sensitive' => 'view-sensitive-users', 'users_sessions_revoke' => 'revoke-sessions',
            'bookings_view' => 'view-bookings', 'seats_remove' => 'remove-seats', 'seats_refund' => 'refund-seats',
            'payments_view' => 'view-payments', 'reports_view' => 'view-reports', 'reports_export' => 'export-reports',
            'settings_view' => 'view-settings', 'settings_update' => 'manage-settings',
            'content_view' => 'view-content', 'content_update' => 'manage-content',
            'notifications_view' => 'view-notifications', 'notifications_send' => 'send-notifications', 'notifications_broadcast' => 'broadcast-notifications',
            'activity_view' => 'view-activity', 'all_terminals' => 'all-terminals',
        ];
        $permissions = [];
        foreach ($map as $key => $action) { $permissions[$key] = Scope::allows($request->user(), 'mobile-app-' . $action); }
        $accountControls = $this->data->accountControlScope($request);
        foreach (['users_update', 'users_blacklist', 'users_limits', 'users_password_reset', 'users_sessions_revoke', 'users_wallet'] as $key) {
            $permissions[$key] = $permissions[$key] && $accountControls;
        }
        foreach (['settings_update', 'app_controls_update', 'content_update'] as $key) {
            $permissions[$key] = $permissions[$key] && $this->data->configurationScope($request);
        }
        $permissions['notifications_broadcast'] = $permissions['notifications_broadcast'] && $this->data->configurationScope($request);
        return $permissions;
    }

    public function options(Request $request)
    {
        $this->authorizeAccess($request);
        $user = $request->user();
        $capabilities = $this->data->capabilities();
        $notices = [];
        foreach (['users', 'bookings', 'payments', 'settings', 'content', 'controls', 'audit'] as $key) {
            if (!$capabilities[$key]) { $notices[] = ucfirst($key) . ' is unavailable until its mobile database migration is installed.'; }
        }
        if (!$this->data->accountControlScope($request)) {
            foreach (['manage-users', 'blacklist-users', 'manage-user-limits', 'force-password-reset', 'revoke-sessions', 'manage-wallet'] as $action) {
                if (Scope::allows($user, 'mobile-app-' . $action)) {
                    $notices[] = ['screens' => ['mobile-app-users'], 'message' => 'Passenger account controls require the relevant role privilege and either company Admin access or access to all company terminals and current routes. Your staff account has limited scope; route assignments can be reviewed in Users.'];
                    break;
                }
            }
        }
        if (!$this->data->configurationScope($request)) {
            foreach (['manage-settings', 'manage-app-controls', 'manage-content'] as $action) {
                if (Scope::allows($user, 'mobile-app-' . $action)) {
                    $notices[] = ['screens' => ['mobile-app-settings', 'mobile-app-content'], 'message' => 'Company configuration requires a company Admin or the relevant role privilege with access to all company terminals and routes.'];
                    break;
                }
            }
        }
        $terminals = DB::table('terminals')->where('company_id', $user->company_id)->whereNull('deleted_at');
        if (!Scope::allows($user, 'mobile-app-all-terminals')) { $terminals->where('id', $user->terminal_id ?: -1); }
        $routes = DB::table('routes')->where('company_id', $user->company_id)->whereNull('deleted_at');
        if ($user->route_ids !== 'all') {
            $assigned = is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true);
            $routes->whereIn('id', is_array($assigned) ? array_map('intval', $assigned) : []);
        }
        return $this->response([
            'permissions' => $this->permissions($request), 'capabilities' => $capabilities, 'notices' => $notices,
            'terminals' => $terminals->orderBy('name')->get(['id', 'name']), 'routes' => $routes->orderBy('name')->get(['id', 'name']),
            'current_user' => $user->only(['id', 'name', 'terminal_id']), 'timezone' => config('app.timezone', 'Asia/Karachi'),
            'privileges' => MobileAppPermissions::definitions(),
        ]);
    }

    public function overview(Request $request)
    {
        $this->authorizeAccess($request, 'view-dashboard');
        $this->filters($request);
        $cap = $this->data->capabilities();
        $metrics = ['users' => null, 'total_bookings' => null, 'active_seats' => null, 'confirmed_sales' => null, 'pending_payments' => null, 'payment_exceptions' => null];
        $recent = $exceptions = [];
        $notices = ['Sales reflect active confirmed ERP tickets. Payment collections and recorded refund amounts are separate. The Live & historical filter excludes known sandbox payments; older records may not have an environment.'];
        if ($cap['users']) { $metrics['users'] = $this->data->users($request)->count('a.id'); }
        if ($cap['bookings']) {
            $bookings = $this->data->invoices($request);
            $totals = DB::query()->fromSub($bookings, 'b')->selectRaw('COUNT(*) as total_bookings, COALESCE(SUM(active_seats),0) as active_seats, COALESCE(SUM(confirmed_sales),0) as confirmed_sales')->first();
            $metrics['total_bookings'] = (int) $totals->total_bookings;
            $metrics['active_seats'] = (int) $totals->active_seats;
            $metrics['confirmed_sales'] = round((float) $totals->confirmed_sales, 2);
            if (Scope::allows($request->user(), 'mobile-app-view-bookings')) {
                $recent = $this->data->presentBookings($request, $this->data->invoices($request)->orderByDesc('i.id')->limit(8)->get());
            }
            if ($cap['payments']) {
                $payments = DB::query()->fromSub($this->data->payments($request), 'p');
                $metrics['pending_payments'] = (clone $payments)->where('status', 'pending')->count();
                $metrics['payment_exceptions'] = (clone $payments)->where('requires_review', 1)->count();
                if (Scope::allows($request->user(), 'mobile-app-view-payments')) {
                    $exceptions = (clone $payments)->where('requires_review', 1)->orderByDesc('id')->limit(8)->get()->map(function ($p) { return $this->data->presentPayment($p); })->all();
                }
            }
        } else { $notices[] = 'The existing mobile booking database is not installed; booking totals are unavailable.'; }
        return $this->response(['metrics' => $metrics, 'recent_bookings' => $recent, 'payment_exceptions' => $exceptions, 'notices' => $notices]);
    }

    public function engagement(Request $request, \App\Services\MobileAppEngagement $engagement)
    {
        $this->authorizeAccess($request, 'view-dashboard');
        $this->filters($request);
        $request->validate(['days' => ['nullable', Rule::in([7, 30])]]);
        return $this->response($engagement->read($request, $this->data));
    }

    private function availabilityFilters(Request $request): void
    {
        $this->authorizeAccess($request, 'view-bookings');
        $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today|before_or_equal:' . now()->addDays(60)->toDateString(),
            'mobile_terminal_id' => 'nullable|integer|min:1', 'route_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:100000',
            'status' => ['nullable', Rule::in(['allowed', 'not_allowed', 'not_open', 'needs_setup'])],
        ]);
    }

    public function availabilityRoutes(Request $request, \App\Services\MobileAppAvailability $availability)
    {
        $this->availabilityFilters($request);
        return $this->response($availability->routes($request));
    }

    public function availabilitySchedules(Request $request, \App\Services\MobileAppAvailability $availability)
    {
        $this->availabilityFilters($request);
        $request->validate(['route_id' => 'required|integer|min:1']);
        return $this->response($availability->schedules($request));
    }

    private function tokenTypes(): array
    {
        $types = ['App\\Models\\PassengerAccount'];
        foreach (Relation::morphMap() as $alias => $class) {
            if ($class === 'App\\Models\\PassengerAccount') { $types[] = $alias; }
        }
        return array_unique($types);
    }

    private function sessions($accountId)
    {
        return DB::table('personal_access_tokens')->whereIn('tokenable_type', $this->tokenTypes())->where('tokenable_id', $accountId);
    }

    private function presentUsers(Request $request, $rows): array
    {
        $counts = collect(); $lastUsed = collect();
        if ($this->data->capabilities()['bookings']) {
            $counts = $this->data->boundary($request, DB::table('invoices as i'), 'i')
                ->whereIn('i.passenger_account_id', $rows->pluck('id'))->groupBy('i.passenger_account_id')
                ->selectRaw('i.passenger_account_id, COUNT(*) as total')->pluck('total', 'passenger_account_id');
        }
        if (Schema::hasTable('personal_access_tokens')) {
            $lastUsed = DB::table('personal_access_tokens')->whereIn('tokenable_type', $this->tokenTypes())->whereIn('tokenable_id', $rows->pluck('id'))
                ->groupBy('tokenable_id')->selectRaw('tokenable_id, MAX(last_used_at) as last_used_at')->pluck('last_used_at', 'tokenable_id');
        }
        return $rows->map(function ($row) use ($request, $counts, $lastUsed) {
            return array_merge($this->data->presentUser($request, $row), ['booking_count' => (int) $counts->get($row->id, 0), 'last_used_at' => $lastUsed->get($row->id)]);
        })->all();
    }

    public function users(Request $request)
    {
        $this->authorizeAccess($request, 'view-users'); $this->filters($request);
        $page = $this->data->users($request)->orderByDesc('a.id')->paginate($request->input('per_page', 25));
        return $this->response($this->data->pagination($page, $this->presentUsers($request, $page->getCollection())));
    }

    public function user(Request $request, int $id)
    {
        $this->authorizeAccess($request, 'view-users'); $this->filters($request);
        $row = $this->data->users($request)->where('a.id', $id)->first();
        abort_unless($row, 404, 'Passenger account not found.');
        $bookings = $sessions = [];
        if ($this->data->capabilities()['bookings'] && Scope::allows($request->user(), 'mobile-app-view-bookings')) {
            $bookings = $this->data->presentBookings($request, $this->data->invoices($request, false)->where('i.passenger_account_id', $id)->orderByDesc('i.id')->limit(25)->get());
        }
        if ($this->permissions($request)['users_sessions_revoke'] && Schema::hasTable('personal_access_tokens')) {
            $sessions = $this->sessions($id)->orderByDesc('id')->get(['id', 'name', 'last_used_at', 'created_at'])->all();
        }
        return $this->response(['user' => $this->presentUsers($request, collect([$row]))[0], 'bookings' => $bookings, 'sessions' => $sessions,
            'controls' => $this->data->accountControlScope($request) ? $this->accountControls((int) $row->company_id, $id) : null]);
    }

    private function authorizeAccountControl(Request $request, string $action, int $id): void
    {
        $this->authorizeAccess($request, 'view-users'); $this->authorizeAccess($request, $action);
        abort_unless($this->data->accountControlScope($request), 403, 'Account controls require company Admin access or all company terminals and routes.');
        $this->data->requireCapability('users'); $this->data->requireCapability('controls'); $this->data->requireCapability('audit');
        // Ignore mutation fields (especially the requested status) when checking target visibility.
        $scopeRequest = Request::create('/', 'GET');
        $scopeRequest->setUserResolver($request->getUserResolver());
        abort_unless($this->data->users($scopeRequest, false)->where('a.id', $id)->exists(), 404, 'Passenger account not found.');
    }

    private function requireControlSchema(): void
    {
        abort_unless(Schema::hasTable('mobile_app_policies') && Schema::hasTable('mobile_app_blacklist_entries')
            && Schema::hasColumn('mobile_app_passenger_controls', 'password_reset_required'), 503, 'Install the Mobile App user-controls migration first.');
    }

    private function accountControls(int $company, int $id): ?array
    {
        if (!Schema::hasColumn('mobile_app_passenger_controls', 'password_reset_required')) { return null; }
        $row = DB::table('mobile_app_passenger_controls')->where('company_id', $company)->where('passenger_account_id', $id)->first();
        $limits = Controls::USER_DEFAULTS;
        foreach ($limits as $key => $default) { $limits[$key] = $row ? $row->$key : $default; }
        foreach (['bookings_enabled', 'otp_enabled'] as $key) { $limits[$key] = (bool) $limits[$key]; }
        foreach (['daily_seat_limit', 'monthly_seat_limit', 'otp_daily_limit', 'otp_monthly_limit'] as $key) {
            if ($limits[$key] !== null) { $limits[$key] = (int) $limits[$key]; }
        }
        return ['limits' => $limits, 'defaults' => Controls::policy($company), 'usage' => Controls::usage($company, $id),
            'password_reset_required' => (bool) ($row->password_reset_required ?? false), 'revision' => $this->data->revision($row)];
    }

    private function invalidateResetCodes($account): void
    {
        $fields = ['otp_digest', 'otp_expires_at', 'password_reset_otp_digest', 'password_reset_otp_expires_at',
            'password_reset_token_digest', 'password_reset_token_expires_at', 'remember_token'];
        $values = array_fill_keys(array_intersect($fields, array_keys((array) $account)), null);
        if ($values) { DB::table('passenger_accounts')->where('id', $account->id)->where('company_id', $account->company_id)->update($values); }
    }

    public function updateUserStatus(Request $request, int $id)
    {
        $this->authorizeAccountControl($request, 'view-users', $id);
        $reason = $this->reason($request);
        $request->validate(['status' => ['required', Rule::in(['active', 'blocked', 'blacklisted'])],
            'expected_status' => ['required', Rule::in(['active', 'blocked', 'blacklisted'])]]);
        $revoked = DB::transaction(function () use ($request, $id, $reason) {
            $account = DB::table('passenger_accounts')->where('company_id', $request->user()->company_id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($account, 404, 'Passenger account not found.');
            $query = DB::table('mobile_app_passenger_controls')->where('company_id', $account->company_id)->where('passenger_account_id', $id);
            $control = (clone $query)->lockForUpdate()->first();
            $old = $control->status ?? 'active';
            $this->authorizeAccess($request, $old === 'blacklisted' || $request->status === 'blacklisted' ? 'blacklist-users' : 'manage-users');
            abort_if($old !== $request->expected_status, 409, 'The account status changed. Refresh and try again.');
            if ($old === $request->status) { return 0; }
            if ($old === 'blacklisted' || $request->status === 'blacklisted') {
                $this->requireControlSchema();
                DB::table('mobile_app_blacklist_entries')->where('company_id', $account->company_id)->where('passenger_account_id', $id)->delete();
                if ($request->status === 'blacklisted') {
                    $cnic = DB::table('customers')->where('company_id', $account->company_id)->where('id', $account->customer_id)->value('cnic');
                    foreach (['mobile' => $account->mobile, 'cnic' => $cnic] as $kind => $value) {
                        if (preg_replace('/\D/', '', $value ?? '') === '') { continue; }
                        DB::table('mobile_app_blacklist_entries')->insert(['company_id' => $account->company_id, 'passenger_account_id' => $id,
                            'kind' => $kind, 'identity_hash' => Controls::identity($kind, $value), 'created_at' => now()]);
                    }
                }
            }
            $values = ['status' => $request->status, 'reason' => $reason, 'updated_by' => $request->user()->id, 'updated_at' => now()];
            if ($control) { $query->update($values); }
            else { DB::table('mobile_app_passenger_controls')->insert($values + ['company_id' => $account->company_id, 'passenger_account_id' => $id, 'created_at' => now()]); }
            $count = $request->status !== 'active' && Schema::hasTable('personal_access_tokens') ? $this->sessions($id)->delete() : 0;
            if ($request->status !== 'active') { $this->invalidateResetCodes($account); }
            $this->data->audit($request, 'account_status_changed', 'passenger', $id, $reason, ['old_status' => $old, 'new_status' => $request->status, 'sessions_revoked' => $count]);
            return $count;
        });
        return $this->response(['id' => $id, 'status' => $request->status, 'sessions_revoked' => $revoked]);
    }

    public function updateUserLimits(Request $request, int $id)
    {
        $this->authorizeAccountControl($request, 'manage-user-limits', $id); $this->requireControlSchema();
        $reason = $this->reason($request);
        $request->validate(['limits' => 'required|array', 'revision' => 'required|string|size:64']);
        abort_if(array_diff(array_keys($request->input('limits')), array_keys(Controls::USER_DEFAULTS)), 422, 'Unsupported passenger control.');
        $rules = [];
        foreach (Controls::USER_DEFAULTS as $key => $default) {
            $rules['limits.' . $key] = is_bool($default) ? 'required|boolean' : 'present|nullable|integer|min:0|max:10000';
        }
        $input = $request->validate($rules)['limits'];
        DB::transaction(function () use ($request, $id, $reason, $input) {
            $account = DB::table('passenger_accounts')->where('company_id', $request->user()->company_id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($account, 404, 'Passenger account not found.');
            $query = DB::table('mobile_app_passenger_controls')->where('company_id', $account->company_id)->where('passenger_account_id', $id);
            $old = (clone $query)->lockForUpdate()->first();
            abort_unless(hash_equals($this->data->revision($old), $request->revision), 409, 'Passenger controls changed. Reload before saving.');
            $values = $input + ['reason' => $reason, 'updated_by' => $request->user()->id, 'updated_at' => now()];
            if ($old) { $query->update($values); }
            else { DB::table('mobile_app_passenger_controls')->insert($values + ['company_id' => $account->company_id, 'passenger_account_id' => $id, 'status' => 'active', 'created_at' => now()]); }
            $this->data->audit($request, 'passenger_limits_changed', 'passenger', $id, $reason,
                ['from' => array_intersect_key((array) $old, Controls::USER_DEFAULTS), 'to' => $input]);
        });
        return $this->response($this->accountControls((int) $request->user()->company_id, $id));
    }

    public function requirePasswordReset(Request $request, int $id)
    {
        $this->authorizeAccountControl($request, 'force-password-reset', $id); $this->requireControlSchema();
        $reason = $this->reason($request);
        $request->validate(['revision' => 'required|string|size:64']);
        abort_unless(Schema::hasTable('personal_access_tokens') && Schema::hasColumn('passenger_accounts', 'password_reset_token_digest'), 503, 'Passenger password-reset support is unavailable.');
        $count = DB::transaction(function () use ($request, $id, $reason) {
            $account = DB::table('passenger_accounts')->where('company_id', $request->user()->company_id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($account, 404, 'Passenger account not found.');
            $query = DB::table('mobile_app_passenger_controls')->where('company_id', $account->company_id)->where('passenger_account_id', $id);
            $old = (clone $query)->lockForUpdate()->first();
            abort_unless(hash_equals($this->data->revision($old), $request->revision), 409, 'Passenger controls changed. Reload before continuing.');
            $values = ['password_reset_required' => true, 'updated_by' => $request->user()->id, 'updated_at' => now()];
            if ($old) { $query->update($values); }
            else { DB::table('mobile_app_passenger_controls')->insert($values + ['company_id' => $account->company_id, 'passenger_account_id' => $id, 'status' => 'active', 'created_at' => now()]); }
            $this->invalidateResetCodes($account);
            $count = $this->sessions($id)->delete();
            $this->data->audit($request, 'password_reset_required', 'passenger', $id, $reason, ['sessions_revoked' => $count]);
            return $count;
        });
        return $this->response(['password_reset_required' => true, 'sessions_revoked' => $count]);
    }

    public function revokeSessions(Request $request, int $id)
    {
        $this->authorizeAccountControl($request, 'revoke-sessions', $id);
        $reason = $this->reason($request);
        abort_unless(Schema::hasTable('personal_access_tokens'), 503, 'Passenger session storage is unavailable.');
        $count = DB::transaction(function () use ($request, $id, $reason) {
            $account = DB::table('passenger_accounts')->where('company_id', $request->user()->company_id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($account, 404, 'Passenger account not found.');
            $count = $this->sessions($id)->delete();
            $this->data->audit($request, 'sessions_revoked', 'passenger', $id, $reason, ['sessions_revoked' => $count]);
            return $count;
        });
        return $this->response(['sessions_revoked' => $count]);
    }

    public function bookings(Request $request)
    {
        $this->authorizeAccess($request, 'view-bookings'); $this->filters($request);
        $page = $this->data->invoices($request)->orderByDesc('i.id')->paginate($request->input('per_page', 25));
        return $this->response($this->data->pagination($page, $this->data->presentBookings($request, $page->getCollection())));
    }

    public function booking(Request $request, int $id)
    {
        $this->authorizeAccess($request, 'view-bookings'); $this->filters($request);
        $row = $this->data->invoices($request, false)->where('i.id', $id)->first();
        abort_unless($row, 404, 'Mobile booking not found.');
        $item = $this->data->presentBookings($request, collect([$row]), true)[0];
        $item['payment'] = null;
        $item['seat_removal_policy'] = $this->seatRemovalPolicy($row->payment_id !== null);
        if ($this->data->capabilities()['payments'] && Scope::allows($request->user(), 'mobile-app-view-payments')) {
            $payment = $this->data->payments($request, false)->where('pay.invoice_id', $id)->first();
            if ($payment) { $item['payment'] = $this->data->presentPayment($payment); }
        }
        return $this->response($item);
    }

    private function seatRemovalPolicy(bool $hasPayment): array
    {
        return [
            'requires_full_cancellation' => $hasPayment,
            'preserve_one_seat' => !$hasPayment,
            'message' => $hasPayment
                ? 'Select all remaining seats for this online booking. Partial cancellation requires a mobile ticket-display update.'
                : 'At least one seat must remain on this legacy booking until the mobile booking-history service is updated.',
        ];
    }

    public function removeSeats(Request $request, int $id)
    {
        $this->authorizeAccess($request, 'view-bookings'); $this->authorizeAccess($request, 'remove-seats');
        $this->data->requireCapability('bookings'); $this->data->requireCapability('audit');
        $reason = $this->reason($request);
        $request->validate(['ticket_ids' => 'required|array|min:1|max:50', 'ticket_ids.*' => 'required|integer|min:1|distinct', 'refund_percentage' => 'required|numeric|min:0|max:100']);
        if ((float) $request->refund_percentage > 0) { $this->authorizeAccess($request, 'refund-seats'); }
        $invoice = $this->data->invoices($request, false)->where('i.id', $id)->first();
        abort_unless($invoice, 404, 'Mobile booking not found.');
        $lock = Cache::lock('stayLock:' . $invoice->schedule_id, 30);
        abort_unless($lock->get(), 409, 'This departure is being updated. Please retry.');
        try {
            $result = DB::transaction(function () use ($request, $id, $invoice, $reason) {
                // Same payment-before-ticket lock order as passenger payment settlement.
                $payment = $this->data->capabilities()['payments']
                    ? DB::table('mobile_payments')->where('company_id', $request->user()->company_id)->where('invoice_id', $id)->lockForUpdate()->first() : null;
                abort_if($payment && in_array($payment->status, ['pending', 'review_required'], true), 409, 'Resolve the gateway payment before removing seats.');
                $current = DB::table('invoices')->where('company_id', $request->user()->company_id)->where('id', $id)->lockForUpdate()->first();
                abort_unless($current && (int) $current->passenger_account_id === (int) $invoice->passenger_id
                    && (int) $current->schedule_id === (int) $invoice->schedule_id, 409, 'The booking changed. Refresh and retry.');
                $tickets = Scope::tickets($request)->where('tickets.invoice_id', $id)->whereIn('tickets.id', $request->ticket_ids)->orderBy('tickets.id')->lockForUpdate()->get();
                abort_unless($tickets->count() === count($request->ticket_ids), 404, 'A selected seat is outside this mobile booking or your access.');
                foreach ($tickets as $ticket) {
                    abort_if($ticket->trashed() || !in_array($ticket->type, ['advance booking', 'booked'], true), 409, 'A selected seat is already cancelled or cannot be removed.');
                    abort_if($ticket->ticket_closing_id || $ticket->ticket_merge_id, 409, 'Closed or assigned-closing tickets must use the existing ERP cancellation workflow.');
                    $departure = Carbon::parse($ticket->schedule_date . ' ' . ($ticket->schedule_time_exact ?: $ticket->schedule_time), config('app.timezone'));
                    abort_if($departure->lessThanOrEqualTo(now()), 409, 'Seats cannot be removed after departure.');
                    if ($ticket->type === 'booked') {
                        $this->authorizeAccess($request, 'refund-seats');
                        abort_if(filter_var(config('services.terminal_cancellations.approval_enabled', false), FILTER_VALIDATE_BOOLEAN), 409, 'This booking requires the existing terminal cancellation approval workflow.');
                    } else {
                        abort_if((float) $request->refund_percentage > 0 || ($payment && $payment->paid_at), 409, 'Unconfirmed seats cannot record a cash refund. Resolve any received payment first.');
                    }
                }
                // The deployed passenger presenter includes all canceled online tickets but
                // excludes canceled legacy tickets. Until it supports partial/empty invoices,
                // protect surviving online QR codes and legacy booking-history pagination.
                $activeIds = DB::table('tickets')->where('company_id', $request->user()->company_id)
                    ->where('invoice_id', $id)->whereNull('deleted_at')->whereIn('type', ['booked', 'advance booking'])
                    ->orderBy('id')->lockForUpdate()->pluck('id');
                $remaining = $activeIds->diff($tickets->pluck('id'))->count();
                $policy = $this->seatRemovalPolicy($payment !== null);
                abort_if(($payment && $remaining > 0) || (!$payment && $remaining === 0), 409, $policy['message']);
                $removed = []; $refund = 0;
                foreach ($tickets as $ticket) {
                    $fare = max(0, round((float) $ticket->seat_fare - (float) $ticket->discount, 2));
                    app(TicketCancellationService::class)->cancelTicket($ticket, (float) $request->refund_percentage, $reason, $request->user()->id, $fare);
                    // Legacy ERP cancellation reports store the deduction in BookingCancel.percentage.
                    // Ticket.refund_percentage and refund_amount retain the actual refund values.
                    $cancel = \App\Models\Booking\BookingCancel::where('company_id', $ticket->company_id)->where('ticket_id', $ticket->id)->latest('id')->first();
                    if ($cancel) { $cancel->update(['percentage' => 100 - (float) $request->refund_percentage]); }
                    $removed[] = (string) $ticket->seat_no;
                    $refund += (float) $ticket->refund_amount;
                }
                $this->data->audit($request, 'seats_removed', 'booking', $id, $reason, [
                    'ticket_ids' => $tickets->pluck('id')->all(), 'seat_numbers' => $removed,
                    'refund_percentage' => (float) $request->refund_percentage, 'recorded_refund_amount' => round($refund, 2),
                    'gateway_refund_sent' => false,
                ], (int) $invoice->terminal_id, (int) $invoice->route_id);
                return ['removed_seats' => $removed, 'recorded_refund_amount' => round($refund, 2), 'gateway_refund_sent' => false,
                    'message' => 'Seats cancelled and history recorded. Any gateway refund must be completed through the approved finance process.'];
            });
        } finally { $lock->release(); }
        return $this->response($result);
    }

    public function payments(Request $request)
    {
        $this->authorizeAccess($request, 'view-payments'); $this->filters($request);
        $page = $this->data->payments($request)->orderByDesc('pay.id')->paginate($request->input('per_page', 25));
        return $this->response($this->data->pagination($page, $page->getCollection()->map(function ($row) { return $this->data->presentPayment($row); })->all()));
    }

    private function reportQuery(Request $request): array
    {
        $request->validate(['report' => ['required', Rule::in(array_merge(['sales', 'routes', 'passengers', 'cancellations', 'payments'], MobileAppUserReports::TYPES))]]);
        if ($this->userReports->supports($request->report)) {
            $this->authorizeAccess($request, 'view-users');
            return $this->userReports->query($request);
        }
        if ($request->report === 'payments') {
            $query = DB::query()->fromSub($this->data->payments($request), 'p')
                ->select(['id', 'invoice_id', 'method', 'transaction_reference', 'status', 'currency', 'environment', 'paid_at', 'created_at', 'requires_review'])
                ->selectRaw('amount_minor / 100.0 as amount')->orderByDesc('id');
            return [$query, ['invoice_id' => 'Booking', 'method' => 'Method', 'transaction_reference' => 'Transaction reference', 'status' => 'Payment status', 'amount' => 'Amount', 'currency' => 'Currency', 'environment' => 'Environment', 'paid_at' => 'Paid at', 'requires_review' => 'Needs review']];
        }
        $query = DB::query()->fromSub($this->data->invoices($request), 'b');
        if ($request->report === 'passengers') {
            $query->select('passenger_id', 'passenger_name')->groupBy('passenger_id', 'passenger_name');
            $columns = ['passenger_id' => 'Account', 'passenger_name' => 'Passenger'];
            $query->orderBy('passenger_name');
        } elseif ($request->report === 'routes') {
            $query->select('route_id', 'route_name')->groupBy('route_id', 'route_name')->orderBy('route_name');
            $columns = ['route_name' => 'Route'];
        } else {
            $query->selectRaw('DATE(created_at) as date')->groupByRaw('DATE(created_at)')->orderByDesc('date');
            $columns = ['date' => 'Booking date'];
            if ($request->report === 'cancellations') { $query->where('cancelled_seats', '>', 0); }
        }
        $query->selectRaw("COUNT(*) as bookings, SUM(seat_count) as seats, SUM(active_seats) as active_seats,
            SUM(confirmed_seats) as confirmed_seats, SUM(cancelled_seats) as cancelled_seats,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(confirmed_sales) as confirmed_sales, SUM(refund_amount) as recorded_refunds");
        $columns += ['bookings' => 'Bookings', 'seats' => 'Original seats', 'active_seats' => 'Active seats', 'confirmed_seats' => 'Confirmed seats',
            'cancelled_seats' => 'Cancelled seats', 'pending_bookings' => 'Pending bookings', 'confirmed_sales' => 'Active confirmed sales', 'recorded_refunds' => 'Recorded refund liability'];
        return [$query, $columns];
    }

    public function reports(Request $request)
    {
        $this->authorizeAccess($request, 'view-reports'); $this->filters($request);
        [$query, $columns] = $this->reportQuery($request);
        $page = (clone $query)->paginate($request->input('per_page', 25));
        $summaryQuery = DB::query()->fromSub((clone $query)->reorder(), 'report');
        $summary = $this->userReports->supports($request->report) ? [] : ($request->report === 'payments'
            ? (array) $summaryQuery->selectRaw("COUNT(*) as records, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_payments, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments, SUM(CASE WHEN status = 'review_required' THEN 1 ELSE 0 END) as review_required, SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_payments")->first()
            : (array) $summaryQuery->selectRaw('COALESCE(SUM(bookings),0) as bookings, COALESCE(SUM(confirmed_sales),0) as confirmed_sales, COALESCE(SUM(recorded_refunds),0) as recorded_refunds')->first());
        if ($this->userReports->supports($request->report)) {
            $summary = $this->userReports->summary($request, $query);
        }
        $items = array_map(function ($row) use ($request) { return $this->presentReportRow($request, $row); }, $page->items());
        return $this->response(array_merge($this->data->pagination($page, $items), [
            'columns' => collect($columns)->map(function ($label, $key) { return compact('key', 'label'); })->values()->all(),
            'summary' => $summary, 'note' => $this->userReports->supports($request->report) ? $this->userReports->note($request) : 'Dates refer to booking creation (payment creation for payment reports). Recorded refunds are accounting amounts, not proof of gateway payout.',
        ]));
    }

    private function presentReportRow(Request $request, $row): array
    {
        return $this->userReports->supports($request->report) ? $this->userReports->present($request, $row) : (array) $row;
    }

    public function exportReports(Request $request)
    {
        $this->authorizeAccess($request, 'view-reports'); $this->authorizeAccess($request, 'export-reports'); $this->filters($request);
        $request->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
        abort_if(Carbon::parse($request->from)->diffInDays(Carbon::parse($request->to)) > 366, 422, 'Export a date range of one year or less.');
        [$query, $columns] = $this->reportQuery($request);
        abort_if(DB::query()->fromSub((clone $query)->reorder(), 'r')->count() > 10000, 422, 'Narrow the filters to export 10,000 rows or fewer.');
        $this->data->audit($request, 'report_exported', 'report', $request->report, 'CSV export', [
            'from' => $request->from, 'to' => $request->to, 'environment' => $request->input('environment', 'live'), 'terminal_id' => $request->terminal_id, 'route_id' => $request->route_id,
            'status' => $request->status, 'verification' => $request->verification,
        ], Scope::terminal($request), $request->filled('route_id') ? (int) $request->route_id : null);
        return response()->streamDownload(function () use ($query, $columns, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_values($columns));
            foreach ($query->cursor() as $row) {
                $item = $this->presentReportRow($request, $row);
                $cells = [];
                foreach (array_keys($columns) as $key) {
                    $value = $item[$key] ?? '';
                    // Treat exported user-provided names/references as text in spreadsheet software.
                    if (is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value)) { $value = "'" . $value; }
                    $cells[] = $value;
                }
                fputcsv($out, $cells);
            }
            fclose($out);
        }, 'mobile-app-' . $request->report . '-' . $request->from . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function activity(Request $request)
    {
        $this->authorizeAccess($request, 'view-activity'); $this->filters($request); $this->data->requireCapability('audit');
        $query = DB::table('mobile_app_admin_audits as a')->where('a.company_id', $request->user()->company_id);
        $terminal = Scope::terminal($request);
        $global = $this->data->globalPassengers($request);
        $user = $request->user();
        $configurationAudits = Scope::isCompanyAdmin($user) && !$request->filled('terminal_id');
        $accountAuditIds = null;
        if (Scope::isCompanyAdmin($user) && Scope::allows($user, 'mobile-app-view-users')
            && !$request->filled('terminal_id') && !$request->filled('route_id') && $this->data->capabilities()['users']) {
            $accountAuditIds = $this->data->users($request->duplicate([], []), false)->select('a.id');
        }
        if ($terminal !== null || $user->route_ids !== 'all' || !$global) {
            $query->where(function ($visible) use ($user, $terminal, $global, $configurationAudits, $accountAuditIds) {
                $visible->where(function ($records) use ($user, $terminal, $global) {
                    if ($terminal !== null) { $records->where('a.terminal_id', $terminal); }
                    if ($user->route_ids !== 'all') {
                        $assigned = is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true);
                        $records->where(function ($routes) use ($assigned, $global) {
                            $routes->whereIn('a.route_id', is_array($assigned) ? array_map('intval', $assigned) : []);
                            if ($global) { $routes->orWhereNull('a.route_id'); }
                        });
                    } elseif (!$global) { $records->whereNotNull('a.route_id'); }
                });
                if ($configurationAudits) {
                    $visible->orWhere(function ($configuration) {
                        $configuration->whereNull('a.terminal_id')->whereNull('a.route_id')
                            ->whereIn('a.subject_type', ['app_config', 'app_policy', 'fleet_media']);
                    });
                }
                if ($accountAuditIds !== null) {
                    $visible->orWhere(function ($accounts) use ($accountAuditIds) {
                        $accounts->whereNull('a.terminal_id')->whereNull('a.route_id')
                            ->where('a.subject_type', 'passenger')->whereIn('a.subject_id', $accountAuditIds);
                    });
                }
            });
        }
        if ($request->filled('route_id')) { $query->where('a.route_id', (int) $request->route_id); }
        $query->leftJoin('users as u', function ($join) { $join->on('u.id', '=', 'a.actor_id')->on('u.company_id', '=', 'a.company_id'); })
            ->select('a.id', 'a.action', 'a.subject_type', 'a.subject_id', 'a.reason', 'a.changes', 'a.created_at', 'u.name as actor_name');
        $this->data->dates($request, $query, 'a.created_at');
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) { $q->where('a.action', 'like', '%' . addcslashes($request->search, '%_\\') . '%')->orWhere('a.subject_id', $request->search); });
        }
        $page = $query->orderByDesc('a.id')->paginate($request->input('per_page', 25));
        $items = $page->getCollection()->map(function ($row) { $item = (array) $row; $item['changes'] = json_decode($row->changes ?? '{}', true); return $item; })->all();
        return $this->response($this->data->pagination($page, $items));
    }
}
