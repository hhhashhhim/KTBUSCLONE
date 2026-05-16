<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RescheduleReportController extends Controller
{
    private const RESCHEDULE_COLUMNS = [
        'terminal_name',
        'passenger_name',
        'passenger_contact',
        'passenger_cnic',
        'status',
        'current_status',
        'from_bus_time',
        'to_bus_time',
        'reschedule_from',
        'reschedule_to',
        'from_seat',
        'to_seat',
        'old_fare',
        'new_fare',
        'remarks',
        'reschedule_by',
        'reschedule_time',
    ];

    public function getTerminals()
    {
        if (!checkForSubmenu("confirm-cancel")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function filterData(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    return $this->transformTickets(
        $this->buildFilteredTicketsQuery($request)->get($this->ticketSelectColumns())
    );
}

    public function getPrintPdf(Request $request)
{

    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json([
            "Error" => ['You are not authorized to access this url']
        ], 403);
    }

    $tickets = $this->transformTickets(
        $this->buildFilteredTicketsQuery($request)->get($this->ticketSelectColumns())
    );

    return view('reports.rescheduleReport', [
        'tickets' => $tickets,
        'visibleColumns' => $this->getVisibleColumns($request),
    ]);
}

    private function buildFilteredTicketsQuery(Request $request): Builder
    {
        $passengerName = trim($request->passenger_name ?? '');
        $passengerContact = preg_replace('/[^0-9]/', '', $request->passenger_contact ?? '');
        $passengerCnic = preg_replace('/[^0-9]/', '', $request->passenger_cnic ?? '');

        return Ticket::with([
            'reschedule_seat',
            'reschedule_seat.old_departure:id,name',
            'reschedule_seat.old_destination:id,name',
            'reschedule_seat.new_departure:id,name',
            'reschedule_seat.new_destination:id,name',
            'reschedule_seat.new_ticket' => function ($q) {
                $q->withTrashed();
            },
            'terminal:id,name',
            'customer:id,name,contact,cnic',
        ])
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'reschedule')
            ->onlyTrashed()
            ->when($request->terminal != 0, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->when($request->fromDate != '', function ($query) use ($request) {
                return $query->whereDate('schedule_date', '>=', $request->fromDate);
            })
            ->when($request->toDate != '', function ($query) use ($request) {
                return $query->whereDate('schedule_date', '<=', $request->toDate);
            })
            ->when($request->type != '' && $request->type != 0 && $request->type != '0', function ($query) use ($request) {
                if ($request->type == 'reschedule') {
                    return $query->where('type', 'reschedule');
                }

                return $query->whereHas('reschedule_seat.new_ticket', function ($q) use ($request) {
                    $targetType = $request->type == 'advance booking'
                        ? 'advance-seat'
                        : $request->type;

                    $q->withTrashed()->where('type', $targetType);
                });
            })
            ->when($passengerName || $passengerContact || $passengerCnic, function ($query) use ($passengerName, $passengerContact, $passengerCnic) {
                $query->whereHas('customer', function ($q) use ($passengerName, $passengerContact, $passengerCnic) {
                    if ($passengerName != '') {
                        $q->where('name', 'LIKE', '%' . $passengerName . '%');
                    }

                    if ($passengerContact != '') {
                        $q->whereRaw("REPLACE(REPLACE(REPLACE(contact, '-', ''), ' ', ''), '+', '') LIKE ?", ['%' . $passengerContact . '%']);
                    }

                    if ($passengerCnic != '') {
                        $q->whereRaw("REPLACE(cnic, '-', '') LIKE ?", ['%' . $passengerCnic . '%']);
                    }
                });
            })
            ->orderBy('id', 'DESC')
            ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000);
    }

    private function ticketSelectColumns(): array
    {
        return [
            'id',
            'terminal_id',
            'schedule_id',
            'schedule_date',
            'schedule_time',
            'schedule_time_exact',
            'customer_id',
            'seat_fare',
            'discount',
            'seat_no',
            'type',
        ];
    }

    private function transformTickets(Collection $tickets): Collection
    {
        return $tickets->map(function ($q) {
            $customer = $q->customer;
            $rescheduleSeat = $q->reschedule_seat;
            $newTicket = optional($rescheduleSeat)->new_ticket;
            $user = User::find(optional($rescheduleSeat)->added_by);

            $q->terminal_name = optional($q->terminal)->name ?? 'N/A';
            $q->reason = optional($rescheduleSeat)->reason ?? 'N/A';
            $q->reschedule_by = $user->name ?? 'N/A';
            $q->reschedule_time = $rescheduleSeat
                ? date('h:i A d-m-Y', strtotime($rescheduleSeat->created_at))
                : 'N/A';

            $q->passenger_name = optional($customer)->name ?? 'N/A';
            $q->passenger_contact = $customer ? formatContact($customer->contact) : 'N/A';
            $q->passenger_cnic = $customer ? formatCNIC($customer->cnic) : 'N/A';

            $q->new_type = $newTicket->type ?? 'N/A';
            $q->old_seat = $q->seat_no ?? 'N/A';
            $q->new_seat = $newTicket->seat_no ?? 'N/A';

            $oldBusTime = $q->schedule_time ?: $q->schedule_time_exact;
            $newBusTime = $newTicket->schedule_time ?? $newTicket->schedule_time_exact ?? null;

            $q->old_bus_time = $oldBusTime
                ? date('h:i A', strtotime($oldBusTime)) . ' ' . date('d-m-Y', strtotime($q->schedule_date))
                : 'N/A';

            $q->new_bus_time = $newBusTime
                ? date('h:i A', strtotime($newBusTime)) . ' ' . date('d-m-Y', strtotime($newTicket->schedule_date ?? $q->schedule_date))
                : 'N/A';

            $q->old_departure = optional(optional($rescheduleSeat)->old_departure)->name ?? 'N/A';
            $q->old_destination = optional(optional($rescheduleSeat)->old_destination)->name ?? 'N/A';
            $q->new_departure = optional(optional($rescheduleSeat)->new_departure)->name ?? 'N/A';
            $q->new_destination = optional(optional($rescheduleSeat)->new_destination)->name ?? 'N/A';

            $q->old_fare = (int) $q->seat_fare - (int) $q->discount;
            $q->new_fare = $newTicket ? ((int) $newTicket->seat_fare - (int) $newTicket->discount) : 0;

            $q->badge = ($rescheduleSeat && $oldBusTime)
                ? getRowBadgeColor(
                    date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($oldBusTime)),
                    $rescheduleSeat->created_at
                )
                : '';

            unset($q->reschedule_seat, $q->schedule, $q->terminal, $q->customer);

            return $q;
        });
    }

    private function getVisibleColumns(Request $request): array
    {
        if (!$request->has('visible_columns')) {
            return self::RESCHEDULE_COLUMNS;
        }

        $visibleColumns = $request->input('visible_columns');

        if (is_string($visibleColumns)) {
            $visibleColumns = trim($visibleColumns) === ''
                ? []
                : array_map('trim', explode(',', $visibleColumns));
        } elseif (!is_array($visibleColumns)) {
            return self::RESCHEDULE_COLUMNS;
        }

        return array_values(array_intersect(self::RESCHEDULE_COLUMNS, $visibleColumns));
    }
}
