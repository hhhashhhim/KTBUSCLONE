<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Customer;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
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

    $tickets = Ticket::with('cancel_ticket', 'schedule:id,time,route_id', 'terminal', 'route')
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
                // to show all
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
        ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
     ->get([
    "id",
    "terminal_id",
    "route_id",
    "schedule_id",
    "schedule_date",
    "customer_id",
    "seat_fare",
    "discount",
    "seat_no",
    "invoice_id",
    "transaction_id"
]);

    $tickets->map(function ($q) {

        // Cancel percentage & basic info
        $q->cancel_percentage = $q->cancel_ticket->percentage;
        $q->type              = $q->cancel_ticket->type;
        $q->cancel_reason     = $q->cancel_ticket->reason;

        // Cancel by user OR auto cancel
        $user = User::find($q->cancel_ticket->added_by);

        if ($user) {
            $q->cancel_by = $user->name;
        } else {
            $q->cancel_by = 'Auto Cancel';
            $q->cancel_reason = $q->cancel_ticket->reason;
        }

        // Dates & times
        $q->cancel_date = date(
            "h:i A d-m-Y",
            strtotime($q->cancel_ticket->created_at)
        );

        $q->bus_time =
            date('h:i A', strtotime($q->schedule->time)) . ' ' .
            date('d-m-Y', strtotime($q->schedule_date));

        // Passenger info
        $customer = Customer::find($q->customer_id);
        $q->passenger_name    = $customer->name ?? 'N/A';
        $q->passenger_contact = isset($customer)
            ? formatContact($customer->contact)
            : 'N/A';
        $q->passenger_cnic = isset($customer)
            ? formatCNIC($customer->cnic)
            : 'N/A';

        // Fare calculations
        $q->total_fare = (int) $q->seat_fare - (int) $q->discount;

        $percentageValue = $q->total_fare * $q->cancel_percentage;
        $final = $percentageValue / 100;

        $q->amount_refund = (int) $q->seat_fare - $final;
        $q->cancelation_charges = round($final);

        // Badge
        $q->badge = getRowBadgeColor(
            date('Y-m-d', strtotime($q->schedule_date)) . ' ' .
                date('H:i:s', strtotime($q->schedule->time)),
            $q->cancel_ticket->time
        );

        // Cleanup
        unset($q->cancel_ticket, $q->schedule);

        return $q;
    });


    return $tickets;
}

    public function getPrintPdf(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')
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
        ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
        ->get([
            "id",
            "terminal_name",
            "route_id",
            "schedule_id",
            "schedule_date",
            "customer_id",
            "seat_fare",
            "discount",
            "seat_no",
            "invoice_id",
            "transaction_id"
        ]);

    $tickets->map(function ($q) {
        $q->cancel_percentage = $q->cancel_ticket->percentage;
        $q->type = $q->cancel_ticket->type;
        $q->cancel_reason = $q->cancel_ticket->reason;
        $q->cancel_by = User::find($q->cancel_ticket->added_by)->name ?? 'N/A';
        $q->cancel_date = date("h:i A d-m-Y", strtotime($q->cancel_ticket->created_at));
        $q->bus_time = date('h:i A', strtotime($q->schedule->time)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));

        $customer = Customer::find($q->customer_id);
        $q->passenger_name = $customer->name ?? 'N/A';
        $q->passenger_contact = isset($customer) ? formatContact($customer->contact) : 'N/A';
        $q->passenger_cnic = isset($customer) ? formatCNIC($customer->cnic) : 'N/A';

        $route = Route::find($q->route_id);
        $q->route_name = $route->name ?? 'N/A';

        $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
        $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
        $final = $percentageValue / 100;

        $q->amount_refund = (int)$q->seat_fare - $final;
        $q->cancelation_charges = round($final);

        $q->badge = getRowBadgeColor(
            date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)),
            $q->cancel_ticket->time
        );

        unset($q->cancel_ticket, $q->schedule);
    });

    return view('reports.confirmCancelReport', [
        'tickets' => $tickets,
        'visibleColumns' => $this->getVisibleColumns($request),
    ]);
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
