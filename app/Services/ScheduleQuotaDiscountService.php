<?php

namespace App\Services;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\User;
use App\Support\SeatNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ScheduleQuotaDiscountService
{
    public function percentage(ScheduleDetail $detail, User $user, $terminalId = null): float
    {
        if ((int) $detail->company_id !== (int) $user->company_id || $user->hide || !Schema::hasTable('schedule_quota_terminal_discounts')) {
            return 0;
        }
        $terminalId = $terminalId ?? $user->terminal_id;
        if (!\App\Models\Terminal::where('company_id', $user->company_id)->where('hide', 0)->whereKey($terminalId)->exists()) return 0;
        return (float) DB::table('schedule_quota_terminal_discounts')->where('company_id', $user->company_id)
            ->where('schedule_id', $detail->schedule_id)->where('terminal_id', $terminalId)->where('active', true)->value('percentage');
    }

    public function preview(ScheduleDetail $detail, $tickets, array $map, User $user, $terminalId = null): array
    {
        $percentage = $this->percentage($detail, $user, $terminalId);
        if ($percentage > 0) {
            if (DB::transactionLevel() > 0) {
                $tickets = \App\Models\Ticket::where('company_id', $detail->company_id)->where('schedule_id', $detail->schedule_id)
                    ->where('schedule_date', $detail->schedule_date)->lockForUpdate()->get();
            }
            $map = app(ScheduleQuotaService::class)->preview($detail, $tickets, $map)['seat_map'];
        }
        foreach ($map as &$row) {
            foreach ($row as &$seat) {
                // Never infer eligibility from a client flag or a class-wide discounted fare.
                $base = max(0, (float) ($seat['fare'] ?? 0));
                $special = $percentage > 0 && !empty($seat['quota_reuse']) && empty($seat['type'])
                    && !empty($seat['reserved']) && (!isset($seat['terminal_allow']) || $seat['terminal_allow']) && $base > 0;
                // Whole-rupee rounding preserves small percentages instead of rounding them away to Rs 50/100.
                $saving = $special ? min($base, round($base * $percentage / 100)) : 0;
                $seat['fare_before_quota_discount'] = $base;
                $seat['quota_discount'] = $saving;
                $seat['quota_discount_percentage'] = $saving > 0 ? $percentage : 0;
                $seat['is_special'] = $saving > 0 ? 'Yes' : 'No';
                $seat['is_speacial'] = $seat['is_special']; // Compatibility with the requested partner field spelling.
                if (isset($seat['fare'])) { $seat['fare'] = $base - $saving; }
                $seat['discount'] = max(0, (float) ($seat['original_fare'] ?? $base) - ($base - $saving));
            }
            unset($seat);
        }
        unset($row);
        return $map;
    }

    /** Reuse the channel's existing authoritative fare/availability calculation under the trip lock. */
    public function bookingQuotes(ScheduleDetail $detail, Request $request, string $channel): array
    {
        $user = $request->user() ?: auth()->user();
        if (!Schema::hasTable('schedule_quota_terminal_discounts')) { return []; }
        if (DB::transactionLevel() === 0) { throw new \LogicException('Quota discount validation requires a booking transaction.'); }
        Schedule::where('company_id', $user->company_id)->whereKey($detail->schedule_id)->lockForUpdate()->firstOrFail();
        $portal = $channel === 'portal';
        $terminalId = $portal ? ($request->terminalId ?? $user->terminal_id) : $user->terminal_id;
        if (!DB::table('schedule_quota_terminal_discounts')->where('company_id', $user->company_id)
            ->where('schedule_id', $detail->schedule_id)->where('terminal_id', $terminalId)->exists()) { return []; }
        if (!$portal && (($request->filled('terminal_id') && (int) $request->terminal_id !== (int) $terminalId)
            || ($request->filled('terminalId') && (int) $request->terminalId !== (int) $terminalId))) {
            throw ValidationException::withMessages(['quota_discount' => 'Please book using the terminal assigned to your API account.']);
        }
        // Current reads avoid stale eligibility after another booking releases the schedule lock.
        $currentTickets = \App\Models\Ticket::where('company_id', $user->company_id)->where('schedule_id', $detail->schedule_id)
            ->where('schedule_date', $detail->schedule_date)->lockForUpdate()->get();
        $selected = (array) ($portal ? $request->selectedSeats : $request->selected_seats);
        $cities = app(ScheduleQuotaService::class)->context($detail)['cities'];
        $from = array_search($detail->departure_id, $cities, false);
        $to = array_search($detail->destination_id, $cities, false);
        $seatKeys = array_map([SeatNumber::class, 'normalize'], $selected);
        foreach ($currentTickets as $ticket) {
            if (!in_array(SeatNumber::normalize($ticket->seat_no), $seatKeys, true)) { continue; }
            $start = array_search($ticket->departure_city_id, $cities, false);
            $end = array_search($ticket->destination_city_id, $cities, false);
            if ($from === false || $to === false || $start === false || $end === false || ($start < $to && $end > $from)) {
                throw ValidationException::withMessages(['quota_discount' => 'A selected seat is no longer available for this journey. Please refresh the seat map.']);
            }
        }
        $params = $portal ? [
            'id' => $detail->schedule_id, 'date' => $detail->departure_date, 'departure_time' => $detail->departure_time,
            'departureCity' => $detail->departure_id, 'destinationCity' => $detail->destination_id,
            'dropTerminal' => $request->terminalId,
        ] : [
            'schedule_id' => $detail->schedule_id, 'date' => $detail->departure_date, 'departure_time' => $detail->departure_time,
            'departure_city_id' => $detail->departure_id, 'destination_city_id' => $detail->destination_id,
        ];
        $previewRequest = Request::create('/', 'POST', $params);
        $previewRequest->setUserResolver(function () use ($user) { return $user; });
        if ($portal) {
            $response = app(\App\Http\Controllers\Booking\BookingController::class)->selected($previewRequest);
            $map = $response instanceof Schedule ? $response->bus_class->seat_map : null;
        } else {
            $controller = $channel === 'legacy' ? \App\Http\Controllers\Api\TicketingApiController::class : \App\Http\Controllers\Api\BookingApiController::class;
            $response = app($controller)->previewSchedule($previewRequest);
            $map = $response instanceof \App\Http\Resources\SuccessResource ? $response->resource->seat_map : null;
        }
        if (!is_array($map)) { throw ValidationException::withMessages(['quota_discount' => 'Unable to verify seat prices. Please refresh the seat map.']); }
        return $this->validateSelection($map,
            (array) ($portal ? $request->selectedSeats : $request->selected_seats),
            (array) ($portal ? $request->selectedSeatsClass : $request->selected_seats_class),
            (array) ($portal ? $request->selectedSeatsFare : $request->selected_seats_fare));
    }

    public function validateSelection(array $map, array $seats, array $classes, array $fares): array
    {
        $bySeat = [];
        foreach ($map as $row) { foreach ($row as $seat) {
            if (isset($seat['seatNo'])) { $bySeat[SeatNumber::normalize($seat['seatNo'])] = $seat; }
        } }
        $quotes = []; $seen = [];
        foreach ($seats as $i => $number) {
            $key = SeatNumber::normalize($number);
            $seat = $bySeat[$key] ?? null;
            if (!$seat || isset($seen[$key]) || empty($seat['reserved']) || !empty($seat['type'])
                || (isset($seat['terminal_allow']) && !$seat['terminal_allow'])
                || (string) ($classes[$i] ?? '') !== (string) ($seat['class'] ?? '')
                || !isset($fares[$i]) || !is_numeric($fares[$i]) || abs((float) $fares[$i] - (float) $seat['fare']) > 0.001) {
                throw ValidationException::withMessages(['quota_discount' => 'A selected seat or its fare has changed. Please refresh the seat map and select the seats again.']);
            }
            $seen[$key] = true; $quotes[$i] = $seat;
        }
        if (!$quotes || count($classes) !== count($seats) || count($fares) !== count($seats)) {
            throw ValidationException::withMessages(['quota_discount' => 'Please select valid seats and refresh their prices.']);
        }
        return $quotes;
    }
}
