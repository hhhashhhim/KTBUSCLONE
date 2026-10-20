<?php

namespace App\Services;

use App\Support\MobileAppScope as Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read only projections of the deployed passenger API schema. Never infer app ownership from a phone number. */
class MobileAppAdminData
{
    public function capabilities(): array
    {
        $users = Schema::hasTable('passenger_accounts') && Schema::hasTable('customers');
        return [
            'users' => $users,
            'bookings' => $users && Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'passenger_account_id') && Schema::hasTable('tickets'),
            'payments' => Schema::hasTable('mobile_payments'),
            'settings' => Schema::hasTable('mobile_app_configs'),
            'content' => Schema::hasTable('mobile_fleet_media'),
            'controls' => Schema::hasTable('mobile_app_passenger_controls'),
            'audit' => Schema::hasTable('mobile_app_admin_audits'),
        ];
    }

    public function requireCapability(string $name): void
    {
        abort_unless($this->capabilities()[$name] ?? false, 503,
            'The mobile ' . $name . ' database is not installed. Install the existing mobile backend schema and the ERP Mobile App module migrations.');
    }

    public function globalPassengers(Request $request): bool
    {
        return Scope::allows($request->user(), 'mobile-app-all-terminals') && Scope::allCompanyRoutes($request->user());
    }

    public function accountControlScope(Request $request): bool
    {
        // Action privileges and the target passenger's visibility are checked separately.
        // This grants no additional access to booking records or passenger lists.
        return Scope::isCompanyAdmin($request->user()) || $this->globalPassengers($request);
    }

    public function configurationScope(Request $request): bool
    {
        // Company configuration is administered by Admin even when booking routes are assigned individually.
        // Keep passenger/booking data scope independent of this configuration-only exception.
        return Scope::isCompanyAdmin($request->user()) || $this->globalPassengers($request);
    }

    public function boundary(Request $request, $query, string $alias)
    {
        $query->where($alias . '.company_id', $request->user()->company_id);
        $terminal = Scope::terminal($request);
        if ($terminal !== null) { $query->where($alias . '.terminal_id', $terminal); }
        if ($request->user()->route_ids !== 'all') {
            $routes = $request->user()->route_ids;
            $routes = is_array($routes) ? $routes : json_decode($routes ?? '[]', true);
            $query->whereIn($alias . '.route_id', is_array($routes) ? array_map('intval', $routes) : []);
        }
        if ($request->filled('route_id')) { $query->where($alias . '.route_id', (int) $request->input('route_id')); }
        return $query;
    }

    public function dates(Request $request, $query, string $column): void
    {
        if ($request->filled('from')) { $query->where($column, '>=', $request->from . ' 00:00:00'); }
        if ($request->filled('to')) { $query->where($column, '<', \Carbon\Carbon::parse($request->to)->addDay()->toDateString() . ' 00:00:00'); }
    }

    public function environment(Request $request, $query, string $alias): void
    {
        $environment = $request->input('environment', 'live');
        if ($environment === 'all') { return; }
        if (!Schema::hasTable('mobile_payments') || !Schema::hasColumn('mobile_payments', 'environment')) {
            if ($environment === 'sandbox') { $query->whereRaw('1 = 0'); }
            return;
        }
        if ($environment === 'sandbox') { $query->where($alias . '.environment', 'sandbox'); }
        else {
            $query->where(function ($q) use ($alias) {
                $q->whereNull($alias . '.id')->orWhereNull($alias . '.environment')->orWhere($alias . '.environment', 'live');
            });
        }
    }

    public function users(Request $request, bool $filterDates = true)
    {
        $this->requireCapability('users');
        $query = DB::table('passenger_accounts as a')->leftJoin('customers as c', function ($join) {
            $join->on('c.id', '=', 'a.customer_id')->on('c.company_id', '=', 'a.company_id');
        })->where('a.company_id', $request->user()->company_id);
        if ($this->capabilities()['controls']) {
            $query->leftJoin('mobile_app_passenger_controls as ctl', function ($join) {
                $join->on('ctl.passenger_account_id', '=', 'a.id')->on('ctl.company_id', '=', 'a.company_id');
            })->selectRaw("COALESCE(ctl.status, 'active') as status");
        } else { $query->selectRaw("'active' as status"); }
        // Accounts have no terminal. Restricted staff can only find accounts with visible bookings.
        if (!$this->globalPassengers($request) || $request->filled('terminal_id') || $request->filled('route_id')) {
            if (!$this->capabilities()['bookings']) { $query->whereRaw('1 = 0'); }
            else {
                $visible = $this->boundary($request, DB::table('invoices as vi'), 'vi');
                $query->whereIn('a.id', $visible->whereNotNull('vi.passenger_account_id')->select('vi.passenger_account_id'));
            }
        }
        $query->addSelect(['a.id', 'a.company_id', 'a.mobile', 'a.email', 'a.mobile_verified_at', 'a.created_at', 'c.name', 'c.cnic']);
        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($search, $request) {
                $q->where('c.name', 'like', $search)->orWhere('a.mobile', 'like', $search)->orWhere('a.email', 'like', $search);
                if (ctype_digit((string) $request->search)) { $q->orWhere('a.id', $request->search); }
                if (Scope::allows($request->user(), 'mobile-app-view-users') && Scope::allows($request->user(), 'mobile-app-view-sensitive-users')
                    && preg_match('/^[0-9\\s-]+$/', $request->search)) {
                    $digits = preg_replace('/\\D/', '', $request->search);
                    if ($digits !== '') { $q->orWhereRaw("REPLACE(REPLACE(c.cnic, '-', ''), ' ', '') LIKE ?", [$digits . '%']); }
                }
            });
        }
        if (in_array($request->status, ['active', 'blocked', 'blacklisted'], true)) {
            if ($this->capabilities()['controls']) { $query->whereRaw("COALESCE(ctl.status, 'active') = ?", [$request->status]); }
            elseif ($request->status !== 'active') { $query->whereRaw('1 = 0'); }
        }
        if ($request->status === 'verified') { $query->whereNotNull('a.mobile_verified_at'); }
        if ($request->status === 'unverified') { $query->whereNull('a.mobile_verified_at'); }
        if ($filterDates) { $this->dates($request, $query, 'a.created_at'); }
        return $query;
    }

    public function presentUser(Request $request, $row): array
    {
        $sensitive = (Scope::allows($request->user(), 'mobile-app-view-users') && Scope::allows($request->user(), 'mobile-app-view-sensitive-users'));
        $mobile = (string) $row->mobile;
        $cnic = preg_replace('/\D/', '', $row->cnic ?? '');
        return [
            'id' => $row->id, 'name' => $row->name, 'mobile' => $sensitive ? $mobile : $this->mask($mobile),
            'email' => $sensitive ? $row->email : ($row->email ? '***@' . substr(strrchr($row->email, '@') ?: '', 1) : null),
            'cnic_masked' => $this->mask($cnic), 'cnic' => $sensitive ? $cnic : null,
            'status' => $row->status, 'mobile_verified_at' => $row->mobile_verified_at, 'created_at' => $row->created_at,
        ];
    }

    public function mask(?string $value): ?string
    {
        return $value ? str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4) : null;
    }

    public function ticketSummary(Request $request, bool $filter = true)
    {
        // Start with mobile invoices so the company/invoice ticket index avoids scanning the entire ERP history.
        // Keep deleted invoices and tickets: cancellations and deleted passenger accounts remain reportable.
        $query = $this->boundary($request, DB::table('invoices as ti'), 'ti')
            ->whereNotNull('ti.passenger_account_id')
            ->join('tickets as t', function ($join) {
                $join->on('t.invoice_id', '=', 'ti.id')->on('t.company_id', '=', 'ti.company_id');
            });
        if ($filter) { $this->dates($request, $query, 'ti.created_at'); }
        return $this->boundary($request, $query, 't')->where('t.type', '!=', 'reschedule')->groupBy('t.invoice_id')
            ->select('t.invoice_id')->selectRaw("COUNT(*) as seat_count,
                SUM(CASE WHEN t.deleted_at IS NULL AND t.type IN ('booked', 'advance booking') THEN 1 ELSE 0 END) as active_seats,
                SUM(CASE WHEN t.deleted_at IS NULL AND t.type = 'booked' THEN 1 ELSE 0 END) as confirmed_seats,
                SUM(CASE WHEN t.deleted_at IS NOT NULL OR t.type = 'canceled' THEN 1 ELSE 0 END) as cancelled_seats,
                SUM(t.seat_fare - COALESCE(t.discount, 0)) as total_fare,
                SUM(CASE WHEN t.deleted_at IS NULL AND t.type = 'booked' THEN t.seat_fare - COALESCE(t.discount, 0) ELSE 0 END) as confirmed_sales,
                SUM(CASE WHEN t.deleted_at IS NOT NULL OR t.type = 'canceled' THEN COALESCE(t.refund_amount, 0) ELSE 0 END) as refund_amount");
    }

    public function invoices(Request $request, bool $filter = true)
    {
        $this->requireCapability('bookings');
        $query = $this->boundary($request, DB::table('invoices as i'), 'i')
            ->joinSub($this->ticketSummary($request, $filter), 'ts', 'ts.invoice_id', '=', 'i.id')
            ->leftJoin('passenger_accounts as a', function ($join) { $join->on('a.id', '=', 'i.passenger_account_id')->on('a.company_id', '=', 'i.company_id'); })
            ->leftJoin('customers as c', function ($join) { $join->on('c.id', '=', 'a.customer_id')->on('c.company_id', '=', 'a.company_id'); })
            ->leftJoin('terminals as tm', function ($join) { $join->on('tm.id', '=', 'i.terminal_id')->on('tm.company_id', '=', 'i.company_id'); })
            ->leftJoin('routes as r', function ($join) { $join->on('r.id', '=', 'i.route_id')->on('r.company_id', '=', 'i.company_id'); })
            ->select(['i.id', 'i.id as invoice_id', 'i.passenger_account_id as passenger_id', 'i.terminal_id', 'i.route_id', 'i.schedule_id',
                'i.created_at', 'i.schedule_date as travel_date', 'i.schedule_time as travel_time', 'a.mobile as passenger_mobile',
                'tm.name as terminal_name', 'r.name as route_name', 'ts.seat_count', 'ts.active_seats', 'ts.confirmed_seats', 'ts.cancelled_seats',
                'ts.total_fare', 'ts.confirmed_sales', 'ts.refund_amount'])->selectRaw("COALESCE(c.name, 'Deleted or unavailable passenger account') as passenger_name")->whereNotNull('i.passenger_account_id');
        $payments = $this->capabilities()['payments'];
        if ($payments) {
            $query->leftJoin('mobile_payments as p', function ($join) {
                $join->on('p.invoice_id', '=', 'i.id')->on('p.company_id', '=', 'i.company_id')->on('p.passenger_account_id', '=', 'i.passenger_account_id');
            })->addSelect('p.status as gateway_status', 'p.method as payment_method', 'p.paid_at', 'p.id as payment_id')
                ->selectRaw("CASE WHEN p.id IS NULL THEN CASE WHEN ts.confirmed_seats > 0 THEN 'recorded_booking' ELSE 'unrecorded' END WHEN p.paid_at IS NOT NULL THEN 'paid' ELSE p.status END as payment_status");
        } else {
            $query->selectRaw("NULL as gateway_status, NULL as payment_method, NULL as paid_at, NULL as payment_id, CASE WHEN ts.confirmed_seats > 0 THEN 'recorded_booking' ELSE 'unrecorded' END as payment_status");
        }
        $status = "CASE WHEN " . ($payments ? "p.status = 'review_required' THEN 'review_required' WHEN " : '')
            . "ts.active_seats = 0 THEN 'canceled' WHEN ts.cancelled_seats > 0 THEN 'partially_cancelled' WHEN ts.confirmed_seats = ts.active_seats THEN 'confirmed' "
            . ($payments ? "WHEN p.status = 'expired' THEN 'expired' " : '') . "ELSE 'pending' END";
        $query->selectRaw($status . ' as status');
        if ($filter) {
            if ($payments) { $this->environment($request, $query, 'p'); }
            elseif ($request->input('environment') === 'sandbox') { $query->whereRaw('1 = 0'); }
            $this->dates($request, $query, 'i.created_at');
            if ($request->filled('status')) { $query->whereRaw($status . ' = ?', [$request->status]); }
            if ($request->filled('search')) {
                $search = '%' . addcslashes($request->search, '%_\\') . '%';
                $query->where(function ($q) use ($request, $search) {
                    $q->where('c.name', 'like', $search)->orWhere('a.mobile', 'like', $search);
                    if (ctype_digit((string) $request->search)) { $q->orWhere('i.id', $request->search); }
                });
            }
        }
        return $query;
    }

    public function presentBookings(Request $request, $rows, bool $detail = false): array
    {
        if ($rows->isEmpty()) { return []; }
        $tickets = $this->boundary($request, DB::table('tickets as t'), 't')->whereIn('t.invoice_id', $rows->pluck('invoice_id'))
            ->select('t.id', 't.invoice_id', 't.seat_no', 't.type', 't.seat_fare', 't.discount', 't.refund_amount', 't.deleted_at', 't.schedule_date', 't.schedule_time', 't.ticket_closing_id')
            ->orderBy('t.id')->get()->groupBy('invoice_id');
        return $rows->map(function ($row) use ($request, $tickets, $detail) {
            $item = (array) $row;
            $item['passenger_mobile'] = (Scope::allows($request->user(), 'mobile-app-view-users') && Scope::allows($request->user(), 'mobile-app-view-sensitive-users')) ? $row->passenger_mobile : $this->mask($row->passenger_mobile);
            foreach (['total_fare', 'confirmed_sales', 'refund_amount'] as $key) { $item[$key] = round((float) $item[$key], 2); }
            foreach (['seat_count', 'active_seats', 'confirmed_seats', 'cancelled_seats'] as $key) { $item[$key] = (int) $item[$key]; }
            $seats = $tickets->get($row->invoice_id, collect());
            $item['seat_numbers'] = $seats->where('type', '!=', 'reschedule')->pluck('seat_no')->map(function ($n) { return (string) $n; })->all();
            $item['departure'] = trim(($row->travel_date ?? '') . ' ' . ($row->travel_time ?? ''));
            if ($detail) { $item['tickets'] = $seats->map(function ($t) { $item = (array) $t; $item['net_fare'] = round($t->seat_fare - ($t->discount ?? 0), 2); return $item; })->all(); }
            return $item;
        })->all();
    }

    public function payments(Request $request, bool $filter = true)
    {
        $this->requireCapability('payments');
        $this->requireCapability('bookings');
        $bookings = $this->invoices($request, false);
        $query = DB::table('mobile_payments as pay')->joinSub($bookings, 'b', function ($join) {
            $join->on('b.invoice_id', '=', 'pay.invoice_id')->on('b.passenger_id', '=', 'pay.passenger_account_id');
        })->where('pay.company_id', $request->user()->company_id)
            ->select(['pay.id', 'pay.invoice_id', 'pay.method', 'pay.transaction_reference', 'pay.status', 'pay.amount_minor', 'pay.currency',
                'pay.paid_at', 'pay.expires_at', 'pay.checked_at', 'pay.created_at', 'b.passenger_name', 'b.terminal_id', 'b.route_id', 'b.active_seats', 'b.confirmed_seats'])
            ->selectRaw(Schema::hasColumn('mobile_payments', 'environment') ? 'pay.environment' : "'unknown' as environment")
            ->selectRaw("CASE WHEN pay.status = 'review_required' OR (pay.paid_at IS NOT NULL AND (b.confirmed_seats <> b.seat_count OR b.active_seats = 0)) THEN 1 ELSE 0 END as requires_review");
        if ($filter) {
            $this->environment($request, $query, 'pay');
            $this->dates($request, $query, 'pay.created_at');
            if ($request->filled('status')) { $query->where('pay.status', $request->status); }
            if ($request->boolean('exceptions')) { $query->where(function ($q) { $q->where('pay.status', 'review_required')->orWhere(function ($q) { $q->whereNotNull('pay.paid_at')->whereColumn('b.confirmed_seats', '<>', 'b.seat_count'); }); }); }
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) { $q->where('pay.transaction_reference', $request->search)->orWhere('b.passenger_name', 'like', '%' . addcslashes($request->search, '%_\\') . '%'); if (ctype_digit((string) $request->search)) { $q->orWhere('pay.invoice_id', $request->search); } });
            }
        }
        return $query;
    }

    public function presentPayment($row): array
    {
        $item = (array) $row;
        $item['amount'] = round((float) $row->amount_minor / 100, 2);
        $item['requires_review'] = (bool) $row->requires_review;
        unset($item['amount_minor']);
        return $item;
    }

    public function pagination($page, array $items): array
    {
        return ['items' => $items, 'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]];
    }

    public function audit(Request $request, string $action, string $subject, $id, string $reason, array $changes = [], ?int $terminal = null, ?int $route = null): void
    {
        $this->requireCapability('audit');
        DB::table('mobile_app_admin_audits')->insert([
            'company_id' => $request->user()->company_id, 'terminal_id' => $terminal, 'route_id' => $route,
            'actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => $subject, 'subject_id' => (string) $id,
            'reason' => $reason, 'changes' => json_encode($changes), 'created_at' => now(),
        ]);
    }

    public function revision($row): string
    {
        return hash('sha256', json_encode((array) $row));
    }
}
