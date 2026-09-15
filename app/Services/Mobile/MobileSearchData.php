<?php

namespace App\Services\Mobile;

use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\LimitedSeat;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\Terminal\TerminalVisibility;
use App\Models\TerminalDiscount;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

/** Data for one search only; never stored on a service or reused for booking validation. */
class MobileSearchData
{
    public $user;
    public $visibilities;
    public $offsets;
    public $limitedRoutes;
    public $terminal;
    public $fareRows;
    public $classNames;
    public $discounts;
    public $terminalDiscounts;
    public $surcharges;
    private $tickets;

    public function __construct(Collection $details, int $companyId, int $terminalId, int $originId, int $destinationId)
    {
        $this->user = User::query()->whereKey((int) config('mobile.booking_user_id'))
            ->where('company_id', $companyId)->where('hide', 0)->first();
        $this->visibilities = collect();
        $this->offsets = collect();
        if ($this->user && $this->user->check_booking_minutes) {
            $routeIds = $details->pluck('schedule.route_id')->unique();
            $this->visibilities = TerminalVisibility::query()->where('company_id', $companyId)
                ->whereIn('route_id', $routeIds)->where('departure_city_id', $originId)
                ->where('destination_city_id', $destinationId)->get()->unique('route_id')->keyBy('route_id');
            $this->offsets = TerminalTimeDifference::query()->where('company_id', $companyId)
                ->where('terminal_id', $terminalId)->whereIn('route_id', $routeIds)
                ->get()->unique('route_id')->pluck('time_difference', 'route_id');
        }
    }

    public function loadAvailability(Collection $details, int $companyId, int $terminalId, int $originId, int $destinationId, string $date): void
    {
        $routeIds = $details->pluck('schedule.route_id')->unique();
        $this->terminal = Terminal::find($terminalId);
        $this->limitedRoutes = LimitedSeat::query()->where('company_id', $companyId)
            ->whereIn('route_id', $routeIds)->where('departure_city_id', $originId)
            ->where('destination_city_id', $destinationId)->where('limited_seat', 1)
            ->pluck('route_id')->flip();
        $this->fareRows = FareTable::query()->where('company_id', $companyId)
            ->where('from_city_id', $originId)->where('to_city_id', $destinationId)
            ->get()->unique('fare_class');
        $classIds = $details->flatMap(function ($detail) {
            return collect(optional($detail->bus_class)->seat_map ?: [])->flatten(1)
                ->filter(function ($seat) {
                    return is_array($seat) && ($seat['reserved'] ?? false) && isset($seat['class']);
                })->pluck('class');
        })->unique();
        $this->classNames = FareClass::whereIn('id', $classIds)->pluck('name', 'id');
        $this->discounts = Discount::query()->whereIn('id', $details->pluck('schedule.discount_id')->filter()->unique())
            ->where('is_active', 1)->whereHas('discount_terminals', function ($query) use ($terminalId) {
                $query->where('terminal_id', $terminalId);
            })->get()->keyBy('id');
        $this->terminalDiscounts = TerminalDiscount::query()->where('terminal_id', $terminalId)
            ->whereIn('route_id', $routeIds)->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)->get()->unique('route_id')->keyBy('route_id');
        $this->surcharges = Surcharge::query()->whereIn('id', $details->pluck('schedule.surcharge_id')->filter()->unique())
            ->where('is_active', 1)->get()->keyBy('id');

        // Match exact runs, including overnight journeys with a different departure date.
        // One fresh read supplies both overlapping seats and the run-wide online quota.
        $runsByDate = $details->groupBy('schedule_date');
        $this->tickets = Ticket::query()->where('company_id', $companyId)
            ->whereNotIn('type', ['cancelled'])
            ->where(function ($runs) use ($runsByDate) {
                foreach ($runsByDate as $runDetails) {
                    // Preserve NULL for legacy runs; Collection group keys turn it into an empty string.
                    $runDate = $runDetails->first()->schedule_date;
                    $runs->orWhere(function ($run) use ($runDate, $runDetails) {
                        $run->where('schedule_date', $runDate)
                            ->whereIn('schedule_id', $runDetails->pluck('schedule_id')->unique());
                    });
                }
            })->get(['id', 'schedule_id', 'schedule_date', 'seat_no', 'departure_city_id', 'destination_city_id', 'gender', 'type', 'online_terminal'])
            ->groupBy(function ($ticket) {
                return $ticket->schedule_id . ':' . substr((string) $ticket->schedule_date, 0, 10);
            });
    }

    public function ticketsFor(ScheduleDetail $detail): Collection
    {
        return $this->tickets->get($detail->schedule_id . ':' . substr((string) $detail->schedule_date, 0, 10), collect());
    }
}
