<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ValidationResource;
use App\Http\Resources\ConflictResource;
use App\Http\Resources\CreatedResource;
use App\Models\v1\Discount;
use App\Models\v1\Surcharge;
use App\Models\v1\FareTable;
use App\Models\v1\FareClass;
use App\Models\v1\TicketIsPartial;
use App\Models\v1\BusClass;
use App\Models\v1\ActivityLog;
use App\Models\v1\TerminalDiscount;
use App\Models\v1\RouteFare;
use App\Models\v1\Ticket;
use App\Models\v1\Invoice;
use App\Http\Resources\SuccessResource;
use App\Models\v1\TicketAdvancedBooked;
use App\Http\Resources\EmptyResource;
use App\Models\v1\Terminal;
use App\Models\v1\Customer;
use App\Models\v1\Schedule;
use App\Models\v1\City;
use App\Models\v1\ScheduleDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BreakResource;
use Exception;

class TicketingApiController extends Controller
{
    
    public function availableSchedules(Request $request)
    {
        try {
            
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
                
                $depId = ($request->departure_city_id == 98) ? 1 : (($request->departure_city_id == 36) ? 3 : 0);
                $desId = ($request->destination_city_id == 98) ? 1 : (($request->destination_city_id == 36) ? 3 : 0);

                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;
                // Data

                $data = ScheduleDetail::with('schedule:id,name,bus_class_id,route_id,discount_id,surcharge_id','schedule.bus_class:id,name',"departure_city:id,name","destination_city:id,name")->whereHas('schedule', function($q){$q->where("hide",0);})->where(['departure_id' => $depId, 'destination_id' => $desId, 'departure_date' => $request->date,'company_id' => $companyId])->get(["id","schedule_id","departure_id","destination_id","departure_time","departure_date","schedule_id","schedule_date"]);
                
                
                $data->map(function($single) use ($companyId,$terminalId){
                    $seat_map = BusClass::find($single->schedule->bus_class_id);
                    $counter = 0;
                    $bus_class_id = [];
                    foreach ($seat_map->seat_map as $i => $iValue) {
                        foreach ($iValue as $j => $column) {
                            if($column['reserved'])
                            {
                                $fare = FareTable::where([
                                    'from_city_id'=> $single->departure_id,
                                    'to_city_id'=> $single->destination_id,
                                    'fare_class'=> $column['class'],
                                    'company_id'=> $companyId,
                                    ])
                                    ->first()->fare;

                                if($column['type'] == 0)
                                {
                                    $counter++;
                                }
                                
                                $bus_class_id[] = $column['class'];
                                
                            }
                        }
                    }
                    $bookedTickets = Ticket::where(["company_id"=>$companyId,"schedule_id"=>$single->schedule_id,"schedule_date"=>$single->schedule_date])->get()->count();
                    $single->total_seats = $counter;
                    $single->available_seats = $counter - $bookedTickets;
                    $single->total_fare = (int)$fare;
                    $single->final_fare = (int)$fare;
                    
                    // this loop get all fare classes from seat map and fetch original fare and discount fare
                    foreach($bus_class_id as $value)
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
                        $original_fare[$name] = (int)$fare; 
                        
                        // this is for discounted price
                        $scheduleDiscount = Discount::where('id', $single->schedule->discount_id)->where('is_active', 1)->first();
                        $scheduleSurcharge = Surcharge::where('id', $single->schedule->surcharge_id)->where('is_active', 1)->first();
                        $terminalDiscount = TerminalDiscount::where(["terminal_id" => $terminalId ?? 0, "route_id" => $single->schedule->route_id])->where('start_date', '<=', date("Y-m-d"))
                        ->where('end_date', '>=', date("Y-m-d"))->first();
                    
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
                        $discounted_fare[$name] = customRound((int)$editFare);
                    }

                    $single->total_fare = $original_fare;
                    $single->final_fare = $discounted_fare;
                    $single->departure_date_time = date("Y-m-d H:i:s", strtotime($single->departure_date . ' ' . $single->departure_time));
                    $single->departure_time = date("h:i A", strtotime($single->departure_time));
                    
                });
                
                $data = $data->where("departure_date_time",'>',date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")) + 5400));
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
                
            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
        
    }

    public function previewSchedule(Request $request)
    {
        try {
                $validator = Validator::make($request->all(), [
                    'departure_city_id' => 'required',
                    'destination_city_id' => 'required',
                    'date' => 'required',
                    'schedule_id' => 'required',
                ]);
            
                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }
                
                $depId = ($request->departure_city_id == 98) ? 1 : (($request->departure_city_id == 36) ? 3 : 0);
                $desId = ($request->destination_city_id == 98) ? 1 : (($request->destination_city_id == 36) ? 3 : 0);
                
                if($depId == 0 || $desId == 0)
                {
                    $error = ["Please Enter Valid City Id"];
                    return new ConflictResource($error);
                }

                $companyId = Auth::user()->company_id;
                $terminalId = Auth::user()->terminal_id;
                
                $uniqueDate = ScheduleDetail::where([
                    'company_id' => $companyId,
                    'schedule_id' => $request->schedule_id,
                    'departure_date' => $request->date,
                    'departure_id' => $depId,
                    'destination_id' => $desId,
                ])->first(['schedule_date']);
                // Getting Already Booked Tickets
                $tickets = Ticket::with('departure_city', 'destination_city', 'schedule', 'customer', 'company', 'addedBy')
                    ->where('company_id', $companyId)->where('schedule_id', $request->schedule_id)
                    ->whereDate('schedule_date', $uniqueDate->schedule_date)->get();
                $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
                $schedule = Schedule::where('id', $request->schedule_id)
                    ->where('company_id', $companyId)
                    ->select('id', 'route_id', 'bus_class_id', 'time', 'discount_id', 'surcharge_id')
                    ->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')
                    ->first();
                $scheduleDiscount = Discount::where('id', $schedule->discount_id)->where('is_active', 1)->first();
                $scheduleSurcharge = Surcharge::where('id', $schedule->surcharge_id)->where('is_active', 1)->first();
                $fareForAllClasses = FareTable::where('from_city_id', $depId)->where('to_city_id', $desId)
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
        
                // Looping Through the seat of the bus
                $seatMap = $schedule->bus_class->seat_map;
                foreach ($seatMap as $i => $iValue) {
                    foreach ($iValue as $j => $column) {
                        // adding fare to each seat
                        if ($column['reserved'] == false)
                        {
                            unset($seatMap[$i][$j]);
                        }
                        
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
        
                                    $before = (array_search($depId, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($depId, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                        array_search($desId, $allFaresOfRoute, false) <= array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($desId, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false));
        
                                    // Condition for validation that departure city and destination city in the request should be "After" the partial seat's targeted cities
                                    $after = (array_search($depId, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($depId, $allFaresOfRoute, false) >= array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                        array_search($desId, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                        array_search($desId, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false)
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
                        ]);
                        ActivityLog::create([
                            "activity_by" => Auth::user()->id,
                            "message" => Auth::user()->name." | stored ticket (advance booking) | time : ".$checkAlreadyBooked[0]->schedule_date." ".$checkAlreadyBooked[0]->schedule_time." | seat no :".json_encode($request->selected_seats),
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

                // for new
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
                ]);

                $depId = ($request->departure_city_id == 98) ? 1 : (($request->departure_city_id == 36) ? 3 : 0);
                $desId = ($request->destination_city_id == 98) ? 1 : (($request->destination_city_id == 36) ? 3 : 0);
                
                if($request->book_type != "booked" && $request->book_type != "advance booking")
                {
                    $error = ["Please Enter Type booked/advance booking"];
                    return new ConflictResource($error);
                }

                if($depId == 0 || $desId == 0)
                {
                    $error = ["Please Enter Valid City Id"];
                    return new ConflictResource($error);
                }
                
                // if validation fails
                if ($validator->fails())
                {
                    return new ValidationResource($validator->errors());
                }
            
                // Data
                DB::beginTransaction();
                
                // this is for get actual schedule date
                $detail = ScheduleDetail::where("departure_id", $depId)
                    ->where("destination_id", $desId)
                    ->where('schedule_id', $request->schedule_id)
                    ->where('departure_date', $request->date)
                    ->where('company_id', $companyId)
                    ->first();
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
                    $checkAlreadyBooked = Ticket::whereIn("seat_no",$request->selected_seats)->where(['company_id' => $companyId, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule_id])->get();
                    if($checkAlreadyBooked->count() > 0)
                    {
                        $error = ["One seat of your combination already booked"];
                        return new ConflictResource($error);
                    }

                    $schedule = Schedule::where('id', $request->schedule_id)->where('company_id', $companyId)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                    $departure_city_id = $schedule->route->fares->first()->departure_city_id;
                    $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                    $isPartial = 0;
                    if ($depId != $departure_city_id || $desId != $destination_city_id) {
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
                    // $scheduleDetail = ScheduleDetail::where([
                    //     'company_id' => $companyId,
                    //     'departure_date' => $request->date,
                    //     'departure_id' => $depId,
                    //     'destination_id' => $desId,
                    //     'schedule_id' => $schedule->id,
                    // ])->first();
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
                            'departure_city_id' => $depId,
                            'destination_city_id' => $desId,
                            'seat_no' => $seat,
                            'bus_class_id' => $request->selected_seats_class[$i],
                            'seat_fare' => $request->selected_seats_fare[$i],
                            'is_partial' => $isPartial,
                            'booking_no' => $bookingNo,
                            'invoice_id' => $invoice->id,
                            'schedule_date' => $detail->schedule_date,
                            'schedule_time' => $detail->departure_time,
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
                            'gender' => $request->gender,
                            'type' => $request->book_type,
                            'discount_type' => null,
                            'added_by' => Auth::user()->id,
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
                                'gender' => $ticket->gender,
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
                                'gender' => $ticket->gender,
                                'type' => $ticket->type,
                                'added_by' => Auth::user()->id,
                            ]);
                        }
                        $allTicket[] = $ticket->id;
                    }
                // }
                DB::commit();
                
                return new CreatedResource(["invoice_id"=>$invoice->id]);
                
            } catch (\Exception $e) {
                return new BreakResource($e->getMessage());
        }
    }
}
