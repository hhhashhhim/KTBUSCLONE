<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\RescheduleExtraAmount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Booking\TicketAdvancedBooked;
use App\Models\Booking\TicketELT;
use App\Models\Company;
use App\Models\Booking\TicketIsPartial;
use Illuminate\Support\Facades\Http;
use App\Models\Booking\TicketReschedule;
use App\Models\Booking\TicketsOverIssue;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Discount\Discount;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use App\Models\Schedule\ScheduleTerminalSequence;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\TerminalCommission;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\Terminal\TerminalVisibility;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use App\Models\Schedule\DropSchedule;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\ScheduleTerminalVisibility;
use App\Models\Schedule\TicketClosing;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Hrm\Employee\Employee;
use App\Models\Terminal;
use App\Models\TerminalDiscount;
use App\Models\Ticket;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $bookings = Ticket::select('schedule_id', 'date', 'schedule_details_id', 'bus_class_id')->with('schedule:id,name', 'scheduleDetail', 'seatClass')->whereDate('date', isset($request->date) ? $request->date : date("Y-m-d"))
            ->where('company_id', Auth::user()->company_id)->get()->groupBy(['date', 'schedule_id']);
        $allBooking = [];
        foreach ($bookings as $i => $singleBooking) {
            $bookingWithDetails = $singleBooking->map(function ($booking) use ($i) {
                $booking[0]->count = $booking->count();
                $booking[0]->date = $i;
                return $booking[0];
            });
            $allBooking[] = $bookingWithDetails->first();
        }
        return $allBooking;
    }

    public function cities()
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if (Auth::user()->departure_city_ids == "all") {
            $ids = City::where("company_id", Auth::user()->company_id)->pluck('id');
        } else {
            $ids = json_decode(Auth::user()->departure_city_ids);
        }
        return City::with('addedBy')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->whereIn('id', $ids)->get();
    }

    public function store(Request $request)
    {
       
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        /*
        * Validation Types on Ticket Must be
        * 1) Target => Same ticket for same departure and designation must not be same
        * Example Karachi to Moro could not be re issued at any cost
        *  
        * Possible Scenario
        * 1) Same Seat can be issue multipe time ( Partial )
        * For Example Karachi to Moro seat no 35 Then seat 35 can be booked onward from Moro to Rwp
        * 2) Reserved Seat can be issue with Flag 1
        */

        $scheduleId = $request->schedule;
        $lockName = "stayLock:" . $scheduleId;  // Dynamic lock based on schedule ID
        try {
                $lock = Cache::lock($lockName, 7);  // 7-second timeout

                if ($lock->get()) {
                // this is for cache lock accuracy so i am calling reschedule method from there.
                if(isset($request->rc_flag))
                {
                    return $this->singleReschedule($request);
                }
                DB::beginTransaction();
                if ($request->terminalId == 0 && is_null(Auth::user()->terminal_id)) {
                    //Check if terminal is assigned to user
                    return response()->json(["errors" => ["Booking Error" => ["If You Are Company Admin Please Assign Terminal To Your Account  For Booking the Ticket, If You Are Employee Of Company Please Contact Your Administrator Or IT Team! "]]], 422);
                }
                //Get the Exact Schedule from Database
                $detail = ScheduleDetail::where("departure_id", $request->departureCity)
                    ->where("destination_id", $request->destinationCity)
                    ->where('schedule_id', $request->schedule)
                    ->where('departure_date', $request->date)
                    ->where('company_id', Auth::user()->company_id)
                    ->where('departure_time', date("H:i:s",strtotime($request->departure_time)))
                    ->first();

                //  $schedule_time_exact ( Date At which bus will leave from first terminal ) 
                $schedule_time_exact = ScheduleDetail::where(["schedule_id"=>$detail->schedule_id,"schedule_date"=>$detail->schedule_date])->first();
                // $schedule To get Route Details ( Example Child City for example Karachi to RWP contains Karachi to Moro and more ) 
                $schedule = Schedule::where('id', $request->schedule)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                // $existingTicket Tickets which are already booked or reserved
                $existingTicket = Ticket::where(['company_id' => Auth::user()->company_id, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule])->orderBy('id', 'desc')->first(['bus_id', 'ticket_closing_id','ticket_merge_id']);
                
                //To create Invoice Number
                $invoice = Invoice::create([
                    "schedule_id"   => $schedule->id,
                    "route_id"      => $schedule->route_id,
                    "terminal_id"   => $request->terminalId ?? Auth::user()->terminal_id,
                    "schedule_date" => $detail->schedule_date,
                    "schedule_time" => $detail->departure_time,
                    "company_id"    => Auth::user()->company_id,
                    "added_by"      => Auth::user()->id,
                ]);

                $finalAmountDiscount = 0;
                if((isset($request->flag) && $request->flag == 1) || $request->type == 'booked')
                {
                    if ($request->usagePoints == true && $request->otp_valid == true) {
    //                  Get Customer's Loyalty Card
                        $cardAssign = CardAssign::where(['id' => $request->pointsCardId, 'company_id' => Auth::user()->company_id])->first();
                        $card = CardCategory::where(['id' => $cardAssign->card_category_id, 'company_id' => Auth::user()->company_id])->first();
                        
                        if ($card->discount_type == 'percentage') {
                            $amountInPercent = (int)$card->percentage_discount * $request->pointsUseInput;
                            $finalAmountDiscount = (int)(($request->totalFare * $amountInPercent) / 100);
                            $cardAssign->update([
                                'starting_points' => $cardAssign->starting_points - $request->pointsUseInput,
                            ]);
                        }
                        if ($card->discount_type == 'flat') {
                            $finalAmountDiscount = (int)$card->flat_discount * $request->pointsUseInput;
                            $cardAssign->update([
                                'starting_points' => $cardAssign->starting_points - $request->pointsUseInput,
                            ]);
                        }
                    }
                    // loyalty card point addition
                    if (!is_null($request->customerCNIC)) {
                        $checkCard = CardAssign::where(['cnic' => plainContactAndCnic($request->customerCNIC), 'company_id' => Auth::user()->company_id])->with("cardCategory")->first();
                        if ($checkCard) {
                            if ($checkCard->cardCategory->point_type == "flatPoints") {
                                $addPoint = $request->totalAmount / $checkCard->cardCategory->point_flat;
                            } else {
                                $distance = FareTable::where(['from_city_id' => $request->departureCity, 'to_city_id' => $request->destinationCity, 'company_id' => Auth::user()->company_id])->first()->distance_in_km;
                                if ($distance) {
                                    $addPoint = $distance / $checkCard->cardCategory->point_distance;
                                } else {
                                    return response()->json(["errors" => ["Error" => ["Please Fill The Distance In Kilometer Field In fare Table"]]], 422);
                                }
                            }
                            $checkCard->increment("starting_points", $addPoint);
                        }
                    }
                }
                /*
                *   Validation
                *   Only Purpose to Seat Avoid Duplication 
                *   $lastDestination means last destination Route will have, Route are saved as 
                *   Route 1) RWP to Moro
                *   Route 2) Moro to Karachi 
                *   $lastDestination will have Karachi
                *   $allFaresOfRout will have RWP and Moro
                *   
                */
                $lastDestination = $schedule->route->fares->last();
                $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
                //RWP MORO KARACHI are now single Array
                array_push($allFaresOfRoute, $lastDestination->destination_city_id);
                //Example MORO departure will have 1 index  
                $scheduleDepIndex = array_search( $request->departureCity, $allFaresOfRoute );
                //Example Karachi destination will have 2 index  
                $scheduleDesIndex = array_search( $request->destinationCity, $allFaresOfRoute );

                // if seat are going to update then case will be different from newly created
                if (isset($request->flag) && $request->flag == 1) {
                    //Seat 35
                    $checkAlreadyBooked = Ticket::whereIn( "seat_no", $request->selectedSeats )
                    ->where([
                        'company_id'    => Auth::user()->company_id, 
                        'schedule_date' => date('Y-m-d', strtotime($detail->schedule_date) ), 
                        'schedule_id'   => $request->schedule,
                        "type"          => "booked"
                    ])
                    ->get();
                }
                else {
                    //Seat 35
                    $checkAlreadyBooked = Ticket::whereIn( "seat_no", $request->selectedSeats )
                    ->where([
                        'company_id'    => Auth::user()->company_id, 
                        'schedule_date' => date('Y-m-d', strtotime($detail->schedule_date) ), 
                        'schedule_id'   => $request->schedule
                    ])
                    ->get();
                }

                foreach($checkAlreadyBooked as $tkt)
                {
                    //Check if Ticket is Booked for RWP to MORO Target is to book MORO to Karachi
                    // $ticketDepIndex will have 0 as Karachi is at index 0
                    $ticketDepIndex = array_search( $tkt->departure_city_id, $allFaresOfRoute );
                    // $ticketDesIndex will have 1 as MORO is at index 1 
                    $ticketDesIndex = array_search( $tkt->destination_city_id, $allFaresOfRoute );

                    /*
                    *   $ticketDepIndex for RWP will have 0 and $scheduleDepIndex will have 1 for MORO $scheduleDesIndex will have 2 For Karachi
                    *   Condition 1) ($ticketDepIndex >= $scheduleDepIndex && $ticketDepIndex < $scheduleDesIndex)
                    *   Output 0 >= 1 && 0 < 2 Result False
                    *   Condition 2 )($ticketDesIndex > $scheduleDepIndex && $ticketDesIndex <= $scheduleDesIndex)
                    *   Output 1 > 1 > 1 && 1 <= 2 Result False
                    */
                    if( ($ticketDepIndex >= $scheduleDepIndex && $ticketDepIndex < $scheduleDesIndex) // Will Check Partial Seat 
                        || ($ticketDesIndex > $scheduleDepIndex && $ticketDesIndex <= $scheduleDesIndex))
                    {
                        return response()->json(["errors" => ["Error" => ["One seat of your combination is already booked"]]], 422);
                    }
                    if( ($scheduleDepIndex >= $ticketDepIndex && $scheduleDepIndex < $ticketDesIndex) // Will Check Partial Seat 
                        || ($scheduleDesIndex > $ticketDepIndex && $scheduleDesIndex <= $ticketDesIndex))
                    {
                        return response()->json(["errors" => ["Error" => ["One seat of your combination is already booked"]]], 422);
                    }
                }
                //Reservation To Booking
                if (isset($request->flag) && $request->flag == 1) {
                    //If Reserved seat is converted into Issued/Booked
                    $allTicket = updateAdvancedSeat($request, $invoice,$finalAmountDiscount);
                    
                    ActivityLog::create([
                        "activity_by"    => Auth::user()->id,
                        "message"        => Auth::user()->name." | update ticket from advance to confirm | time : $detail->schedule_date $detail->departure_time | ".json_encode($request->alreadyBookedId)." --- ".$request->totalFare,
                        "requested_host" => $request->ip(),
                        "company_id"     => Auth::user()->company_id
                    ]);
                } else {
                    //Book || Issue || Reserve
                    if (count($request->selectedSeats) == 0) {
                        //Check If Seat is already Booked
                        return response()->json(["errors" => ["Error" => ["Please refresh your seat map you entered some wrong/duplicate entry"]]], 422);
                    }
                    
                    $departure_city_id   = $schedule->route->fares->first()->departure_city_id;
                    $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                    $isPartial = 0;
                    if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
                        $isPartial = 1;
                    }
                    if ($request->customerCNIC && $request->type == 'booked') {
                        $customer = Customer::where('cnic', plainContactAndCnic($request->customerCNIC))->where('company_id', Auth::user()->company_id)->first();
                    } else if ($request->type == 'advance booking') {
                        $customer = Customer::where('contact', plainContactAndCnic($request->contact))->where('company_id', Auth::user()->company_id)->first();
                    } else {
                        $customer = false;
                    }
                    // Fare Fetching About the Schedule
                    if ($customer && $request->type == "booked") {
                        $customer->name = $request->customerName;
                        $customer->cnic = is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC);
                        $customer->contact = plainContactAndCnic($request->contact);
                        $customer->save();
                    } else {
                        $customer = Customer::create([
                            'company_id' => Auth::user()->company_id,
                            'added_by' => Auth::user()->id,
                            'name' => $request->customerName,
                            'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
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
                    
                    $allTicket = [];
                    foreach ($request->selectedSeats as $i => $seat) {
                        $checkDiscount =  checkDiscountAmount($detail,$request->terminalId,$request->selectedSeatsClass[$i]);
                        $ticket = Ticket::create([
                            'company_id'          => Auth::user()->company_id,
                            'departure_city_id'   => $request->departureCity,
                            'destination_city_id' => $request->destinationCity,
                            'seat_no'             => $seat,
                            'bus_class_id'        => $detail->bus_class_id,
                            'seat_fare'           => $request->selectedSeatsFare[$i],
                            'is_partial'          => $isPartial,
                            'booking_no'          => $bookingNo,
                            'invoice_id'          => $invoice->id,
                            'schedule_date'       => $detail->schedule_date,
                            'schedule_time'       => $detail->departure_time,
                            'schedule_time_exact' => $schedule_time_exact->departure_time,
                            'date'                => $request->date,
                            'customer_id'         => $customer->id,
                            'schedule_id'         => $schedule->id,
                            'route_id'            => $schedule->route_id,
                            'ticket_closing_id'   => $existingTicket ? $existingTicket->ticket_closing_id : null,
                            'ticket_merge_id'     => $existingTicket ? $existingTicket->ticket_merge_id : null,
                            'bus_id'              => $existingTicket ? $existingTicket->bus_id : null,
                            'schedule_details_id' => $detail->id,
                            'terminal_id'         => $request->terminalId ?? Auth::user()->terminal_id,
                            'terminal_name'       => Terminal::find($request->terminalId ?? Auth::user()->terminal_id)->name,
                            'online_terminal'     => Terminal::find($request->terminalId ?? Auth::user()->terminal_id)->is_online_terminal,
                            'remarks'             => $request->remarks,
                            'gender'              => $request->gender,
                            'type'                => $request->type,
                            'discount_type'       => $request->usagePoints ? 'card' : null,
                            'booked_time'         => date("Y-m-d H:i:s"),
                            'added_by'            => Auth::user()->id,
                            'updated_by'          => Auth::user()->id,
                            'discount'            => ($request->discount ? round($request->discount / count($request->selectedSeats)) : ($request->usagePoints ? ($finalAmountDiscount / count($request->selectedSeats)) : 0)),
                            'schedule_discount'   => $checkDiscount->schedule_discount,
                            'terminal_discount'   => $checkDiscount->terminal_discount,
                            'points_usage'        => $request->pointsUseInput / count($request->selectedSeats),
                        ]);
                        if ($isPartial == 1) {
                            TicketIsPartial::create([
                                'company_id'          => Auth::user()->company_id,
                                'departure_city_id'   => $ticket->departure_city_id,
                                'destination_city_id' => $ticket->destination_city_id,
                                'ticket_id'           => $ticket->id,
                                'seat_no'             => $ticket->seat_no,
                                'seat_fare'           => $ticket->seat_fare,
                                'booking_no'          => $ticket->booking_no,
                                'date'                => $ticket->date,
                                'customer_id'         => $ticket->customer_id,
                                'schedule_id'         => $ticket->schedule_id,
                                'gender'              => $ticket->gender,
                                'type'                => $ticket->type,
                                'added_by'            => Auth::user()->id,
                            ]);
                        }

                        if ($request->type == 'advance booking') {
                            TicketAdvancedBooked::create([
                                'company_id'          => Auth::user()->company_id,
                                'departure_city_id'   => $ticket->departure_city_id,
                                'destination_city_id' => $ticket->destination_city_id,
                                'ticket_id'           => $ticket->id,
                                'seat_no'             => $ticket->seat_no,
                                'seat_fare'           => $ticket->seat_fare,
                                'booking_no'          => $ticket->booking_no,
                                'date'                => $ticket->date,
                                'customer_id'         => $ticket->customer_id,
                                'schedule_id'         => $ticket->schedule_id,
                                'gender'              => $ticket->gender,
                                'type'                => $ticket->type,
                                'added_by'            => Auth::user()->id,
                            ]);
                        }
                        $allTicket[] = $ticket->id;
                    }
                    ActivityLog::create([
                        "activity_by"    => Auth::user()->id,
                        "message"        => Auth::user()->name." | stored ticket ($request->type) | time : $detail->schedule_date $detail->departure_time | seat no :".json_encode($request->selectedSeats)." --- ".json_encode($request->selectedSeatsFare),
                        "requested_host" => $request->ip(),
                        "company_id"     => Auth::user()->company_id
                    ]);
                }

                
                DB::commit();
                return [
                    'ids' => implode('-', $allTicket),
                    'ticket' => Ticket::where('company_id', Auth::user()->company_id)->whereIn('id', $allTicket)->get(),
                    'authTerminalId' => Auth::user()->terminal_id,
                ];
            } else {
                return response()->json(["errors" => ["Error" => ['System is busy. Please try again.']]], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public function whatsappMessage(Request $request)
    {
        return ticketConfirmedMessage($request->invoice_id);
    }

    public function whatsappCancelMessage(Request $request)
    {
        return ticketCanceledMessage($request->tickets);
    }
    
    public function sendOtp(Request $request)
    {
        return sendOtpForTicket($request);
    }
    
    public function verifyOtp(Request $request)
    {
        $customer = Customer::where("cnic",plainContactAndCnic($request->customerCNIC))->first();
        
        if($customer && $customer->loyalty_otp == $request->otp && $customer->loyalty_otp_expiration > now())
        {
            return response()->json(["error" => ["Verified Successfully"]], 200);
        }
        else
        {
            return response()->json(["error" => ["An error occurred during verification. Please check your data."]], 409);
        }
    }


    private function singleReschedule($request)
    {
        if(!checkPermissionButtons("reschedule-seats"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
  
        try {
            DB::beginTransaction();
            $scheduleDetail = ScheduleDetail::where([
                'company_id' => Auth::user()->company_id,
                'departure_date' => $request->data[0]['rescheduleDate'],
                'departure_id' => $request->data[0]['dataDepartureCity'],
                'destination_id' => $request->data[0]['dataDestination'],
                'schedule_id' => $request->data[0]['rescheduleSchedule'],
                'departure_time' =>  date("H:i:s",strtotime($request->data[0]['departure_time'])),
            ])->first();
            $schedule_time_exact = ScheduleDetail::where(["schedule_id"=>$scheduleDetail->schedule_id,"schedule_date"=>$scheduleDetail->schedule_date])->first();
            $schedule = Schedule::where('id', $scheduleDetail->schedule_id)->where('company_id', Auth::user()->company_id)->select('id', 'route_id', 'bus_class_id')->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
            /*
            *   Validation
            *   Only Purpose to Seat Avoid Duplication 
            *   $lastDestination means last destination Route will have, Route are saved as 
            *   Route 1) RWP to Moro
            *   Route 2) Moro to Karachi 
            *   $lastDestination will have Karachi
            *   $allFaresOfRout will have RWP and Moro
            *   
            */
            $lastDestination = $schedule->route->fares->last();
            $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
            //RWP MORO KARACHI are now single Array
            array_push($allFaresOfRoute, $lastDestination->destination_city_id);
            //Example MORO departure will have 1 index  
            $scheduleDepIndex = array_search( $scheduleDetail->departure_id, $allFaresOfRoute );
            //Example Karachi destination will have 2 index  
            $scheduleDesIndex = array_search( $scheduleDetail->destination_id, $allFaresOfRoute );

         
            
            
           

            foreach($request->data as $key => $item)
            {
                //Seat 35
                $tkt = Ticket::where( "seat_no", $item['selected_seatNo'] )
                ->where([
                    'company_id'    => Auth::user()->company_id, 
                    'schedule_date' => date('Y-m-d', strtotime($scheduleDetail->schedule_date) ), 
                    'schedule_id'   => $schedule->id
                ])
                ->first();
                if($tkt)
                {
                    //Check if Ticket is Booked for RWP to MORO Target is to book MORO to Karachi
                    // $ticketDepIndex will have 0 as Karachi is at index 0
                    $ticketDepIndex = array_search( $tkt->departure_city_id, $allFaresOfRoute );
                    // $ticketDesIndex will have 1 as MORO is at index 1 
                    $ticketDesIndex = array_search( $tkt->destination_city_id, $allFaresOfRoute );

                    /*
                    *   $ticketDepIndex for RWP will have 0 and $scheduleDepIndex will have 1 for MORO $scheduleDesIndex will have 2 For Karachi
                    *   Condition 1) ($ticketDepIndex >= $scheduleDepIndex && $ticketDepIndex < $scheduleDesIndex)
                    *   Output 0 >= 1 && 0 < 2 Result False
                    *   Condition 2 )($ticketDesIndex > $scheduleDepIndex && $ticketDesIndex <= $scheduleDesIndex)
                    *   Output 1 > 1 > 1 && 1 <= 2 Result False
                    */
                    if( ($ticketDepIndex >= $scheduleDepIndex && $ticketDepIndex < $scheduleDesIndex) // Will Check Partial Seat 
                        || ($ticketDesIndex > $scheduleDepIndex && $ticketDesIndex <= $scheduleDesIndex))
                    {
                        return response()->json(["errors" => ["Error" => ["One seat of your combination is already booked"]]], 422);
                    }
                }
            }
            $old_tickets = [];
            $new_tickets = [];
            foreach ($request->data as $key => $item) {
                $ticket = $item['dataAll'];
                if ($item['existingDate'] == $item['rescheduleDate']) {
                    $bookingNo = Ticket::where('date', $item['existingDate'])->latest()->first()->booking_no ?? 0;
                    ++$bookingNo;
                } else {
                    $bookingNo = Ticket::where('date', $item['rescheduleDate'])->latest()->first()->booking_no ?? 0;
                    ++$bookingNo;
                }
                
             
                $schedule_time_exact = ScheduleDetail::where(["schedule_id"=>$scheduleDetail->schedule_id,"schedule_date"=>$scheduleDetail->schedule_date])->first();
                $schedule = Schedule::where('id', $item['newDepartureTime'])->where('company_id', Auth::user()->company_id)->select('id', 'route_id', 'bus_class_id')->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                
                
                $departure_city_id = $schedule->route->fares->first()->departure_city_id;
                $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                $isPartial = 0;
                if ($item['dataDepartureCity'] != $departure_city_id || $item['dataDestination'] != $destination_city_id) {
                    $isPartial = 1;
                }
                $existingTicket = Ticket::where(['company_id' => Auth::user()->company_id, 'schedule_date' => $scheduleDetail->schedule_date, 'schedule_id' => $scheduleDetail->schedule_id])->latest()->first(['bus_id', 'ticket_closing_id','ticket_merge_id','schedule_id']);
                if ($item['selected_seatFare'] != $item['dataAll']['seat_fare']) {
                    RescheduleExtraAmount::create([
                        'company_id' => Auth::user()->company_id,
                        'old_ticket_id' => $item['dataAll']['id'],
                        'old_seat_no' => $ticket['seat_no'],
                        'new_seat_no' => $item['selected_seatNo'],
                        'old_seat_class' => $ticket['bus_class_id'],
                        'new_seat_class' => $item['selected_seatClass'],
                        'old_seat_fare' => $ticket['seat_fare'],
                        'new_seat_fare' => $item['selected_seatFare'],
                        'type' => priceDiff($ticket['seat_fare'], $item['selected_seatFare'])['type'],
                        'diff_amount' => priceDiff($ticket['seat_fare'], $item['selected_seatFare'])['diff'],
                        'old_departure_city_id' => $ticket['departure_city_id'],
                        'new_departure_city_id' => $item['dataDepartureCity'],
                        'old_destination_city_id' => $ticket['destination_city_id'],
                        'new_destination_city_id' => $item['dataDestination'],
                        'old_schedule_id' => $ticket['schedule_id'],
                        'new_schedule_id' => $item['dataSchedule'],
                        'old_booking_date' => $ticket['date'],
                        'new_booking_date' => $item['rescheduleDate'],
                    ]);
                }
                $newTicket = Ticket::create([
                    'company_id' => Auth::user()->company_id,
                    'departure_city_id' => $item['dataDepartureCity'],
                    'destination_city_id' => $item['dataDestination'],
                    'seat_no' => $item['selected_seatNo'],
                    'terminal_id' => $ticket['terminal_id'],
                    'invoice_id' => $ticket['invoice_id'],
                    'ticket_closing_id' => $existingTicket ? $existingTicket->ticket_closing_id : null,
                    'ticket_merge_id' => $existingTicket ? $existingTicket->ticket_merge_id : null,
                    'bus_id' => $existingTicket ? $existingTicket->bus_id : null,
                    'bus_class_id' => $scheduleDetail->bus_class_id,
                    'seat_fare' => $item['selected_seatFare'],
                    'is_partial' => $isPartial,
                    'booking_no' => $bookingNo,
                    'schedule_date' => $scheduleDetail->schedule_date,
                    'schedule_time' => $scheduleDetail->departure_time,
                    'schedule_time_exact' => $schedule_time_exact->departure_time,
                    'date' => $item['rescheduleDate'],
                    'schedule_details_id' => $scheduleDetail->id,
                    'customer_id' => $item['dataCustomer'],
                    'terminal_name'       => Terminal::find($ticket['terminal_id'])->name,
                    'online_terminal' => Terminal::find($ticket['terminal_id'])->is_online_terminal,
                    'schedule_id' => $item['newDepartureTime'],
                    'route_id' => $schedule->route_id,
                    'remarks' => $item['reason']??"",
                    'gender' => $ticket['gender'],
                    'type' => $ticket['type'],
                    'reschedule_type' => $item['overIssueReschedule'],
                    'added_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                    'discount' => $item['rescheduleDiscount'] ?? 0,
                    'booked_time' => date("Y-m-d H:i:s"),
                ]);
                TicketReschedule::create([
                    'company_id' => Auth::user()->company_id,
                    'old_ticket_id' => $ticket['id'],
                    'new_ticket_id' => $newTicket->id,
                    'schedule_id' => $ticket['schedule_id'],
                    'reSchedule_id' => $item['dataSchedule'],
                    'customer_id' => $ticket['customer_id'],
                    'date' => $ticket['date'],
                    'reschedule_date' => $item['rescheduleDate'],
                    'departure_city_id' => $ticket['departure_city_id'],
                    'reschedule_departure_city_id' => $item['dataDepartureCity'],
                    'reschedule_destination_city_id' => $item['dataDestination'],
                    'destination_city_id' => $ticket['destination_city_id'],
                    'reason' => $item['reason'] ?? null,
                    'added_by' => Auth::user()->id,
                ]);
                $old_ticket = Ticket::where('id', $ticket['id'])->first();
                $eltTicket = TicketELT::where(['company_id' => Auth::user()->company_id, 'ticket_id' => $ticket['id']])->first();
                if ($eltTicket) {
                    $eltTicket->ticket_id = $newTicket->id;
                    $eltTicket->customer_id = $newTicket->customer_id;
                    $eltTicket->departure_city = $newTicket->departure_city_id;
                    $eltTicket->destination_city = $newTicket->destination_city_id;
                    $eltTicket->seat_no = $newTicket->seat_no;
                    $eltTicket->schedule_id = $newTicket->schedule_id;
                    $eltTicket->seat_fare = $newTicket->seat_fare;
                    $eltTicket->date = $newTicket->date;
                    $eltTicket->save();
                }
                $old_ticket->update([
                    'type' => 'reschedule'
                ]);
                $old_tickets[] = $ticket['id'];
                $new_tickets[] = $newTicket->id;
                $old_ticket->delete();
            }
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | rescheduled ticket seat no : ".$ticket['seat_no']." to  ".$item['selected_seatNo']." | to time : $scheduleDetail->schedule_date $scheduleDetail->departure_time",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            ticketRescheduledMessage($old_tickets,$new_tickets);
            return response()->json(['success' => 'Success'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    // public function deleteBooking(Request $request)
    // {
    //     return Ticket::find($request->id)->delete();
    // }

    public function fetchSpecificSchedule(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if (!$request->date) {
            return "Date is Required";
        }
        $visibleScheduleIds = ScheduleTerminalVisibility::where(["company_id"=>Auth::user()->company_id,"terminal_id"=>$request->terminal??Auth::user()->terminal_id,"visibility"=>1])->pluck("schedule_id");
        $advanceBookingDays = Terminal::where("id",Auth::user()->terminal_id)->first()->advance_booking;

        $allSchedules = ScheduleDetail::whereIn("schedule_id",$visibleScheduleIds)
        ->with('schedule')
        ->whereHas('schedule', function($q){$q->where("hide",0);})
        ->where(['departure_id' => $request->departure_city_id, 'destination_id' => $request->destination_city_id, 'departure_date' => $request->date,'company_id' => Auth::user()->company_id])
        ->oldest("departure_time")
        ->when($advanceBookingDays!=null,function($q)use($advanceBookingDays){
            $q->where("departure_date",'<',now()->addDays($advanceBookingDays)->format("Y-m-d"));
        })
        ->get();
        
        foreach ($allSchedules as $key => $single) {
            $sub = 0;
            $terminalTime = TerminalTimeDifference::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id, 'route_id' => $single->schedule->route_id])->first();
            if($terminalTime)
            {
                $sub = $terminalTime->time_difference * 60;
            }

            $single->departure_city_time = $single->departure_time;
            $exactDate = date("Y-m-d h:i A", strtotime($single->departure_date . ' ' . $single->departure_time) + $sub);
            $single->departure_date_time = date("Y-m-d H:i:s",strtotime($exactDate));
            $single->departure_date = date("m/d/Y", strtotime($exactDate));
            $single->departure_time = date("h:i A", strtotime($exactDate));
           
            $visibilty = TerminalVisibility::where(["route_id"=>$single->schedule->route_id,"departure_city_id"=>$single->departure_id,"destination_city_id"=>$single->destination_id])->first();
            if(Auth::user()->check_booking_minutes && isset($visibilty->booking_minutes) && $visibilty->booking_minutes >= 0)
            {
                
                $bookingTime = strtotime($single->departure_date . ' ' . $single->departure_time) - ($visibilty->booking_minutes * 60);
                $currentTime = strtotime(date("Y-m-d H:i:s"));

                if ($currentTime < $bookingTime) {
                    unset($allSchedules[$key]);
                }
            }
            
        }
        // return $allSchedules;
        if(checkPermissionButtons("time-lock"))
        {
            $filteredSchedules = $allSchedules->where("departure_date_time", '>', date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s")) - 7200));
            return array_values($filteredSchedules->toArray());
        }
        return $allSchedules;
    }

    public
    function fetchSpecificDestination(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id', Auth::user()->company_id)->pluck('destination_city_id')->toArray();
        if (Auth::user()->destination_city_ids == "all") {
            $finalArray = $depart_city;
        } else {
            $ids = json_decode(Auth::user()->destination_city_ids);
            $finalArray = array_intersect(array_unique($depart_city), $ids);
        }
        return City::whereIn('id', $finalArray)->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(['id', 'name']);
    }

    public function fetchSpecificOverIssueSeat(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $ticket = Ticket::where(["company_id" => Auth::user()->company_id, "date" => $request->date, "seat_no" => $request->seat_no, "schedule_id" => $request->schedule_id, "destination_city_id" => $request->destination_id, "departure_city_id" => $request->departure_id])->select('booking_no', 'customer_id', 'gender', 'is_partial', 'type', 'remarks')->first();
        $customer = Customer::where(["company_id" => Auth::user()->company_id, "id" => $ticket->customer_id])->first();
        $ticket = json_decode(json_encode($ticket), true);
        $fare = ["fare" => $request->seat_fare, "seat_no" => $request->seat_no];
        $ticket = array_merge($ticket, $fare);
        $customer = json_decode(json_encode($customer), true);
        return [
            'ticket' => $ticket,
            'customer' => $customer,
        ];
    }

    public function overIssueAddNew(Request $request)
    {
        if(!checkPermissionButtons("overissue-seat"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
            DB::beginTransaction();
            $ticket = Ticket::where([
                'company_id' => Auth::user()->company_id,
                'date' => $request->date,
                'schedule_id' => $request->schedule_id,
                'customer_id' => $request->customer_id,
                'departure_city_id' => $request->departure_id,
                'destination_city_id' => $request->destination_id,
                'seat_no' => $request->seat_no,

            ])->first();
            $ticket->update([
                'type' => 'over-issue',
            ]);
            TicketsOverIssue::create([
                'company_id' => Auth::user()->company_id,
                'ticket_id' => $ticket->id,
                'percentage' => $request->percentage,
                'reason' => $request->remarks,
                'added_by' => Auth::user()->id,
            ]);
            $ticket->delete();
            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | added seat ($ticket->seat_no) to over issue | time : $ticket->schedule_date $ticket->schedule_time",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();
            return $ticket;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }
    }

    public
    function getCnic(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if ($request->status == 'addFormCNIC') {
            $cnic = plainContactAndCnic($request['cnicNumber']);
            return Customer::where('company_id', Auth::user()->company_id)->where('cnic', $cnic)->first();
        }
        if ($request->status == 'addFormContact') {
            return Customer::where('company_id', Auth::user()->company_id)->where('contact', plainContactAndCnic($request['phoneNumber']))->first();
        }
    }

    public
    function getPoints(Request $request)
    {
        if ($request->status == 'addFormCNIC' && $request['cnicNumber']) {
            $cardAssign = CardAssign::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request['cnicNumber']))->first();
            if (!is_null($cardAssign)) {
                if ($cardAssign->expiry_date >= date('Y-m-d')) {
                    return $cardAssign;
                } else {
                    return response()->json(["expiredData" => "Loyalty Card is Expired Please Renew It"], 201);
                }
            }
            return response()->json(["not_found" => "This Customer Dont have loyalty card"], 404);
        }
    }

    public
    function usagePoints(Request $request)
    {
        $category = CardAssign::where(['company_id' => Auth::user()->company_id, 'id' => $request->id])->select('card_category_id', 'starting_points')->first();
        $data = CardCategory::where('id', $category->card_category_id)->first(['discount_type', 'flat_discount', 'percentage_discount', 'point_type', 'point_flat', 'point_distance']);
        if ($data->discount_type == 'percentage') {
            return ($data->percentage_discount * $category->starting_points) >= 100 ? 100 . '% Discount' : $data->percentage_discount * $category->starting_points . '% Discount';
        }
        if ($data->discount_type == 'flat') {
            return $data->flat_discount * $category->starting_points . ' Discount In Flat Amount';
        }
    }

    public
    function getTerminals()
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return [
            'terminals' => Terminal::with('city')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get(),
            'authTerminalId' => Auth::user()->terminal_id ?? 0,
        ];
    }

    // public
    // function detailTicket(Request $request)
    // {
    //     if(!checkPermissionButtons("seat-details"))
    //     {
    //         return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    //     }
    //     return Ticket::with('addedBy', 'customer')->where('company_id', Auth::user()->company_id)
    //         ->whereDate('date', $request->date)
    //         ->where('schedule_id', $request->schedule_id)
    //         ->get();
    // }

    public
    function advanceData(Request $request)
    {
        if(!checkPermissionButtons("seat-details"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first(['schedule_date']);
        $tickets = Ticket::with('scheduleDetail', 'schedule', 'customer', 'company', 'destination_city', 'departure_city', 'busClass','added_name')->where('company_id', Auth::user()->company_id)->whereIn('seat_no', $request->seatNO)->where('schedule_id', $request->scheduleId)->where('schedule_date', $uniqueDate->schedule_date)->get()->groupBy('seat_no');
        $checkCustomers = [];
        foreach ($tickets as $key => $single) {
            foreach ($single as $key => $item) {
                $checkCustomers[] = $item->customer_id;
                $item->bookingDate = $item->booked_time ? date('d/m/Y H:i A', strtotime($item->booked_time)) : '---';
            }
        }

        if (count(array_unique($checkCustomers)) == 1) {
            return [
                'showButton' => true,
                'tickets' => $tickets,
            ];
        } else {
            return [
                'showButton' => false,
                'tickets' => $tickets,
            ];
        }
    }

    public
    function getFareClass()
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return FareClass::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public
    function dropCheck(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->id,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first()->schedule_date;
        $found = DropSchedule::with("drop_by")->where([
            'company_id' => Auth::user()->company_id,
            'schedule_date' => $uniqueDate,
            'schedule_id' => $request->id,
            'is_drop' => 1,
        ])->first();

        return [
            "checkDrop" => $found,
        ];
    }


    public
    function checkAssignedBus(Request $request)
    {
        $departureTime = ScheduleDetail::where(["schedule_id" => $request->scheduleId,
            "departure_id" => $request->departureCity,
            "destination_id" => $request->destinationCity,
            "departure_date" => $request->date,
            "company_id" => Auth::user()->company_id
        ])->first();
        $checkBusAssigning = Ticket::where(["schedule_date" => $departureTime->schedule_date, "schedule_id" => $departureTime->schedule_id])->where("bus_id", '!=', null)->first();
        if ($checkBusAssigning) {
            return response()->json([], 200);
        } else {
            return response()->json([], 204);
        }
    }

    public
    function fetchELTDetails(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where("departure_id", $request->departureCity)
            ->where("destination_id", $request->destinationCity)
            ->where('schedule_id', $request->id)
            ->where('departure_date', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->first();
        $tickets = Ticket::where("schedule_date", $uniqueDate->schedule_date)->where("schedule_id", $uniqueDate->schedule_id)->pluck("id");
        $eltTickets = TicketELT::with('customer:id,name')->whereIn("ticket_id", $tickets)->get();
        if ($eltTickets) {
            return $eltTickets;
        } else {
            return response()->json([], 204);
        }
    }

    public
    function terminalSeats(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $seats = Terminal::where('id', $request->terminal_id)->value('available_seats');
        if (!is_null($seats) && Auth::user()->check_allowed_seats == 1) {
            if (strpos($seats, '-') !== false) {
                $rangeSeats = explode("|", str_replace(',', '|', $seats));
                $output = [];
                foreach ($rangeSeats as $range) {
                    $parts = explode("-", $range);
                    $start = intval($parts[0]);
                    $end = intval($parts[1]);
                    for ($i = $start; $i <= $end; $i++) {
                        $output[] = (int)str_pad($i, 2, "0", STR_PAD_LEFT);
                    }
                }
                return array_unique($output);
            }
            $arrays = explode(",", $seats);
            $seats = [];
            foreach ($arrays as $item) {
                $seats[] = (int)$item;
            }
            return $seats;
        }
        return response()->json([], 204);
    }

    public
    function selected(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if (!$request->departureCity || !$request->destinationCity || !$request->date) {
            echo "Error";
            return [];
        }
        $scheduleDetail = ScheduleDetail::with("bus_class:id,seat_map")->where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->id,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first();
        // Getting Already Booked Tickets
        $tickets = Ticket::with('departure_city', 'destination_city', 'schedule', 'customer', 'company', 'addedBy' ,'updated_name')
            ->where('company_id', Auth::user()->company_id)->where('schedule_id', $request->id)
            ->whereDate('schedule_date', $scheduleDetail->schedule_date)->get();
        $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
        $schedule = Schedule::where('id', $request->id)
            ->where('company_id', Auth::user()->company_id)
            ->select('id', 'route_id', 'bus_class_id', 'time', 'discount_id', 'surcharge_id')
            ->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')
            ->first();
        $scheduleDiscount = Discount::where('id', $schedule->discount_id)
        ->where('is_active', 1)
        ->whereHas("discount_terminals", function ($q) use ($request) {
            $q->where("terminal_id", $request->dropTerminal);
        })
        ->first();
        $scheduleSurcharge = Surcharge::where('id', $schedule->surcharge_id)->where('is_active', 1)->first();
        $fareForAllClasses = FareTable::where('from_city_id', $request->departureCity)->where('to_city_id', $request->destinationCity)
            ->where('company_id', Auth::user()->company_id)
            ->get()->unique('fare_class');
        // getting cities sequence for checking which city will be after other one
        $lastFare = $schedule->route->fares->last();
        $allFaresOfRoute = $schedule->route->fares->unique('departure_city_id')->pluck('departure_city_id')->toArray();
        array_push($allFaresOfRoute, $lastFare->destination_city_id);
        $fareClasses = FareClass::where('company_id', Auth::user()->company_id)->get();
        if (count($fareClasses) != count($fareForAllClasses)) {
            return response()->json([
                "errors" => [
                    "Fare Error" => ["Please Fill the Fare Table Completely First ( For All Fare Classes ) !!!"]
                ]
            ], 422);
        }
//        //Apply terminal discount
        $terminalDiscount = TerminalDiscount::where(["terminal_id" => $request->dropTerminal ?? 0, "route_id" => $schedule->route_id])->where('start_date', '<=', date("Y-m-d"))
        ->where('end_date', '>=', date("Y-m-d"))->first();

        // Looping Through the seat of the bus
        $seatMap = $scheduleDetail->bus_class->seat_map;
        foreach ($seatMap as $i => $iValue) {
            foreach ($iValue as $j => $column) {
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
                        $tdiscount = ((int)$data->fare / 100) * (float)$terminalDiscount->discount;
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
                    $seatMap[$i][$j]['terminal'] = $tickets[$result]['terminal_id'];
                    $seatMap[$i][$j]['remarks'] = $tickets[$result]['remarks'] == null ? 'N/A' : $tickets[$result]['remarks'];
                    $seatMap[$i][$j]['customer_cnic'] = $tickets[$result]['customer']['cnic'];
                    $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                    $seatMap[$i][$j]['seat_fare'] = $tickets[$result]['seat_fare'] - $tickets[$result]['discount'];
                    $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                    $seatMap[$i][$j]['online_terminal'] = $tickets[$result]['online_terminal'];
                    $seatMap[$i][$j]['booked_by'] = $tickets[$result]['updated_name']['name']??"N/A";
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
                            $seatMap[$i][$j]['terminal'] = $tickets[$result]['terminal_id'];
                            $seatMap[$i][$j]['remarks'] = $tickets[$singlePartial]['remarks'] == null ? 'N/A' : $tickets[$singlePartial]['remarks'];
                            $seatMap[$i][$j]['customer_cnic'] = $tickets[$singlePartial]['customer']['cnic'];
                            $seatMap[$i][$j]['customer_name'] = $tickets[$singlePartial]['customer']['name'];
                            $seatMap[$i][$j]['seat_fare'] = $tickets[$result]['seat_fare'] - $tickets[$result]['discount'];
                            $seatMap[$i][$j]['customer_phone'] = $tickets[$singlePartial]['customer']['contact'];
                            $seatMap[$i][$j]['online_terminal'] = $tickets[$singlePartial]['online_terminal'];
                            $seatMap[$i][$j]['booked_by'] = $tickets[$singlePartial]['updated_name']['name']??'N/A';
                            $seatMap[$i][$j]['departure_city_name'] = $tickets[$singlePartial]['departure_city']['name'];
                            $seatMap[$i][$j]['destination_city_name'] = $tickets[$singlePartial]['destination_city']['name'];
                            $seatMap[$i][$j]['class_name'] = $fareClasses->where('id', $column['class'])->first()->name;
                            $seatMap[$i][$j]['fare'] = 0;

                            $before = (array_search($request->departureCity, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->departureCity, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->destinationCity, $allFaresOfRoute, false) <= array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->destinationCity, $allFaresOfRoute, false) < array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false));

                            // Condition for validation that departure city and destination city in the request should be "After" the partial seat's targeted cities
                            $after = (array_search($request->departureCity, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->departureCity, $allFaresOfRoute, false) >= array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->destinationCity, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['departure_city_id'], $allFaresOfRoute, false) &&
                                array_search($request->destinationCity, $allFaresOfRoute, false) > array_search($tickets[$singlePartial]['destination_city_id'], $allFaresOfRoute, false)
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
                            $tdiscount = ((int)$fare / 100) * (float)$terminalDiscount->discount;
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
                        // check if any discount/surcharge apply then it should apply custom round other wise show fix fare
                        if($fare != $seatMap[$i][$j]['fare'])
                        {
                            $seatMap[$i][$j]['fare'] = customRound($seatMap[$i][$j]['fare']??0);
                        }
                    }
                }
                
            }
        }
        $schedule->bus_class->seat_map = $seatMap;
        $schedule->bus_no = $tickets->count() > 0 && $tickets[0]->bus_id ? Bus::find($tickets[0]->bus_id)->bus_number : "N/A";
        unset($schedule->route);
        $schedule->auth_terminal = Auth::user()->terminal;
        return $schedule;
    }

    public function dropSchedule(Request $request)
    {
        if(!checkPermissionButtons("drop-schedule"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $scheduleDetail = ScheduleDetail::where([
                    'company_id' => Auth::user()->company_id,
                    'schedule_id' => $request->schedule_id,
                    'departure_date' => $request->date,
                    'departure_id' => $request->departure_city_id,
                    'destination_id' => $request->destination_city_id,
                ])->first();

                $tickets = Ticket::where([
                    'company_id' => Auth::user()->company_id,
                    'schedule_id' => $request->schedule_id,
                    'schedule_date' => $scheduleDetail->schedule_date,
                ])->first();
                $old = DropSchedule::where([
                    'company_id' => Auth::user()->company_id,
                    'date' => $request->date,
                    'schedule_date' => $scheduleDetail->schedule_date,
                    'schedule_id' => $request->schedule_id,
                ])->first();
                
                if (isset($tickets) && $tickets->ticket_closing_id != null) {
                    TicketClosingMember::where([
                        'company_id' => Auth::user()->company_id,
                        'ticket_closing_id' => $tickets->ticket_closing_id,
                    ])->delete();

                    
                    $merge = TicketClosing::where([
                        'company_id' => Auth::user()->company_id,
                        'id' => $tickets->ticket_closing_id,
                    ])->first();

                    if($merge)
                    {
                        TicketClosing::where([
                            'company_id' => Auth::user()->company_id,
                            'id' => $tickets->ticket_closing_id,
                        ])->delete();
    
                        $mergeRecord = TicketClosingMerge::find($merge->ticket_merge_id);
                        if ($mergeRecord->schedule_complete == 1) {
                            TicketClosingMerge::where([
                                'company_id' => Auth::user()->company_id,
                                'id' => $merge->ticket_merge_id,
                            ])->update([
                                "schedule_complete" => 0,
                                "schedule_return_date" => null,
                            ]);
                        } else {
                            $mergeRecord->delete();
                        }
                    }
                }
                if (!$old) {
                    DropSchedule::create([
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'date' => $request->date,
                        'schedule_date' => $scheduleDetail->schedule_date,
                        'schedule_id' => $request->schedule_id,
                        'added_by' => Auth::user()->id,
                        'is_drop' => 1,
                        'reason' => $request->reason,
                    ]);
                    ActivityLog::create([
                        "activity_by" => Auth::user()->id,
                        "message" => Auth::user()->name." | dropped schedule | time : $scheduleDetail->schedule_date $scheduleDetail->departure_time",
                        "requested_host" => $request->ip(),
                        "company_id" => Auth::user()->company_id
                    ]);
                    DB::commit();
                    return response()->json(['success' => 'Success'], 200);
                }
                DB::commit();
                return response()->json(["errors" => ["Error" => ["This Schedule is already Closed"]]], 422);
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    public function revertDropSchedule(Request $request)
    {
        if(!checkPermissionButtons("drop-schedule"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $scheduleDetail = ScheduleDetail::where([
                    'company_id' => Auth::user()->company_id,
                    'schedule_id' => $request->schedule_id,
                    'departure_date' => $request->date,
                    'departure_id' => $request->departure_city_id,
                    'destination_id' => $request->destination_city_id,
                ])->first();
                DropSchedule::where([
                    'company_id' => Auth::user()->company_id,
                    'schedule_date' => $scheduleDetail->schedule_date,
                    'schedule_id' => $request->schedule_id,
                    'is_drop' => 1,
                ])->delete();
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | revert schedule | time : $scheduleDetail->schedule_date $scheduleDetail->departure_time",
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

    public
    function getClosingData(Request $request)
    {
        if(!checkPermissionButtons("assign-bus"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first()->schedule_date;

        $schedule = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'schedule_date' => $uniqueDate,
        ])
            ->with("schedule.route:id,name")
            ->first();

        // if already assign
        $checkAssign = TicketClosing::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'schedule_date' => $uniqueDate,
        ])
            ->with("members")
            ->first();

        $infoData = (object)[];
        $infoData->schedule = date("m/d/Y h:i A", strtotime("$schedule->schedule_date $schedule->departure_time")) . ' - ' . $schedule->schedule->name;
        $infoData->ticket_closing_id = $checkAssign ? $checkAssign->id : '';
        $infoData->merge_id = $checkAssign ? $checkAssign->ticket_merge_id : '';
        $infoData->alreadyAssigned = $checkAssign ? 1 : 0;
        $infoData->schedule_date = $schedule->schedule_date;
        $infoData->schedule_id = $schedule->schedule_id;
        $infoData->route_name = $schedule->schedule->route->name;
        $infoData->description = $checkAssign ? $checkAssign->description : '';
        $infoData->bus = $checkAssign ? $checkAssign->bus_id : '';
        $infoData->bus_no = $checkAssign ? Bus::find($checkAssign->bus_id)->bus_number : 'N/A';
        $infoData->drivers = $checkAssign ? $checkAssign->members->where("type", 1)->pluck('user_id') : [];
        $infoData->hosts = $checkAssign ? $checkAssign->members->where("type", 2)->pluck('user_id') : [];
        $buses = Bus::where('company_id', Auth::user()->company_id)->orderBy('id')->get();
        $hosts = Employee::where(['employee_type' => 2, 'company_id' => Auth::user()->company_id,"hide"=>0])->orderBy('id')->where("user_id", '!=', 0)->get(["user_id", "name", "cnic"]);
        $drivers = Employee::where(['employee_type' => 1, 'company_id' => Auth::user()->company_id,"hide"=>0])->orderBy('id')->get(["id", "user_id", "name", "cnic"]);
        
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "infoData" => $infoData,
        ];
        return $data;
    }
    
    public
    function getBusClasses(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $bus_classes =  BusClass::with('addedBy')->orderBy('id')->where(['company_id'=> Auth::user()->company_id,"hide" => 0])->get();
    
        $data = [
            "bus_classes" => $bus_classes,
        ];
        return $data;
    }
    
    function updateBusClass(Request $request)
    {
        if(!checkPermissionButtons("bus-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                $scheduleDetail = ScheduleDetail::with("bus_class:id,seat_map")->where([
                    'company_id' => Auth::user()->company_id,
                    'schedule_id' => $request->id,
                    'departure_date' => $request->date,
                    'departure_id' => $request->departureCity,
                    'destination_id' => $request->destinationCity,
                    'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
                ])->first();
                
                ScheduleDetail::where(['company_id'=> Auth::user()->company_id,"schedule_id" => $scheduleDetail->schedule_id,"schedule_date"=>$scheduleDetail->schedule_date])->update([
                    "bus_class_id" => $request->bus_class
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public
    function bookingElt(Request $request)
    {
        if(!checkPermissionButtons("add-elt"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                if (is_null(Auth::user()->terminal_id)) {
                    return response()->json(["errors" => ["Booking Error" => ["Some Error Occur, Please Refresh The page, If Error Still Occurs Please Contact to Your IT-Team"]]], 422);
                }
                $ticket = Ticket::where([
                    'company_id' => Auth::user()->company_id,
                    'date' => $request->date,
                    'schedule_id' => $request->schedule_id,
                    'customer_id' => $request->customer_id,
                    'departure_city_id' => $request->departure_id,
                    'destination_city_id' => $request->destination_id,
                    'seat_no' => $request->seat_no,
                ])->first();
                $old = TicketELT::where([
                    'company_id' => Auth::user()->company_id,
                    'ticket_id' => $ticket->id,
                    'customer_id' => $request->customer_id,
                    'schedule_id' => $request->schedule_id,
                    'date' => $request->date,
                ])->first();

                if (!$old && $request->alreadyExist !== 0) {
                    $ticketElt = TicketELT::create([
                        'company_id' => Auth::user()->company_id,
                        'ticket_id' => $ticket->id,
                        'customer_id' => $request->customer_id,
                        'departure_city' => $request->departure_id,
                        'destination_city' => $request->destination_id,
                        'schedule_id' => $request->schedule_id,
                        'seat_no' => $request->seat_no,
                        'date' => $request->date,
                        'elt_price' => $request->totalPrice,
                        'seat_fare' => $request->singleFare,
                        'elt_weight' => $request->eltWeight,
                        'elt_description' => $request->eltDescription,
                        'added_by' => Auth::user()->id,
                    ]);
                    ActivityLog::create([
                        "activity_by" => Auth::user()->id,
                        "message" => Auth::user()->name." | stored elt against seat no ($request->seat_no) | time : $ticket->schedule_date $ticket->schedule_time",
                        "requested_host" => $request->ip(),
                        "company_id" => Auth::user()->company_id
                    ]);
                    
                    DB::commit();
                    return $ticketElt;
                } else {
                    $old->update([
                        'elt_price' => $request->totalPrice,
                        'elt_weight' => $request->eltWeight,
                        'elt_description' => $request->eltDescription,
                        'updated_by' => Auth::user()->company_id,
                    ]);
                    ActivityLog::create([
                        "activity_by" => Auth::user()->id,
                        "message" => Auth::user()->name." | update elt against seat no ($request->seat_no) | time : $ticket->schedule_date $ticket->schedule_time",
                        "requested_host" => $request->ip(),
                        "company_id" => Auth::user()->company_id
                    ]);
                    DB::commit();
                    return $old;
                }
                // return response()->json(["errors" => ["Error" => ["Elt Already Exist Against This Seat! Please Select any Other Seat"]]], 422);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public
    function cancelingBooking(Request $request)
    {
        if(!checkPermissionButtons("cancel-ticket") && !checkPermissionButtons("reserved-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $ticket = Ticket::where([
                    'company_id' => Auth::user()->company_id,
                    'date' => $request->date,
                    'schedule_id' => $request->schedule_id,
                    'customer_id' => $request->customer_id,
                    'departure_city_id' => $request->departure_id,
                    'destination_city_id' => $request->destination_id,
                    'seat_no' => $request->seat_no,

                ])->first();

                //Deduct points reverse in case of cancellation
                $customer = Customer::where('id', $ticket->customer_id)->first();
                $checkCard = CardAssign::where(['cnic' => $customer->cnic, 'company_id' => Auth::user()->company_id])->with("cardCategory")->first();
                if ($checkCard) {
                    if ($checkCard->cardCategory->point_type == "flatPoints") {
                        $subPoint = $ticket->seat_fare / $checkCard->cardCategory->point_flat;
                    } else {
                        $distance = FareTable::where(['from_city_id' => $ticket->departure_city_id, 'to_city_id' => $ticket->destination_city_id, 'company_id' => Auth::user()->company_id])->first()->distance_in_km;
                        $subPoint = $distance / $checkCard->cardCategory->point_distance;
                    }
                    if($ticket->type == "booked")
                    {
                        $checkCard->decrement("starting_points", $subPoint);
                    }

                    $checkCard->increment("starting_points", $ticket->points_usage);
                }

                $delElt = TicketELT::where('ticket_id', $ticket->id)->first();
                if ($delElt) {
                    $delElt->delete();
                }
                $type = $ticket->type;
                $ticket->update([
                    'type' => 'canceled',
                ]);
                BookingCancel::create([
                    'company_id' => Auth::user()->company_id,
                    'ticket_id' => $ticket->id,
                    'percentage' => $request->percentage,
                    'reason' => $request->remarks,
                    'type' => $type,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | canceled booking. seat no ($request->seat_no) | time : $ticket->schedule_date $ticket->schedule_time",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                
                $tkts = [];
                if($type == "booked")
                {
                    $tkts[] = $ticket->id;
                }
                $ticket->delete();
                DB::commit();
                return [
                    "tickets" => $tkts
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    public
    function cancelingAllBooking(Request $request)
    {
        if(!checkPermissionButtons("cancel-ticket") && !checkPermissionButtons("reserved-cancel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $tickets = Ticket::whereIn("id",$request->cancelAllSeat)->where(['company_id' => Auth::user()->company_id])->get();
                $status = $tickets[0]->type;
                foreach($tickets as $ticket)
                {
                    $delElt = TicketELT::where('ticket_id', $ticket->id)->first();
                    if ($delElt) {
                        $delElt->delete();
                    }


                    //Deduct points reverse in case of cancellation
                    $customer = Customer::where('id', $ticket->customer_id)->first();
                    $checkCard = CardAssign::where(['cnic' => $customer->cnic, 'company_id' => Auth::user()->company_id])->with("cardCategory")->first();
                    if ($checkCard) {
                        if ($checkCard->cardCategory->point_type == "flatPoints") {
                            $subPoint = $ticket->seat_fare / $checkCard->cardCategory->point_flat;
                        } else {
                            $distance = FareTable::where(['from_city_id' => $ticket->departure_city_id, 'to_city_id' => $ticket->destination_city_id, 'company_id' => Auth::user()->company_id])->first()->distance_in_km;
                            $subPoint = $distance / $checkCard->cardCategory->point_distance;
                        }
                        if($ticket->type == "booked")
                        {
                            $checkCard->decrement("starting_points", $subPoint);
                        }

                        $checkCard->increment("starting_points", $ticket->points_usage);
                    }

                    $type = $ticket->type;
                    $ticket->update([
                        'type' => 'canceled',
                    ]);
                    BookingCancel::create([
                        'company_id' => Auth::user()->company_id,
                        'ticket_id' => $ticket->id,
                        'percentage' => $request->percentage,
                        'reason' => $request->reason,
                        'type' => $type,
                        'added_by' => Auth::user()->id,
                    ]);
                    $ticket->delete();
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | canceled booking. seat no ".(implode(',',$tickets->pluck('seat_no')->toArray()))." | time : ".$tickets[0]->schedule_date." ".$tickets[0]->schedule_time,
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                
                $tkts = [];
                if($type == "booked")
                {
                    $tkts = $tickets->pluck('id');
                }
                
                DB::commit();
                return [
                    "tickets" => $tkts
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public
    function terminalInvoice(Request $request)
    {
        if(!checkPermissionButtons("terminal-invoice"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $scheduleDetail = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first();

        $passengerData = Ticket::withTrashed()->with('customer:id,name,cnic,contact', 'addedBy:id,name','updated_name:id,name', 'terminal:id,name', 'elt:id,elt_price,ticket_id', 'destination_city:id,name', 'departure_city:id,name')->where([
            'company_id' => Auth::user()->company_id,
            'terminal_id' => $request->terminal_id ?? Auth::user()->terminal_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $scheduleDetail->schedule_date,
        ])->where(function ($query) {
            $query->where("type", "booked")
                  ->orWhere("type", "over-issue");
        })
        ->get();
       
        $routeId = Schedule::where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first()->route_id;
        $commission = TerminalCommission::where(["company_id" => Auth::user()->company_id, 'terminal_id' => $request->terminal_id ?? Auth::user()->terminal_id, "route_id" => $routeId])->first();

        $driverInfo = getMembers($passengerData->first(), Auth::user()->company_id, 1) ?? [];
        $hostInfo = getMembers($passengerData->first(), Auth::user()->company_id, 2) ?? [];
        $routeName = routeName($request->schedule_id);
        $bus = (object)[];
        $bus->bus_class = BusClass::find($scheduleDetail->bus_class_id)->name??'N/A';
        $bus->bus_no = $passengerData->count() > 0 ? Bus::find($passengerData[0]->bus_id)->bus_number??'N/A' : "N/A";
        
        $date = date_format(date_create($scheduleDetail->schedule_date . ' ' . $scheduleDetail->departure_time), "l") . ' , ' . date_format(date_create($scheduleDetail->schedule_date . ' ' . $scheduleDetail->departure_time), "d F Y H:i:s A");
        $eltAmount = 0;
        foreach ($passengerData as $passenger) {
            $eltAmount += $passenger->elt != null ? $passenger->elt->elt_price : 0;
        }
        $passengerData = $passengerData->sortBy(function ($item) {
            // Extract the integer part of the 'age' values using a regular expression
            preg_match('/\d+/', $item['seat_no'], $matches);
            
            // Use the extracted integer part for sorting
            return $matches[0] ?? 0;
        })->values();
        
        $refunds = Ticket::with('cancel_ticket:ticket_id,percentage')->where([
            'company_id' => Auth::user()->company_id,
            'terminal_id' => $request->terminal_id ?? Auth::user()->terminal_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $scheduleDetail->schedule_date,
        ])->onlyTrashed()->get(['id', 'seat_fare', 'discount']);
        $refundData = 0;
        foreach ($refunds as $single) {
            $percentageValue = ((int)$single->seat_fare - ((int)$single->discount)) * (is_null($single->cancel_ticket) ? 0 : $single->cancel_ticket->percentage);
            $final = $percentageValue / 100;
            $refundData += $final;
        }
        ;
        $passengerData = ['record' => $passengerData, 'driverInfo' => $driverInfo, 'hostInfo' => $hostInfo, 'routeName' => $routeName, 'bus' => $bus, 'date' => $date, 'terminalGross' => $passengerData->sum('seat_fare'), 'totalElt' => $eltAmount, 'commission' => $commission, 'refund' => round($refundData)];
        $terminal = Terminal::find(Auth::user()->terminal_id);
        $format = TicketsTemplate::with("terminal")
        ->join("ticket_template_terminals","ticket_template_terminals.ticket_template_id","tickets_templates.id")
        ->whereNull('tickets_templates.deleted_at')
        ->whereNull('ticket_template_terminals.deleted_at')
        ->where(['tickets_templates.company_id'=> Auth::user()->company_id,"ticket_template_terminals.terminal_id"=>Auth::user()->terminal_id])->where('tickets_templates.status', 1)
        ->first();
        return view('pdf/TerminalPaxDetails', ['data' => $passengerData, 'terminal' => $terminal,"format"=>$format]);
    }

    public
    function busInvoice(Request $request)
    {
        if(!checkPermissionButtons("bus-invoice"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first()->schedule_date;
        $route = Schedule::where([
            'company_id' => Auth::user()->company_id,
            'id' => $request->schedule_id,
        ])->with('route')->first()->route;
        $scheduleDeparture = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->first();

        // this is for general data
        $infoData = (object)[];
        $infoData->route = $route->name;
        $infoData->departure_date = $scheduleDeparture->schedule_date;
        $infoData->departure_time = $scheduleDeparture->departure_time;
        $infoData->current_terminal = Terminal::where([
            'company_id' => Auth::user()->company_id,
            'id' => Auth::user()->terminal_id,
        ])->first() ? Terminal::where([
            'company_id' => Auth::user()->company_id,
            'id' => Auth::user()->terminal_id,
        ])->first()->name : "Not Assigned Terminal";

        $mainData = Ticket::withTrashed()->where([
            'tickets.company_id' => Auth::user()->company_id,
            'tickets.schedule_id' => $request->schedule_id,
            'tickets.schedule_date' => $uniqueDate,
        ])->where('type', '!=', 'reschedule')->where('type', '!=', 'canceled')->where('type', '!=', 'advance booking')
            ->with("terminal:id,name", "destination_city:id,name")
            ->with(["commission" => function ($q) use ($route) {
                return $q->where("route_id", $route->id);
            }])
            ->leftJoin("ticket_e_l_t_s", "ticket_e_l_t_s.ticket_id", "tickets.id") //this for if elt exist show else null
            ->select("tickets.*", "ticket_e_l_t_s.elt_price")
            ->get()->groupBy(["terminal_id", "destination_city_id"]);

        // for add cancelation charges into the bus invoice paid by customer
        $cancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                'schedule_id' => $request->schedule_id,
                'schedule_date' => $uniqueDate,
            ])
            ->where(function($query) {
                $query->where("type", "canceled")
                    ->whereHas('cancel_ticket', function ($query) {
                        $query->where('percentage', '>', 0);
                    });
            })
            ->with("cancel_ticket:id,ticket_id,percentage","terminal:id,name")
            ->get(["id","seat_fare","discount","terminal_id","seat_no"])->groupBy("terminal_id");

        $refundTerminal = [];
        $cancelTicket->map(function($single) use (&$refundTerminal){
            
            $refundAmount = 0;
            $refundSeats = [];
            $single->map(function($ticket) use (&$refundAmount,&$refundSeats){
            
                if($ticket->cancel_ticket)
                {
                    $refundAmount += (($ticket->seat_fare - $ticket->discount) / 100) * $ticket->cancel_ticket->percentage;
                    $refundSeats[] = $ticket->seat_no;
                }
            });
            $singleTerminal = [];
            $singleTerminal["terminal"] = $single[0]->terminal->name;
            $singleTerminal["amount"] = $refundAmount;
            $singleTerminal["seats"] = implode(",",$refundSeats);

            $refundTerminal[] = $singleTerminal;
        });
        // $refundAmount = 0;
        // $cancelTicket->map(function($single) use (&$refundAmount){
        //     if($single->cancel_ticket)
        //     {
        //         $refundAmount += (($single->seat_fare - $single->discount) / 100) * $single->cancel_ticket->percentage;
        //     }
        // });
        
        $busData = TicketClosing::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])
            ->with("bus:id,bus_number", "members:id,user_id,ticket_closing_id,type", "members.driver_name:id,name,contact", "members.host_name:id,name,contact")
            ->first(["id", "bus_id"]);

        $infoData->bus_data = $busData;

       
        $format = TicketsTemplate::with("terminal")
        ->join("ticket_template_terminals","ticket_template_terminals.ticket_template_id","tickets_templates.id")
        ->whereNull('tickets_templates.deleted_at')
        ->whereNull('ticket_template_terminals.deleted_at')
        ->where(['tickets_templates.company_id'=> Auth::user()->company_id,"ticket_template_terminals.terminal_id"=>Auth::user()->terminal_id])->where('tickets_templates.status', 1)
        ->first();;
        return view('pdf/PrintBusInvoice', ["infoData" => $infoData, "mainData" => $mainData,"refundTerminal" => $refundTerminal,"format"=>$format]);
    }

    public
    function ticketPdf(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        if ((int)$request->duplicate == 0) {
            $ids = explode("-", $request->ticket_ids);
        } else {
            $ids = [$request->ticket_id];
        }
        // return $ids;
       $tickets = Ticket::with('customer', 'schedule', 'seatClass', 'destination_city', 'departure_city','terminal')->where('company_id', Auth::user()->company_id)->withTrashed()->whereIn('id', $ids)->get();
        $tickets->map(function ($item) {
          

            $item->acutal_time = $item->date . " " . $item->schedule_time; //if ticket booked from another terminal

            $sub = 0;
            $terminalTime = TerminalTimeDifference::where(['company_id' => $item->company_id, 'terminal_id' => $item->terminal_id, 'route_id' => $item->route_id])->first();
            if($terminalTime)
            {
                $sub = $terminalTime->time_difference * 60;
            }

            $item->acutal_time = date("Y-m-d H:i:00", strtotime($item->date . " " . $item->schedule_time) + $sub);
        });
        $format = TicketsTemplate::with("terminal")
                ->join("ticket_template_terminals","ticket_template_terminals.ticket_template_id","tickets_templates.id")
                ->whereNull('tickets_templates.deleted_at')
                ->whereNull('ticket_template_terminals.deleted_at')
                ->where(['tickets_templates.company_id'=> Auth::user()->company_id,"ticket_template_terminals.terminal_id"=>Auth::user()->terminal_id])->where('tickets_templates.status', 1)
                ->first();
        $finalData = [
            'tickets' => $tickets,
            'format' => $format,
            'duplicate' => (int)$request->duplicate,
        ];
        
        return view('pdf/pdf', ['data' => $finalData]);
    }

    public
    function eltPdf(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $ticketsElt = TicketELT::with('schedule', 'customer', 'ticket.seatClass:id,name', 'destination', 'departure')->where(['company_id' => Auth::user()->company_id, 'id' => $request->elt_ids])->first();
        $format = TicketsTemplate::with("terminal")
        ->join("ticket_template_terminals","ticket_template_terminals.ticket_template_id","tickets_templates.id")
        ->whereNull('tickets_templates.deleted_at')
        ->whereNull('ticket_template_terminals.deleted_at')
        ->where(['tickets_templates.company_id'=> Auth::user()->company_id,"ticket_template_terminals.terminal_id"=>Auth::user()->terminal_id])->where('tickets_templates.status', 1)
        ->first();
        $finalData = [
            'elt' => $ticketsElt,
            'format' => $format,
        ];
        return view('pdf/eltPdf', ['data' => $finalData]);
    }

    // public
    // function getPassengersList(Request $request)
    // {
    //     if(!checkPermissionButtons("pax-list"))
    //     {
    //         return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    //     }
    //     $customers_id = Ticket::where([
    //         'company_id' => Auth::user()->company_id,
    //         'schedule_id' => $request->schedule_id,
    //         'departure_city_id' => $request->departure_city_id,
    //         'destination_city_id' => $request->destination_city_id,
    //         'date' => $request->date,
    //     ])->pluck('customer_id')->toArray();
    //     return implode('-', array_unique($customers_id));
    // }

    public
    function passengerListPdf(Request $request)
    {
        if(!checkPermissionButtons("pax-list"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
            'departure_time' =>  date("H:i:s",strtotime($request->departure_time)),
        ])->first()->schedule_date;
        $scheduleTime = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->first()->departure_time;
        $passengerData = Ticket::with('customer', 'schedule', 'schedule.bus_class', 'terminal:id,name,city_id', 'terminal.city:id,name', 'destination_city', 'departure_city')->where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->whereIn('type', ['booked', 'advanced booking', 'reschedule'])->get();
        $terminalGroup = Ticket::with('terminal:id,name,city_id', 'terminal.city:id,name')->where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
            'type' => 'booked',
        ])->groupBy('terminal_id')->selectRaw('terminal_id,count(*) as terminalPassengerCount')->get();
        $departureGroup = Ticket::with('departure_city')->where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
            'type' => 'booked',
        ])->groupBy('departure_city_id')->selectRaw('departure_city_id,count(*) as departurePassengerCount')->get();
        $destinationGroup = Ticket::with('destination_city')->where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
            'type' => 'booked',
        ])->groupBy('destination_city_id')->selectRaw('destination_city_id,count(*) as destinationPassengerCount')->get();
        $format = TicketsTemplate::with("terminal")
        ->join("ticket_template_terminals","ticket_template_terminals.ticket_template_id","tickets_templates.id")
        ->whereNull('tickets_templates.deleted_at')
        ->whereNull('ticket_template_terminals.deleted_at')
        ->where(['tickets_templates.company_id'=> Auth::user()->company_id,"ticket_template_terminals.terminal_id"=>Auth::user()->terminal_id])->where('tickets_templates.status', 1)
        ->first();
        $countPassenger = count($passengerData);
        $actualDeparture = date('m/d/Y h:i A', strtotime($uniqueDate . ' ' . $scheduleTime));
        $driverInfo = getMembers($passengerData->first(), Auth::user()->company_id, 1) ?? [];
        $hostInfo = getMembers($passengerData->first(), Auth::user()->company_id, 2) ?? [];
        $scheduleName = Schedule::where('id', $request->schedule_id)->first()->name;
        $busNo = Schedule::with('bus_class:id,name')->where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first('bus_class_id');
        $remainData = ['passengerCount' => $countPassenger, 'actualDepart' => $actualDeparture, 'driverInfo' => $driverInfo, 'hostInfo' => $hostInfo, 'scheduleName' => $scheduleName, 'busNo' => $busNo];
        $passengerData = $passengerData->sortBy(function ($item) {
            // Extract the integer part of the 'age' values using a regular expression
            preg_match('/\d+/', $item['seat_no'], $matches);
            
            // Use the extracted integer part for sorting
            return $matches[0] ?? 0;
        });
        return view('pdf/passengerList', ['data' => $passengerData, 'format' => $format, 'terminalData' => $terminalGroup, 'departureData' => $departureGroup, 'destinationData' => $destinationGroup, 'remain' => $remainData]);
    }

    public
    function fetchScheduleSurchargeDiscount(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Schedule::with(['surcharge' => function ($q) {
            $q->where('is_active', 1);
        }])->with(['discount' => function ($q) {
            $q->where('is_active', 1);
        }])->where(['id' => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first(['id', 'discount_id', 'surcharge_id']);
    }

    public
    function fetchTerminalDiscount(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $schedule = Schedule::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();
        return $terminalDiscount = TerminalDiscount::where(["terminal_id" => $request->dropTerminal ?? 0, "route_id" => $schedule->route_id])->first();
    }

    public
    function fetchOverIssueSeat(Request $request)
    {
        if(!checkForSubmenu("bookings"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $uniqueDate = ScheduleDetail::where("departure_id", $request->departureCity)
            ->where("destination_id", $request->destinationCity)
            ->where('schedule_id', $request->id)
            ->where('departure_date', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->first();
        $tickets = Ticket::withTrashed()->with('overIssueSeats','overIssueSeats.overissue_by', 'scheduleDetail', 'schedule', 'customer', 'company', 'destination_city', 'departure_city', 'busClass')->where(["schedule_date" => $uniqueDate->schedule_date, "schedule_id" => $uniqueDate->schedule_id, 'type' => 'over-issue'])->get();
        foreach ($tickets as $key => $single) {
            $single->bookingDate = date('d/m/Y H:i A', strtotime($single->booked_time));
            $single->OverIssueDate = date('d/m/Y H:i A', strtotime($single->overIssueSeats->time));
            $single->overIssueBy = $single->overIssueSeats->overissue_by->name??"N/A";
        }
        return $tickets;
    }

    public
    function revertOverIssueSeat(Request $request)
    {
        if(!checkPermissionButtons("overissue-seat"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $ticket = Ticket::where([
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $request->schedule_date,
            'seat_no' => $request->seat_no,
        ])->first();
        if (!$ticket) {
            Ticket::withTrashed()->where('id', $request->ticket_id)->update([
                'type' => 'booked',
                'deleted_at' => null,
            ]);
            return TicketsOverIssue::where('ticket_id', $request->ticket_id)->delete();
        } else {
            return response()->json(["errors" => ["Revert Error" => ["This seat has been booked by Someone Else! \n You can't Revert This Seat"]]], 422);
        }
    }

    public
    function getFetchOldELT(Request $request)
    {
        if(!checkPermissionButtons("add-elt"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $foundELT = TicketELT::where([
            'date' => $request->date,
            'customer_id' => $request->customer_id,
            'seat_no' => $request->seat_no,
            'schedule_id' => $request->schedule_id,
            'departure_city' => $request->departure_city_id,
            'destination_city' => $request->destination_city_id,
            'company_id' => Auth::user()->company_id,
            'ticket_id' => $request->id,
        ])->first();
        $foundELT->alreadyExist = $foundELT ? 1 : 0;
        if ($foundELT) {
            return $foundELT;
        } else {
            return response()->json([], 204);
        }
    }

}
