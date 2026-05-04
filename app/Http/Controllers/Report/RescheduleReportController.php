<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
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

    $passengerName = trim($request->passenger_name ?? '');
    $passengerContact = preg_replace('/[^0-9]/', '', $request->passenger_contact ?? '');
    $passengerCnic = preg_replace('/[^0-9]/', '', $request->passenger_cnic ?? '');

    $tickets = Ticket::with([
        'reschedule_seat',
        'reschedule_seat.old_departure:id,name',
        'reschedule_seat.old_destination:id,name',
        'reschedule_seat.new_departure:id,name',
        'reschedule_seat.new_destination:id,name',
        'reschedule_seat.new_ticket' => function ($q) {
            $q->withTrashed();
        },
        'terminal:id,name',
        'customer:id,name,contact,cnic'
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
        ->when($request->type != 0, function ($query) use ($request) {
            if ($request->type == 'reschedule') {
                return $query->where('type', 'reschedule');
            }

            return $query->whereHas('reschedule_seat.new_ticket', function ($q) use ($request) {
                $q->withTrashed()->where('type', $request->type);
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
        ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
        ->get([
            "id",
            "terminal_id",
            "schedule_id",
            "schedule_date",
            "schedule_time_exact",
            "customer_id",
            "seat_fare",
            "discount",
            "seat_no",
            "type"
        ]);

    $tickets->map(function ($q) {
        $customer = $q->customer;
        $rescheduleSeat = $q->reschedule_seat;
        $newTicket = optional($rescheduleSeat)->new_ticket;
        $user = User::find(optional($rescheduleSeat)->added_by);

        $q->terminal_name = $q->terminal->name ?? 'N/A';
        $q->reason = $rescheduleSeat->reason ?? 'N/A';
        $q->reschedule_by = $user->name ?? 'N/A';
        $q->reschedule_time = $rescheduleSeat
            ? date("h:i A d-m-Y", strtotime($rescheduleSeat->created_at))
            : 'N/A';

        $q->passenger_name = $customer->name ?? 'N/A';
        $q->passenger_contact = $customer ? formatContact($customer->contact) : 'N/A';
        $q->passenger_cnic = $customer ? formatCNIC($customer->cnic) : 'N/A';

        $q->type = $q->type;
        $q->new_type = $newTicket->type ?? 'N/A';
        $q->old_seat = $q->seat_no ?? 'N/A';
        $q->new_seat = $newTicket->seat_no ?? 'N/A';

        $q->old_bus_time = $q->schedule_time_exact
            ? date('h:i A', strtotime($q->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->schedule_date))
            : 'N/A';

        $q->new_bus_time = $newTicket
            ? date('h:i A', strtotime($newTicket->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($newTicket->schedule_date))
            : 'N/A';

        $q->old_departure = optional($rescheduleSeat->old_departure)->name ?? 'N/A';
        $q->old_destination = optional($rescheduleSeat->old_destination)->name ?? 'N/A';
        $q->new_departure = optional($rescheduleSeat->new_departure)->name ?? 'N/A';
        $q->new_destination = optional($rescheduleSeat->new_destination)->name ?? 'N/A';

        $q->old_fare = (int) $q->seat_fare - (int) $q->discount;
        $q->new_fare = $newTicket ? ((int) $newTicket->seat_fare - (int) $newTicket->discount) : 0;

        $q->badge = $rescheduleSeat
            ? getRowBadgeColor(
                date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule_time_exact)),
                $rescheduleSeat->created_at
            )
            : '';

        unset($q->reschedule_seat, $q->schedule, $q->terminal, $q->customer);

        return $q;
    });

    return $tickets;
}

    public
    function getPrintPdf(Request $request)
    {
        if (!checkForSubmenu("confirm-cancel")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with('reschedule_seat')
            ->with("reschedule_seat.old_departure:id,name", "reschedule_seat.old_destination:id,name")
            ->with("reschedule_seat.new_departure:id,name", "reschedule_seat.new_destination:id,name")
            ->with(['reschedule_seat.new_ticket' => function ($q) {
                return $q->withTrashed();
            }])
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'reschedule')
            ->onlyTrashed()
            ->when($request->terminal != 0, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->when($request->fromDate != '', function ($query) use ($request) {
                return $query->where('schedule_date', '>=', $request->fromDate);
            })
            ->when($request->toDate != '', function ($query) use ($request) {
                return $query->where('schedule_date', '<=', $request->toDate);
            })
            ->orderBy('id', 'DESC')
            ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
            ->get(["id", "terminal_name", "schedule_id", "schedule_date", "schedule_time_exact", "customer_id", "seat_fare", "discount", "seat_no", "type"]);

        $tickets->map(function ($q) {
            $q->reason = $q->reschedule_seat->reason;
            $q->reschedule_by = User::find($q->reschedule_seat->added_by)->name ?? 'N/A';
            $q->reschedule_time = date("h:i A d-m-Y", strtotime($q->reschedule_seat->created_at));
            $customer = Customer::find($q->customer_id);
            $q->passenger_name = $customer->name ?? 'N/A';
            $q->passenger_contact = isset($customer) ? formatContact($customer->contact) : 'N/A';
            $q->passenger_cnic = isset($customer) ? formatCNIC($customer->cnic) : 'N/A';
            $q->type = $q->type;
            $q->new_type = $q->reschedule_seat->new_ticket->type;
            $q->old_seat = $q->seat_no;
            $q->new_seat = $q->reschedule_seat->new_ticket->seat_no;
            $q->old_bus_time = date('h:i A', strtotime($q->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));
            $q->new_bus_time = date('h:i A', strtotime($q->reschedule_seat->new_ticket->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->reschedule_seat->new_ticket->schedule_date));
            $q->old_departure = $q->reschedule_seat->old_departure->name;
            $q->old_destination = $q->reschedule_seat->old_destination->name;
            $q->new_departure = $q->reschedule_seat->new_departure->name;
            $q->new_destination = $q->reschedule_seat->new_destination->name;
            $q->old_fare = (int)$q->seat_fare - (int)$q->discount;
            $q->new_fare = (int)$q->reschedule_seat->new_ticket->seat_fare - (int)$q->reschedule_seat->new_ticket->discount;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule_time_exact)), $q->reschedule_seat->created_at);
            unset($q->reschedule_seat, $q->schedule);
        });
        return view('reports.rescheduleReport', [
            'tickets' => $tickets,
            'visibleColumns' => $this->getVisibleColumns($request),
        ]);
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
