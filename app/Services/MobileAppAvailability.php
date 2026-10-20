<?php

namespace App\Services;

use App\Support\MobileAppScope as Scope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only explanation of MobileSchedulePolicy; never changes booking authority. */
class MobileAppAvailability
{
    private function base(Request $request)
    {
        $query = DB::table('routes as r')->where('r.company_id', $request->user()->company_id)->whereNull('r.deleted_at');
        if ($request->user()->route_ids !== 'all') {
            $ids = $request->user()->route_ids;
            $ids = is_array($ids) ? $ids : json_decode($ids ?? '[]', true);
            $query->whereIn('r.id', is_array($ids) ? array_map('intval', $ids) : []);
        }
        return $query;
    }

    private function context(Request $request): array
    {
        foreach (['route_online_terminals', 'schedules', 'schedule_details', 'schedule_terminal_visibilities',
            'terminal_visibilities', 'terminal_time_differences', 'drop_schedules', 'cities', 'bus_classes'] as $table) {
            abort_unless(Schema::hasTable($table), 503, 'Schedule monitoring is unavailable until the existing scheduling tables are installed.');
        }
        $company = (int) $request->user()->company_id;
        $runtime = Schema::hasTable('mobile_app_runtime') ? DB::table('mobile_app_runtime')->where('company_id', $company)->first() : null;
        $terminalId = $request->filled('mobile_terminal_id') ? (int) $request->mobile_terminal_id : (int) ($runtime->terminal_id ?? 0);
        $query = DB::table('terminals')->where('company_id', $company)->whereNull('deleted_at')->where('is_online_terminal', 1);
        if (!Scope::allows($request->user(), 'mobile-app-all-terminals')) { $query->where('id', $request->user()->terminal_id ?: -1); }
        $terminals = $query->orderBy('name')->get(['id', 'name', 'hide', 'advance_booking']);
        $terminal = $terminals->firstWhere('id', $terminalId);
        abort_if($terminalId && !$terminal, 403, 'This online terminal is outside your permitted scope.');
        $confirmed = $runtime && $terminal && (int) $runtime->terminal_id === $terminalId;
        $bookingUser = $confirmed ? DB::table('users')->where('id', $runtime->booking_user_id)->where('company_id', $company)
            ->where('hide', 0)->whereNull('deleted_at')->first(['id', 'check_booking_minutes']) : null;
        return compact('company', 'runtime', 'terminal', 'terminals', 'confirmed', 'bookingUser');
    }

    private function publicContext(array $context): array
    {
        $maintenance = Schema::hasTable('mobile_app_configs') && DB::table('mobile_app_configs')->where('company_id', $context['company'])->value('maintenance_mode');
        $notice = $maintenance ? 'The app is in maintenance mode.' : (!MobileAppControlData::policy($context['company'])['bookings_enabled'] ? 'New app bookings are paused in App Controls.' : null);
        return ['booking_notice' => $notice, 'terminal_id' => $context['terminal']->id ?? null, 'terminal_name' => $context['terminal']->name ?? null,
            'terminals' => $context['terminals']->map(function ($t) { return ['id' => $t->id, 'name' => $t->name]; })->all(),
            'confirmed' => (bool) $context['confirmed'],
            'last_seen_at' => $context['runtime'] ? Carbon::parse($context['runtime']->last_seen_at, 'UTC')->toIso8601String() : null];
    }

    private function routeState($route, array $context, $assignments): array
    {
        if (!$context['terminal']) { return ['needs_setup', 'Choose the app’s online terminal.']; }
        if ($context['terminal']->hide === null || (int) $context['terminal']->hide !== 0) { return ['not_allowed', 'Online terminal is inactive.']; }
        if ($route->hide === null || (int) $route->hide !== 0) { return ['not_allowed', 'Route is hidden.']; }
        $mapped = $assignments->get($route->id, collect());
        if ($mapped->isNotEmpty() && !$mapped->contains((int) $context['terminal']->id)) { return ['not_allowed', 'Route is assigned to other online terminals.']; }
        return ['allowed', 'Online terminal is allowed on this route.'];
    }

    private function mappings(int $company, array $ids)
    {
        return DB::table('route_online_terminals')->where('company_id', $company)->whereIn('route_id', $ids)
            ->get(['route_id', 'terminal_id'])->groupBy('route_id')->map(function ($rows) { return $rows->pluck('terminal_id')->map(function ($id) { return (int) $id; }); });
    }

