<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfirmCancellationReportController extends Controller
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
        $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'canceled')
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
            ->whereHas('cancel_ticket', function ($query) use ($request) {
                if ($request->type != 0) {
                    $query->where('type', $request->type);
                }
                else
                {
                    // to show all
                    $query->whereIn('type',["advance booking","booked"]);
                }
            })
            ->orderBy('id','DESC')
            ->limit(2000)
            ->get(["id","terminal_name","schedule_id","schedule_date","customer_id","seat_fare","discount","seat_no"]);

        $tickets->map(function ($q) {
            $q->cancel_percentage = $q->cancel_ticket->percentage;
            $q->type = $q->cancel_ticket->type;
            $q->cancel_reason = $q->cancel_ticket->reason;
            $q->cancel_by = User::find($q->cancel_ticket->added_by)->name??'N/A';
            $q->cancel_date = $q->cancel_ticket->time;
            $q->bus_time = date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
            $final = $percentageValue / 100;
            $q->amount_refund = (int)$q->seat_fare - $final;
            $q->cancelation_charges = round($final);
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)), $q->cancel_ticket->time);
            unset($q->cancel_ticket, $q->schedule);
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
        $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'canceled')
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
            ->whereHas('cancel_ticket', function ($query) use ($request) {
                if ($request->type != 0) {
                    $query->where('type', $request->type);
                }
                else
                {
                    // to show all
                    $query->whereIn('type',["advance booking","booked"]);
                }
            })
            ->orderBy('id','DESC')
            ->limit(2000)
            ->get(["id","terminal_name","schedule_id","schedule_date","customer_id","seat_fare","discount","seat_no"]);

        $tickets->map(function ($q) {
            $q->cancel_percentage = $q->cancel_ticket->percentage;
            $q->type = $q->cancel_ticket->type;
            $q->cancel_reason = $q->cancel_ticket->reason;
            $q->cancel_by = User::find($q->cancel_ticket->added_by)->name??'N/A';
            $q->cancel_date = $q->cancel_ticket->time;
            $q->bus_time = date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
            $final = $percentageValue / 100;
            $q->amount_refund = (int)$q->seat_fare - $final;
            $q->cancelation_charges = round($final);
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)), $q->cancel_ticket->time);
            unset($q->cancel_ticket, $q->schedule);
        });
        return view('reports.confirmCancelReport', ['tickets' => $tickets]);
    }
}
