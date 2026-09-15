<?php

namespace App\Services\Mobile;

use App\Models\LimitedSeat;
use App\Models\Schedule\DropSchedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\ScheduleTerminalVisibility;
use App\Models\Terminal;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\Terminal\TerminalVisibility;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class MobileSchedulePolicy
{
    public function eligibleDetails(int $companyId, int $terminalId, int $originId, int $destinationId, string $date): Builder
    {
        $terminal = Terminal::query()->whereKey($terminalId)
            ->where('company_id', $companyId)->where('hide', 0)->first();
        if (!$terminal || (int) $terminal->is_online_terminal !== 1) {
            throw new RuntimeException('Configure an active online terminal for mobile bookings.', 503);
        }

        $query = ScheduleDetail::query()
            ->with([
                'departure_city:id,name', 'destination_city:id,name', 'bus_class:id,name,seat_map',
                'schedule:id,route_id,bus_class_id,discount_id,surcharge_id,hide',
                'schedule.route:id,name,online_seat_choices,online_seats,hide',
                'schedule.route.fares:id,route_id,departure_city_id,destination_city_id',
            ])
            ->where('company_id', $companyId)
            ->where('departure_id', $originId)->where('destination_id', $destinationId)
            ->where('departure_date', $date)
            ->where('departure_date', '>=', now()->toDateString())
            // An empty permission set must never expose every schedule.
            ->whereIn('schedule_id', ScheduleTerminalVisibility::query()
                ->select('schedule_id')->where('company_id', $companyId)
                ->where('terminal_id', $terminalId)->where('visibility', 1))
            ->whereHas('departure_city', function ($city) use ($companyId) {
                $city->where('company_id', $companyId)->where('hide', 0);
            })
            ->whereHas('destination_city', function ($city) use ($companyId) {
                $city->where('company_id', $companyId)->where('hide', 0);
            })
            ->whereHas('schedule', function ($schedule) use ($companyId, $terminalId, $originId, $destinationId) {
                $schedule->where('company_id', $companyId)->where('hide', 0)
                    ->whereHas('route', function ($route) use ($companyId, $terminalId, $originId, $destinationId) {
                        $route->where('company_id', $companyId)->where('hide', 0)
                            ->whereNotIn('id', TerminalVisibility::query()->select('route_id')
                                ->where('company_id', $companyId)->whereNotNull('route_id')
                                ->where('departure_city_id', $originId)->where('destination_city_id', $destinationId)
                                // The spelling and inverted flag are from the existing ERP schema.
                                ->where('online_visibilty', 1))
                            ->where(function ($allowed) use ($companyId, $terminalId) {
                                $allowed->whereDoesntHave('onlineTerminalAssignments', function ($mapping) use ($companyId) {
                                    $mapping->where('company_id', $companyId);
                                })->orWhereHas('onlineTerminalAssignments', function ($mapping) use ($companyId, $terminalId) {
                                    $mapping->where('company_id', $companyId)->where('terminal_id', $terminalId);
                                });
                            });
                    });
            })
            ->whereNotIn('schedule_id', DropSchedule::query()->select('schedule_id')
                ->where('company_id', $companyId)->whereNotNull('schedule_id')
                ->whereColumn('schedule_date', 'schedule_details.schedule_date'));

        if (!is_null($terminal->advance_booking)) {
            // ERP uses an exclusive upper date: one day means today only.
            $query->where('departure_date', '<', now()->addDays((int) $terminal->advance_booking)->toDateString());
        }

        return $query;
    }

    public function bookingIsOpen(ScheduleDetail $detail, int $companyId, int $terminalId, ?MobileSearchData $search = null): bool
    {
        $user = $search ? $search->user : User::query()->whereKey((int) config('mobile.booking_user_id'))
            ->where('company_id', $companyId)->where('hide', 0)->first();
        if (!$user) {
            throw new RuntimeException('The mobile booking service user is not configured.', 503);
        }
        if (!$user->check_booking_minutes) {
            return true;
        }

        $visibility = $search ? $search->visibilities->get($detail->schedule->route_id) : TerminalVisibility::query()->where('company_id', $companyId)
            ->where('route_id', $detail->schedule->route_id)
            ->where('departure_city_id', $detail->departure_id)
            ->where('destination_city_id', $detail->destination_id)->first();
        if (!$visibility || is_null($visibility->booking_minutes) || $visibility->booking_minutes < 0) {
            return true;
        }

        $offset = $search ? $search->offsets->get($detail->schedule->route_id) : TerminalTimeDifference::query()->where('company_id', $companyId)
            ->where('terminal_id', $terminalId)->where('route_id', $detail->schedule->route_id)
            ->value('time_difference');
        // ERP booking_minutes opens sales this many minutes BEFORE adjusted departure;
        // it is not a cutoff that closes sales this many minutes before departure.
        $opensAt = Carbon::parse($detail->departure_date . ' ' . $detail->departure_time)
            ->addMinutes((int) $offset)->subMinutes((int) $visibility->booking_minutes);

        return now()->greaterThanOrEqualTo($opensAt);
    }

    public function remainingOnlineSeats(ScheduleDetail $detail, int $companyId, ?MobileSearchData $search = null): ?int
    {
        $limited = $search ? $search->limitedRoutes->has($detail->schedule->route_id) : LimitedSeat::query()->where('company_id', $companyId)
            ->where('route_id', $detail->schedule->route_id)
            ->where('departure_city_id', $detail->departure_id)
            ->where('destination_city_id', $detail->destination_id)->where('limited_seat', 1)->exists();
        if (!$limited) {
            return null;
        }

        // ERP's online quota covers the whole run, across online terminals and city pairs.
        $sold = $search ? $search->ticketsFor($detail)->where('online_terminal', 1)->count() : Ticket::query()->where('company_id', $companyId)
            ->where('schedule_id', $detail->schedule_id)->where('schedule_date', $detail->schedule_date)
            ->where('online_terminal', 1)->whereNotIn('type', ['cancelled'])->count();

        return max(0, (int) $detail->schedule->route->online_seats - $sold);
    }
}
