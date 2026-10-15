<?php

namespace App\Support;

use App\Models\Route\RouteFare;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class CallCenterSeatAvailability
{
    /** Read-only counts for the same service date, seat map and journey used by booking. */
    public function forDepartures(Collection $details, $user, $terminal): array
    {
        if ($details->isEmpty()) { return []; }

        // Occupancy includes sales from every terminal, without returning passenger data.
        $trips = $details->unique(function ($detail) { return $detail->schedule_id . ':' . $detail->schedule_date; });
        $tickets = Ticket::where('company_id', $user->company_id)
            ->where(function ($query) use ($trips) {
                foreach ($trips as $detail) {
                    $query->orWhere(function ($trip) use ($detail) {
                        $trip->where('schedule_id', $detail->schedule_id)->where('schedule_date', $detail->schedule_date);
                    });
                }
            })->get(['schedule_id', 'schedule_date', 'seat_no', 'departure_city_id', 'destination_city_id'])
            ->groupBy(function ($ticket) { return $ticket->schedule_id . ':' . $ticket->schedule_date; });

        // BookingController::selected/store derive city order from route fares in this order.
        $routeFares = RouteFare::where('company_id', $user->company_id)
            ->whereIn('route_id', $details->pluck('schedule.route_id')->unique())->orderBy('id')
            ->get(['route_id', 'departure_city_id', 'destination_city_id'])->groupBy('route_id');
        $allowed = (int) $user->check_allowed_seats === 1 ? $terminal->available_seats : null;
        $counts = [];
        foreach ($details as $detail) {
            $fares = $routeFares->get($detail->schedule->route_id, collect());
            $cities = $fares->pluck('departure_city_id')->unique()->values()->all();
            if ($fares->isNotEmpty()) { $cities[] = $fares->last()->destination_city_id; }
            $counts[$detail->id] = $this->count(
                optional($detail->bus_class)->seat_map,
                $tickets->get($detail->schedule_id . ':' . $detail->schedule_date, collect()),
                $cities, $detail->departure_id, $detail->destination_id, $allowed
            );
        }
        return $counts;
    }

    private function count($seatMap, Collection $tickets, array $cities, $departure, $destination, ?string $allowed): ?int
    {
        $from = array_search($departure, $cities);
        $to = array_search($destination, $cities);
        if (!is_array($seatMap) || !$seatMap || $from === false || $to === false || $from >= $to) { return null; }

        $seats = [];
        foreach ($seatMap as $row) {
            if (!is_array($row)) { return null; }
            foreach ($row as $seat) {
                if (!is_array($seat)) { return null; }
                if (empty($seat['reserved']) || !isset($seat['seatNo'])) { continue; }
                // A base-map block (including not_for_sale) is not an available seat.
                if (!in_array($seat['type'] ?? 0, [0, '0', null, ''], true)) { continue; }
                $number = (int) $seat['seatNo'];
                if ($number > 0 && $this->allowed($number, $allowed)) { $seats[$number] = true; }
            }
        }

        foreach ($tickets as $ticket) {
            $number = (int) $ticket->seat_no;
            if (!isset($seats[$number])) { continue; }
            $ticketFrom = array_search($ticket->departure_city_id, $cities);
            $ticketTo = array_search($ticket->destination_city_id, $cities);
            // Unknown journey data must not make an occupied seat appear free.
            if ($ticketFrom === false || $ticketTo === false || $ticketFrom >= $ticketTo) { return null; }
            // Same overlap rule as booking: a seat may be sold again at the end of a leg.
            if ($ticketFrom < $to && $ticketTo > $from) { unset($seats[$number]); }
        }
        return count($seats);
    }

    private function allowed(int $seat, ?string $restriction): bool
    {
        if ($restriction === null) { return true; }
        foreach (preg_split('/[,|]/', $restriction) as $part) {
            if (preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', $part, $range)) {
                if ($seat >= (int) $range[1] && $seat <= (int) ($range[2] ?? $range[1])) { return true; }
            }
        }
        return false;
    }
}
