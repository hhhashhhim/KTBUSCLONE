<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Expense\TicketMergeExpense;
use App\Models\ReportHeaderLink;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\OfficeExpense;
use App\Models\TerminalCommission;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailySummaryReportController extends Controller
{
    public function getRoutes()
    {
        if(!checkForSubmenu("close-trip"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where('company_id', Auth::user()->company_id)->get(['id', 'name','via']);
    }

    public function getBuses()
    {
        if(!checkForSubmenu("close-trip"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Bus::where('company_id', Auth::user()->company_id)->get(['id', 'bus_number']);
    }

    public function exportReport(Request $request)
    {
        if(!checkForSubmenu("close-trip"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $closings = TicketClosingMerge::
        with(['closing:id,ticket_merge_id,bus_id', 'closing.tickets.elt:id,elt_price,ticket_id','closing.tickets'=>function($q){
                    $q->where("type","booked");
                    $q->select(["id","ticket_closing_id","seat_fare","discount","terminal_id"]);
                }])
            ->where('schedule_complete', 1)
            ->where('company_id', Auth::user()->company_id)
            ->where(function ($q) use ($request) {
                // if ($request->route != 0) {
                //     $schedules = Schedule::where('route_id', $request->route)->where('company_id', Auth::user()->company_id)->pluck('id');
                //     $closing_ids = TicketClosing::whereIn('schedule_id', $schedules)->where('schedule_return', 1)->where('company_id', Auth::user()->company_id)->pluck('ticket_merge_id');
                //     $q->whereIn('id', $closing_ids);
                // }
                if ($request->busNO != 0) {
                    $q->where('bus_id', $request->busNO);
                }
                if ($request->fromDate != null) {
                    $q->where('closing_date', '>=', $request->fromDate);
                }
                if ($request->toDate != null) {
                    $q->where('closing_date', '<=', $request->toDate);
                }
            })
            ->get();

        $mergeIds = TicketClosingMerge::where('schedule_complete', 1)
            ->where(function ($p) use ($request) {
                if ($request->fromDate != null) {
                    $p->where('closing_date', '>=', $request->fromDate);
                }
                if ($request->toDate != null) {
                    $p->where('closing_date', '<=', $request->toDate);
                }
            })->pluck('id');
        $headerLink = ReportHeaderLink::where('company_id', Auth::user()->company_id)->whereIn('ticket_merge_id', $mergeIds)->get(['id', 'header_id', 'ticket_merge_id', 'value'])->groupBy(['ticket_merge_id', 'header_id']);
        $onlineTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)->where('company_id', Auth::user()->company_id)->where(['online_terminal'=>1,'type'=>"booked"])->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount',"schedule_id","route_id"])->groupBy(['ticket_merge_id', 'terminal_id']);
        $physicalTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)->where('company_id', Auth::user()->company_id)->where(['online_terminal'=>0,'type'=>"booked"])->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount',"schedule_id","route_id"])->groupBy(['ticket_merge_id','schedule_id', 'terminal_id']);
        //Map function for single iteration
        $closings->map(function ($closing) {
            //            get data from single iteration with relation
            $closing->closing->map(function ($ticket) use ($closing) {
                $ticket->ticket_fare = $ticket->tickets->sum("seat_fare") - $ticket->tickets->sum("discount");
                $ticket->elt_fare = 0;
                $ticket->tickets->map(function ($elt) use ($ticket) {
                    if (!is_null($elt->elt)) {
                        $ticket->elt_fare = $elt->elt->sum('elt_price');
                    }
                });
                $closing->total_income = (int)$closing->closing->sum('ticket_fare') + (int)$closing->closing->sum('elt_fare');
            });
            $closing->total_expenses = (int)TicketMergeExpense::where('ticket_merge_id', $closing->id)->sum('amount');
            $closing->mod = ($closing->closing[0]->tickets->count() + $closing->closing[1]->tickets->count()) * 20;
            
            return $closing;
        });
        
        
        // for applying terminal commission only online terminal
        $onlineTerminalData->map(function ($merge) {
            $merge->map(function ($terminal) {
                $terminal->map(function ($ticket) {
                    $commission = TerminalCommission::where(["company_id" => Auth::user()->company_id, 'terminal_id' => $ticket->terminal_id, "route_id" => $ticket->route_id])->first();
                    if($commission)
                    {
                        if($commission->flat_commission == 0)
                        {
                            $amount = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->percentage_commission;
                        }
                        else
                        {
                            $amount = $commission->flat_commission;
                        }
                        $ticket->commission_amount = intVal($amount);
                    }
                    else
                    {
                        $ticket->commission_amount = 0;
                    }
                });  
            });
        });
        // for applying terminal commission only physical terminal
        $physicalTerminalData->map(function ($merge) {
            $merge->map(function ($schedule) {
                $schedule->map(function ($terminal) {
                    $terminal->map(function ($ticket) {
                        $commission = TerminalCommission::where(["company_id" => Auth::user()->company_id, 'terminal_id' => $ticket->terminal_id, "route_id" => $ticket->route_id])->first();
                        if($commission)
                        {
                            if($commission->flat_commission == 0)
                            {
                                $amount = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->percentage_commission;
                            }
                            else
                            {
                                $amount = $commission->flat_commission;
                            }
                            $ticket->commission_amount = intVal($amount);
                            $ticket->kt_commission = (($ticket->seat_fare - $ticket->discount) / 100) * $commission->adjustment_commission;
                            $ticket->fix_commission = intVal($commission->fix_commission);
                        }
                        else
                        {
                            $ticket->commission_amount = 0;
                            $ticket->fix_commission = 0;
                            $ticket->kt_commission = 0;
                        }
                    });  
                });
            });
        });
            
  
            // return $onlineTerminalData;
        if (strtolower($request->language) == 'english') {
            return view('reports.dailySummeryReportEng', [
                "data" => $closings,
                "online_terminals" => $onlineTerminalData,
                "physical_terminals" => $physicalTerminalData,
                "headers_link" => $headerLink,
            ]);
        }
        else
        {
            return view('reports.dailySummeryReportUrdu', [
                "data" => $closings,
                "online_terminals" => $onlineTerminalData,
                "physical_terminals" => $physicalTerminalData,
                "headers_link" => $headerLink,
                "office_expense" => OfficeExpense::where("company_id",Auth::user()->company_id)->whereBetween("closing_date",[$request->fromDate,$request->toDate])->get()->sum("amount"),
            ]);
        }
    }
}
