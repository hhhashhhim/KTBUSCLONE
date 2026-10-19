<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FareTable;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\ScheduleTerminalVisibility;
use App\Models\Terminal;
use App\Models\Terminal\TerminalTimeDifference;
use App\Support\CallCenterScope as Scope;
use App\Support\CallCenterSeatAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CallCenterInformationController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()->company_id && Scope::allows($request->user(), 'call-center-quick-answers'), 403, 'Call center access denied.');
    }

    public function options(Request $request)
    {
        $this->authorizeAccess($request);
        $terminal = Scope::terminal($request);
        return response()->json(['status' => true, 'data' => [
            // Contact directory only: booking/schedule terminal scope remains below.
            'terminal_directory' => Terminal::where('company_id', $request->user()->company_id)->where('hide', 0)
                ->whereIn('city_id', City::where('company_id', $request->user()->company_id)->where('hide', 0)->select('id'))
                ->orderBy('name')->get(['id', 'name', 'city_id', 'address', 'contact']),
            'cities' => City::where('company_id', $request->user()->company_id)->where('hide', 0)->orderBy('name')->get(['id', 'name']),
            'terminals' => Terminal::where('company_id', $request->user()->company_id)->where('hide', 0)
                ->when($terminal !== null, function ($q) use ($terminal) { $q->where('id', $terminal); })
                ->orderBy('name')->get(['id', 'name', 'city_id', 'address', 'contact']),
        ]]);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess($request);
        $request->validate(['departure_id' => 'required|integer|min:1', 'destination_id' => 'required|integer|min:1|different:departure_id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:' . Carbon::now('Asia/Karachi')->toDateString(), 'terminal_id' => 'nullable|integer|min:1']);
        $user = $request->user();
        $terminalId = Scope::terminal($request) ?? ($request->terminal_id ?: $user->terminal_id);
        $terminal = Terminal::where('company_id', $user->company_id)->where('hide', 0)->findOrFail($terminalId);
        $cityCount = City::where('company_id', $user->company_id)->where('hide', 0)->whereIn('id', [$request->departure_id, $request->destination_id])->count();
        abort_unless($cityCount === 2, 422, 'Choose departure and destination cities from the list.');
        $routeIds = $user->route_ids === 'all' ? null : (is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true));
        if ($user->route_ids !== 'all' && !is_array($routeIds)) { $routeIds = []; }
        $visible = ScheduleTerminalVisibility::where('company_id', $user->company_id)->where('terminal_id', $terminal->id)->where('visibility', 1)->select('schedule_id');
        $details = ScheduleDetail::where('company_id', $user->company_id)->whereIn('schedule_id', $visible)
            ->where('departure_id', $request->departure_id)->where('destination_id', $request->destination_id)->where('departure_date', $request->date)
            ->whereHas('schedule', function ($q) use ($user, $routeIds) {
                $q->where('company_id', $user->company_id)->where('hide', 0);
                if ($routeIds !== null) { $q->whereIn('route_id', is_array($routeIds) ? $routeIds : []); }
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('drop_schedules')->whereColumn('drop_schedules.company_id', 'schedule_details.company_id')
                    ->whereColumn('drop_schedules.schedule_id', 'schedule_details.schedule_id')->whereColumn('drop_schedules.schedule_date', 'schedule_details.schedule_date')->whereNull('drop_schedules.deleted_at');
            })
            ->with(['schedule:id,name,route_id', 'bus_class:id,name,seat_map'])->orderBy('departure_time')->limit(100)->get();
        $times = TerminalTimeDifference::where('company_id', $user->company_id)->where('terminal_id', $terminal->id)
            ->whereIn('route_id', $details->pluck('schedule.route_id'))->get()->keyBy('route_id');
        $now = Carbon::now('Asia/Karachi');
        $availableSeats = app(CallCenterSeatAvailability::class)->forDepartures($details, $user, $terminal);
        $departures = $details->map(function ($detail) use ($times, $now, $availableSeats) {
            $minutes = (int) optional($times->get($detail->schedule->route_id))->time_difference;
            $departure = Carbon::parse($detail->departure_date . ' ' . $detail->departure_time, 'Asia/Karachi')->addMinutes($minutes);
            if ($departure->lt($now)) { return null; }
            return ['id' => $detail->id, 'schedule_name' => $detail->schedule->name, 'bus_class' => optional($detail->bus_class)->name,
                'departure_at' => $departure->format('Y-m-d H:i:s'), 'available_seats' => $availableSeats[$detail->id] ?? null];
        })->filter()->values();
        // Read the existing fare table; do not duplicate booking discount/surcharge rules.
        $fares = $departures->isEmpty() ? collect() : FareTable::where('company_id', $user->company_id)->where('from_city_id', $request->departure_id)
            ->where('to_city_id', $request->destination_id)->with('class:id,name')->get()->unique('fare_class')->values()
            ->map(function ($fare) { return ['class_name' => optional($fare->class)->name ?? 'Fare class', 'base_fare' => $fare->fare]; });
        return response()->json(['status' => true, 'data' => ['departures' => $departures, 'fares' => $fares,
            'terminal' => $terminal->only(['id', 'name', 'address', 'contact']), 'checked_at' => $now->format('Y-m-d H:i:s'),
            'fare_note' => 'Base fares from the fare table. Discounts and surcharges may change the final booking price.',
            'schedule_note' => 'Pakistan time. Seats available for this journey when checked; refresh before confirming a booking.']]);
    }
}
