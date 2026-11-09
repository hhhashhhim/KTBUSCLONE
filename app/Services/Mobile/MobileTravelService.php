<?php

namespace App\Services\Mobile;

use App\Models\City;
use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\TerminalDiscount;
use Carbon\Carbon;
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
        return $this->cityOptions();
    }

    public function destinations(int $originId): Collection
    {
        return $this->cityOptions($originId);
    }

    private function cityOptions(?int $originId = null): Collection
    {
        $companyId = $this->companyId();
        $details = $this->policy->visibleDetails($companyId, $this->terminalId());

        if (is_null($originId)) {
            $details->select('departure_id');
        } else {
            $details->select('destination_id')
                ->where('departure_id', $originId)
                ->where('destination_id', '<>', $originId);
        }

        // Keep the eligible IDs inside SQL so cities stay unique without loading runs
        // into PHP or evaluating a separate correlated schedule query for each city.
        return City::query()
            ->where('company_id', $companyId)
            ->where('hide', 0)
            ->whereIn('id', $details)
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
        // Reuse the ERP city-pair journey time, already loaded and company-scoped.
        $travelTime = optional($search->fareRows->sortBy('id')->first())->time_difference;

        return $details->map(function ($detail) use ($originId, $destinationId, $companyId, $search, $travelTime) {
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
                'arrival_at' => $this->estimatedArrivalAt($detail, $travelTime),
                'bus_class' => [
                    'id' => $detail->bus_class_id,
                    'name' => optional($detail->bus_class)->name,
                ],
                'available_seats' => is_null($quota) ? $available : min($available, $quota),
                'available_special_seats' => is_null($quota)
                    ? $layout['available_special_seats']
                    : min($layout['available_special_seats'], $quota),
                'total_seats' => $layout['total_seats'],
                'fares' => $fares,
            ];
        })->values();
    }

    private function estimatedArrivalAt(ScheduleDetail $detail, ?string $travelTime): ?string
    {
        // Unlike a clock time, the hour component can exceed 23 for long journeys.
        if (!preg_match('/^(\d{1,3}):([0-5]\d)(?::([0-5]\d))?$/', trim($travelTime ?? ''), $parts)) {
            return null;
        }
        $seconds = ((int) $parts[1] * 3600) + ((int) $parts[2] * 60) + (int) ($parts[3] ?? 0);
        if ($seconds <= 0) {
            return null;
        }

        return Carbon::parse($detail->departure_date . ' ' . $detail->departure_time, config('app.timezone'))
            ->addSeconds($seconds)->format('Y-m-d\TH:i:s');
    }

    public function seatLayout(
        int $scheduleDetailId,
        int $originId,
        int $destinationId,
        string $date
    ): array {
        $detail = $this->findDetail($scheduleDetailId, $originId, $destinationId, $date);

        $fares = $this->faresForDetail($detail, $originId, $destinationId);
        $tickets = app(MobileQuotaService::class)->tickets($detail);
        $quota = $this->policy->remainingOnlineSeats($detail, $this->companyId(), null, $tickets);

        return $this->layoutForDetail($detail, $originId, $destinationId, $fares, $quota, null, false, $tickets);
    }

    private function layoutForDetail(
        ScheduleDetail $detail,
        int $originId,
        int $destinationId,
        array $fares,
        ?int $quota,
        ?MobileSearchData $search = null,
        bool $countsOnly = false,
        ?Collection $tickets = null
    ): array {
        $maximum = (int) config('mobile.maximum_selectable_seats', 5);
        $quotaService = app(MobileQuotaService::class);
        $tickets = $tickets ?? ($search ? $search->ticketsFor($detail) : $quotaService->tickets($detail));
        $midway = $quotaService->context($detail, $tickets, $search ? $search->mainDepartureFor($detail) : null, $search !== null);
        $percentage = $midway ? (float) ($search ? $search->quotaPercentages :
            $quotaService->percentages(collect([$detail]), $this->companyId(), $this->terminalId()))->get($detail->schedule_id, 0) : 0;
        $blocked = $this->blockedTickets($detail, $originId, $destinationId, $tickets)->keyBy(function ($ticket) {
            return MobileQuotaService::seatKey($ticket->seat_no);
        });
        $fares = collect($fares)->keyBy('class_id');
        $allowedNumbers = $this->allowedSeatNumbers($detail, $search);
        $seats = [];
        $total = 0;
        $available = 0;
        $reusable = 0;
        $specialAvailable = 0;

        foreach ((array) optional($detail->bus_class)->seat_map as $rowIndex => $row) {
            foreach ((array) $row as $columnIndex => $column) {
                $number = isset($column['seatNo']) ? (string) $column['seatNo'] : null;
                $isSeat = $number !== null && ($column['reserved'] ?? false) && (int) ($column['type'] ?? 0) === 0;
                if (!$isSeat) {
                    continue;
                }

                $classId = (int) ($column['class'] ?? 0);
                $fare = $fares->get($classId);
                $ticket = $blocked->get(MobileQuotaService::seatKey($number));
                $reuse = !$ticket && $midway && in_array(MobileQuotaService::seatKey($number), $midway['used_seats'], true);
                $allowed = (is_null($quota) || $quota > 0)
                    && (is_null($allowedNumbers) || in_array($this->numericSeat($number), $allowedNumbers, true))
                    && (!$midway || !$midway['restricted'] || $midway['remaining'] > 0 || $reuse);
                $status = $ticket ? 'booked' : ($allowed ? 'available' : 'unavailable');

                $total++;
                if ($status === 'available') {
                    $available++;
                    if ($reuse) { $reusable++; }
                }
                if ($countsOnly) {
                    if ($status === 'available' && $reuse && $percentage > 0) {
                        // Use the seat map's pricing rules: reuse alone is not a saving,
                        // and a small percentage can disappear after fare rounding.
                        $specialFare = $quotaService->adjustSeat([
                            'price' => (float) ($fare['amount'] ?? 0),
                            'discount' => (float) ($fare['discount'] ?? 0),
                            'status' => $status,
                        ], true, $percentage);
                        if ($specialFare['is_special']) { $specialAvailable++; }
                    }
                    continue;
                }
                $seats[] = $quotaService->adjustSeat([
                    'number' => $number,
                    'row' => (int) $rowIndex,
                    'column' => (int) $columnIndex,
                    'deck' => $column['deck'] ?? 'lower',
                    'type' => $column['seatType'] ?? 'seat',
                    'class_id' => $classId,
                    'class_name' => $fare['class_name'] ?? null,
                    'price' => (float) ($fare['amount'] ?? 0),
                    'base_fare' => (float) ($fare['original_amount'] ?? 0),
                    'discount' => (float) ($fare['discount'] ?? 0),
                    'surcharge' => (float) ($fare['surcharge'] ?? 0),
                    'rounding_adjustment' => (float) ($fare['rounding_adjustment'] ?? 0),
                    'status' => $status,
                    'booked_gender' => $ticket
                        ? ([1 => 'male', 0 => 'female'][(string) $ticket->gender] ?? null)
                        : null,
                    'gender_restriction' => $column['gender'] ?? null,
                ], (bool) $reuse, $percentage);
            }
        }

        if ($midway && $midway['restricted']) {
            $available = min($available, $reusable + $midway['remaining']);
            $maximum = min($maximum, $available);
        }
        if ($countsOnly) {
            return [
                'total_seats' => $total,
                'available_seats' => $available,
                'available_special_seats' => min($specialAvailable, $available),
            ];
        }

        return [
            'schedule_id' => $detail->schedule_id,
            'schedule_detail_id' => $detail->id,
            'maximum_selectable_seats' => is_null($quota) ? $maximum : min($maximum, $quota),
            'midway_quota' => $midway ? ['restricted' => $midway['restricted'], 'remaining' => $midway['remaining']] : null,
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
            ->get()
            ->unique('fare_class');
        $fareClasses = $search ? $search->fareClasses : FareClass::where('company_id', $this->companyId())
            ->get(['id', 'name', 'is_active'])->keyBy('id');
        // ERP requires the city-pair tariff to cover every company fare class.
        if ($fareClasses->count() !== $fareRows->count()) {
            throw new RuntimeException('The fare table is incomplete for this schedule.', 422);
        }

        return $classIds->map(function ($classId) use ($fareRows, $fareClasses, $detail, $search) {
            $row = $fareRows->firstWhere('fare_class', $classId);
            if (!$row) {
                throw new RuntimeException('The fare table is incomplete for this schedule.', 422);
            }
            $class = $fareClasses->get($classId);
            if (!$class || (int) $class->is_active !== 1) {
                throw new RuntimeException('This schedule contains an inactive or unavailable fare class.', 422);
            }
            $original = (float) $row->fare;
            return array_merge([
                'class_id' => $classId,
                'class_name' => $class->name,
            ], $this->applyFareAdjustments($detail, $original, $search));
        })->all();
    }

    private function applyFareAdjustments(ScheduleDetail $detail, float $fare, ?MobileSearchData $search = null): array
    {
        $terminalId = $this->terminalId();
        $schedule = $detail->schedule;
        $discount = $search ? $search->discounts->get(optional($schedule)->discount_id) : Discount::query()
            ->whereKey(optional($schedule)->discount_id)
            ->where('is_active', 1)
            ->whereHas('discount_terminals', function ($query) use ($terminalId) {
                $query->where('terminal_id', $terminalId);
            })
            ->first();

        $terminalDiscount = $search ? $search->terminalDiscounts->get(optional($schedule)->route_id) : TerminalDiscount::query()
            ->where('terminal_id', $terminalId)
            ->where('route_id', optional($schedule)->route_id)
            ->whereDate('start_date', '<=', $detail->departure_date)
            ->whereDate('end_date', '>=', $detail->departure_date)
            ->first();

        $surcharge = $search ? $search->surcharges->get(optional($schedule)->surcharge_id) : Surcharge::query()
            ->whereKey(optional($schedule)->surcharge_id)
            ->where('is_active', 1)
            ->first();
        $result = MobileFareCalculator::calculate($fare, $discount, $terminalDiscount, $surcharge);
        if ($result['amount'] < 0) {
            // Do not silently cap an invalid ERP tariff and invent a different price.
            throw new RuntimeException('The fare configuration is invalid for this schedule.', 422);
        }
        return $result;
    }

    private function blockedTickets(
        ScheduleDetail $detail,
        int $originId,
        int $destinationId,
        Collection $tickets
    ): Collection {
        $sequence = $this->routeSequence($detail);
        $originIndex = array_search($originId, $sequence, true);
        $destinationIndex = array_search($destinationId, $sequence, true);
        if ($originIndex === false || $destinationIndex === false || $originIndex >= $destinationIndex) {
            throw new RuntimeException('The requested city pair is not valid for this route.', 422);
        }

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
        $fares = (optional(optional($detail->schedule)->route)->fares ?: collect())->sortBy('id');
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
