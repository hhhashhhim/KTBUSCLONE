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
use App\Models\ActivityLog;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleClosingController extends Controller
{
    public function closing()
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $buses = Bus::where('company_id', Auth::user()->company_id)->orderBy('id')->get();
        $hosts = Employee::where(['employee_type' => 2, 'company_id' => Auth::user()->company_id,"hide"=>0])->where("user_id", '!=', 0)->get(["user_id", "name", "cnic"]);
        $drivers = Employee::where(['employee_type' => 1, 'company_id' => Auth::user()->company_id,"hide"=>0])->get(["id", "user_id", "name", "cnic"]);
        $closings = TicketClosing::where('company_id', Auth::user()->company_id)
        ->with("bus:id,bus_number", "schedule:id,name,route_id", "schedule.route:id,name")
        ->get()
        ->groupBy('ticket_merge_id')
        ->filter(function ($group){
            return $group->count() == 2;
        });
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "closings" => $closings,
        ];
        return $data;
    }
    
    public function unclosing()
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $closings = TicketClosing::where('company_id', Auth::user()->company_id)
        ->with("bus:id,bus_number", "schedule:id,name,route_id", "schedule.route:id,name")
        ->where("hide",0)
        ->get()
        ->groupBy('ticket_merge_id')
        ->filter(function ($group){
            return $group->count() == 1;
        });
        $data = [
            "closings" => $closings,
        ];
        return $data;
    }
    
    public function hideUnclosing(Request $request)
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $unclosing = TicketClosing::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | unclosing deleted ($unclosing->id)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $unclosing->update([
            "hide" => 1
        ]);
    }
    
    public function revertUnclosing(Request $request)
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $unclosing = TicketClosing::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | unclosing revert ($unclosing->id)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $unclosing->update([
            "hide" => 0
        ]);
    }
    
    public function spareUnclosing(Request $request)
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $closings = TicketClosing::where('company_id', Auth::user()->company_id)
        ->with("bus:id,bus_number", "schedule:id,name,route_id", "schedule.route:id,name")
        ->where("hide",1)
        ->get();
        $data = [
            "closings" => $closings,
        ];
        return $data;
    }
    
    public function mergeClosing(Request $request)
    {
        if(!checkForSubmenu("closing"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
                $mergeOne = TicketClosingMerge::where("id",$request->mergeIds[0])->first();
                $mergeTwo = TicketClosingMerge::where("id",$request->mergeIds[1])->first();
                $merge = TicketClosingMerge::create([
                    "bus_id" => $mergeOne->bus_id,
                    "schedule_departure_date" => $mergeOne->schedule_departure_date,
                    "schedule_return_date" => $mergeTwo->schedule_departure_date,
                    "schedule_complete" => 1,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                TicketClosing::whereIn("ticket_merge_id",$request->mergeIds)->update([
                    "ticket_merge_id" => $merge->id
                ]);
                TicketClosingMerge::whereIn("id",$request->mergeIds)->delete();
                $closingIds = TicketClosing::where("ticket_merge_id",$merge->id)->pluck("id");
                Ticket::whereIn("ticket_closing_id",$closingIds)
                ->withTrashed()
                ->update([
                    "ticket_merge_id" => $merge->id
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | closed merge ($closingIds[0] $closingIds[1] $merge->id)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
            DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    
    public function releaseClosing(Request $request)
    {
        if(!checkPermissionButtons("edit-close-booking"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
                $mergeId = TicketClosing::find($request->closingId)->ticket_merge_id;
                $closings = TicketClosing::where("ticket_merge_id",$mergeId)->get();
                $previousMerge = TicketClosingMerge::find($mergeId);
                $mergeOne = TicketClosingMerge::create([
                    "bus_id" => $previousMerge->bus_id,
                    "schedule_departure_date" => $closings[0]->schedule_date,
                    "schedule_complete" => 0,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                $mergeTwo = TicketClosingMerge::create([
                    "bus_id" => $previousMerge->bus_id,
                    "schedule_departure_date" => $closings[1]->schedule_date,
                    "schedule_complete" => 0,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                Ticket::where("ticket_merge_id",$mergeId)->update([
                    "ticket_merge_id" => null
                ]);
                $previousMerge->delete();
                $closings[0]->update([
                    "ticket_merge_id" => $mergeOne->id
                ]);
                $closings[1]->update([
                    "ticket_merge_id" => $mergeTwo->id
                ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | released merge (".$closings[0]->id." ".$closings[1]->id." $mergeOne->id $mergeTwo->id)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function merges(Request $request)
    {
        if(!checkForSubmenu("merges"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $merges = TicketClosingMerge::where(['company_id' => Auth::user()->company_id, 'schedule_complete' => 1])
            ->withSum("expenses",'amount')
            ->with("bus:id,bus_number")
            ->with("closing:id,ticket_merge_id,schedule_id", "closing.schedule:id,name")
            ->with("tickets.elt:id,ticket_id,elt_price","tickets.schedule:id,route_id")
            ->with(["tickets"=>function($q){
                $q->withTrashed();
                $q->where(function ($query) {
                    $query->where("type", "booked")
                          ->orWhere("type", "over-issue");
                });
                $q->select("id","ticket_merge_id","seat_fare","discount","schedule_id","terminal_id","ticket_closing_id");
            }])
            ->where(function($q) use ($request){
                if($request->bus_number)
                {
                    $q->where("bus_id",$request->bus_number);
                }
                if($request->from_date)
                {
                    $q->where("schedule_departure_date",'>=',$request->from_date);
                }
                if($request->to_date)
                {
                    $q->where("schedule_departure_date",'<=',$request->to_date);
                }
            })
            ->limit(($request->from_date == '' && $request->to_date == '') ? 20 : 2000)
            ->orderBy("schedule_departure_date","DESC")
            ->get(["id","schedule_departure_date","schedule_return_date","bus_id","closing_date"]);

            
        // this is for show sale at front
        $merges->map(function($single){
            
            $single->seat_fare = $single->tickets->sum("seat_fare");
            $single->discount = $single->tickets->sum("discount");

            // elt amount | commission
            $eltAmount = 0;
            $commission = 0;
            $closingOne = [];
            $closingTwo = [];
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
                    if($single->closing[0]->id == $ticket->ticket_closing_id)
                    {
                        $closingOne[] = $terminalCommission->id;
                    }
                    else
                    {
                        $closingTwo[] = $terminalCommission->id;
                    }
                    
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
            
            $commission += TerminalCommission::whereIn("id",array_unique($closingOne))->get()->sum("fix_commission");
            $commission += TerminalCommission::whereIn("id",array_unique($closingTwo))->get()->sum("fix_commission");
            
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
            "buses" => Bus::orderBy('id')->where('company_id', Auth::user()->company_id)->get(["id","bus_number"]),
        ];
        return $data;
    }
    
    public function mergesPdf(Request $request)
    {
        if(!checkForSubmenu("merges"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $merges = TicketClosingMerge::where(['company_id' => Auth::user()->company_id, 'schedule_complete' => 1])
            ->withSum("expenses",'amount')
            ->with("bus:id,bus_number")
            ->with("closing:id,ticket_merge_id,schedule_id", "closing.schedule:id,name")
            ->with("tickets.elt:id,ticket_id,elt_price","tickets.schedule:id,route_id")
            ->with(["tickets"=>function($q){
                $q->withTrashed();
                $q->where(function ($query) {
                    $query->where("type", "booked")
                          ->orWhere("type", "over-issue");
                });
                $q->select("id","ticket_merge_id","seat_fare","discount","schedule_id","terminal_id","ticket_closing_id");
            }])
            ->where(function($q) use ($request){
                if($request->bus_number)
                {
                    $q->where("bus_id",$request->bus_number);
                }
                if($request->from_date)
                {
                    $q->where("schedule_departure_date",'>=',$request->from_date);
                }
                if($request->to_date)
                {
                    $q->where("schedule_departure_date",'<=',$request->to_date);
                }
            })
            ->limit(($request->from_date == '' && $request->to_date == '') ? 20 : 2000)
            ->latest("schedule_departure_date")
            ->get(["id","schedule_departure_date","schedule_return_date","bus_id","closing_date"]);

            
        // this is for show sale at front
        $merges->map(function($single){
            
            $single->seat_fare = $single->tickets->sum("seat_fare");
            $single->discount = $single->tickets->sum("discount");

            // elt amount | commission
            $eltAmount = 0;
            $commission = 0;
            $closingOne = [];
            $closingTwo = [];
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
                    if($single->closing[0]->id == $ticket->ticket_closing_id)
                    {
                        $closingOne[] = $terminalCommission->id;
                    }
                    else
                    {
                        $closingTwo[] = $terminalCommission->id;
                    }
                    
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
            
            $commission += TerminalCommission::whereIn("id",array_unique($closingOne))->get()->sum("fix_commission");
            $commission += TerminalCommission::whereIn("id",array_unique($closingTwo))->get()->sum("fix_commission");
            
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
        return view('reports.busMergeReport', ['data' => $data]);
    }
    
    public function getMembers(Request $request)
    {
        $data = [
            "drivers" => TicketClosingMember::where(['type' => 1, 'company_id' => Auth::user()->company_id,"ticket_closing_id" => $request->closingId])->pluck("user_id"),
            "hosts" => TicketClosingMember::where(['type' => 2, 'company_id' => Auth::user()->company_id,"ticket_closing_id" => $request->closingId])->pluck("user_id"),
        ];
        return $data;
    }
    
    public function updateClosingDate(Request $request)
    {
        if(!checkForSubmenu("merges"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        TicketClosingMerge::where("id",$request->mergeId)->update([
            "closing_date" => $request->closingDate,
        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | updated closing date ($request->closingDate)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
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
        if(!checkPermissionButtons("assign-bus"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            
            $depTime = ScheduleDetail::where(["schedule_id" => $request->schedule,
                "departure_id" => $request->departureCity,
                "destination_id" => $request->destinationCity,
                "schedule_date" => $request->date,
                'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
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

            
            $newRecord = TicketClosingMerge::create([
                "bus_id" => $request->bus,
                "schedule_departure_date" => $request->date,
                "schedule_complete" => 0,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            

            $closingRecord = TicketClosing::create([
                "bus_id" => $request->bus,
                "ticket_merge_id" => $newRecord->id,
                "schedule_id" => $request->schedule,
                "schedule_date" => $request->date,
                "schedule_time" => $depTime->departure_time,
                "schedule_start" => $request->departureCity,
                "schedule_end" => $request->destinationCity,
                "schedule_return" => 0,
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

            Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, "schedule_date" => $request->date])
            ->withTrashed()
            ->update([
                "bus_id" => $request->bus,
                "ticket_closing_id" => $closingRecord->id,
            ]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | closed schedule ($closingRecord->id)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            
        DB::commit();
        return $closingRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-close-booking"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            // delete old members
            TicketClosingMember::where(["company_id" => Auth::user()->company_id, "ticket_closing_id" => $request->closingId])->delete();
            // for 
            $closing = TicketClosing::find($request->closingId);
            $merge = TicketClosingMerge::find($closing->ticket_merge_id);
            if($merge->schedule_complete == 0)
            {
                $closing->update([
                    "bus_id" => $request->bus
                ]);
                $merge->update([
                    "bus_id" => $request->bus
                ]);
            }
            foreach ($request->drivers as $value) {
                TicketClosingMember::create([
                    "user_id" => $value,
                    "type" => 1,
                    "ticket_closing_id" => $request->closingId,
                    "bus_id" => $closing->bus_id,
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
                    "bus_id" => $closing->bus_id,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
            
            Ticket::where("ticket_closing_id",$closing->id)->withTrashed()->update(["bus_id"=>$request->bus]);
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | updated closed schedule ($request->closingId)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
        DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

}
