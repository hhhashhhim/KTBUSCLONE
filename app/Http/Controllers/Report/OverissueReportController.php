<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

    public function filterData(Request $request)
    {
        if(!checkForSubmenu("confirm-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with('overIssueSeats', 'schedule:id,time')
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'over-issue')
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
            ->orderBy('id','DESC')
            ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
            ->get(["id","terminal_name","schedule_id","schedule_date","customer_id","seat_fare","discount","seat_no","type"]);

        $tickets->map(function ($q) {
            $q->overissue_reason = $q->overIssueSeats->reason;
            $q->type = $q->type;
            $q->overissue_by = User::find($q->overIssueSeats->added_by)->name??'N/A';
            $q->overissue_time = date("h:i A d-m-Y",strtotime($q->overIssueSeats->created_at));
            $q->bus_time = date('h:i A', strtotime($q->schedule->time)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)), $q->overIssueSeats->created_at);
            unset($q->overIssueSeats, $q->schedule);
        });
        
        return $tickets;
    }

    public
    function getPrintPdf(Request $request)
    {
        if(!checkForSubmenu("confirm-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with('overIssueSeats', 'schedule:id,time')
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'over-issue')
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
            ->orderBy('id','DESC')
            ->limit(($request->fromDate == '' && $request->toDate == '') ? 50 : 2000)
            ->get(["id","terminal_name","schedule_id","schedule_date","customer_id","seat_fare","discount","seat_no","type"]);

        $tickets->map(function ($q) {
            $q->overissue_reason = $q->overIssueSeats->reason;
            $q->type = $q->type;
            $q->overissue_by = User::find($q->overIssueSeats->added_by)->name??'N/A';
            $q->overissue_date = date("h:i A d-m-Y",strtotime($q->overIssueSeats->created_at));
            $q->bus_time = date('h:i A', strtotime($q->schedule->time)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)), $q->overIssueSeats->created_at);
            unset($q->overIssueSeats, $q->schedule);
        });
        return view('reports.overIssueReport', ['tickets' => $tickets]);
    }
}
