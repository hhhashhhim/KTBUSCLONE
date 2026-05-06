<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ConfirmCancellationReportController extends Controller
{
    private const CONFIRM_CANCELLATION_COLUMNS = [
        'bus_time',
        'cancel_date',
        'remarks',
        'terminal_name',
        'route',
        'transaction_id',
        'invoice',
        'cancel_by',
        'seat_no',
        'type',
        'passenger_name',
        'passenger_contact',
        'passenger_cnic',
        'total_fare',
        'cancel_percentage',
        'amount_refund',
        'cancelation_charges',
    ];

    public function getTerminals()
    {
        if (!checkForSubmenu("confirm-cancel")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }
public function buses()
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $buses = Bus::where('company_id', Auth::user()->company_id)
        ->orderBy('bus_number', 'ASC')
        ->get()
        ->map(function ($bus) {
            return [
                'id'   => $bus->id,
                'text' => $bus->bus_number
            ];
        });

    return response()->json($buses);
}
   public function routes()
    {
        if (!checkForSubmenu("confirm-cancel")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where(['company_id' => Auth::user()->company_id, "hide" => 0])->get();
    }
    public function filterData(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $tickets = $this->buildFilteredTicketsQuery($request)->get([
        "id",
        "terminal_id",
        "terminal_name",
        "route_id",
        "schedule_id",
        "schedule_date",
        "schedule_time",
        "schedule_time_exact",
        "customer_id",
        "seat_fare",
        "discount",
        "seat_no",
        "invoice_id",
        "transaction_id"
    ]);

    $this->hydrateReportRows($tickets);

    return $tickets;
}

    public function getPrintPdf(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $tickets = $this->buildFilteredTicketsQuery($request)
        ->get([
            "id",
            "terminal_id",
            "terminal_name",
            "route_id",
            "schedule_id",
            "schedule_date",
            "schedule_time",
            "schedule_time_exact",
            "customer_id",
            "seat_fare",
            "discount",
            "seat_no",
            "invoice_id",
            "transaction_id"
        ]);

    $this->hydrateReportRows($tickets);

    return view('reports.confirmCancelReport', [
        'tickets' => $tickets,
        'visibleColumns' => $this->getVisibleColumns($request),
    ]);
}

    private function buildFilteredTicketsQuery(Request $request)
    {
        return Ticket::with([
            'cancel_ticket:id,ticket_id,percentage,reason,type,added_by,time,created_at',
            'cancel_ticket.addedBy:id,name',
            'schedule:id,time,route_id',
            'terminal:id,name',
            'route:id,name',
            'customer:id,name,contact,cnic',
        ])
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'canceled')
            ->onlyTrashed()
            ->when($request->terminal != 0, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->when($request->route != 0, function ($query) use ($request) {
                return $query->where('route_id', $request->route);
            })
            ->when($request->invoice_id, function ($query) use ($request) {
                return $query->where('invoice_id', 'LIKE', '%' . $request->invoice_id . '%');
            })
            ->when($request->transaction_id, function ($query) use ($request) {
                return $query->where('transaction_id', 'LIKE', '%' . $request->transaction_id . '%');
            })
            ->when($request->fromDate != '', function ($query) use ($request) {
                return $query->where('schedule_date', '>=', $request->fromDate);
            })
            ->when($request->toDate != '', function ($query) use ($request) {
                return $query->where('schedule_date', '<=', $request->toDate);
            })
            ->whereHas('cancel_ticket', function ($query) use ($request) {
                if ($request->type != 0) {
                    $query->where('type', $request->type);
                } else {
                    $query->whereIn('type', ["advance booking", "booked"]);
                }
            })
            ->whereHas('customer', function ($query) use ($request) {
                if ($request->passenger_name) {
                    $query->where('name', 'LIKE', '%' . $request->passenger_name . '%');
                }

                if ($request->passenger_contact) {
                    $query->where('contact', 'LIKE', '%' . str_replace("-", "", $request->passenger_contact) . '%');
                }

                if ($request->passenger_cnic) {
                    $query->where('cnic', 'LIKE', '%' . str_replace("-", "", $request->passenger_cnic) . '%');
                }
            })
            ->orderBy('id', 'DESC')
            ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000);
    }

    private function hydrateReportRows($tickets): void
    {
        $terminalTimeDifferences = TerminalTimeDifference::where('company_id', Auth::user()->company_id)
            ->whereIn('terminal_id', $tickets->pluck('terminal_id')->filter()->unique()->values())
            ->whereIn('route_id', $tickets->pluck('route_id')->filter()->unique()->values())
            ->get()
            ->keyBy(function ($item) {
                return $item->terminal_id . '-' . $item->route_id;
            });

        $tickets->transform(function ($ticket) use ($terminalTimeDifferences) {
            $customer = $ticket->customer;
            $cancelTicket = $ticket->cancel_ticket;

            $ticket->cancel_percentage = optional($cancelTicket)->percentage;
            $ticket->type = optional($cancelTicket)->type ?? $ticket->type;
            $ticket->cancel_reason = optional($cancelTicket)->reason;
            $ticket->cancel_by = optional(optional($cancelTicket)->addedBy)->name ?? 'Auto Cancel';

            $busDateTime = $this->getTerminalWiseBusDateTime($ticket, $terminalTimeDifferences);
            $cancellationDateTime = $this->getCancellationDateTime($cancelTicket);

            $ticket->bus_datetime = $busDateTime ? $busDateTime->format('Y-m-d H:i:s') : null;
            $ticket->bus_time = $busDateTime ? $busDateTime->format('h:i A d-m-Y') : 'N/A';
            $ticket->cancellation_datetime = $cancellationDateTime ? $cancellationDateTime->format('Y-m-d H:i:s') : null;
            $ticket->cancel_date = $cancellationDateTime ? $cancellationDateTime->format('h:i A d-m-Y') : 'N/A';

            $ticket->is_late_cancelled = $busDateTime && $cancellationDateTime
                ? $cancellationDateTime->greaterThanOrEqualTo($busDateTime)
                : null;
            $ticket->cancellation_status_color = $this->getCancellationStatusColor($busDateTime, $cancellationDateTime);
            $ticket->badge = $ticket->cancellation_status_color;

            $ticket->terminal_name = $ticket->terminal->name ?? $ticket->terminal_name ?? 'N/A';
            $ticket->route_name = $ticket->route->name ?? 'N/A';
            $ticket->passenger_name = $customer->name ?? 'N/A';
            $ticket->passenger_contact = $customer ? formatContact($customer->contact) : 'N/A';
            $ticket->passenger_cnic = $customer ? formatCNIC($customer->cnic) : 'N/A';

            $ticket->total_fare = (int) $ticket->seat_fare - (int) $ticket->discount;
            $percentageValue = $ticket->total_fare * (int) $ticket->cancel_percentage;
            $final = $percentageValue / 100;
            $ticket->amount_refund = (int) $ticket->seat_fare - $final;
            $ticket->cancelation_charges = round($final);

            unset($ticket->cancel_ticket, $ticket->schedule, $ticket->customer);

            return $ticket;
        });
    }

    private function getTerminalWiseBusDateTime($ticket, $terminalTimeDifferences): ?Carbon
    {
        $baseTime = $ticket->schedule_time
            ?? $ticket->schedule_time_exact
            ?? optional($ticket->schedule)->time;

        if (!$ticket->schedule_date || !$baseTime) {
            return null;
        }

        $dateTime = Carbon::parse($ticket->schedule_date . ' ' . $baseTime);
        $terminalTime = $terminalTimeDifferences->get($ticket->terminal_id . '-' . $ticket->route_id);

        if ($terminalTime) {
            $dateTime->addSeconds((int) round($terminalTime->time_difference * 60));
        }

        return $dateTime;
    }

    private function getCancellationDateTime($cancelTicket): ?Carbon
    {
        if (!$cancelTicket) {
            return null;
        }

        $cancellationDateTime = $cancelTicket->time ?? $cancelTicket->created_at ?? null;

        return $cancellationDateTime ? Carbon::parse($cancellationDateTime) : null;
    }

    private function getCancellationStatusColor(?Carbon $busDateTime, ?Carbon $cancellationDateTime): string
    {
        if (!$busDateTime || !$cancellationDateTime) {
            return 'white';
        }

        return getRowBadgeColor(
            $busDateTime->format('Y-m-d H:i:s'),
            $cancellationDateTime->format('Y-m-d H:i:s')
        );
    }

    private function getVisibleColumns(Request $request): array
    {
        if (!$request->has('visible_columns')) {
            return self::CONFIRM_CANCELLATION_COLUMNS;
        }

        $visibleColumns = $request->input('visible_columns');

        if (is_string($visibleColumns)) {
            $visibleColumns = trim($visibleColumns) === ''
                ? []
                : array_map('trim', explode(',', $visibleColumns));
        } elseif (!is_array($visibleColumns)) {
            return self::CONFIRM_CANCELLATION_COLUMNS;
        }

        return array_values(array_intersect(self::CONFIRM_CANCELLATION_COLUMNS, $visibleColumns));
    }
}
