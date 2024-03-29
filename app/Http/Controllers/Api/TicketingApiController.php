<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ValidationResource;
use App\Http\Resources\ConflictResource;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Booking\TicketAdvancedBooked;
use App\Models\Bus\BusClass;
use App\Models\Terminal\TerminalVisibility;
use App\Models\Booking\TicketIsPartial;
use App\Models\Schedule\DropSchedule;
use App\Http\Resources\CreatedResource;
use App\Models\Discount\Discount;
use App\Models\Surcharge\Surcharge;
use App\Models\FareTable;
use App\Models\FareClass;
use App\Models\ActivityLog;
use App\Models\TerminalDiscount;
use App\Models\Route\RouteFare;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\EmptyResource;
use App\Models\Terminal;
use App\Models\Customer;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleTerminalVisibility;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BreakResource;
use Exception;

class TicketingApiController extends Controller
{
    public function departureCities(Request $request)
    {
        try {

                $companyId = Auth::user()->company_id;
                // Data

                $data = City::where(['company_id'=> $companyId,"hide"=>0])->get(["id","name"]);

                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }

            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }

    public function destinationCities(Request $request)
    {

        try {

                $validator = Validator::make($request->all(), [
                    'departure_city_id' => 'required',
                ]);

                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }

                $companyId = Auth::user()->company_id;
                // Data

                $destination_cities = RouteFare::where('departure_city_id', $request->departure_city_id)->where('company_id', $companyId)->pluck('destination_city_id')->toArray();
                $data = City::whereIn('id', $destination_cities)->where(['company_id'=> $companyId,"hide"=>0])->get(['id', 'name']);


                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }

            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }
    
    public function checkTicketsStatus(Request $request)
    {

        try {

                $validator = Validator::make($request->all(), [
                    'invoice_id' => 'required',
                ]);

                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }

                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;
                
                // Data
                $data = Ticket::where(['company_id'=> $companyId,"terminal_id"=>$terminalId,"invoice_id"=>$request->invoice_id])->get(["invoice_id","departure_city_id","destination_city_id","date","type","created_at"]);

                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }

            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }

    public function availableSchedules(Request $request)
    {

        // try {
                $validator = Validator::make($request->all(), [
                    'departure_city_id' => 'required',
                    'destination_city_id' => 'required',
                    'date' => 'required',
                ]);

                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }

                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;
                // Data
                $visibleScheduleIds = ScheduleTerminalVisibility::where(["company_id"=>Auth::user()->company_id,"terminal_id"=>$request->terminal??Auth::user()->terminal_id,"visibility"=>1])->pluck("schedule_id");
                $advanceBookingDays = Terminal::where("id",Auth::user()->terminal_id)->first()->advance_booking;

             

                $data = ScheduleDetail::whereIn("schedule_id",$visibleScheduleIds)
                ->whereHas('schedule', function($q){$q->where("hide",0);})
                ->with("departure_city:id,name","destination_city:id,name","bus_class:id,name","bus_class_map:id,name,seat_map")
                ->with('schedule:id,name,bus_class_id,route_id,discount_id,surcharge_id')
                ->where(['departure_id' => $request->departure_city_id, 'destination_id' => $request->destination_city_id, 'departure_date' => $request->date,'company_id' => $companyId])
                ->when($advanceBookingDays!=null, function($q) use ($advanceBookingDays){
                    $q->where("departure_date",'<',now()->addDays($advanceBookingDays)->format("Y-m-d"));
                })
                ->get(["id","schedule_id","departure_id","destination_id","departure_time","departure_date","schedule_id","schedule_date","bus_class_id"]);


                $bookedTickets = Ticket::where(["company_id"=>$companyId])->whereIn("schedule_id",$data->pluck("schedule_id"))->whereIn("schedule_date",$data->pluck("schedule_date"))->get(["id","schedule_id","schedule_date"]);

                $data->map(function($single,$key) use ($data,$companyId,$terminalId,$bookedTickets){
                    // this is for if some schedule is drooped then it should not be throw
                    $scheduleDrop = DropSchedule::where(["schedule_date"=>$single->schedule_date,"schedule_id"=>$single->schedule_id])->first();
                    if($scheduleDrop)
                    {
                        unset($data[$key]);
                    }
                    // this is subroute visibility to check that this terminal if allow to fetch of specific subroute schedule
                    $visibility =TerminalVisibility::where(["departure_city_id"=>$single->departure_id,"destination_city_id"=>$single->destination_id,"route_id"=>$single->schedule->route_id])->first();
                    if(isset($visibility) && $visibility->online_visibilty == 1)
                    {
                        unset($data[$key]);
                    }

                    $seatMap = $single->bus_class_map->seat_map;

                    $flattenedSeatMap = array_merge(...$seatMap);
                    $reservedSeatsOfType0 = array_filter($flattenedSeatMap, function($seat) {
                        return isset($seat['reserved']) && $seat['reserved'] === true &&
                            isset($seat['type']) && $seat['type'] === 0;
                    });
                    $count = array_reduce($reservedSeatsOfType0, function($carry, $seat) {
                        return $carry + 1;
                    }, 0);


                    $flattenedSeatMap = array_merge(...$seatMap);
                    $classes = array_reduce($flattenedSeatMap, function($carry, $seat) {
                        if(isset($seat['class'])) {
                            $carry[] = $seat['class'];
                        }
                        return $carry;
                    }, []);
                    $class_id = array_unique($classes);

                    unset($single->bus_class_map);

                    $booked = $bookedTickets->where("schedule_id",$single->schedule_id)->where("schedule_date",$single->schedule_date)->count();

                    $single->total_seats = $count;
                    $single->available_seats = $count - $booked;


                    // this loop get all fare classes from seat map and fetch original fare and discount fare
                    foreach($class_id as $value)
                    {
                        // orginal fare
                        $name = FareClass::find($value)->name;
                        $fare = FareTable::where([
                            'from_city_id'=> $single->departure_id,
                            'to_city_id'=> $single->destination_id,
                            'fare_class'=> $value,
                            'company_id'=> $companyId,
                            ])
                            ->first()->fare;
                        $original_fare[] = ["name"=>$name,"fare"=>(int)$fare];

                        // this is for discounted price
                        $scheduleDiscount = Discount::where('id', $single->schedule->discount_id)
                        ->where('is_active', 1)
                        ->whereHas("discount_terminals", function ($q) use ($terminalId) {
                            $q->where("terminal_id", $terminalId);
                        })
                        ->first();
                        $scheduleSurcharge = Surcharge::where('id', $single->schedule->surcharge_id)->where('is_active', 1)->first();
                        $terminalDiscount = TerminalDiscount::where(["terminal_id" => $terminalId ?? 0, "route_id" => $single->schedule->route_id])->first();

                        $editFare = $fare;
                        if ($scheduleDiscount) {
                            if ($scheduleDiscount->type == "percentage") {
                                $number = $scheduleDiscount->percentage / 100;
                                $percentage = (int)$editFare * $number;
                                $editFare = round((int)$editFare - $percentage);
                            } else {
                                $editFare = (int)$editFare - (int)$scheduleDiscount->flat;
                            }
                        }
                        if ($terminalDiscount) {
                            $tdiscount = ((int) $fare / 100) * (int)$terminalDiscount->discount;
                            $editFare = $editFare - $tdiscount;
                        }
                        if ($scheduleSurcharge) {
                            if ($scheduleSurcharge->type == "percentage") {
                                $number = $scheduleSurcharge->percentage / 100;
                                $percentage = (int)$fare * $number;
                                $editFare = round((int)$editFare + $percentage);
                            } else {
                                $editFare = (int)$editFare + $scheduleSurcharge->flat;
                            }
                        }
                        /////////

                        // after discount
                        $discounted_fare[] = ["name"=>$name,"fare"=>customRound((int)$editFare)];
                    }

                    $faresOriginal = array_column($original_fare, 'fare');
                    array_multisort($faresOriginal, SORT_ASC, $original_fare);
                    
                    $faresDiscounted = array_column($discounted_fare, 'fare');
                    array_multisort($faresDiscounted, SORT_ASC, $discounted_fare);
                    
                    $single->total_fare = $original_fare;
                    $single->final_fare = $discounted_fare;

                    $single->departure_date_time = date("Y-m-d H:i:s", strtotime($single->departure_date . ' ' . $single->departure_time));

                });

                $data = $data->where("departure_date_time",'>',date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")) + 5400))->sortBy("departure_date_time");
                $arrayData = json_decode($data, true);
                $data = collect(array_values($arrayData));

                // data found | not found
                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }

        //     } catch (\Exception $e) {
        //         return new BreakResource($e->getMessage());
        // }

    }

    public function previewSchedule(Request $request)
    {
        try {

                // to make array of terminal's available seats
                $available_seats = Terminal::where('id', Auth::user()->terminal_id)->value('available_seats');
                if (!is_null($available_seats)) {
                    if (strpos($available_seats, '-') !== false) {
                        $rangeSeats = explode("|", str_replace(',', '|', $available_seats));
                        $output = [];
                        foreach ($rangeSeats as $range) {
                            $parts = explode("-", $range);
                            $start = intval($parts[0]);
                            $end = intval($parts[1]);
                            for ($i = $start; $i <= $end; $i++) {
                                $output[] = (int)str_pad($i, 2, "0", STR_PAD_LEFT);
                            }
                        }
                        $seats = array_unique($output);
                    }
                    else
                    {
                        $arrays = explode(",", $available_seats);
                        $seats = [];
                        foreach ($arrays as $item) {
                            $seats[] = (int)$item;
                        }
                    }

                }

                $validator = Validator::make($request->all(), [
                    'departure_city_id' => 'required',
                    'destination_city_id' => 'required',
                    'date' => 'required',
                    'schedule_id' => 'required',
                    'departure_time' => 'required',
                ]);

                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }

                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;

                $scheduleDetail = ScheduleDetail::with("bus_class:id,seat_map")->where([
                    'company_id' => $companyId,
                    'schedule_id' => $request->schedule_id,
                    'departure_date' => $request->date,
                    'departure_id' => $request->departure_city_id,
                    'destination_id' => $request->destination_city_id,
                    'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
                ])->first();
                // Getting Already Booked Tickets
                $tickets = Ticket::with('departure_city', 'destination_city', 'schedule', 'customer', 'company', 'addedBy')
                    ->where('company_id', $companyId)->where('schedule_id', $request->schedule_id)
                    ->whereDate('schedule_date', $scheduleDetail->schedule_date)->get();
                $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
                $schedule = Schedule::where('id', $request->schedule_id)
                    ->where('company_id', $companyId)
                    ->select('id', 'route_id', 'bus_class_id', 'time', 'discount_id', 'surcharge_id')
                    ->with('route:id,name,online_seat_choices', 'route.fares:id,route_id,departure_city_id,destination_city_id')
                    ->first();
                $scheduleDiscount = Discount::where('id', $schedule->discount_id)
                ->where('is_active', 1)
                ->whereHas("discount_terminals", function ($q) use ($terminalId) {
                    $q->where("terminal_id", $terminalId);
                })
                ->first();
                $scheduleSurcharge = Surcharge::where('id', $schedule->surcharge_id)->where('is_active', 1)->first();
                $fareForAllClasses = FareTable::where('from_city_id', $request->departure_city_id)->where('to_city_id', $request->destination_city_id)
                    ->where('company_id', $companyId)
                    ->get()->unique('fare_class');
                // getting cities sequence for checking which city will be after other one
                $lastFare = $schedule->route->fares->last();
                $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
                array_push($allFaresOfRoute, $lastFare->destination_city_id);
                $fareClasses = FareClass::where('company_id', $companyId)->get();
                if (count($fareClasses) != count($fareForAllClasses)) {
                    return response()->json([
                        "errors" => [
                            "Fare Error" => ["Please Fill the Fare Table Completely First ( For All Fare Classes ) !!!"]
                        ]
                    ], 422);
                }
        //        //Apply terminal discount
                $terminalDiscount = TerminalDiscount::where(["terminal_id" => $terminalId ?? 0, "route_id" => $schedule->route_id])->first();
                $seatChoices =  $schedule->route->online_seat_choices ? explode(",",$schedule->route->online_seat_choices) : null;
                // Looping Through the seat of the bus
                $seatMap = $scheduleDetail->bus_class->seat_map;
                foreach ($seatMap as $i => &$iValue) {
                    foreach ($iValue as $j => &$column) {
                        // adding fare to each seat
                        if ($column['reserved']) {

                            $data = $fareForAllClasses->where('fare_class', $column['class'])->first();
                            $seatMap[$i][$j]['fare'] = (int)$data->fare;
                            if ($scheduleDiscount) {
                                if ($scheduleDiscount->type == "percentage") {
                                    $number = $scheduleDiscount->percentage / 100;
                                    $percentage = (int)$data->fare * $number;
                                    $seatMap[$i][$j]['fare'] = round((int)$data->fare - $percentage);
                                } else {
                                    $seatMap[$i][$j]['fare'] = (int)$data->fare - (int)$scheduleDiscount->flat;
                                }
                            }
                            if ($terminalDiscount) {
                                $tdiscount = ((int)$data->fare / 100) * (int)$terminalDiscount->discount;
                                $seatMap[$i][$j]['fare'] = $seatMap[$i][$j]['fare'] - $tdiscount;
                            }
                            if ($scheduleSurcharge) {
                                if ($scheduleSurcharge->type == "percentage") {
                                    $number = $scheduleSurcharge->percentage / 100;
                                    $percentage = (int)$data->fare * $number;
                                    $seatMap[$i][$j]['fare'] = round((int)$data->fare + $percentage);
                                } else {
                                    $seatMap[$i][$j]['fare'] = (int)$data->fare + $scheduleSurcharge->flat;
                                }
                            }

                            // allow seat manage terminal wise
                            if(isset($seats) && !in_array(preg_replace("/[^0-9]/", "", $column['seatNo']), $seats))
                            {
                                $column['terminal_allow'] = false;
                            }
                            else
                            {
                                $column['terminal_allow'] = true;
                            }

                            // allow seat manage route wise
                            if($seatChoices)
                            {
                                if(in_array(preg_replace("/[^0-9]/", "", $column['seatNo']), $seatChoices) &&  $column['terminal_allow'] == true)
                                {
                                    $column['terminal_allow'] = true;
                                }
                                else
                                {
                                    $column['terminal_allow'] = false;
                                }
                            }





                        }
                        $result = isset($column['seatNo']) ? array_search($column['seatNo'], $ticketSeatNumbers) : false;
                        if ($result !== false) {   /*&& $leavingIn30Min != true*/
                            $seatMap[$i][$j]['id'] = $tickets[$result]['id'];
                            $seatMap[$i][$j]['gender'] = $tickets[$result]['gender'];
                            $seatMap[$i][$j]['partial'] = $tickets[$result]['is_partial'];
                            $seatMap[$i][$j]['type'] = $tickets[$result]['type'];
                            // $seatMap[$i][$j]['remarks'] = $tickets[$result]['remarks'] == null ? 'N/A' : $tickets[$result]['remarks'];
                            // $seatMap[$i][$j]['customer_cnic'] = $tickets[$result]['customer']['cnic'];
                            // $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                            // $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                            // $seatMap[$i][$j]['booked_by'] = $tickets[$result]['addedBy']['name'];
                            $seatMap[$i][$j]['departure_city_name'] = $tickets[$result]['departure_city']['name'];
                            $seatMap[$i][$j]['destination_city_name'] = $tickets[$result]['destination_city']['name'];
                            $seatMap[$i][$j]['class_name'] = $fareClasses->where('id', $column['class'])->first()->name;
                            $seatMap[$i][$j]['fare'] = 0;
                            if ($tickets[$result]['is_partial'] == 1) {
                                $resultPartials = isset($column['seatNo']) ? array_keys($ticketSeatNumbers, $column['seatNo']) : false;
                                foreach ($resultPartials as $singlePartial) {
                                    $seatMap[$i][$j]['id'] = $tickets[$singlePartial]['id'];
                                    $seatMap[$i][$j]['gender'] = $tickets[$singlePartial]['gender'];
                                    $seatMap[$i][$j]['partial'] = $tickets[$singlePartial]['is_partial'];
                                    $seatMap[$i][$j]['type'] = $tickets[$singlePartial]['type'];
                                    // $seatMap[$i][$j]['remarks'] = $tickets[$singlePartial]['remarks'] == null ? 'N/A' : $tickets[$singlePartial]['remarks'];
                                    // $seatMap[$i][$j]['customer_cnic'] = $tickets[$singlePartial]['customer']['cnic'];
                                    // $seatMap[$i][$j]['customer_name'] = $tickets[$singlePartial]['customer']['name'];
                                    // $seatMap[$i][$j]['customer_phone'] = $tickets[$singlePartial]['customer']['contact'];
                                    // $seatMap[$i][$j]['booked_by'] = $tickets[$singlePartial]['addedBy']['name'];
                                    $seatMap[$i][$j]['departure_city_name'] = $tickets[$singlePartial]['departure_city']['name'];
                                    $seatMap[$i][$j]['destination_city_name'] = $tickets[$singlePartial]['destination_city']['name'];
                                    $seatMap[$i][$j]['class_name'] = $fareClasses->where('id', $column['class'])->first()->name;
                                    $seatMap[$i][$j]['fare'] = 0;

                                    $before = (array_search($request->departure_city_id, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->departure_city_id, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->destination_city_id, $allFaresOfRoute, false) <= array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->destination_city_id, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false));

                                    // Condition for validation that departure city and destination city in the request should be "After" the partial seat's targeted cities
                                    $after = (array_search($request->departure_city_id, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->departure_city_id, $allFaresOfRoute, false) >= array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->destination_city_id, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($request->destination_city_id, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false)
                                    );

                                    if ($before || $after) {
                                        // removing partial tag for that seats which fulfill the conditions
                                        unset($seatMap[$i][$j]['partial'], $seatMap[$i][$j]['type'], $seatMap[$i][$j]['gender']);
                                    } else {
                                        break;
                                    }
                                    $seatMap[$i][$j]['departure_city'] = $tickets[$singlePartial]['departure_city']->name;
                                    $seatMap[$i][$j]['destination_city'] = $tickets[$singlePartial]['destination_city']->name;
                                }
                            }
                        }
                        if (isset($column['class'])) {
                            $class = $fareClasses->where('id', $column['class'])->first();
                            $seatMap[$i][$j]['color'] = $class ? $class->color : '';
                            $seatMap[$i][$j]['class_name'] = $fareClasses->where('id', $column['class'])->first()->name;
                            if ($class && $class->is_active == 0) {
                                return response()->json([
                                    "errors" => [
                                        "Fare Error" => ["This Bus Class Includes a Class Which isn't Active Please Active That Class First !!!"]
                                    ]
                                ], 422);
                            }
                            if ($class) {
                                $fare = (int)$fareForAllClasses->where('fare_class', $class->id)->first()->fare;
                                $seatMap[$i][$j]['fare'] = $fare;
                                if ($scheduleDiscount) {
                                    if ($scheduleDiscount->type == "percentage") {
                                        $number = $scheduleDiscount->percentage / 100;
                                        $percentage = $fare * $number;
                                        $seatMap[$i][$j]['fare'] = round($fare - $percentage);
                                    } else {
                                        $seatMap[$i][$j]['fare'] = $fare - (int)$scheduleDiscount->flat;
                                    }
                                }
                                if ($terminalDiscount) {
                                    $tdiscount = ((int)$fare / 100) * (int)$terminalDiscount->discount;
                                    $seatMap[$i][$j]['fare'] = $seatMap[$i][$j]['fare'] - $tdiscount;
                                }
                                if ($scheduleSurcharge) {
                                    if ($scheduleSurcharge->type == "percentage") {
                                        $number = $scheduleSurcharge->percentage / 100;
                                        $percentage = $fare * $number;
                                        $seatMap[$i][$j]['fare'] = round($fare + $percentage);
                                    } else {
                                        $seatMap[$i][$j]['fare'] = $fare + $scheduleSurcharge->flat;
                                    }
                                }
                            }
                        }
                        $seatMap[$i][$j]['fare'] = customRound($seatMap[$i][$j]['fare']??0);
                    }
                }
                $schedule->bus_class->seat_map = $seatMap;
                $data = $schedule->bus_class;



                // data found | not found
                if($data->count() > 0)
                {
                    return new SuccessResource($data);
                }
                else
                {
                    return new EmptyResource($data);
                }

            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }

    public function bookSeat(Request $request)
    {
        try {
                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;

                // for reserved to confirm
                if (isset($request->flag) && $request->flag == 1)
                {
                    $validator = Validator::make($request->all(), [
                        'invoice_id' => 'required|integer',
                    ]);
                    if ($validator->fails())
                    {
                        return new ValidationResource($validator->errors());
                    }
                    // checking only reserved seats will go through this process
                    $checkAlreadyBooked = Ticket::where("invoice_id",$request->invoice_id)->where(['company_id' => $companyId, "type" => "advance booking"])->get();
                    if($checkAlreadyBooked->count() > 0 )
                    {
                        Ticket::where("invoice_id",$request->invoice_id)->update([
                            'type' => 'booked',
                            'updated_by' => Auth::user()->id,
                            'booked_time' => date("Y-m-d H:i:s"),
                        ]);
                        ActivityLog::create([
                            "activity_by" => Auth::user()->id,
                            "message" => Auth::user()->name." | update ticket (advance to confirm) | time : ".$checkAlreadyBooked[0]->schedule_date." ".$checkAlreadyBooked[0]->schedule_time." | invoice id :".$request->invoice_id,
                            "requested_host" => $request->ip(),
                            "company_id" => Auth::user()->company_id
                        ]);
                        return new CreatedResource(["invoice_id"=>$request->invoice_id]);

                    }
                    else
                    {
                        $error = ["your seat combinations are not reserved for confirm booking"];
                        return new ConflictResource($error);
                    }
                }

                $validator = Validator::make($request->all(), [
                    'departure_city_id' => 'required',
                    'destination_city_id' => 'required',
                    'date' => 'required',
                    'gender' => 'required',
                    'book_type' => 'required',
                    'selected_seats' => 'required',
                    'selected_seats_class' => 'required',
                    'selected_seats_fare' => 'required',
                    'customer_name' => 'required',
                    'customer_cnic' => 'required',
                    'contact' => 'required',
                    'schedule_id' => 'required',
                    'departure_time' => 'required',
                ]);


                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }

                if($request->book_type != "booked" && $request->book_type != "advance booking")
                {
                    $error = ["Please Enter Type booked/advance booking"];
                    return new ConflictResource($error);
                }

                // to make array of terminal's available seats
                $available_seats = Terminal::where('id', Auth::user()->terminal_id)->value('available_seats');
                if (!is_null($available_seats)) {
                    if (strpos($available_seats, '-') !== false) {
                        $rangeSeats = explode("|", str_replace(',', '|', $available_seats));
                        $output = [];
                        foreach ($rangeSeats as $range) {
                            $parts = explode("-", $range);
                            $start = intval($parts[0]);
                            $end = intval($parts[1]);
                            for ($i = $start; $i <= $end; $i++) {
                                $output[] = (int)str_pad($i, 2, "0", STR_PAD_LEFT);
                            }
                        }
                        $seats = array_unique($output);
                    }
                    else
                    {
                        $arrays = explode(",", $available_seats);
                        $seats = [];
                        foreach ($arrays as $item) {
                            $seats[] = (int)$item;
                        }
                    }

                }
                // Data
                DB::beginTransaction();

                // this is for get actual schedule date
                $detail = ScheduleDetail::where("departure_id", $request->departure_city_id)
                    ->where("destination_id", $request->destination_city_id)
                    ->where('schedule_id', $request->schedule_id)
                    ->where('departure_date', $request->date)
                    ->where('company_id', $companyId)
                    ->where('departure_time', date("H:i:s",strtotime($request->departure_time)))
                    ->first();
                
                $schedule_time_exact = ScheduleDetail::where(["schedule_id"=>$detail->schedule_id,"schedule_date"=>$detail->schedule_date])->first();
                $existingTicket = Ticket::where(['company_id' => $companyId, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule_id])->latest()->first(['bus_id', 'ticket_closing_id','ticket_merge_id']);

                $allTicket = [];
                // if (isset($request->flag) && $request->flag == 1) {
                //     // checking only reserved seats will go through this process
                //     $checkAlreadyBooked = Ticket::whereIn("id",$request->alreadyBookedId)->where(['company_id' => $companyId, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule_id,"type" => "advance booking"])->get();
                //     if($checkAlreadyBooked->count() != count($request->alreadyBookedId))
                //     {
                //         $error = ["Some of your seat combinations are not reserved for confirm booking"];
                //         return new ConflictResource($error);
                //     }
                //     $allTicket = updateAdvancedSeatApi($request, $companyId);
                // } else {

                    // checking booking available with these seat selection
                    // check seat duplication
                    $schedule = Schedule::where('id', $request->schedule_id)->where('company_id', $companyId)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('route:id,name,online_seat_choices', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                    $lastFare = $schedule->route->fares->last();
                    $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
                    array_push($allFaresOfRoute, $lastFare->destination_city_id);
                    $scheduleDepIndex = array_search($request->departure_city_id,$allFaresOfRoute);
                    $scheduleDesIndex = array_search($request->destination_city_id,$allFaresOfRoute);
                    $checkAlreadyBooked = Ticket::whereIn("seat_no",$request->selected_seats)->where(['company_id' => $companyId, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule_id])->get();
                    foreach($checkAlreadyBooked as $tkt)
                    {
                        $ticketDepIndex = array_search($tkt->departure_city_id,$allFaresOfRoute);
                        $ticketDesIndex = array_search($tkt->destination_city_id,$allFaresOfRoute);
                        if(($ticketDepIndex > $scheduleDepIndex && $ticketDepIndex < $scheduleDesIndex) || ($ticketDesIndex > $scheduleDepIndex && $ticketDesIndex <= $scheduleDesIndex))
                        {
                            return response()->json(["errors" => ["Error" => ["One seat of your combination already booked"]]], 422);
                        }
                    }
                    $seatChoices =  $schedule->route->online_seat_choices ? explode(",",$schedule->route->online_seat_choices) : null;
                    // check allow seat
                    foreach($request->selected_seats as $seatNo)
                    {
                        // allow seat manage
                        if(isset($seats) && !in_array(preg_replace("/[^0-9]/", "", $seatNo), $seats))
                        {
                            $error = ["One seat of your combination is not allow to book"];
                            return new ConflictResource($error);
                        }
                        // allow seat manage route wise
                        if($seatChoices)
                        {
                            if(!in_array(preg_replace("/[^0-9]/", "", $seatNo), $seatChoices))
                            {
                                $error = ["One seat of your combination is not allow to book"];
                                return new ConflictResource($error);
                            }
                        }
                    }


                    $departure_city_id = $schedule->route->fares->first()->departure_city_id;
                    $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                    $isPartial = 0;
                    if ($request->departure_city_id != $departure_city_id || $request->destination_city_id != $destination_city_id) {
                        $isPartial = 1;
                    }
                    if ($request->customer_cnic && $request->book_type == 'booked') {
                        $customer = Customer::where('cnic', plainContactAndCnic($request->customer_cnic))->where('company_id', $companyId)->first();
                    } else if ($request->book_type == 'advance booking') {
                        $customer = Customer::where('contact', plainContactAndCnic($request->contact))->where('company_id', $companyId)->first();
                    } else {
                        $customer = false;
                    }
                    // Fare Fetching About the Schedule
                    if ($customer) {
                        $customer->name = $request->customer_name;
                        $customer->cnic = is_null($request->customer_cnic) ? 0 : plainContactAndCnic($request->customer_cnic);
                        $customer->contact = plainContactAndCnic($request->contact);
                        $customer->save();
                    } else {
                        $customer = Customer::create([
                            'company_id' => $companyId,
                            'added_by' => Auth::user()->id,
                            'name' => $request->customer_name,
                            'cnic' => is_null($request->customer_cnic) ? 0 : plainContactAndCnic($request->customer_cnic),
                            'contact' => plainContactAndCnic($request->contact),
                        ]);
                    }
                    // Getting Already Booked Tickets
                    if ($request->date == date('Y-m-d')) {
                        $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                        ++$bookingNo;
                    } else {
                        $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                        ++$bookingNo;
                    }
                    $invoice = Invoice::create([
                        "schedule_id" => $schedule->id,
                        "route_id" => $schedule->route_id,
                        "terminal_id" => $request->terminalId ?? Auth::user()->terminal_id,
                        "schedule_date" => $detail->schedule_date,
                        "schedule_time" => $detail->departure_time,
                        "company_id" => Auth::user()->company_id,
                        "added_by" => Auth::user()->id,
                    ]);
                    $allTicket = [];
                    foreach ($request->selected_seats as $i => $seat) {
                        $ticket = Ticket::create([
                            'company_id' => $companyId,
                            'departure_city_id' => $request->departure_city_id,
                            'destination_city_id' => $request->destination_city_id,
                            'seat_no' => $seat,
                            'bus_class_id' => $request->selected_seats_class[$i],
                            'seat_fare' => $request->selected_seats_fare[$i],
                            'is_partial' => $isPartial,
                            'booking_no' => $bookingNo,
                            'invoice_id' => $invoice->id,
                            'schedule_date' => $detail->schedule_date,
                            'schedule_time' => $detail->departure_time,
                            'schedule_time_exact' => $schedule_time_exact->departure_time,
                            'date' => $request->date,
                            'customer_id' => $customer->id,
                            'schedule_id' => $schedule->id,
                            'route_id' => $schedule->route_id,
                            'ticket_closing_id' => $existingTicket ? $existingTicket->ticket_closing_id : null,
                            'ticket_merge_id' => $existingTicket ? $existingTicket->ticket_merge_id : null,
                            'bus_id' => $existingTicket ? $existingTicket->bus_id : null,
                            'schedule_details_id' => $detail->id,
                            'terminal_id' => $terminalId,
                            'terminal_name' => Terminal::find($terminalId)->name,
                            'online_terminal' => Terminal::find($terminalId)->is_online_terminal,
                            'remarks' => $request->remarks,
                            'gender' => $request->gender[$i],
                            'type' => $request->book_type,
                            'discount_type' => null,
                            'booked_time' => date("Y-m-d H:i:s"),
                            'added_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id,
                            'discount' => 0,
                            'points_usage' => 0,
                        ]);
                        if ($isPartial == 1) {

                            TicketIsPartial::create([
                                'company_id' => $companyId,
                                'departure_city_id' => $ticket->departure_city_id,
                                'destination_city_id' => $ticket->destination_city_id,
                                'ticket_id' => $ticket->id,
                                'seat_no' => $ticket->seat_no,
                                'seat_fare' => $ticket->seat_fare,
                                'booking_no' => $ticket->booking_no,
                                'date' => $ticket->date,
                                'customer_id' => $ticket->customer_id,
                                'schedule_id' => $ticket->schedule_id,
                                'gender' => $request->gender[$i],
                                'type' => $ticket->type,
                                'added_by' => Auth::user()->id,
                            ]);
                        }

                        if ($request->book_type == 'advance booking') {
                            TicketAdvancedBooked::create([
                                'company_id' => $companyId,
                                'departure_city_id' => $ticket->departure_city_id,
                                'destination_city_id' => $ticket->destination_city_id,
                                'ticket_id' => $ticket->id,
                                'seat_no' => $ticket->seat_no,
                                'seat_fare' => $ticket->seat_fare,
                                'booking_no' => $ticket->booking_no,
                                'date' => $ticket->date,
                                'customer_id' => $ticket->customer_id,
                                'schedule_id' => $ticket->schedule_id,
                                'gender' => $request->gender[$i],
                                'type' => $ticket->type,
                                'added_by' => Auth::user()->id,
                            ]);
                        }
                        $allTicket[] = $ticket->id;
                    }
                // }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | stored ticket ($request->book_type) | time : $detail->schedule_date $detail->departure_time | seat no :".json_encode($request->selected_seats),
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();

                return new CreatedResource(["invoice_id"=>$invoice->id]);

            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }
}
