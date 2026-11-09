<?php

namespace App\Services\Mobile;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Mobile adapter for the ERP's paid midway-seat quota and terminal reuse discount. */
class MobileQuotaService
{
    public static function seatKey($number): string
    {
        return strtoupper(trim((string) $number));
    }

    public function lockSchedule(int $companyId, int $scheduleId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Schedule quota validation requires a transaction.');
        }
        if (!Schedule::where('company_id', $companyId)->whereKey($scheduleId)->lockForUpdate()->first()) {
            throw new RuntimeException('The selected schedule is no longer available.', 409);
        }
    }

    public function tickets(ScheduleDetail $detail): Collection
    {
        $query = Ticket::where('company_id', $detail->company_id)->where('schedule_id', $detail->schedule_id)
            ->where('schedule_date', $detail->schedule_date)->whereNotIn('type', ['cancelled', 'canceled']);
        // Use a current read after acquiring the shared ERP schedule row lock.
        if (DB::transactionLevel() > 0) { $query->lockForUpdate(); }
        return $query->get();
    }

    public function percentages(Collection $details, int $companyId, int $terminalId): Collection
    {
        if (!Schema::hasTable('schedule_quota_terminal_discounts')) { return collect(); }
        $query = DB::table('schedule_quota_terminal_discounts')->where('company_id', $companyId)
            ->where('terminal_id', $terminalId)->where('active', true)
            ->whereIn('schedule_id', $details->pluck('schedule_id')->unique());
        if (DB::transactionLevel() > 0) { $query->lockForUpdate(); }
        $percentages = $query->pluck('percentage', 'schedule_id');
        if ($percentages->contains(function ($rate) { return (float) $rate > 0; }) && !Schema::hasColumn('tickets', 'quota_discount')) {
            throw new RuntimeException('The mobile quota schema needs updating.', 503);
        }
        return $percentages;
    }

    public function context(ScheduleDetail $detail, Collection $tickets, ?ScheduleDetail $main = null, bool $departureLoaded = false): ?array
    {
        $schedule = $detail->schedule;
        if ($schedule->intermediate_quota === null) { return null; }
        $fares = $schedule->route->fares->sortBy('id');
        $cities = $fares->pluck('departure_city_id')->map(function ($id) { return (int) $id; })->unique()->values()->all();
        if (!$cities) { throw new RuntimeException('The schedule route has no cities.', 422); }
        $cities[] = (int) $fares->last()->destination_city_id;
        $exemptions = $schedule->quota_exempt_routes;
        if (is_string($exemptions)) { $exemptions = json_decode($exemptions, true); }
        $exempt = collect($exemptions ?: [])->contains(function ($pair) use ($detail) {
            return (int) ($pair['departure_city_id'] ?? 0) === (int) $detail->departure_id
                && (int) ($pair['destination_city_id'] ?? 0) === (int) $detail->destination_id;
        });
        if (!$main && !$departureLoaded) {
            $main = ScheduleDetail::where('company_id', $detail->company_id)->where('schedule_id', $detail->schedule_id)
                ->where('schedule_date', $detail->schedule_date)->where('departure_id', $cities[0])->orderBy('id')->first();
        }
        $departure = Carbon::parse(($main ? $main->departure_date : $detail->schedule_date) . ' ' .
            ($main ? $main->departure_time : $schedule->time), config('app.timezone'));
        $used = $tickets->filter(function ($ticket) use ($cities) {
            return $ticket->type === 'booked'
                && ((int) $ticket->departure_city_id !== $cities[0] || (int) $ticket->destination_city_id !== end($cities));
        })->pluck('seat_no')->map([self::class, 'seatKey'])->unique()->values()->all();
        $intermediate = (int) $detail->departure_id !== $cities[0] || (int) $detail->destination_id !== end($cities);
        return [
            'remaining' => max(0, (int) $schedule->intermediate_quota - count($used)),
            'restricted' => $intermediate && !$exempt && now()->lt($departure),
            'used_seats' => $used,
        ];
    }

    public function adjustSeat(array $seat, bool $reuse, float $percentage): array
    {
        // Same order and per-seat nearest-Rs.-100 rounding as ERP quota discounts:
        // first calculate the ordinary ERP fare, then reduce eligible reused seats.
        $base = (float) $seat['price'];
        $eligible = $seat['status'] === 'available' && $reuse && $percentage > 0;
        $payable = $eligible ? min($base, max(0, round($base * (1 - $percentage / 100), -2, PHP_ROUND_HALF_UP))) : $base;
        $saving = round($base - $payable, 2);
        $seat['price'] = $payable;
        $seat['fare_before_quota_discount'] = $base;
        $seat['quota_discount'] = $saving;
        $seat['quota_discount_percentage'] = $saving > 0 ? $percentage : 0.0;
        $seat['quota_reuse'] = $reuse && $seat['status'] === 'available';
        $seat['is_special'] = $saving > 0;
        // Included in discount, not an extra deduction from the quote total.
        $seat['discount'] = round((float) $seat['discount'] + $saving, 2);
        return $seat;
    }

    public function assertSelection(array $layout, array $numbers): void
    {
        $quota = $layout['midway_quota'] ?? null;
        if (!$quota || !$quota['restricted']) { return; }
        $selected = array_map([self::class, 'seatKey'], $numbers);
        $newSeats = collect($layout['seats'])->filter(function ($seat) use ($selected) {
            return in_array(self::seatKey($seat['number']), $selected, true) && !$seat['quota_reuse'];
        })->count();
        if ($newSeats > $quota['remaining']) {
            throw new RuntimeException('The midway seat quota has changed. Refresh the seat map and select fewer new seats.', 409);
        }
    }

    /** A reservation does not consume the paid-seat quota until confirmation. */
    public function assertConfirmation(Collection $reservations): void
    {
        foreach ($reservations->groupBy('schedule_details_id') as $group) {
            $first = $group->first();
            $detail = ScheduleDetail::with(['schedule.route.fares'])->where('company_id', $first->company_id)
                ->where('schedule_id', $first->schedule_id)->where('schedule_date', $first->schedule_date)
                ->whereKey($first->schedule_details_id)->first();
            if (!$detail || !$detail->schedule || !$detail->schedule->route) {
                throw new RuntimeException('The reserved journey is no longer available.', 409);
            }
            $tickets = $this->tickets($detail);
            $cities = $detail->schedule->route->fares->sortBy('id')->pluck('departure_city_id')->map(function ($id) { return (int) $id; })->unique()->values()->all();
            $last = $detail->schedule->route->fares->sortBy('id')->last();
            if ($last) { $cities[] = (int) $last->destination_city_id; }
            $from = array_search((int) $detail->departure_id, $cities, true);
            $to = array_search((int) $detail->destination_id, $cities, true);
            if ($from === false || $to === false || $from >= $to) {
                throw new RuntimeException('The reserved journey has changed.', 409);
            }
            foreach ($tickets->whereNotIn('id', $group->pluck('id')) as $other) {
                if (!$group->contains(function ($own) use ($other) { return self::seatKey($own->seat_no) === self::seatKey($other->seat_no); })) { continue; }
                $start = array_search((int) $other->departure_city_id, $cities, true);
                $end = array_search((int) $other->destination_city_id, $cities, true);
                if ($start === false || $end === false || ($start < $to && $end > $from)) {
                    throw new RuntimeException('The reserved seat is no longer available.', 409);
                }
            }
            $context = $this->context($detail, $tickets);
            $selected = $group->pluck('seat_no')->map([self::class, 'seatKey'])->unique()->all();
            if ($context && $context['restricted'] && count(array_diff($selected, $context['used_seats'])) > $context['remaining']) {
                throw new RuntimeException('The midway seat quota is no longer available.', 409);
            }
            $special = $group->filter(function ($ticket) { return (float) $ticket->quota_discount > 0; });
            if ($special->isEmpty()) { continue; }
            $terminal = Terminal::where('company_id', $first->company_id)->where('hide', 0)->find($first->terminal_id);
            $percentage = $terminal ? (float) $this->percentages(collect([$detail]), $first->company_id, $terminal->id)->get($detail->schedule_id, 0) : 0;
            foreach ($special as $ticket) {
                // Reservation prices are fixed; revalidate the quota saving against that
                // agreed pre-quota fare. Never silently charge a different price after payment.
                $reuse = $context && in_array(self::seatKey($ticket->seat_no), $context['used_seats'], true);
                $seat = $this->adjustSeat(['price' => (float) $ticket->seat_fare, 'discount' => 0, 'status' => 'available'], $reuse, $percentage);
                if (abs($seat['quota_discount'] - (float) $ticket->quota_discount) > 0.001) {
                    throw new RuntimeException('The special seat fare is no longer available.', 409);
                }
            }
        }
    }
}