    public function routes(Request $request): array
    {
        $context = $this->context($request);
        $query = $this->base($request);
        if ($request->filled('search')) { $query->where('r.name', 'like', '%' . addcslashes($request->search, '%_\\') . '%'); }
        $routes = $query->orderBy('r.name')->get(['r.id', 'r.name', 'r.hide']);
        $mapping = $this->mappings($context['company'], $routes->pluck('id')->all());
        $runQuery = DB::table('schedule_details as d')->join('schedules as s', function ($join) { $join->on('s.id', '=', 'd.schedule_id')->on('s.company_id', '=', 'd.company_id'); })
            ->where('d.company_id', $context['company'])->where('d.departure_date', $request->date)
            ->whereNull('d.deleted_at')->whereNull('s.deleted_at')->whereIn('s.route_id', $routes->pluck('id'))
            ->select('s.route_id', 'd.schedule_id', 'd.schedule_date')->distinct();
        $runs = DB::query()->fromSub($runQuery, 'runs')->groupBy('route_id')->selectRaw('route_id, COUNT(*) as schedules')->get()->keyBy('route_id');
        $items = $routes->map(function ($route) use ($context, $mapping, $runs) {
            [$status, $reason] = $this->routeState($route, $context, $mapping);
            return ['id' => $route->id, 'name' => $route->name, 'status' => $status, 'reason' => $reason,
                'schedules' => (int) ($runs->get($route->id)->schedules ?? 0)];
        });
        $summary = ['total' => $items->count(), 'allowed' => $items->where('status', 'allowed')->count(), 'not_allowed' => $items->where('status', 'not_allowed')->count()];
        if ($request->filled('status')) { $items = $items->where('status', $request->status)->values(); }
        return ['context' => $this->publicContext($context), 'summary' => $summary] + $this->page($items, $request);
    }

