<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllBookingController extends Controller
{
    public function routes()
    {
        return Route::where(['company_id'=>Auth::user()->company_id,"hide"=>0])->get();
    }

    public function terminals()
    {
        return Terminal::where(['company_id'=>Auth::user()->company_id,"hide"=>0])->get();
    }

    public function buses()
    {
        return Bus::where('company_id', Auth::user()->company_id)->get();
    }

    public function filter(Request $request)
    {
        $data = Ticket::where(["tickets.company_id" => Auth::user()->company_id])

            // Within Customer Table
            ->where("customers.cnic", 'like', '%' . str_replace("-", "", $request->cnicFilter) . '%')
            ->where("customers.contact", 'like', '%' . str_replace("-", "", $request->phoneFilter) . '%')
            ->where("customers.name", 'like', '%' . $request->nameFilter . '%')
            ->join("customers", "customers.id", "tickets.customer_id")

            ->where(function ($q) use ($request) {
                // Within Ticket Table
                if ($request->terminalFilter) {
                    $q->where("terminal_id", $request->terminalFilter);
                }
                if ($request->invoiceFilter) {
                    $q->where("invoice_id",'like','%' . $request->invoiceFilter . '%');
                }
                if ($request->busFilter) {
                    $q->where("bus_id", $request->busFilter);
                }
                if ($request->fromDateFilter) {
                    $q->where("date",'>=', $request->fromDateFilter);
                }
                if ($request->toDateFilter) {
                    $q->where("date",'<=', $request->toDateFilter);
                }
                if ($request->routeFilter) {
                    $q->where("route_id", $request->routeFilter);
                }
                if ($request->statusFilter == "reschedule") {
                    $q->where("reschedule_type", '!=', $request->statusFilter);
                } elseif ($request->statusFilter) {
                    $q->where("type", $request->statusFilter);
                }
                return $q;
            });

        
        // Within Ticket Table
        if ($request->statusFilter == "canceled") {
            $data->where("type", $request->statusFilter)->withTrashed();
        }

        return [
            "data" => $data->with("schedule:id,route_id", "schedule.route:id,name", "bus:id,bus_number", "terminal:id,name", "addedBy:id,name", "scheduleDetail:id,departure_time", "cancel_ticket:id,ticket_id,added_by,created_at", "cancel_ticket.addedBy:id,name")->select("tickets.*", "customers.name", "customers.cnic", "customers.contact")->get(),
            "total_fare" => $data->with("schedule:id,route_id", "schedule.route:id,name", "bus:id,bus_number", "terminal:id,name", "addedBy:id,name", "scheduleDetail:id,departure_time", "cancel_ticket:id,ticket_id,added_by,created_at", "cancel_ticket.addedBy:id,name")->select("tickets.*", "customers.name", "customers.cnic", "customers.contact")->sum("seat_fare")
        ];
            
        
    }
}
