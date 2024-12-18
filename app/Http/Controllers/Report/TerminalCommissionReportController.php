<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use App\Models\CounterExpense;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Terminal;
use App\Models\TerminalCommission;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerminalCommissionReportController extends Controller
{
    public function getUserNames()
    {
        if(!checkForSubmenu("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return User::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }
    

    public function getTerminals()
    {
        if(!checkForSubmenu("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function getRoutes()
    {
        if(!checkForSubmenu("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function filterData(Request $request)
    {
        if(!checkForSubmenu("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time',"bus:id,bus_number")
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'booked')
            ->selectRaw('*, CONCAT(schedule_date, " ", schedule_time_exact) AS schedule_date_time')
            ->where(function($query) use ($request){
                if($request->terminal)
                {
                    return $query->where('terminal_id', $request->terminal);
                }
            })
            ->when($request->user, function ($query) use ($request) {
                return $query->where('updated_by', $request->user);
            })
            ->when($request->route, function ($query) use ($request) {
                return $query->whereIn('route_id', $request->route);
            })
            ->whereBetween('schedule_date', [date("Y-m-d",strtotime($request->fromDateTime)), date("Y-m-d",strtotime($request->toDateTime))])
            
            ->get();
        
        $tickets = $tickets->whereBetween('schedule_date_time', [date("Y-m-d H:i:s",strtotime($request->fromDateTime)), date("Y-m-d H:i:s",strtotime($request->toDateTime))])->groupBy(['schedule_date_time','terminal_id']); 

        $sortData = [];
        foreach ($tickets as $time) {
            foreach ($time as $inner) {
                $terminalComm = 0;
                $fixedComm = 0;
                $terminalCommission = TerminalCommission::where(["terminal_id"=>$inner[0]->terminal_id,"route_id"=>$inner[0]->route_id,"company_id"=>Auth::user()->company_id])->first();
                if($terminalCommission)
                {   
                    if($terminalCommission->flat_commission == 0)
                        $terminalComm = (($inner->sum('seat_fare') - $inner->sum('discount'))/100)*$terminalCommission->percentage_commission;
                    else
                    {
                        $terminalComm = $inner->count()*$terminalCommission->flat_commission;
                    }

                    $fixedComm = $terminalCommission->fix_commission??0;
                }
                
                $single = [];
                $single['bus_number'] = $inner[0]->bus->bus_number??'N/A';
                $single['bus_class'] = $inner[0]->busClass->name;
                $single['seats'] = $inner->count();
                $single['terminal'] = $inner[0]->terminal->name;
                $single['fix_commission'] = intVal($fixedComm);
                $single['terminal_commission'] = intVal($terminalComm);
                $single['sales'] = $inner->sum('seat_fare') - $inner->sum('discount');
                $single['date'] = date("Y-m-d",strtotime($inner[0]->schedule_date_time));
                $single['time'] = date("h:i A",strtotime($inner[0]->schedule_date_time));
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
            
        return [
            'record' => $sortData,
        ];

    }

    public function advanceSalePdf(Request $request)
    {
        return 'this is need to be updated';
        if(!checkForSubmenu("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $route = explode(",",$request->route);
        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time',"bus:id,bus_number")
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'booked')
            
            ->where(function($query) use ($request){
                if($request->terminal)
                {
                    return $query->where('terminal_id', $request->terminal);
                }
                else
                {
                    return $query->where('terminal_id', Auth::user()->terminal_id);
                }
            })
            ->when($request->user, function ($query) use ($request) {
                return $query->where('updated_by', $request->user);
            })
            ->when($request->route, function ($query) use ($request) {
                return $query->whereIn('route_id',  explode(",",$request->route));
            })
            ->orderBy('date', 'desc')
            ->get();

        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time));
            return $single;
        });

        // date filter
        if($request->fromDateTime)
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime)
        {
            $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s",strtotime($request->toDateTime)));
        }
        // return $tickets;
        $tickets = $tickets->sortBy('schedule_date_time')->groupBy(['schedule_date_time','route_id', 'updated_by']); 

        $sortData = [];
        foreach ($tickets as $time) {
            foreach ($time as $route) {
                foreach ($route as $inner) {
                
                    $single = [];
                    $single['bus_number'] = $inner[0]->bus->bus_number??'N/A';
                    $single['bus_class'] = $inner[0]->busClass->name;
                    $single['seats'] = $inner->count();
                    $single['terminal'] = $inner[0]->terminal->name;
                    $single['user'] = $inner[0]->updated_name->name??'N/A';
                    $single['sales'] = $inner->sum('seat_fare') - $inner->sum('discount');
                    $single['date'] = date("Y-m-d",strtotime($inner[0]->schedule_date_time));
                    $single['time'] = date("h:i A",strtotime($inner[0]->schedule_date_time));
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

//        Refund Data Details

        // $refundTickets = Ticket::with('cancel_ticket', 'schedule:id,time')->where('company_id', Auth::user()->company_id)
        //     ->where('type', 'canceled')->withTrashed()
        //     ->when($request->terminal, function ($query) use ($request) {
        //         return $query->where('terminal_id', $request->terminal);
        //     })
        //     ->when($request->user, function ($query) use ($request) {
        //         return $query->where('added_by', $request->user);
        //     })
        //     ->when($request->route, function ($query) use ($request) {
        //         return $query->whereIn('route_id',  explode(",",$request->route));
        //     })
        //     ->when($request->fromDateTime, function ($query) use ($request) {
        //         return $query->where('schedule_time', '>=', $request->fromDateTime);
        //     })->when($request->toDateTime, function ($query) use ($request) {
        //         return $query->where('schedule_time', '<=', $request->toDateTime);
        //     })->get();
        // $refundTickets->map(function ($q) {
        //     $q->cancel_percentage = $q->cancel_ticket->percentage;
        //     $user = User::find($q->cancel_ticket->added_by);
        //     $q->refund_by = $user ? $user->name : '-';
        //     $q->cancel_date = $q->cancel_ticket->time;
        //     $q->bus_NO = BusClass::find($q->bus_class_id)->name;
        //     $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
        //     $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
        //     $final = $percentageValue / 100;
        //     $q->amount_refund = round((int)$q->seat_fare - $final);
        //     $q->cancelation_charges = round($final);
        //     unset($q->cancel_ticket, $q->schedule);
        // });
        // Counter expenses data
        if ((int)$request->terminal !== 0 || (int)$request->user !== 0 || $request->fromDateTime || $request->toDateTime) {
        $counterexpenses = CounterExpense::with('added_by', 'terminal')->where('company_id', Auth::user()->company_id)
            ->when($request->terminal, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->when($request->user, function ($query) use ($request) {
                return $query->where('added_by', $request->user);
            })
            ->when($request->fromDateTime, function ($query) use ($request) {
                return $query->where('time', '>=', $request->fromDateTime);
            })
            ->when($request->toDateTime, function ($query) use ($request) {
                return $query->where('time', '<=', $request->toDateTime);
            })
            ->get();
        }
        $filterData = (object)[];
        $filterData->terminal = Terminal::find($request->terminal)->name??"All";
        $filterData->user = User::find($request->terminal)->name??"All";
        $filterData->route = Route::whereIn("id",$route)->pluck("name")->toArray()??"All";
        $filterData->from = date("Y/m/d H:i A",strtotime($request->fromDateTime));
        $filterData->to = date("Y/m/d h:i A",strtotime($request->toDateTime));
       
    
    // return $counterexpenses;
        return view('reports.advanceSaleReport', [
            'record' => $sortData,
            'refund' =>[],
            'counterExpenses' => $counterexpenses ?? [],
            'filterData' => $filterData,
        ]);
    }


}
