<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OverissueReportController extends Controller
{
    public function getTerminals()
    {
        if(!checkForSubmenu("confirm-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
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

    $tickets = Ticket::with(['overIssueSeats', 'schedule:id,time', 'terminal:id,name', 'customer:id,name,contact,cnic', 'route:id,name'])
        ->where('company_id', Auth::user()->company_id)
        ->where('type', 'over-issue')
        ->onlyTrashed()
        ->when($request->terminal != 0, function ($query) use ($request) {
            return $query->where('terminal_id', $request->terminal);
        })
        ->when($request->route != 0, function ($query) use ($request) {
            return $query->where('route_id', $request->route);
        })
        ->when($request->invoice_id != '', function ($query) use ($request) {
            return $query->where('invoice_id', 'LIKE', '%' . $request->invoice_id . '%');
        })
        ->when($request->transaction_id != '', function ($query) use ($request) {
            return $query->where('transaction_id', 'LIKE', '%' . $request->transaction_id . '%');
        })
        ->when($request->fromDate != '', function ($query) use ($request) {
            return $query->where('schedule_date', '>=', $request->fromDate);
        })
        ->when($request->toDate != '', function ($query) use ($request) {
            return $query->where('schedule_date', '<=', $request->toDate);
        })
        ->when($request->type != 0, function ($query) use ($request) {
            return $query->where('type', $request->type);
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
            "type",
            "invoice_id",
            "transaction_id"
        ]);

    $tickets->map(function ($q) {
        $q->terminal_name = $q->terminal->name ?? 'N/A';
        $q->route_id = $q->route_id ?? 'N/A';

        $q->overissue_reason = $q->overIssueSeats->reason ?? 'N/A';
        $q->overissue_by = optional(User::find(optional($q->overIssueSeats)->added_by))->name ?? 'N/A';
        $q->overissue_time = $q->overIssueSeats
            ? date("h:i A d-m-Y", strtotime($q->overIssueSeats->created_at))
            : 'N/A';

        $q->bus_time = $q->schedule
            ? date('h:i A', strtotime($q->schedule->time)) . ' ' . date('d-m-Y', strtotime($q->schedule_date))
            : 'N/A';

        $q->passenger_name = $q->customer->name ?? 'N/A';
        $q->passenger_contact = $q->customer ? formatContact($q->customer->contact) : 'N/A';
        $q->passenger_cnic = $q->customer ? formatCNIC($q->customer->cnic) : 'N/A';
        $q->total_fare = (int) $q->seat_fare - (int) $q->discount;

        $q->badge = ($q->schedule && $q->overIssueSeats)
            ? getRowBadgeColor(
                date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)),
                $q->overIssueSeats->created_at
            )
            : '';

        unset($q->overIssueSeats, $q->schedule, $q->terminal, $q->customer);

        return $q;
    });

    return $tickets;
}

   public function getPrintPdf(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $tickets = Ticket::with(['overIssueSeats', 'schedule:id,time', 'terminal:id,name', 'customer:id,name,contact,cnic'])
        ->where('company_id', Auth::user()->company_id)
        ->where('type', 'over-issue')
        ->onlyTrashed()
        ->when($request->terminal != 0, function ($query) use ($request) {
            return $query->where('terminal_id', $request->terminal);
        })
        ->when($request->route != 0, function ($query) use ($request) {
            return $query->where('route_id', $request->route);
        })
        ->when($request->invoice_id != '', function ($query) use ($request) {
            return $query->where('invoice_id', 'LIKE', '%' . $request->invoice_id . '%');
        })
        ->when($request->transaction_id != '', function ($query) use ($request) {
            return $query->where('transaction_id', 'LIKE', '%' . $request->transaction_id . '%');
        })
        ->when($request->fromDate != '', function ($query) use ($request) {
            return $query->where('schedule_date', '>=', $request->fromDate);
        })
        ->when($request->toDate != '', function ($query) use ($request) {
            return $query->where('schedule_date', '<=', $request->toDate);
        })
        ->when($request->type != 0, function ($query) use ($request) {
            return $query->where('type', $request->type);
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
            "terminal_name",
            "route_id",
            "schedule_id",
            "schedule_date",
            "customer_id",
            "seat_fare",
            "discount",
            "seat_no",
            "type",
            "invoice_id",
            "transaction_id"
        ]);

    $tickets->map(function ($q) {
        $q->terminal_name = $q->terminal->name ?? $q->terminal_name ?? 'N/A';

        $route = Route::find($q->route_id);
        $q->route_name = $route->name ?? 'N/A';

        $q->overissue_reason = $q->overIssueSeats->reason ?? 'N/A';
        $q->overissue_by = optional(User::find(optional($q->overIssueSeats)->added_by))->name ?? 'N/A';

        $q->overissue_date = $q->overIssueSeats
            ? date("h:i A d-m-Y", strtotime($q->overIssueSeats->created_at))
            : 'N/A';

        $q->bus_time = $q->schedule
            ? date('h:i A', strtotime($q->schedule->time)) . ' ' . date('d-m-Y', strtotime($q->schedule_date))
            : 'N/A';

        $q->passenger_name = $q->customer->name ?? 'N/A';
        $q->passenger_contact = $q->customer ? formatContact($q->customer->contact) : 'N/A';
        $q->passenger_cnic = $q->customer ? formatCNIC($q->customer->cnic) : 'N/A';

        $q->total_fare = (int)$q->seat_fare - (int)$q->discount;

        $q->badge = ($q->schedule && $q->overIssueSeats)
            ? getRowBadgeColor(
                date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)),
                $q->overIssueSeats->created_at
            )
            : '';

        unset($q->overIssueSeats, $q->schedule, $q->terminal, $q->customer);
    });

    return view('reports.overIssueReport', ['tickets' => $tickets]);
}
}
