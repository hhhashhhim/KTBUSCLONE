<?php

namespace App\Services\Mobile;

use App\Models\City;
use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\TerminalDiscount;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use RuntimeException;

class MobileTravelService
{
    private $policy;

    public function __construct(MobileSchedulePolicy $policy)
    {
        $this->policy = $policy;
    }

    public function companyId(): int
    {
        $companyId = (int) config('mobile.company_id');
        if ($companyId < 1) {
            throw new RuntimeException('The mobile company is not configured.', 503);
        }

        return $companyId;
    }

    public function terminalId(): int
    {
        $terminalId = (int) config('mobile.terminal_id');
        if ($terminalId < 1) {
            throw new RuntimeException('The mobile booking terminal is not configured.', 503);
        }

        return $terminalId;
    }

    public function cities(): Collection
    {
        return City::query()
            ->where('company_id', $this->companyId())
            ->where('hide', 0)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function destinations(int $originId): Collection
    {
        $companyId = $this->companyId();
        $ids = RouteFare::query()
            ->where('company_id', $companyId)
            ->where('departure_city_id', $originId)
            ->pluck('destination_city_id')
            ->unique();

        return City::query()
            ->where('company_id', $companyId)
            ->where('hide', 0)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function schedules(int $originId, int $destinationId, string $date): Collection
    {
        $companyId = $this->companyId();
        $terminalId = $this->terminalId();
        $details = $this->policy->eligibleDetails($companyId, $terminalId, $originId, $destinationId, $date)
            ->orderBy('departure_time')->get();
        if ($details->isEmpty()) {
            return collect();
        }
        $search = new MobileSearchData($details, $companyId, $terminalId, $originId, $destinationId);
        $details = $details->filter(function ($detail) use ($companyId, $terminalId, $search) {
            return $this->policy->bookingIsOpen($detail, $companyId, $terminalId, $search);
        });
        if ($details->isEmpty()) {
            return collect();
        }
        $search->loadAvailability($details, $companyId, $terminalId, $originId, $destinationId, $date);

        return $details->map(function ($detail) use ($originId, $destinationId, $companyId, $search) {
            $fares = $this->faresForDetail($detail, $originId, $destinationId, $search);
            $quota = $this->policy->remainingOnlineSeats($detail, $companyId, $search);
            $layout = $this->layoutForDetail($detail, $originId, $destinationId, $fares, $quota, $search, true);
            $available = $layout['available_seats'];

            return [
                'id' => $detail->schedule_id,
                'schedule_detail_id' => $detail->id,
                'route_id' => optional($detail->schedule)->route_id,
                'origin' => [
                    'id' => $detail->departure_id,
                    'name' => optional($detail->departure_city)->name,
                ],
                'destination' => [
                    'id' => $detail->destination_id,
                    'name' => optional($detail->destination_city)->name,
                ],
                'departure_at' => $detail->departure_date . 'T' . $detail->departure_time,
                'arrival_at' => null,
                'bus_class' => [
                    'id' => $detail->bus_class_id,
                    'name' => optional($detail->bus_class)->name,
                ],
                'available_seats' => is_null($quota) ? $available : min($available, $quota),
                'total_seats' => $layout['total_seats'],
                'fares' => $fares,
            ];
        })->values();
    }

    public function seatLayout(
        int $scheduleDetailId,
        int $originId,
        int $destinationId,
        string $date
    ): array {
        $detail = $this->findDetail($scheduleDetailId, $originId, $destinationId, $date);

        $fares = $this->faresForDetail($detail, $originId, $destinationId);
        $quota = $this->policy->remainingOnlineSeats($detail, $this->companyId());

        return $this->layoutForDetail($detail, $originId, $destinationId, $fares, $quota);
    }

    private function layoutForDetail(
        ScheduleDetail $detail,
        int $originId,
        int $destinationId,
        array $fares,
        ?int $quota,
        ?MobileSearchData $search = null,
        bool $countsOnly = false
    ): array {
        $maximum = (int) config('mobile.maximum_selectable_seats', 5);
        $blocked = $this->blockedTickets($detail, $originId, $destinationId, $search)->keyBy(function ($ticket) {
            return (string) $ticket->seat_no;
        });
        $fares = collect($fares)->keyBy('class_id');
        $allowedNumbers = $this->allowedSeatNumbers($detail, $search);
        $seats = [];
        $total = 0;
        $available = 0;

        foreach ((array) optional($detail->bus_class)->seat_map as $rowIndex => $row) {
            foreach ((array) $row as $columnIndex => $column) {
                $number = isset($column['seatNo']) ? (string) $column['seatNo'] : null;
                $isSeat = $number !== null && ($column['reserved'] ?? false) && (int) ($column['type'] ?? 0) === 0;
                if (!$isSeat) {
                    continue;
                }

                $classId = (int) ($column['class'] ?? 0);
                $fare = $fares->get($classId);
                $ticket = $blocked->get($number);
                $allowed = (is_null($quota) || $quota > 0)
                    && (is_null($allowedNumbers) || in_array($this->numericSeat($number), $allowedNumbers, true));
                $status = $ticket ? 'booked' : ($allowed ? 'available' : 'unavailable');

                $total++;
                if ($status === 'available') {
                    $available++;
                }
                if ($countsOnly) {
                    continue;
                }
                $seats[] = [
                    'number' => $number,
                    'row' => (int) $rowIndex,
                    'column' => (int) $columnIndex,
                    'deck' => $column['deck'] ?? 'lower',
                    'type' => $column['seatType'] ?? 'seat',
                    'class_id' => $classId,
                    'class_name' => $fare['class_name'] ?? null,
                    'price' => (float) ($fare['amount'] ?? 0),
                    'status' => $status,
                    'gender_restriction' => $column['gender'] ?? null,
                ];
            }
        }

        if ($countsOnly) {
            return ['total_seats' => $total, 'available_seats' => $available];
        }

        return [
            'schedule_id' => $detail->schedule_id,
            'schedule_detail_id' => $detail->id,
            'maximum_selectable_seats' => is_null($quota) ? $maximum : min($maximum, $quota),
            'seats' => $seats,
        ];
    }

    public function findDetail(
        int $scheduleDetailId,
        int $originId,
        int $destinationId,
        string $date
    ): ScheduleDetail {
        $companyId = $this->companyId();
        $terminalId = $this->terminalId();
        $detail = $this->policy->eligibleDetails($companyId, $terminalId, $originId, $destinationId, $date)
            ->whereKey($scheduleDetailId)->first();
        if (!$detail || !$this->policy->bookingIsOpen($detail, $companyId, $terminalId)) {
            throw new RuntimeException('The selected schedule is not available.', 404);
        }

        return $detail;
    }

    public function faresForDetail(
        ScheduleDetail $detail,
        int $originId,
        int $destinationId,
        ?MobileSearchData $search = null
    ): array {
        $classIds = collect(optional($detail->bus_class)->seat_map ?: [])
            ->flatten(1)
            ->filter(function ($seat) {
                return is_array($seat) && ($seat['reserved'] ?? false) && isset($seat['class']);
            })
            ->pluck('class')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        $fareRows = $search ? $search->fareRows : FareTable::query()
            ->where('company_id', $this->companyId())
            ->where('from_city_id', $originId)
            ->where('to_city_id', $destinationId)
            ->whereIn('fare_class', $classIds)
            ->get()
            ->unique('fare_class');
        $classNames = $search ? $search->classNames : FareClass::whereIn('id', $classIds)->pluck('name', 'id');

        return $classIds->map(function ($classId) use ($fareRows, $classNames, $detail, $search) {
            $row = $fareRows->firstWhere('fare_class', $classId);
            if (!$row) {
                throw new RuntimeException('The fare table is incomplete for this schedule.', 422);
            }
            $original = (float) $row->fare;
            $amount = $this->applyFareAdjustments($detail, $original, $search);

            return [
                'class_id' => $classId,
                'class_name' => $classNames->get($classId, 'Standard'),
                'original_amount' => $original,
                'amount' => $amount,
            ];
        })->all();
    }

    private function applyFareAdjustments(ScheduleDetail $detail, float $fare, ?MobileSearchData $search = null): float
    {
        $terminalId = $this->terminalId();
        $adjusted = $fare;
        $schedule = $detail->schedule;
        $discount = $search ? $search->discounts->get(optional($schedule)->discount_id) : Discount::query()
            ->whereKey(optional($schedule)->discount_id)
            ->where('is_active', 1)
            ->whereHas('discount_terminals', function ($query) use ($terminalId) {
                $query->where('terminal_id', $terminalId);
            })
            ->first();
        if ($discount) {
            $adjusted -= $discount->type === 'percentage'
                ? ($fare * ((float) $discount->percentage / 100))
                : (float) $discount->flat;
        }

        $terminalDiscount = $search ? $search->terminalDiscounts->get(optional($schedule)->route_id) : TerminalDiscount::query()
            ->where('terminal_id', $terminalId)
            ->where('route_id', optional($schedule)->route_id)
            ->whereDate('start_date', '<=', $detail->departure_date)
            ->whereDate('end_date', '>=', $detail->departure_date)
            ->first();
        if ($terminalDiscount) {
            $adjusted -= $fare * ((float) $terminalDiscount->discount / 100);
        }

        $surcharge = $search ? $search->surcharges->get(optional($schedule)->surcharge_id) : Surcharge::query()
            ->whereKey(optional($schedule)->surcharge_id)
            ->where('is_active', 1)
            ->first();
        if ($surcharge) {
            $adjusted += $surcharge->type === 'percentage'
                ? ($fare * ((float) $surcharge->percentage / 100))
                : (float) $surcharge->flat;
        }

        return $adjusted === $fare ? $fare : (float) customRound((int) round($adjusted));
    }

    private function blockedTickets(
        ScheduleDetail $detail,
        int $originId,
        int $destinationId,
        ?MobileSearchData $search = null
    ): Collection {
        $sequence = $this->routeSequence($detail);
        $originIndex = array_search($originId, $sequence, true);
        $destinationIndex = array_search($destinationId, $sequence, true);
        if ($originIndex === false || $destinationIndex === false || $originIndex >= $destinationIndex) {
            throw new RuntimeException('The requested city pair is not valid for this route.', 422);
        }

        $tickets = $search ? $search->ticketsFor($detail) : Ticket::query()
            ->where('company_id', $this->companyId())
            ->where('schedule_id', $detail->schedule_id)
            ->where('schedule_date', $detail->schedule_date)
            ->whereNotIn('type', ['cancelled'])
            ->get(['id', 'seat_no', 'departure_city_id', 'destination_city_id', 'gender', 'type']);

        return $tickets->filter(function ($ticket) use ($sequence, $originIndex, $destinationIndex) {
                $ticketOrigin = array_search((int) $ticket->departure_city_id, $sequence, true);
                $ticketDestination = array_search((int) $ticket->destination_city_id, $sequence, true);
                if ($ticketOrigin === false || $ticketDestination === false) {
                    return true;
                }

                return $ticketOrigin < $destinationIndex && $ticketDestination > $originIndex;
            })
            ->values();
    }

    private function routeSequence(ScheduleDetail $detail): array
    {
        $fares = optional(optional($detail->schedule)->route)->fares ?: collect();
        $sequence = $fares->pluck('departure_city_id')->map(function ($id) {
            return (int) $id;
        })->unique()->values()->all();
        $last = $fares->last();
        if ($last) {
            $sequence[] = (int) $last->destination_city_id;
        }

        return array_values(array_unique($sequence));
    }

    private function allowedSeatNumbers(ScheduleDetail $detail, ?MobileSearchData $search = null): ?array
    {
        $terminal = $search ? $search->terminal : Terminal::find($this->terminalId());
        $allowed = $this->parseSeatNumbers(optional($terminal)->available_seats);
        $routeChoices = $this->parseSeatNumbers(optional(optional($detail->schedule)->route)->online_seat_choices);

        if (is_null($allowed)) {
            return $routeChoices;
        }
        if (is_null($routeChoices)) {
            return $allowed;
        }

        return array_values(array_intersect($allowed, $routeChoices));
    }

    private function parseSeatNumbers(?string $value): ?array
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $result = [];
        foreach (preg_split('/[,|]+/', $value) as $part) {
            $part = trim($part);
            if (strpos($part, '-') !== false) {
                list($start, $end) = array_map('intval', explode('-', $part, 2));
                for ($number = $start; $number <= $end; $number++) {
                    $result[] = $number;
                }
            } else {
                $result[] = (int) preg_replace('/\D+/', '', $part);
            }
        }

        return array_values(array_unique(array_filter($result)));
    }

    private function numericSeat(string $number): int
    {
        return (int) preg_replace('/\D+/', '', $number);
    }
}
