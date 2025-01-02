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
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time',"bus:id,bus_number")
            ->withTrashed()
            ->where('company_id', Auth::user()->company_id)
             ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            
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
                return $query->whereIn('route_id', $request->route);
            })
            ->when($request->counterSale, function ($query) use ($request) {
                return $query->whereBetween('created_at', [date("Y-m-d H:i:s",strtotime($request->fromDateTime)),date("Y-m-d H:i:s",strtotime($request->toDateTime))]);
            })
            ->orderBy('date', 'desc')
            ->get();

        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
            return $single;
        });

        // date filter
        if($request->fromDateTime && $request->counterSale == false)
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime && $request->counterSale == false)
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
        //         return $query->whereIn('route_id', $request->route);
        //     })
        //     ->get();
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
        // if ((int)$request->terminal !== 0 || (int)$request->user !== 0 || $request->fromDateTime || $request->toDateTime) {
        //     $counterexpenses = CounterExpense::with('added_by', 'terminal')->where('company_id', Auth::user()->company_id)
        //         ->when($request->terminal, function ($query) use ($request) {
        //             return $query->where('terminal_id', $request->terminal);
        //         })
        //         ->when($request->user, function ($query) use ($request) {
        //             return $query->where('added_by', $request->user);
        //         })
        //         ->when($request->fromDateTime, function ($query) use ($request) {
        //             return $query->where('time', '>=', $request->fromDateTime);
        //         })
        //         ->when($request->toDateTime, function ($query) use ($request) {
        //             return $query->where('time', '<=', $request->toDateTime);
        //         })
        //         ->get();
        //     }
            
            
            return [
                'record' => $sortData,
                'refund' => [],
                'counterExpenses' => $counterexpenses ?? [],
        ];

    }

    public function advanceSalePdf(Request $request)
    {
        if(!checkForSubmenu("sales"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $route_ids = $request->route ? explode(",",$request->route) : [];
        $tickets = Ticket::with('updated_name:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time',"bus:id,bus_number")
            ->withTrashed()
            ->where('company_id', Auth::user()->company_id)
             ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            
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
            ->when($request->route, function ($query) use ($route_ids) {
                return $query->whereIn('route_id', $route_ids);
            })
            ->when(($request->counterSale == "true"), function ($query) use ($request) {
                return $query->whereBetween('created_at', [date("Y-m-d H:i:s",strtotime($request->fromDateTime)),date("Y-m-d H:i:s",strtotime($request->toDateTime))]);
            })
            ->orderBy('date', 'desc')
            ->get();
        
        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
            return $single;
        });

        // date filter
        if($request->fromDateTime && $request->counterSale == "false")
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime && $request->counterSale == "false")
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
        
        
        $filterData = (object)[];
        $filterData->terminal = Terminal::find($request->terminal)->name??"All";
        $filterData->user = User::find($request->terminal)->name??"All";
        $filterData->route = Route::whereIn("id",$route_ids)->pluck("name")->toArray();
        $filterData->from = date("Y/m/d H:i A",strtotime($request->fromDateTime));
        $filterData->to = date("Y/m/d h:i A",strtotime($request->toDateTime));
       
        
    
        return view('reports.advanceSaleReport', [
            'record' => $sortData,
            'refund' =>[],
            'counterExpenses' => $counterexpenses ?? [],
            'filterData' => $filterData,
        ]);
    }


}
