<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use App\Models\CounterExpense;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvanceSalesReportController extends Controller
{
    public function getUserNames()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return User::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function getSchedules()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Schedule::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function getTerminals()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function getRoutes()
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name',"via"]);
    }

   public function filterData(Request $request)
{
    if (!checkForSubmenu("sales")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $tickets = Ticket::with(
        'updated_name:id,name',
        'ticketElt:id,ticket_id,elt_price',
        'terminal:id,name',
        'busClass:id,name',
        'schedule:id,name,time',
        'bus:id,bus_number',
        'customer:id,name,contact,cnic'
    )
        ->withTrashed()
        ->where('company_id', Auth::user()->company_id)
        ->where(function ($query) {
            $query->where("type", "booked")
                ->orWhere("type", "over-issue");
        })
        ->when($request->terminal && $request->terminal != 0, function ($query) use ($request) {
            return $query->where('terminal_id', $request->terminal);
        })
        ->when($request->user && $request->user != 0, function ($query) use ($request) {
            return $query->where('updated_by', $request->user);
        })
        ->when(!empty($request->route) && count($request->route) > 0, function ($query) use ($request) {
            return $query->whereIn('route_id', $request->route);
        })
        ->when($request->invoice_id != '', function ($query) use ($request) {
            return $query->where('invoice_id', 'LIKE', '%' . trim($request->invoice_id) . '%');
        })
        ->when($request->transaction_id != '', function ($query) use ($request) {
            return $query->where('transaction_id', 'LIKE', '%' . trim($request->transaction_id) . '%');
        })
        ->whereHas('customer', function ($query) use ($request) {
            if ($request->passenger_name) {
                $query->where('name', 'LIKE', '%' . trim($request->passenger_name) . '%');
            }

            if ($request->passenger_contact) {
                $query->where('contact', 'LIKE', '%' . str_replace("-", "", trim($request->passenger_contact)) . '%');
            }

            if ($request->passenger_cnic) {
                $query->where('cnic', 'LIKE', '%' . str_replace("-", "", trim($request->passenger_cnic)) . '%');
            }
        })
        ->when($request->counterSale, function ($query) use ($request) {
            return $query->whereBetween('created_at', [
                date("Y-m-d H:i:s", strtotime($request->fromDateTime)),
                date("Y-m-d H:i:s", strtotime($request->toDateTime))
            ]);
        })
        ->orderBy('date', 'desc')
        ->get();

    $tickets->transform(function ($single) {
        $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
        return $single;
    });

    if ($request->fromDateTime && $request->counterSale == false) {
        $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s", strtotime($request->fromDateTime)));
    }

    if ($request->toDateTime && $request->counterSale == false) {
        $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s", strtotime($request->toDateTime)));
    }

    $tickets = $tickets->sortBy('schedule_date_time')->groupBy(['schedule_date_time', 'route_id', 'updated_by']);

    $sortData = [];

    foreach ($tickets as $time) {
        foreach ($time as $route) {
            foreach ($route as $inner) {
                $single = [];
                $single['bus_number'] = $inner[0]->bus->bus_number ?? 'N/A';
                $single['bus_class'] = $inner[0]->busClass->name ?? 'N/A';
                $single['seats'] = $inner->count();
                $single['terminal'] = $inner[0]->terminal->name ?? 'N/A';
                $single['user'] = $inner[0]->updated_name->name ?? 'N/A';
                $single['sales'] = $inner->sum('seat_fare') - $inner->sum('discount');
                $single['date'] = date("Y-m-d", strtotime($inner[0]->schedule_date_time));
                $single['time'] = date("h:i A", strtotime($inner[0]->schedule_date_time));
                $single['invoice_id'] = $inner[0]->invoice_id ?? 'N/A';
$single['transaction_id'] = $inner[0]->transaction_id ?? 'N/A';
$single['passenger_name'] = $inner[0]->customer->name ?? 'N/A';
$single['passenger_contact'] = $inner[0]->customer ? formatContact($inner[0]->customer->contact) : 'N/A';
$single['passenger_cnic'] = $inner[0]->customer ? formatCNIC($inner[0]->customer->cnic) : 'N/A';

                $eltSum = 0;

                foreach ($inner as $tkt) {
                    $eltSum += $tkt->ticketElt ? $tkt->ticketElt->elt_price : 0;
                }

                $single['elt'] = $eltSum;

                array_push($sortData, $single);
            }
        }
    }

    return [
        'record' => $sortData,
        'refund' => [],
        'counterExpenses' => $counterexpenses ?? [],
    ];
}

   public function advanceSalePdf(Request $request)
{
    if (!checkForSubmenu("sales")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $route_ids = $request->route ? explode(",", $request->route) : [];

    $tickets = Ticket::with(
        'updated_name:id,name',
        'ticketElt:id,ticket_id,elt_price',
        'terminal:id,name',
        'busClass:id,name',
        'schedule:id,name,time',
        'bus:id,bus_number',
        'customer:id,name,contact,cnic'
    )
        ->withTrashed()
        ->where('company_id', Auth::user()->company_id)
        ->where(function ($query) {
            $query->where("type", "booked")
                ->orWhere("type", "over-issue");
        })
        ->when($request->terminal && $request->terminal != 0, function ($query) use ($request) {
            return $query->where('terminal_id', $request->terminal);
        })
        ->when($request->user && $request->user != 0, function ($query) use ($request) {
            return $query->where('updated_by', $request->user);
        })
        ->when($request->route && count($route_ids) > 0, function ($query) use ($route_ids) {
            return $query->whereIn('route_id', $route_ids);
        })
        ->when($request->invoice_id != '', function ($query) use ($request) {
            return $query->where('invoice_id', 'LIKE', '%' . trim($request->invoice_id) . '%');
        })
        ->when($request->transaction_id != '', function ($query) use ($request) {
            return $query->where('transaction_id', 'LIKE', '%' . trim($request->transaction_id) . '%');
        })
        ->whereHas('customer', function ($query) use ($request) {
            if ($request->passenger_name) {
                $query->where('name', 'LIKE', '%' . trim($request->passenger_name) . '%');
            }

            if ($request->passenger_contact) {
                $query->where('contact', 'LIKE', '%' . str_replace("-", "", trim($request->passenger_contact)) . '%');
            }

            if ($request->passenger_cnic) {
                $query->where('cnic', 'LIKE', '%' . str_replace("-", "", trim($request->passenger_cnic)) . '%');
            }
        })
        ->when(($request->counterSale == "true"), function ($query) use ($request) {
            return $query->whereBetween('created_at', [
                date("Y-m-d H:i:s", strtotime($request->fromDateTime)),
                date("Y-m-d H:i:s", strtotime($request->toDateTime))
            ]);
        })
        ->orderBy('date', 'desc')
        ->get();

    $tickets->transform(function ($single) {
        $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
        return $single;
    });

    if ($request->fromDateTime && $request->counterSale == "false") {
        $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s", strtotime($request->fromDateTime)));
    }

    if ($request->toDateTime && $request->counterSale == "false") {
        $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s", strtotime($request->toDateTime)));
    }

    $tickets = $tickets->sortBy('schedule_date_time')->groupBy(['schedule_date_time', 'route_id', 'updated_by']);

    $sortData = [];

    foreach ($tickets as $time) {
        foreach ($time as $route) {
            foreach ($route as $inner) {

                $single = [];
                $single['bus_number'] = $inner[0]->bus->bus_number ?? 'N/A';
                $single['bus_class'] = $inner[0]->busClass->name ?? 'N/A';
                $single['seats'] = $inner->count();
                $single['terminal'] = $inner[0]->terminal->name ?? 'N/A';
                $single['user'] = $inner[0]->updated_name->name ?? 'N/A';

                $single['invoice_id'] = $inner[0]->invoice_id ?? 'N/A';
                $single['transaction_id'] = $inner[0]->transaction_id ?? 'N/A';
                $single['passenger_name'] = $inner[0]->customer->name ?? 'N/A';
                $single['passenger_contact'] = $inner[0]->customer ? formatContact($inner[0]->customer->contact) : 'N/A';
                $single['passenger_cnic'] = $inner[0]->customer ? formatCNIC($inner[0]->customer->cnic) : 'N/A';

                $single['sales'] = $inner->sum('seat_fare') - $inner->sum('discount');
                $single['date'] = date("Y-m-d", strtotime($inner[0]->schedule_date_time));
                $single['time'] = date("h:i A", strtotime($inner[0]->schedule_date_time));

                $eltSum = 0;

                foreach ($inner as $tkt) {
                    if ($tkt->ticketElt) {
                        $eltSum += $tkt->ticketElt->elt_price;
                    } else {
                        $eltSum += 0;
                    }
                }

                $single['elt'] = $eltSum;

                array_push($sortData, $single);
            }
        }
    }

    $filterData = (object)[];
    $filterData->terminal = Terminal::find($request->terminal)->name ?? "All";
    $filterData->user = User::find($request->user)->name ?? "All";
    $filterData->route = Route::whereIn("id", $route_ids)->pluck("name")->toArray();
    $filterData->from = date("Y/m/d H:i A", strtotime($request->fromDateTime));
    $filterData->to = date("Y/m/d h:i A", strtotime($request->toDateTime));

    return view('reports.advanceSaleReport', [
        'record' => $sortData,
        'refund' => [],
        'counterExpenses' => $counterexpenses ?? [],
        'filterData' => $filterData,
    ]);
}


}
