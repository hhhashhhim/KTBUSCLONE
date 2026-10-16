<?php

namespace App\Services;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Support\SeatNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Portal quota: confirmed intermediate tickets, shared by every terminal of a dated trip. */
class ScheduleQuotaService
{
    public function context(ScheduleDetail $detail, bool $lock = false): array
    {
        $query = Schedule::where('company_id', $detail->company_id)->whereKey($detail->schedule_id);
        // Hold until the booking commits. A timed cache lock alone is insufficient.
        if ($lock) {
            if (DB::transactionLevel() === 0) {
                throw new \LogicException('Quota checks require a booking transaction.');
            }
            $query->lockForUpdate();
        }
        $schedule = $query->firstOrFail();
        $fares = $schedule->route->fares()->orderBy('id')->get();
        if ($fares->isEmpty()) {
            throw ValidationException::withMessages(['quota' => 'The schedule route has no cities. Please check its route setup.']);
        }
        $cities = $fares->unique('departure_city_id')->pluck('departure_city_id')->all();
        $cities[] = $fares->last()->destination_city_id;
        // Use the starting city's dated departure, never the customer's midway/terminal time.
        $main = ScheduleDetail::where('company_id', $detail->company_id)
            ->where('schedule_id', $detail->schedule_id)->where('schedule_date', $detail->schedule_date)
            ->where('departure_id', $cities[0])->orderBy('id')->first();
        $departure = Carbon::parse(($main ? $main->departure_date : $detail->schedule_date) . ' ' .
            ($main ? $main->departure_time : $schedule->time), config('app.timezone'));

        return [
            'limit' => $schedule->intermediate_quota === null ? null : (int) $schedule->intermediate_quota,
            'departure_at' => $departure->format('Y-m-d H:i:s'),
            'departed' => now()->greaterThanOrEqualTo($departure),
            'intermediate' => (int) $detail->departure_id !== (int) $cities[0]
                || (int) $detail->destination_id !== (int) end($cities),
            'cities' => $cities,
        ];
    }

    public function assertAvailable(ScheduleDetail $detail, int $paidTickets, array $replacedIds = []): array
    {
        $context = $this->context($detail, true);
        if ($paidTickets <= 0 || $context['limit'] === null || $context['departed'] || !$context['intermediate']) {
            return $context;
        }
        // A locking/current read avoids an older REPEATABLE READ snapshot after waiting for the schedule lock.
        $tickets = Ticket::where('company_id', $detail->company_id)
            ->where('schedule_id', $detail->schedule_id)->where('schedule_date', $detail->schedule_date)
            ->where('type', 'booked')->whereNotIn('id', $replacedIds)
            ->lockForUpdate()->get(['id', 'type', 'departure_city_id', 'destination_city_id', 'booked_time', 'deleted_at']);
        $used = $tickets->filter(function ($ticket) use ($context) {
            return $this->counts($ticket, $context);
        })->count();
        if ($used + $paidTickets > $context['limit']) {
            $remaining = max(0, $context['limit'] - $used);
            throw ValidationException::withMessages(['quota' =>
                "Intermediate-route quota reached: {$used} of {$context['limit']} paid tickets used; {$remaining} remaining. " .
                "You selected {$paidTickets} paid ticket(s). Full-route tickets are not limited. " .
                "This limit lifts at the main departure ({$context['departure_at']})."]);
        }
        return $context;
    }

    public function counts($ticket, array $context): bool
    {
        $cities = $context['cities'];
        return $ticket->type === 'booked' && !$ticket->deleted_at
            && ((int) $ticket->departure_city_id !== (int) $cities[0]
                || (int) $ticket->destination_city_id !== (int) end($cities))
            && (!$ticket->booked_time || (string) $ticket->booked_time < $context['departure_at']);
    }

    public function preview(ScheduleDetail $detail, $tickets, array $seatMap): array
    {
        $context = $this->context($detail);
        $paid = $tickets->filter(function ($ticket) use ($context) { return $this->counts($ticket, $context); });
        $cities = $context['cities'];
        $from = array_search($detail->departure_id, $cities, false);
        $to = array_search($detail->destination_id, $cities, false);
        foreach ($seatMap as &$row) {
            foreach ($row as &$seat) {
                $seat['quota_reuse'] = false;
                if ($context['limit'] === null || !isset($seat['seatNo']) || !empty($seat['type'])
                    || $from === false || $to === false || $from >= $to) {
                    continue;
                }
                $matching = $tickets->filter(function ($ticket) use ($seat) {
                    return SeatNumber::normalize($ticket->seat_no) === SeatNumber::normalize($seat['seatNo']);
                });
                $overlap = $matching->contains(function ($ticket) use ($cities, $from, $to) {
                    $start = array_search($ticket->departure_city_id, $cities, false);
                    $end = array_search($ticket->destination_city_id, $cities, false);
                    return $start === false || $end === false || ($start < $to && $end > $from);
                });
                if (!$overlap) {
                    $seat['quota_reuse'] = $paid->contains(function ($ticket) use ($seat, $cities, $from) {
                        $end = array_search($ticket->destination_city_id, $cities, false);
                        return SeatNumber::normalize($ticket->seat_no) === SeatNumber::normalize($seat['seatNo'])
                            && $end !== false && $end <= $from;
                    });
                }
            }
            unset($seat);
        }
        unset($row);
        unset($context['cities']);
        $context['used'] = $paid->count();
        $context['remaining'] = $context['limit'] === null ? null : max(0, $context['limit'] - $context['used']);
        return ['quota' => $context, 'seat_map' => $seatMap];
    }
}
