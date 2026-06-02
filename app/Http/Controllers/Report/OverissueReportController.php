<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use App\Support\ReportFilterScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OverissueReportController extends Controller
{
    private const OVERISSUE_COLUMNS = [
        'bus_time',
        'terminal_name',
        'route',
        'transaction_id',
        'invoice',
        'seat_no',
        'type',
        'passenger_name',
        'passenger_contact',
        'passenger_cnic',
        'total_fare',
        'remarks',
        'overissue_by',
        'overissue_time',
    ];

    public function getTerminals()
    {
        if(!checkForSubmenu("confirm-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::terminals('over-issue-terminal-filter');
    }
    public function getUsers()
    {
        if(!checkForSubmenu("confirm-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::users('over-issue-user-filter');
    }
public function routes()
    {
        if (!checkForSubmenu("confirm-cancel")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ReportFilterScope::routes('over-issue-route-filter');
    }
    public function filterData(Request $request)
{
    if (!checkForSubmenu("confirm-cancel")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $terminalId = ReportFilterScope::terminalId($request, 'over-issue-terminal-filter');
    $routeIds = ReportFilterScope::routeIds($request, 'over-issue-route-filter');
    $userId = ReportFilterScope::userId($request, 'over-issue-user-filter');

    $tickets = Ticket::with(['overIssueSeats', 'schedule:id,time', 'terminal:id,name', 'customer:id,name,contact,cnic', 'route:id,name'])
        ->where('company_id', Auth::user()->company_id)
        ->where('type', 'over-issue')
        ->onlyTrashed()
        ->when($terminalId, function ($query) use ($terminalId) {
            return $query->where('terminal_id', $terminalId);
        })
        ->when($routeIds !== null, function ($query) use ($routeIds) {
            return empty($routeIds) ? $query->whereRaw('1 = 0') : $query->whereIn('route_id', $routeIds);
        })
        ->whereHas('overIssueSeats', function ($query) use ($userId) {
            if ($userId) {
                $query->where('added_by', $userId);
            }
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
                Carbon::parse($q->schedule_date . ' ' . $q->schedule->time)->format('d-m-Y h:i A'),
                Carbon::parse($q->overIssueSeats->created_at)->format('d-m-Y h:i A')
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

    $terminalId = ReportFilterScope::terminalId($request, 'over-issue-terminal-filter');
    $routeIds = ReportFilterScope::routeIds($request, 'over-issue-route-filter');
    $userId = ReportFilterScope::userId($request, 'over-issue-user-filter');

    $tickets = Ticket::with(['overIssueSeats', 'schedule:id,time', 'terminal:id,name', 'customer:id,name,contact,cnic'])
        ->where('company_id', Auth::user()->company_id)
        ->where('type', 'over-issue')
        ->onlyTrashed()
        ->when($terminalId, function ($query) use ($terminalId) {
            return $query->where('terminal_id', $terminalId);
        })
        ->when($routeIds !== null, function ($query) use ($routeIds) {
            return empty($routeIds) ? $query->whereRaw('1 = 0') : $query->whereIn('route_id', $routeIds);
        })
        ->whereHas('overIssueSeats', function ($query) use ($userId) {
            if ($userId) {
                $query->where('added_by', $userId);
            }
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
                Carbon::parse($q->schedule_date . ' ' . $q->schedule->time)->format('d-m-Y h:i A'),
                Carbon::parse($q->overIssueSeats->created_at)->format('d-m-Y h:i A')
            )
            : '';

        unset($q->overIssueSeats, $q->schedule, $q->terminal, $q->customer);
    });

    return view('reports.overIssueReport', [
        'tickets' => $tickets,
        'visibleColumns' => $this->getVisibleColumns($request),
    ]);
}

    private function getVisibleColumns(Request $request): array
    {
        if (!$request->has('visible_columns')) {
            return self::OVERISSUE_COLUMNS;
        }

        $visibleColumns = $request->input('visible_columns');

        if (is_string($visibleColumns)) {
            $visibleColumns = trim($visibleColumns) === ''
                ? []
                : array_map('trim', explode(',', $visibleColumns));
        } elseif (!is_array($visibleColumns)) {
            return self::OVERISSUE_COLUMNS;
        }

        return array_values(array_intersect(self::OVERISSUE_COLUMNS, $visibleColumns));
    }
}
