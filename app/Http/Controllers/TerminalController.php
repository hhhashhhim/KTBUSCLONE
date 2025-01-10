<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Company;
use App\Models\Terminal;
use App\Models\TerminalCommission;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\Bus\BusClass;
use App\Models\CounterExpense;
use App\Models\Schedule\Schedule;
use App\Models\Ticket;
use App\Models\User;
use App\Models\TerminalDiscount;
use App\Models\Route\Route;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Schedule\DropSchedule;
use App\Models\Schedule\ScheduleDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return City::has('terminal', '>', 0)->withCount(['terminal'=>function($q){$q->where("hide",0);}])->with('addedBy')->where('company_id', Auth::user()->company_id)->get();
    }

    public function companies()
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Company::orderBy('id', 'desc')->get();
    }

    public function cities()
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return City::with('addedBy')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->orderBy('id')->get();
    }

    public function allTerminals()
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return [
            'terminals' => Terminal::with('city')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name', 'city_id']),
            'authTerminalId' => Auth::user()->terminal_id,
        ];
    }

    public function getTerminal(Request $request)
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::with('addedBy')->where('city_id', $request->id)->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get();
    }

    public function getRoutes(Request $request)
    {
        if(!checkForSubmenu("terminals"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where(['company_id'=>Auth::user()->company_id,"hide"=>0])->get(["id", "name","via"]);
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('terminals', 'name')->where('city_id', $request->city_id)->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'urdu_name' => ['required', Rule::unique('terminals', 'urdu_name')->where('city_id', $request->city_id)->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'city_id' => 'required',
                    'contact' => 'required',
                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Terminal Name already exist against This City',
                    'city_id.required' => 'Please Select Any City ',
                    'contact.required' => 'Please Enter your Phone Number',
                ];
                $this->validate($request, $rules, $customMessages);

                Terminal::create([
                    'name' => $request->name,
                    'urdu_name' => $request->urdu_name,
                    'contact' => plainContactAndCnic($request->contact),
                    'address' => $request->address ?? " ",
                    'longitude' => $request->longitude,
                    'latitude' => $request->latitude,
                    'time_difference' => $request->time_difference,
                    'available_seats' => $request->available_seats,
                    'advance_booking' => $request->advance_booking,
                    'reservation_cancel' => $request->reservation_cancel,
                    'active_sms' => $request->active_sms ? 1 : 0,
                    'city_id' => $request->city_id,
                    'online_terminal_name' => $request->online_terminal_name ?? " ",
                    'is_online_terminal' => isset($request->is_online) ? 1 : 0,
                    'status' => $request->active ? 1 : 0,
                    'is_main' => $request->is_main ? 1 : 0,
                    'allowed_type' => $request->seatNumberType,
                    'fixed_commission' => $request->commission ?? 0,
                    'ticket_flat_commission' => $request->flatCommission ?? 0,
                    'ticket_percentage_commission' => $request->percentageCommission ?? 0,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->is_super_admin == 0 ? Auth::user()->company_id : $request->company_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added terminal ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $this->index();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function hideTerminal(Request $request)
    {
        if(!checkPermissionButtons("delete-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $terminal = Terminal::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | deleted terminal ($terminal->name)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $terminal->update([
            "hide" => 1
        ]);
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $this->validate($request, [
                    'name' => 'required',
                    'urdu_name' => 'required',
                    // 'online_terminal_name' => 'required',
                    'contact' => 'required',
                ]);
                Terminal::where("city_id",$request->city_id)->update(["is_main"=>0]);
                Terminal::find($request->id)->update([
                    'name' => $request->name,
                    'urdu_name' => $request->urdu_name,
                    'contact' => plainContactAndCnic($request->contact),
                    'address' => $request->address,
                    'longitude' => $request->longitude,
                    'latitude' => $request->latitude,
                    'time_difference' => $request->time_difference,
                    'advance_booking' => $request->advance_booking,
                    'reservation_cancel' => $request->reservation_cancel,
                    'available_seats' => $request->available_seats,
                    'city_id' => $request->city_id,
                    'online_terminal_name' => $request->online_terminal_name??"",
                    'is_online_terminal' => $request->is_online_terminal == true ? 1 : 0,
                    'is_main' => (int)$request->is_main,
                    'fixed_commission' => $request->fixed_commission ?? 0,
                    'ticket_flat_commission' => $request->ticket_flat_commission ?? 0,
                    'ticket_percentage_commission' => $request->ticket_percentage_commission ?? 0,
                    'allowed_type' => $request->allowed_type,
                    'active_sms' => $request->active_sms ? 1 : 0,
                    'status' => (int)$request->status,
                    'other_terminal_passenger_detail' => $request->other_terminal_passenger_detail,
                    'send_message' => $request->send_message,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated terminal ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();

                return response()->json([
                    'message' => 'Updated Successfully',
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function terminalCommissions(Request $request)
    {
        if(!checkPermissionButtons("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $terminalCommission = TerminalCommission::where(["terminal_id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->orderBy('id')->get();
        $terminal = Terminal::where(["id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->first();
        return [
            "terminalCommission" => $terminalCommission,
            "terminal" => $terminal,
        ];
    }

    public function commissionStore(Request $request)
    {
        if(!checkPermissionButtons("commission"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "terminal_id" => 'required',
                    "route" => 'required',
                    "fixCommission" => 'required',
                    "flatCommission" => 'required',
                    "percentCommission" => 'required',
                    "adjustmentCommission" => 'required',
                ]);
                TerminalCommission::where("terminal_id", $request->terminal_id)->delete();
                foreach ($request->route as $key => $value) {
                    $checkExist = TerminalCommission::where(["terminal_id" => $request->terminal_id, "route_id" => $request->route[$key], 'company_id' => Auth::user()->company_id])->first();
                    if (!$checkExist) {
                        TerminalCommission::create([
                            'terminal_id' => $request->terminal_id,
                            'route_id' => $request->route[$key],
                            'fix_commission' => $request->fixCommission[$key],
                            'flat_commission' => $request->flatCommission[$key],
                            'percentage_commission' => $request->percentCommission[$key],
                            'adjustment_commission' => $request->adjustmentCommission[$key],
                            'company_id' => Auth::user()->company_id,
                            'added_by' => Auth::user()->id,
                        ]);
                    }
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated terminal commission ($request->terminal_id)",
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

    public function terminalDiscounts(Request $request)
    {
        if(!checkPermissionButtons("discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $terminalDiscount = TerminalDiscount::where(["terminal_id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->orderBy('id')->get();
        $terminal = Terminal::where(["id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->first();
        return [
            "terminalDiscount" => $terminalDiscount,
            "terminal" => $terminal,
        ];
    }

    public function discountStore(Request $request)
    {
        if(!checkPermissionButtons("discount"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "terminal_id" => 'required',
                    "route" => 'required',
                    "discount" => 'required',
                    "startDate" => 'required',
                    "endDate" => 'required',
                ]);

                TerminalDiscount::where("terminal_id", $request->terminal_id)->delete();
                foreach ($request->route as $key => $value) {
                    $checkExist = TerminalDiscount::where(["terminal_id" => $request->terminal_id, "route_id" => $request->route[$key], 'company_id' => Auth::user()->company_id])->first();
                    if (!$checkExist) {
                        TerminalDiscount::create([
                            'terminal_id' => $request->terminal_id,
                            'route_id' => $request->route[$key],
                            'discount' => $request->discount[$key],
                            'start_date' => $request->startDate[$key],
                            'end_date' => $request->endDate[$key],
                            'company_id' => Auth::user()->company_id,
                            'added_by' => Auth::user()->id,
                        ]);
                    }
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated terminal discount ($request->terminal_id)",
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

    public function terminalTimes(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $terminalTimes = TerminalTimeDifference::where(["terminal_id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->orderBy('id')->get();
        $terminal = Terminal::where(["id" => $request->terminal_id, 'company_id' => Auth::user()->company_id])->first();
        return [
            "terminalTimes" => $terminalTimes,
            "terminal" => $terminal,
        ];
    }

    public function timeStore(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "terminal_id" => 'required',
                ]);

                TerminalTimeDifference::where("terminal_id", $request->terminal_id)->delete();
                $city_id = Terminal::find($request->terminal_id)->city_id;
                foreach ($request->route as $key => $value) {
                    $checkExist = TerminalTimeDifference::where(["terminal_id" => $request->terminal_id, "route_id" => $request->route[$key], 'company_id' => Auth::user()->company_id])->first();
                    if (!$checkExist) {
                        TerminalTimeDifference::create([
                            'terminal_id' => $request->terminal_id,
                            'city_id' => $city_id,
                            'route_id' => $request->route[$key],
                            'display_name' => $request->name[$key],
                            'show' => $request->show[$key],
                            'time_difference' => $request->time[$key],
                            'company_id' => Auth::user()->company_id,
                            'added_by' => Auth::user()->id,
                        ]);
                    }
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated terminal time ($request->terminal_id)",
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

    public function filterData(Request $request)
    {
        if(!checkForSubmenu("terminal-sale"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with(
                'updated_name:id,name',
                'terminal:id,name',
                'busClass:id,name',
                'schedule:id,name,time',
                "bus:id,bus_number",
                "customer:id,name,cnic,contact",
                "route:id,name,via",
                "cancel_ticket:id,percentage,ticket_id"
            )
            ->withTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue")
                      ->orWhere(function($query) {
                            $query->where("type", "canceled")
                                ->whereHas('cancel_ticket', function ($query) {
                                    $query->where('percentage', '>', 0);
                                });
                        });
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
            ->orderBy('date', 'desc')
            ->get(["id","terminal_id","route_id","bus_class_id","schedule_date","schedule_time","schedule_time_exact","updated_by","bus_id","invoice_id","seat_fare","discount","seat_no","customer_id","route_id","type"]);

        $tickets->map(function ($single) {
            $single->load(['commission'=>function($q){
                $q->where("route_id",2);
                $q->select("id","terminal_id","route_id","fix_commission","percentage_commission");
            }]);

            if ($single->commission) 
            {
                if($single->commission->fix_commission == 0)
                {
                    
                    $single->comsn =  (($single->seat_fare - $single->discount)/100)*($single->commission->percentage_commission);
                }
                else
                {
                    
                    $single->comsn =   $single->commission->fix_commission;
                }
            } 
            else 
            {
              
                $single->comsn =  0; 
            }

            $refundValue = 0;
            if($single->type == "canceled")
            {
                $refundValue = (($single->seat_fare - $single->discount) * $single->cancel_ticket->percentage) / 100;
            }

            $single->refund = $refundValue;
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule_time_exact));
            return $single;
        });

        // date filter
        if($request->fromDateTime)
        {
            $tickets = $tickets->where('schedule_date_time', '>=', date("Y-m-d H:i:s",strtotime($request->fromDateTime)));
        }
        if($request->toDateTime)
        {
            $tickets = $tickets->where('schedule_date_time', '<=', date("Y-m-d H:i:s",strtotime($request->toDateTime)));
        }
        // return $tickets;
        $tickets = $tickets->sortBy('schedule_date_time'); 
            
        return [
                'record' => $tickets
            ];

    }
    
    public function filterDataDiscount(Request $request)
    {
        if(!checkForSubmenu("terminal-sale"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $tickets = Ticket::with(
            'terminal:id,name',
            'busClass:id,name',
            'schedule:id,name,time',
            "bus:id,bus_number",
            "customer:id,name,cnic,contact",
            "route:id,name,via",
        )
        ->where('company_id', Auth::user()->company_id)
        ->where("type", "booked")
        ->where(function ($query) {
            $query->where('discount', '>', 0)
                  ->orWhere('terminal_discount', '>', 0)
                  ->orWhere('schedule_discount', '>', 0);
        })
        ->when($request->terminal, function ($query) use ($request) {
            return $query->where('terminal_id', $request->terminal);
        })
        ->when($request->schedule, function ($query) use ($request) {
            return $query->where('schedule_id', $request->schedule);
        })
        ->when($request->route, function ($query) use ($request) {
            return $query->whereIn('route_id', $request->route);
        })
        ->when($request->fromDateTime, function ($query) use ($request) {
            return $query->whereRaw(
                "CONCAT(schedule_date, ' ', schedule_time_exact) >= ?", 
                [date("Y-m-d H:i:s", strtotime($request->fromDateTime))]
            );
        })
        ->when($request->toDateTime, function ($query) use ($request) {
            return $query->whereRaw(
                "CONCAT(schedule_date, ' ', schedule_time_exact) <= ?", 
                [date("Y-m-d H:i:s", strtotime($request->toDateTime))]
            );
        })
        ->orderBy('schedule_date', 'asc')
        ->get();
        // return $tickets;
        $tickets = $tickets->sortBy('schedule_date_time'); 
            
        return [
                'record' => $tickets
            ];

    }

    public function dashboardData(Request $request)
    {
        if(checkPermissionButtons("super-data"))
        {
            $superdata = false;
        }
        else
        {
            $superdata = true;
        }
        
        $today = now()->format("Y-m-d");
        $lastday = now()->subDays(1)->format("Y-m-d");
        
        // confirm | reserve | over issue | today | lastday tickets
        $ticketData = Ticket::when($superdata, function ($query) {
            $query->where('terminal_id', Auth::user()->terminal_id);
        })
        ->withTrashed()
        ->whereBetween("date", [$lastday, $today])
        ->get();
        
        // today new customer
        $newCustomer = Ticket::when($superdata, function ($query) {
            $query->where('terminal_id', Auth::user()->terminal_id);
        })
        ->whereBetween("date", [$lastday, $today])
        ->whereNotIn('customer_id', function ($query) use ($lastday) {
            $query->select('customer_id')
                  ->from('tickets')
                  ->whereDate('date', '<', $lastday);
        })
        ->select('customer_id','date')
        ->distinct() 
        ->get();
        
        // today customer repeat
        $oldCustomer = Ticket::when($superdata, function ($query) {
            $query->where('terminal_id', Auth::user()->terminal_id);
        })
        ->whereBetween("date", [$lastday, $today])
        ->whereIn('customer_id', function ($query) use ($lastday) {
            $query->select('customer_id')
                  ->from('tickets')
                  ->whereDate('date', '<', $lastday);
        })
        ->select('customer_id','date')
        ->distinct() 
        ->get();


        $schedule_ids = ScheduleDetail::where("schedule_date",$today)->pluck("schedule_id")->unique();
        $schedules = Schedule::whereIn("id",$schedule_ids)->with("bus_class:id,seat_map")->get(["id","name","bus_class_id"]);
        
        $schedules->map(function($schedule,$key) use ($schedules,$today){
            $schedule->total_seat = countSeatFromMap($schedule->bus_class->seat_map);
            $schedule->booked_seats = Ticket::where(["schedule_id"=>$schedule->id,"schedule_date"=>$today,"type"=>"booked"])->distinct('seat_no')->count();
            $schedule->progress = intVal(($schedule->booked_seats / $schedule->total_seat) * 100);
            $detail = ScheduleDetail::where(["schedule_id"=>$schedule->id,"schedule_date"=>$today])->first();
            $schedule->departure_time = date("h:i A d/m/Y",strtotime($detail->departure_date.' '.$detail->departure_time));
            $schedule->schedule_date = date("d/m/Y",strtotime($detail->schedule_date));
            $checkDrop = DropSchedule::where(["schedule_id"=>$schedule->id,"schedule_date"=>$today])->first();
            $schedule->drop = $checkDrop ? true : false;
            
        });

        
        return [
            "cart" => [
                "last_confirm" => $ticketData->where("date",$lastday)->where("type","booked")->count(),
                "today_confirm" => $ticketData->where("date",$today)->where("type","booked")->count(),
                "last_reserve" => $ticketData->where("date",$lastday)->where("type","advance booking")->count(),
                "today_reserve" => $ticketData->where("date",$today)->where("type","advance booking")->count(),
                "last_cancel" => $ticketData->where("date",$lastday)->where("type","canceled")->count(),
                "today_cancel" => $ticketData->where("date",$today)->where("type","canceled")->count(),
                "last_overissue" => $ticketData->where("date",$lastday)->where("type","over-issue")->count(),
                "today_overissue" => $ticketData->where("date",$today)->where("type","over-issue")->count(),
                "last_discount" => $ticketData->where("type","booked")->where("date",$lastday)->sum(function ($ticket) {
                    return $ticket->discount + $ticket->terminal_discount + $ticket->schedule_discount;
                }),
                "today_discount" => $ticketData->where("type","booked")->where("date",$today)->sum(function ($ticket) {
                    return $ticket->discount + $ticket->terminal_discount + $ticket->schedule_discount;
                }),
                "last_new_customers" => $newCustomer->where("date",$lastday)->count(),
                "today_new_customers" => $newCustomer->where("date",$today)->count(),
                "last_old_customers" => $oldCustomer->where("date",$lastday)->count(),
                "today_old_customers" => $oldCustomer->where("date",$today)->count(),
                "last_sale" => $ticketData->whereIn('type', ['booked', 'over-issue'])->where("date",$lastday)->sum(function ($ticket) {
                    return $ticket->seat_fare - $ticket->discount;
                }),
                "today_sale" => $ticketData->whereIn('type', ['booked', 'over-issue'])->where("date",$today)->sum(function ($ticket) {
                    return $ticket->seat_fare - $ticket->discount;
                }),
            ],
            "schedules" => $schedules
        ];
    }

}
