<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Hrm\Employee\Employee;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalCommission;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleClosingController extends Controller
{
    public function index()
    {
        $buses = Bus::where('company_id', Auth::user()->company_id)->orderBy('id')->get();
        $hosts = Employee::where(['employee_type' => 2, 'company_id' => Auth::user()->company_id,"hide"=>0])->where("user_id", '!=', 0)->get(["user_id", "name", "cnic"]);
        $drivers = Employee::where(['employee_type' => 1, 'company_id' => Auth::user()->company_id,"hide"=>0])->get(["id", "user_id", "name", "cnic"]);
        $closings = TicketClosing::
        where('company_id', Auth::user()->company_id)
            ->with("bus:id,bus_number", "schedule:id,name,route_id","schedule.route:id,name")
            ->get()
            ->groupBy('ticket_merge_id');
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "closings" => $closings,
        ];
        return $data;
    }

    public function merges()
    {
        $merges = TicketClosingMerge::where(['company_id' => Auth::user()->company_id, 'schedule_complete' => 1])
            ->with("bus:id,bus_number")
            ->with("closing:id,ticket_merge_id,schedule_id", "closing.schedule:id,name")
            ->with("tickets:id,ticket_merge_id,seat_fare,discount,schedule_id,terminal_id","tickets.elt:id,ticket_id,elt_price","tickets.schedule:id,route_id")
            ->get(["id","schedule_departure_date","schedule_return_date","bus_id"]);

        // this is for show sale at front
        $merges->map(function($single){
            
            $single->seat_fare = $single->tickets->sum("seat_fare");
            $single->discount = $single->tickets->sum("discount");

            // elt amount | commission
            $eltAmount = 0;
            $commission = 0;
            $forFixCommission = [];
            foreach($single->tickets as $ticket)
            {
                // elt
                if($ticket->elt)
                {
                    $eltAmount += $ticket->elt->elt_price;
                }
                // commission
                $terminalCommission = TerminalCommission::where(["terminal_id"=>$ticket->terminal_id,"route_id"=>$ticket->schedule->route_id,"company_id"=>Auth::user()->terminal_id])->first();
                if($terminalCommission)
                {    
                    $forFixCommission[] = $terminalCommission->id;
                    
                    if($terminalCommission->flat_commission == 0)
                        $commission += (($ticket->seat_fare - ($ticket->discount))/100)*$terminalCommission->percentage_commission;
                    else
                    {
                        $commission += $terminalCommission->flat_commission;
                    }
                    // kt adjustment commission
                    $commission += (($ticket->seat_fare - $ticket->discount)/100)*$terminalCommission->adjustment_commission;
                }
                else
                {
                    $commission += 0;
                }
                 
            }
            
            $fixcommission = TerminalCommission::whereIn("id",array_unique($forFixCommission))->get();
            $commission += $fixcommission->sum("fix_commission");
            
            $single->elt += $eltAmount;
            $single->commission += (int)$commission;

            // for add cancelation charges into the sale
            $cancelTicket = Ticket::
                onlyTrashed()
                ->where([
                    'company_id' => Auth::user()->company_id,
                    'ticket_merge_id' => $single->id,
                    'type' => "canceled",
                ])
                ->with("cancel_ticket:id,ticket_id,percentage")
                ->get(["id","seat_fare","discount"]);

        
            $refundAmount = 0;
            $cancelTicket->map(function($item) use (&$refundAmount){
                if($item->cancel_ticket)
                {
                    $refundAmount += (($item->seat_fare - $item->discount) / 100) * $item->cancel_ticket->percentage;
                }
            });

            $single->refund += $refundAmount;
            
        });
        
        $data = [
            "merges" => $merges,
        ];
        return $data;
    }
    
    public function getMembers(Request $request)
    {
        $data = [
            "drivers" => TicketClosingMember::where(['type' => 1, 'company_id' => Auth::user()->company_id,"ticket_closing_id" => $request->closingId])->pluck("user_id"),
            "hosts" => TicketClosingMember::where(['type' => 2, 'company_id' => Auth::user()->company_id,"ticket_closing_id" => $request->closingId])->pluck("user_id"),
        ];
        return $data;
    }

    public function fetchSchedule(Request $request)
    {
        return Schedule::
        where('start_date', '<=', $request->date)
            ->where('end_date', '>=', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->with(["scheduleDetail" => function ($q) use ($request) {
                return $q->where("schedule_date", $request->date);
            }])
            ->orderBy('id')
            ->get(["id", "name"]);
    }

    public function store(Request $request)
    {
        // this is for get route id that will be followed by schedule
        $route = Schedule::find($request->schedule)->route_id;
        // this is for get schedule start city
        $departure = RouteFare::where("route_id", $route)->orderBy('id', 'ASC')->first();
        // this is for get schedule end city
        $destination = RouteFare::where("route_id", $route)->orderBy('id', 'DESC')->first();
        // this is for get schedule departure time
        $depTime = ScheduleDetail::where(["schedule_id" => $request->schedule,
            "departure_id" => $departure->departure_city_id,
            "destination_id" => $departure->destination_city_id,
            "departure_date" => $request->date,
            "company_id" => Auth::user()->company_id
        ])->first();

        $bookingAvailable = Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, 'schedule_date' => $depTime->schedule_date])->get();
        if (count($bookingAvailable) == 0) {
            return response()->json(["errors" => ["Tickets Error" => ["No Booking Found! \n\n Booked Any Single Seat First"]]], 422);
        }
        // if already assign
        $checkAssign = TicketClosing::where([
            'company_id' => Auth::user()->company_id,
            "bus_id" => $request->bus,
            'schedule_id' => $request->schedule,
            'schedule_date' => $depTime->schedule_date,
        ])
            ->first();

        if ($checkAssign) {
            return response()->json(["errors" => ["Closing Error" => ["Already Closed"]]], 422);
        }

        $checkMergeRecord = TicketClosingMerge::where(["company_id" => Auth::user()->company_id, "bus_id" => $request->bus, "schedule_complete" => 0])->latest("id")->first();
        if ($checkMergeRecord) {
            TicketClosingMerge::where("id", $checkMergeRecord->id)->update([
                "schedule_return_date" => $request->date,
                "schedule_complete" => 1,
            ]);
        } else {
            $newRecord = TicketClosingMerge::create([
                "bus_id" => $request->bus,
                "schedule_departure_date" => $request->date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        $closingRecord = TicketClosing::create([
            "bus_id" => $request->bus,
            "ticket_merge_id" => $checkMergeRecord ? $checkMergeRecord->id : $newRecord->id,
            "schedule_id" => $request->schedule,
            "schedule_date" => $request->date,
            "schedule_time" => $depTime->departure_time,
            "schedule_start" => $departure->departure_city_id,
            "schedule_end" => $destination->destination_city_id,
            "schedule_return" => $checkMergeRecord ? 1 : 0,
            "description" => $request->description,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ]);

        // for driver
        foreach ($request->drivers as $value) {

            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 1,
                "ticket_closing_id" => $closingRecord->id,
                "bus_id" => $request->bus,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }
        // for host
        foreach ($request->hosts as $value) {
            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 2,
                "ticket_closing_id" => $closingRecord->id,
                "bus_id" => $request->bus,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, "schedule_date" => $request->date])->update([
            "bus_id" => $request->bus,
            "ticket_closing_id" => $closingRecord->id,
            "ticket_merge_id" => $checkMergeRecord ? $checkMergeRecord->id : $newRecord->id,
        ]);
        return $closingRecord;
    }

    public function update(Request $request)
    {
        $prevMerge = TicketClosingMerge::
        where(["company_id" => Auth::user()->company_id, "id" => $request->mergeId])
            ->first();

        // revert previous bus merge record
        if ($prevMerge->schedule_complete == 1) {
            TicketClosingMerge::where("id", $prevMerge->id)->update([
                "schedule_return_date" => null,
                "schedule_complete" => 0,
            ]);
        } else {
            $prevMerge->delete();
        }
        
        $checkMergeRecord = TicketClosingMerge::
        where(["company_id" => Auth::user()->company_id, "bus_id" => $request->bus, "schedule_complete" => 0])
            ->latest("id")->first();

        // for new bus
        if ($checkMergeRecord) {
            TicketClosingMerge::where("id", $checkMergeRecord->id)->update([
                "schedule_return_date" => $request->date,
                "schedule_complete" => 1,
            ]);
        } else {
            $newRecord = TicketClosingMerge::create([
                "bus_id" => $request->bus,
                "schedule_departure_date" => $request->date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        // delete old members
        TicketClosingMember::where(["company_id" => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->delete();


        TicketClosing::where("id", $request->closingId)->update([
            "bus_id" => $request->bus,
            "ticket_merge_id" => $checkMergeRecord ? $checkMergeRecord->id : $newRecord->id,
            "schedule_return" => $checkMergeRecord ? 1 : 0,
            "description" => $request->description,
            'added_by' => Auth::user()->id,
        ]);

        // for driver
        foreach ($request->drivers as $value) {
            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 1,
                "ticket_closing_id" => $request->closingId,
                "bus_id" => $request->bus,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }
        // for host
        foreach ($request->hosts as $value) {
            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 2,
                "ticket_closing_id" => $request->closingId,
                "bus_id" => $request->bus,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        Ticket::where(["company_id" => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->update([
            "bus_id" => $request->bus,
            "ticket_merge_id" => $checkMergeRecord ? $checkMergeRecord->id : $newRecord->id,
        ]);
    }

}
