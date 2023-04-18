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
        return Terminal::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function filterData(Request $request)
    {
        $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')->where('company_id', Auth::user()->company_id)
            ->where('type', 'canceled')->withTrashed()
            ->when($request->terminal != 0, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->get();
        $tickets->map(function ($q) {
            $q->cancel_percentage = $q->cancel_ticket->percentage;
            $q->cancel_reason = $q->cancel_ticket->reason;
            $q->cancel_by = User::find($q->cancel_ticket->added_by)->name;
            $q->cancel_date = $q->cancel_ticket->time;
            $q->bus_time = date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
            $final = $percentageValue / 100;
            $q->amount_refund = (int)$q->seat_fare - $final;
            $q->cancelation_charges = $final;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time)), $q->cancel_ticket->time);
            unset($q->cancel_ticket, $q->schedule);
        });
        return
            $tickets->when($request->fromDate != '', function ($query) use ($request) {
                return $query->where('bus_time', '>=', $request->fromDate);
            })->when($request->toDate != '', function ($query) use ($request) {
                return $query->where('bus_time', '<=', $request->toDate);
            });
    }

    public
    function getPrintPdf(Request $request)
    {
        $tickets = Ticket::with('cancel_ticket', 'schedule:id,time')->where('company_id', Auth::user()->company_id)
            ->where('type', 'canceled')->withTrashed()
            ->when($request->terminal, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->get();
        $tickets->map(function ($q) {
            $q->cancel_percentage = $q->cancel_ticket->percentage;
            $q->cancel_reason = $q->cancel_ticket->reason;
            $q->cancel_by = User::find($q->cancel_ticket->added_by)->name;
            $q->cancel_date = $q->cancel_ticket->time;
            $q->bus_time = date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule->time));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->total_fare = (int)$q->seat_fare - (int)$q->discount;
            $percentageValue = ((int)$q->seat_fare - (int)$q->discount) * $q->cancel_percentage;
            $final = $percentageValue / 100;
            $q->amount_refund = (int)$q->seat_fare - $final;
            $q->cancelation_charges = $final;
            unset($q->cancel_ticket, $q->schedule);
        });
        $tickets->when($request->fromDate, function ($query) use ($request) {
            return $query->where('bus_time', '>=', $request->fromDate);
        })->when($request->toDate, function ($query) use ($request) {
            return $query->where('bus_time', '<=', $request->toDate);
        });
        return view('reports.confirmCancelReport', ['tickets' => $tickets]);
    }
}