    public function schedules(Request $request): array
    {
        $context = $this->context($request);
        $route = $this->base($request)->where('r.id', $request->route_id)->first(['r.id', 'r.name', 'r.hide']);
        abort_unless($route, 404, 'Route not found in your permitted scope.');
        [$routeStatus, $routeReason] = $this->routeState($route, $context, $this->mappings($context['company'], [$route->id]));
        $rows = DB::table('schedule_details as d')->join('schedules as s', function ($join) { $join->on('s.id', '=', 'd.schedule_id')->on('s.company_id', '=', 'd.company_id'); })
            ->leftJoin('cities as origin', function ($join) { $join->on('origin.id', '=', 'd.departure_id')->on('origin.company_id', '=', 'd.company_id'); })
            ->leftJoin('cities as destination', function ($join) { $join->on('destination.id', '=', 'd.destination_id')->on('destination.company_id', '=', 'd.company_id'); })
            ->leftJoin('bus_classes as bus', function ($join) { $join->on('bus.id', '=', 'd.bus_class_id')->on('bus.company_id', '=', 'd.company_id'); })
            ->where('d.company_id', $context['company'])->where('s.route_id', $route->id)->where('d.departure_date', $request->date)
            ->whereNull('s.deleted_at')->whereNull('d.deleted_at')->orderBy('d.departure_time')->orderBy('d.id')
            ->get(['d.id', 'd.schedule_id', 'd.schedule_date', 'd.departure_date', 'd.departure_time', 'd.departure_id', 'd.destination_id',
                's.name as schedule_name', 's.hide as schedule_hidden', 'origin.name as origin', 'origin.hide as origin_hidden', 'origin.deleted_at as origin_deleted',
                'destination.name as destination', 'destination.hide as destination_hidden', 'destination.deleted_at as destination_deleted', 'bus.name as bus_class']);
        $ids = $rows->pluck('schedule_id')->unique();
        $visibility = DB::table('schedule_terminal_visibilities')->where('company_id', $context['company'])->where('terminal_id', $context['terminal']->id ?? -1)
            ->whereNull('deleted_at')->where('visibility', 1)->whereIn('schedule_id', $ids)->pluck('schedule_id')->map(function ($id) { return (int) $id; })->all();
        $drops = DB::table('drop_schedules')->where('company_id', $context['company'])->whereNull('deleted_at')
            ->whereIn('schedule_id', $ids)->whereIn('schedule_date', $rows->pluck('schedule_date')->unique())->get(['schedule_id', 'schedule_date'])
            ->mapWithKeys(function ($row) { return [$row->schedule_id . ':' . $row->schedule_date => true]; });
        $pairs = DB::table('terminal_visibilities')->where('company_id', $context['company'])->where('route_id', $route->id)->whereNull('deleted_at')->orderBy('id')->get();
        $offset = DB::table('terminal_time_differences')->where('company_id', $context['company'])->where('route_id', $route->id)
            ->where('terminal_id', $context['terminal']->id ?? -1)->whereNull('deleted_at')->value('time_difference');
        $items = $rows->map(function ($row) use ($context, $routeStatus, $routeReason, $visibility, $drops, $pairs, $offset) {
            $status = $routeStatus; $reason = $routeReason;
            $matching = $pairs->filter(function ($p) use ($row) { return (int) $p->departure_city_id === (int) $row->departure_id && (int) $p->destination_city_id === (int) $row->destination_id; });
            $pair = $matching->first();
            if ($status === 'allowed') {
                if ($row->schedule_hidden === null || (int) $row->schedule_hidden !== 0) { $status = 'not_allowed'; $reason = 'Schedule is hidden.'; }
                elseif (!$row->origin || !$row->destination || $row->origin_hidden === null || (int) $row->origin_hidden !== 0 || $row->destination_hidden === null || (int) $row->destination_hidden !== 0 || $row->origin_deleted || $row->destination_deleted) { $status = 'not_allowed'; $reason = 'A journey city is unavailable.'; }
                elseif (!in_array((int) $row->schedule_id, $visibility, true)) { $status = 'not_allowed'; $reason = 'Schedule is not enabled for this online terminal.'; }
                elseif ($matching->contains(function ($p) { return (int) $p->online_visibilty === 1; })) { $status = 'not_allowed'; $reason = 'This city pair is hidden for online booking.'; }
                elseif ($drops->has($row->schedule_id . ':' . $row->schedule_date)) { $status = 'not_allowed'; $reason = 'Departure is cancelled.'; }
                elseif ($context['terminal']->advance_booking !== null && $row->departure_date >= now('Asia/Karachi')->addDays((int) $context['terminal']->advance_booking)->toDateString()) { $status = 'not_open'; $reason = 'Outside the advance booking window.'; }
                elseif ($context['confirmed'] && !$context['bookingUser']) { $status = 'needs_setup'; $reason = 'App booking account needs configuration.'; }
                elseif (!$context['confirmed'] && $pair && $pair->booking_minutes !== null && (int) $pair->booking_minutes >= 0) { $status = 'needs_setup'; $reason = 'Opening time needs the app connection.'; }
                elseif ($context['bookingUser'] && $context['bookingUser']->check_booking_minutes && $pair && $pair->booking_minutes !== null && (int) $pair->booking_minutes >= 0) {
                    $opens = Carbon::parse($row->departure_date . ' ' . $row->departure_time, 'Asia/Karachi')->addMinutes((int) $offset)->subMinutes((int) $pair->booking_minutes);
                    if (now('Asia/Karachi')->lt($opens)) { $status = 'not_open'; $reason = 'Booking opens ' . $opens->format('d M, g:i A') . ' PKT.'; }
                }
            }
            if ($status === 'allowed') { $reason = 'Shown in app search. Seat and fare checks apply when booking.'; }
            return ['id' => $row->id, 'schedule_id' => $row->schedule_id, 'name' => $row->schedule_name, 'origin' => $row->origin,
                'destination' => $row->destination, 'departure_time' => $row->departure_time, 'bus_class' => $row->bus_class, 'status' => $status, 'reason' => $reason];
        });
        $summary = ['total' => $items->count(), 'allowed' => $items->where('status', 'allowed')->count(),
            'not_allowed' => $items->where('status', 'not_allowed')->count(), 'not_open' => $items->where('status', 'not_open')->count(), 'needs_setup' => $items->where('status', 'needs_setup')->count()];
        if ($request->filled('status')) { $items = $items->where('status', $request->status)->values(); }
        return ['context' => $this->publicContext($context), 'route' => ['id' => $route->id, 'name' => $route->name], 'summary' => $summary] + $this->page($items, $request);
    }

    private function page($items, Request $request): array
    {
        $page = (int) $request->input('page', 1); $size = 25;
        return ['items' => $items->slice(($page - 1) * $size, $size)->values()->all(),
            'pagination' => ['total' => $items->count(), 'current_page' => $page, 'last_page' => max(1, (int) ceil($items->count() / $size)), 'per_page' => $size]];
    }
}
