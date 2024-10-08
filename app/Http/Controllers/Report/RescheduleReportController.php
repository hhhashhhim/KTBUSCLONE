<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RescheduleReportController extends Controller
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
        $tickets = Ticket::with('reschedule_seat')
            ->with("reschedule_seat.old_departure:id,name","reschedule_seat.old_destination:id,name")
            ->with("reschedule_seat.new_departure:id,name","reschedule_seat.new_destination:id,name")
            ->with(['reschedule_seat.new_ticket'=>function($q){
                return $q->withTrashed();
            }])
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'reschedule')
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
            $q->reason = $q->reschedule_seat->reason;
            $q->reschedule_by = User::find($q->reschedule_seat->added_by)->name??'N/A';
            $q->reschedule_time = date("h:i A d-m-Y",strtotime($q->reschedule_seat->created_at));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->type = $q->type;
            $q->new_type = $q->reschedule_seat->new_ticket->type;
            $q->old_seat = $q->seat_no;
            $q->new_seat = $q->reschedule_seat->new_ticket->seat_no;
            $q->old_bus_time = date('h:i A', strtotime($q->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));
            $q->new_bus_time = date('h:i A', strtotime($q->reschedule_seat->new_ticket->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->reschedule_seat->new_ticket->schedule_date));
            $q->old_departure = $q->reschedule_seat->old_departure->name;
            $q->old_destination = $q->reschedule_seat->old_destination->name;
            $q->new_departure = $q->reschedule_seat->new_departure->name;
            $q->new_destination = $q->reschedule_seat->new_destination->name;
            $q->old_fare = (int)$q->seat_fare - (int)$q->discount;
            $q->new_fare = (int)$q->reschedule_seat->new_ticket->seat_fare - (int)$q->reschedule_seat->new_ticket->discount;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule_time_exact)), $q->reschedule_seat->created_at);
            unset($q->reschedule_seat, $q->schedule);
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
        $tickets = Ticket::with('reschedule_seat')
            ->with("reschedule_seat.old_departure:id,name","reschedule_seat.old_destination:id,name")
            ->with("reschedule_seat.new_departure:id,name","reschedule_seat.new_destination:id,name")
            ->with(['reschedule_seat.new_ticket'=>function($q){
                return $q->withTrashed();
            }])
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'reschedule')
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
            $q->reason = $q->reschedule_seat->reason;
            $q->reschedule_by = User::find($q->reschedule_seat->added_by)->name??'N/A';
            $q->reschedule_time = date("h:i A d-m-Y",strtotime($q->reschedule_seat->created_at));
            $q->passenger_name = Customer::find($q->customer_id)->name;
            $q->passenger_contact = formatContact(Customer::find($q->customer_id)->contact);
            $q->type = $q->type;
            $q->new_type = $q->reschedule_seat->new_ticket->type;
            $q->old_seat = $q->seat_no;
            $q->new_seat = $q->reschedule_seat->new_ticket->seat_no;
            $q->old_bus_time = date('h:i A', strtotime($q->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->schedule_date));
            $q->new_bus_time = date('h:i A', strtotime($q->reschedule_seat->new_ticket->schedule_time_exact)) . ' ' . date('d-m-Y', strtotime($q->reschedule_seat->new_ticket->schedule_date));
            $q->old_departure = $q->reschedule_seat->old_departure->name;
            $q->old_destination = $q->reschedule_seat->old_destination->name;
            $q->new_departure = $q->reschedule_seat->new_departure->name;
            $q->new_destination = $q->reschedule_seat->new_destination->name;
            $q->old_fare = (int)$q->seat_fare - (int)$q->discount;
            $q->new_fare = (int)$q->reschedule_seat->new_ticket->seat_fare - (int)$q->reschedule_seat->new_ticket->discount;
            $q->badge = getRowBadgeColor(date('Y-m-d', strtotime($q->schedule_date)) . ' ' . date('H:i:s', strtotime($q->schedule_time_exact)), $q->reschedule_seat->created_at);
            unset($q->reschedule_seat, $q->schedule);
        });
        return view('reports.rescheduleReport', ['tickets' => $tickets]);
    }
}
