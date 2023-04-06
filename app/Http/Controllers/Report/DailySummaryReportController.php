<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Expense\TicketMergeExpense;
use App\Models\ReportHeaderLink;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailySummaryReportController extends Controller
{
    public function getSchedule()
    {
        return Schedule::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function getBuses()
    {
        return Bus::where('company_id', Auth::user()->company_id)->get(['id', 'bus_number']);
    }

    public function exportReport(Request $request)
    {
        dd($request->all());
        if (strtolower($request->language) == 'english') {
//                    main Data
            $closings = TicketClosingMerge::with('closing:id,ticket_merge_id,bus_id', 'closing.tickets:id,ticket_closing_id,seat_fare,discount', 'closing.tickets.elt:id,elt_price,ticket_id')->where('schedule_complete', 1)->whereBetween('schedule_departure_date', [$request->fromDate,$request->toDate])->get(['id', 'schedule_complete']);
            $mergeIds = TicketClosingMerge::where('schedule_complete', 1)->where('schedule_departure_date', '>', '2023-04-02')->pluck('id');
            $headerLink = ReportHeaderLink::where('company_id', Auth::user()->company_id)->whereIn('ticket_merge_id', $mergeIds)->get(['id', 'header_id', 'ticket_merge_id', 'value'])->groupBy(['ticket_merge_id', 'header_id']);
            $onlineTerminalData = Ticket::whereIn('ticket_merge_id', $mergeIds)->where('company_id', Auth::user()->company_id)->where('online_terminal', 1)->get(['id', 'terminal_id', 'seat_fare', 'ticket_merge_id', 'discount'])->groupBy(['ticket_merge_id', 'terminal_id']);
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
            return view('reports.dailySummeryReportEng', [
                "data" => $closings,
                "online_terminals" => $onlineTerminalData,
                "headers_link" => $headerLink,
            ]);
        }

    }
}
