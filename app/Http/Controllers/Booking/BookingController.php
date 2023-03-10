<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\RescheduleExtraAmount;
use App\Models\Booking\TicketAdvancedBooked;
use App\Models\Booking\TicketELT;
use App\Models\Booking\TicketIsPartial;
use App\Models\Booking\TicketReschedule;
use App\Models\Booking\TicketsOverIssue;
use App\Models\Bus\Bus;
use App\Models\City;
use App\Models\Schedule\ScheduleTerminalSequence;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\TerminalCommission;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use App\Models\Schedule\DropSchedule;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\TicketClosing;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Hrm\Employee\Employee;
use App\Models\Terminal;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Rawilk\Printing\Facades\Printing;

class BookingController extends Controller
{
    public function index(Request $request)
    {
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

        if (Auth::user()->departure_city_ids == "all") {
            $ids = City::where("company_id", Auth::user()->company_id)->pluck('id');
        } else {
            $ids = json_decode(Auth::user()->departure_city_ids);
        }

        return City::with('addedBy')->where('company_id', Auth::user()->company_id)->whereIn('id', $ids)->get();
    }

    public function store(Request $request)
    {
        //            dd($request->all());
        if ($request->terminalId == 0 && is_null(Auth::user()->terminal_id)) {
            return response()->json(["errors" => ["Booking Error" => ["If You Are Company Admin Please Assign Terminal To Your Account  For Booking the Ticket, If You Are Employee Of Company Please Contact Your Administrator Or IT Team! "]]], 422);
        }
        //        try {
        //            DB::beginTransaction();

        // this is for get actual schedule date
        $detail = ScheduleDetail::where("departure_id", $request->departureCity)
            ->where("destination_id", $request->destinationCity)
            ->where('schedule_id', $request->schedule)
            ->where('departure_date', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->first();
        $existingTicket = Ticket::where(['company_id' => Auth::user()->company_id, 'schedule_date' => $detail->schedule_date, 'schedule_id' => $request->schedule])->latest()->first(['bus_id', 'ticket_closing_id']);
        $allTicket = [];
        if (isset($request->flag) && $request->flag == 1) {
            $allTicket[] = updateAdvancedSeat($request, Auth::user()->company_id);
        } else {
            if (count($request->selectedSeats) == 0) {
                return response()->json(["errors" => ["Error" => ["One of Your Selected Seat is Already Booked ! Please Select Any other seat / combination"]]], 422);
            }
            //            dd($oldBooking, $request->all());
            $schedule = Schedule::where('id', $request->schedule)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
            $departure_city_id = $schedule->route->fares->first()->departure_city_id;
            $destination_city_id = $schedule->route->fares->last()->destination_city_id;
            $isPartial = 0;
            if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
                $isPartial = 1;
            }
            if ($request->customerCNIC && $request->type == 'booked') {
                $customer = Customer::where('cnic', plainContactAndCnic($request->customerCNIC))->first();
            } else {
                $customer = false;
            }

            // Fare Fetching About the Schedule
            if (!$customer) {
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
            $scheduleDetail = ScheduleDetail::where([
                'company_id' => Auth::user()->company_id,
                'departure_date' => $request->date,
                'departure_id' => $request->departureCity,
                'destination_id' => $request->destinationCity,
                'schedule_id' => $schedule->id,
            ])->first();
            $allTicket = [];
            foreach ($request->selectedSeats as $i => $seat) {
                $ticket = Ticket::create([
                    'company_id' => Auth::user()->company_id,
                    'departure_city_id' => $request->departureCity,
                    'destination_city_id' => $request->destinationCity,
                    'seat_no' => $seat,
                    'bus_class_id' => $request->selectedSeatsClass[$i],
                    'seat_fare' => $request->selectedSeatsFare[$i],
                    'is_partial' => $isPartial,
                    'booking_no' => $bookingNo,
                    'schedule_date' => $detail->schedule_date,
                    'date' => $request->date,
                    'customer_id' => $customer->id,
                    'schedule_id' => $schedule->id,
                    'ticket_closing_id' => $existingTicket ? $existingTicket->ticket_closing_id : null,
                    'bus_id' => $existingTicket ? $existingTicket->bus_id : null,
                    'schedule_details_id' => $scheduleDetail->id,
                    'terminal_id' => $request->terminalId ?? Auth::user()->terminal_id,
                    'remarks' => $request->remarks,
                    'gender' => $request->gender,
                    'type' => $request->type,
                    'added_by' => Auth::user()->id,
                    'discount' => $request->discount ?? 0,
                ]);
                if ($isPartial == 1) {
                    TicketIsPartial::create([
                        'company_id' => Auth::user()->company_id,
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

                if ($request->type == 'advance booking') {
                    TicketAdvancedBooked::create([
                        'company_id' => Auth::user()->company_id,
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
        }
        return [
            'ids' => implode('-', $allTicket),
            'ticket' => Ticket::where('company_id', Auth::user()->company_id)->whereIn('id', $allTicket)->get(),
            'authTerminalId' => Auth::user()->terminal_id,
        ];
        //        } catch (\Exception $e) {
        //            DB::rollBack();
        //            return response()->json(["errors" => ["Error" => ["OOPS!! Something Went Wrong Please Try Again"]]], 422);
        ////            return response()->json(["errors" => ["Error" => [$e->getMessage()]]], 422);
        //        }
    }

    public function singleReschedule(Request $request)
    {
        foreach ($request->data as $key => $item) {
            $ticket = $item['dataAll'];
            if ($item['existingDate'] == $item['rescheduleDate']) {
                $bookingNo = Ticket::where('date', $item['existingDate'])->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            } else {
                $bookingNo = Ticket::where('date', $item['rescheduleDate'])->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            }
            $scheduleDetail = ScheduleDetail::where([
                'company_id' => Auth::user()->company_id,
                'departure_date' => $item['rescheduleDate'],
                'departure_id' => $item['dataDepartureCity'],
                'destination_id' => $item['dataDestination'],
                'schedule_id' => $item['dataSchedule'],
            ])->first();
            $schedule = Schedule::where('id', $ticket['schedule_id'])->where('company_id', Auth::user()->company_id)->select('id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
            $departure_city_id = $schedule->route->fares->first()->departure_city_id;
            $destination_city_id = $schedule->route->fares->last()->destination_city_id;
            $isPartial = 0;
            if ($item['dataDepartureCity'] != $departure_city_id || $item['dataDestination'] != $destination_city_id) {
                $isPartial = 1;
            }

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
                'ticket_closing_id' => $ticket['ticket_closing_id'],
                'bus_id' => $ticket['bus_id'],
                'bus_class_id' => $ticket['bus_class_id'],
                'seat_fare' => $ticket['seat_fare'],
                'is_partial' => $isPartial,
                'booking_no' => $bookingNo,
                'schedule_date' => $scheduleDetail->schedule_date,
                'date' => $item['rescheduleDate'],
                'schedule_details_id' => $scheduleDetail->id,
                'customer_id' => $item['dataCustomer'],
                'schedule_id' => $item['newDepartureTime'],
                'remarks' => $ticket['remarks'],
                'gender' => $ticket['gender'],
                'type' => $item['rescheduleType'],
                'reschedule_type' => $item['overIssueReschedule'],
                'added_by' => Auth::user()->id,
                'discount' => $item['rescheduleDiscount'] ?? 0,
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
            $old_ticket->update([
                'type' => 'reschedule'
            ]);
            $old_ticket->delete();
        }
        return response()->json(['success' => 'Success'], 200);
    }

    public function deleteBooking(Request $request)
    {
        return Ticket::find($request->id)->delete();
    }

    public
    function fetchSpecificSchedule(Request $request)
    {
        if (!$request->date) {
            return "Date is Required";
        }
        $allSchedules = ScheduleDetail::with('schedule')->where(['departure_id' => $request->departure_city_id, 'destination_id' => $request->destination_city_id, 'departure_date' => $request->date])->get();
        $allSchedules->map(function ($single) {
            $single->departure_date = date("m/d/Y", strtotime($single->departure_date));
            $single->departure_time = date("h:i A", strtotime($single->departure_time));
        });
        return $allSchedules;
    }

    public
    function fetchSpecificDestination(Request $request)
    {
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id', Auth::user()->company_id)->pluck('destination_city_id')->toArray();
        if (Auth::user()->destination_city_ids == "all") {
            $finalArray = $depart_city;
        } else {
            $ids = json_decode(Auth::user()->destination_city_ids);
            $finalArray = array_intersect(array_unique($depart_city), $ids);
        }
        return City::whereIn('id', $finalArray)->where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function fetchSpecificOverIssueSeat(Request $request)
    {
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

    public
    function overIssueAddNew(Request $request)
    {
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
        return $ticket;
    }

    public function getCnic(Request $request)
    {
        if ($request->status == 'addFormCNIC') {
            return Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request['cnicNumber']))->first();
        }
        if ($request->status == 'addFormContact') {
            return Customer::where('company_id', Auth::user()->company_id)->where('contact', plainContactAndCnic($request['phoneNumber']))->first();
        }
    }

    public function getTerminals()
    {
        return [
            'terminals' => Terminal::with('city')->where('company_id', Auth::user()->company_id)->get(),
            'authTerminalId' => Auth::user()->terminal_id ?? 0,
        ];
    }

    public function detailTicket(Request $request)
    {
        return Ticket::with('addedBy', 'customer')->where('company_id', Auth::user()->company_id)
            ->whereDate('date', $request->date)
            ->where('schedule_id', $request->schedule_id)
            ->get();
    }

    public function advanceData(Request $request)
    {
        //        dd($request->all());
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
        ])->first(['schedule_date']);
        $tickets = Ticket::with('scheduleDetail', 'schedule', 'customer', 'company', 'destination_city', 'departure_city', 'seatClass')->where('company_id', Auth::user()->company_id)->whereIn('seat_no', $request->seatNO)->where('schedule_id', $request->scheduleId)->where('schedule_date', $uniqueDate->schedule_date)->get()->groupBy('seat_no');
        $checkCustomers = [];
        foreach ($tickets as $key => $single) {
            foreach ($single as $key => $item) {
                $checkCustomers[] = $item->customer_id;
                $item->bookingDate = date('d/m/Y H:i A', strtotime($item->created_at));
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
    public function getFareClass()
    {
        return FareClass::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }
    public function dropCheck(Request $request)
    {
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->id,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
        ])->first()->schedule_date;
        $found = DropSchedule::where([
            'company_id' => Auth::user()->company_id,
            'schedule_date' => $uniqueDate,
            'schedule_id' => $request->id,
            'is_drop' => 1,
        ])->first();

        return $found;
    }

    public function selected(Request $request)
    {
        if (!$request->departureCity || !$request->destinationCity || !$request->date) {
            echo "Error";
            return [];
        }
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->id,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
        ])->first(['schedule_date']);
        // Getting Already Booked Tickets
        $tickets = Ticket::with('departure_city', 'destination_city', 'schedule', 'customer', 'company', 'addedBy')
            ->where('company_id', Auth::user()->company_id)->where('schedule_id', $request->id)
            ->whereDate('schedule_date', $uniqueDate->schedule_date)->get();
        $ticketSeatNumbers = $tickets->pluck('seat_no')->toArray();
        $schedule = Schedule::where('id', $request->id)
            ->where('company_id', Auth::user()->company_id)
            ->select('id', 'route_id', 'bus_class_id', 'time')
            ->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')
            ->first();

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
        // Looping Through the seat of the bus
        $seatMap = $schedule->bus_class->seat_map;
        foreach ($seatMap as $i => $iValue) {
            foreach ($iValue as $j => $column) {
                // adding fare to each seat
                if ($column['reserved']) {
                    $data = $fareForAllClasses->where('fare_class', $column['class'])->first();
                    $seatMap[$i][$j]['fare'] = (int)$data->fare;
                }
                $result = isset($column['seatNo']) ? array_search($column['seatNo'], $ticketSeatNumbers) : false;
                if ($result !== false) {   /*&& $leavingIn30Min != true*/
                    $seatMap[$i][$j]['id'] = $tickets[$result]['id'];
                    $seatMap[$i][$j]['gender'] = $tickets[$result]['gender'];
                    $seatMap[$i][$j]['partial'] = $tickets[$result]['is_partial'];
                    $seatMap[$i][$j]['type'] = $tickets[$result]['type'];
                    $seatMap[$i][$j]['remarks'] = $tickets[$result]['remarks'] == null ? 'N/A' : $tickets[$result]['remarks'];
                    $seatMap[$i][$j]['customer_cnic'] = $tickets[$result]['customer']['cnic'];
                    $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                    $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                    $seatMap[$i][$j]['booked_by'] = $tickets[$result]['addedBy']['name'];
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
                            $seatMap[$i][$j]['remarks'] = $tickets[$singlePartial]['remarks'] == null ? 'N/A' : $tickets[$singlePartial]['remarks'];
                            $seatMap[$i][$j]['customer_cnic'] = $tickets[$singlePartial]['customer']['cnic'];
                            $seatMap[$i][$j]['customer_name'] = $tickets[$singlePartial]['customer']['name'];
                            $seatMap[$i][$j]['customer_phone'] = $tickets[$singlePartial]['customer']['contact'];
                            $seatMap[$i][$j]['booked_by'] = $tickets[$singlePartial]['addedBy']['name'];
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
                //                if ($result !== false && $leavingIn30Min) {
                //                    $seatMap[$i][$j]['over_issue'] = true;
                //                    $seatMap[$i][$j]['departure_city'] = $tickets[$result]['departure_city']->id;
                //                    $seatMap[$i][$j]['destination_city'] = $tickets[$result]['destination_city']->id;
                //                    $seatMap[$i][$j]['customer_cnic'] = $tickets[$result]['customer']['cnic'];
                //                    $seatMap[$i][$j]['remarks'] = $tickets[$result]['remarks'] == null ? 'N/A' : $tickets[$result]['remarks'];
                //                    $seatMap[$i][$j]['customer_name'] = $tickets[$result]['customer']['name'];
                //                    $seatMap[$i][$j]['customer_phone'] = $tickets[$result]['customer']['contact'];
                //                    $seatMap[$i][$j]['booked_by'] = $tickets[$result]['addedBy']['name'];
                //                    $seatMap[$i][$j]['departure_city_name'] = $tickets[$result]['departure_city']['name'];
                //                    $seatMap[$i][$j]['destination_city_name'] = $tickets[$result]['destination_city']['name'];
                //                    $seatMap[$i][$j]['class_name'] = $fareClasses->where('id', $column['class'])->first()->name;
                //                }
                //                 print_r($column);
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
                        $seatMap[$i][$j]['fare'] = (int)$fareForAllClasses->where('fare_class', $class->id)->first()->fare;
                    }
                }
            }
        }
        $schedule->bus_class->seat_map = $seatMap;
        unset($schedule->route);
        return $schedule;
    }

    public function dropSchedule(Request $request)
    {
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
        ])->first()->schedule_date;

        $tickets = Ticket::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->first();
        $old = DropSchedule::where([
            'company_id' => Auth::user()->company_id,
            'date' => $request->date,
            'schedule_date' => $uniqueDate,
            'schedule_id' => $request->schedule_id,
        ])->first();

        if (isset($tickets) && $tickets->ticket_closing_id != null) {
            TicketClosingMember::where([
                'company_id' => Auth::user()->company_id,
                'ticket_closing_id' => $tickets->ticket_closing_id,
            ])->delete();

            $mergeId = TicketClosing::where([
                'company_id' => Auth::user()->company_id,
                'id' => $tickets->ticket_closing_id,
            ])->first()->ticket_merge_id;

            TicketClosing::where([
                'company_id' => Auth::user()->company_id,
                'id' => $tickets->ticket_closing_id,
            ])->delete();


            $mergeRecord = TicketClosingMerge::find($mergeId);
            if ($mergeRecord->schedule_complete == 1) {
                TicketClosingMerge::where([
                    'company_id' => Auth::user()->company_id,
                    'id' => $mergeId,
                ])->update([
                    "schedule_complete" => 0,
                    "schedule_return_date" => null,
                ]);
            } else {
                $mergeRecord->delete();
            }
        }
        if (!$old) {
            DropSchedule::create([
                'company_id' => Auth::user()->company_id,
                'terminal_id' => Auth::user()->terminal_id,
                'date' => $request->date,
                'schedule_date' => $uniqueDate,
                'schedule_id' => $request->schedule_id,
                'added_by' => Auth::user()->id,
                'is_drop' => 1,
                'reason' => $request->reason,
            ]);
            return response()->json(['success' => 'Success'], 200);
        }
        return response()->json(["errors" => ["Error" => ["This Schedule is already Closed"]]], 422);
    }

    public function getClosingData(Request $request)
    {
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
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
        $infoData->schedule_date = $schedule->schedule_date;
        $infoData->schedule_id = $schedule->schedule_id;
        $infoData->route_name = $schedule->schedule->route->name;
        $infoData->description = $checkAssign ? $checkAssign->description : '';
        $infoData->bus = $checkAssign ? $checkAssign->bus_id : '';
        $infoData->drivers = $checkAssign ? $checkAssign->members->where("type", 1)->pluck('user_id') : [];
        $infoData->hosts = $checkAssign ? $checkAssign->members->where("type", 2)->pluck('user_id') : [];

        $buses = Bus::where('company_id', Auth::user()->company_id)->orderBy('id')->get();
        $hosts = Employee::where(['employee_type' => 2, 'company_id' => Auth::user()->company_id])->orderBy('id')->get(["user_id", "name", "cnic"]);
        $drivers = Employee::where(['employee_type' => 1, 'company_id' => Auth::user()->company_id])->orderBy('id')->get(["id", "user_id", "name", "cnic"]);
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "infoData" => $infoData,
        ];
        return $data;
    }

    public function bookingElt(Request $request)
    {
        if (is_null(Auth::user()->terminal_id)) {
            return response()->json(["errors" => ["Booking Error" => ["Some Error Occur, Please Refresh The page, If Error Still Occurs Please Contact to Your IT-Team"]]], 422);
        }
        //        try {
        //            DB::beginTransaction();
        $ticket = Ticket::where([
            'company_id' => Auth::user()->company_id,
            'date' => $request->date,
            'schedule_id' => $request->schedule_id,
            'customer_id' => $request->customer_id,
            'departure_city_id' => $request->departure_id,
            'destination_city_id' => $request->destination_id,
            'seat_no' => $request->seat_no,
        ])->first(['id']);
        $old = TicketELT::where([
            'company_id' => Auth::user()->company_id,
            'ticket_id' => $ticket->id,
            'customer_id' => $request->customer_id,
            'schedule_id' => $request->schedule_id,
            'date' => $request->date,
        ])->first();

        if (!$old) {
            return TicketELT::create([
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
        }
        return response()->json(["errors" => ["Error" => ["Elt Already Exist Against This Seat! Please Select any Other Seat"]]], 422);

        //            printEltTicket($elt->id, Auth::user()->company_id);
        //        } catch (\Exception $e) {
        //            DB::rollBack();
        //            return response()->json(["errors" => ["Booking Error" => [$e->getMessage()]]], 422);
        //        }
    }

    public
    function cancelingBooking(Request $request)
    {
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
            'type' => 'canceled',
        ]);
        BookingCancel::create([
            'company_id' => Auth::user()->company_id,
            'ticket_id' => $ticket->id,
            'percentage' => $request->percentage,
            'reason' => $request->remarks,
            'added_by' => Auth::user()->id,
        ]);
        return $ticket->delete();
    }

    public function terminalInvoice(Request $request)
    {

        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
        ])->first()->schedule_date;
        $scheduleTime = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->first()->departure_time;

        $passengerData = Ticket::with('customer:id,name,cnic,contact', 'addedBy:id,name', 'terminal:id,name', 'elt:id,elt_price,ticket_id', 'destination_city:id,name', 'departure_city:id,name')->where([
            'company_id' => Auth::user()->company_id,
            'terminal_id' => Auth::user()->terminal_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])->get();

        $routeId = Schedule::where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first()->route_id;
        $commission = TerminalCommission::where(["company_id" => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id, "route_id" => $routeId])->first();

        $driverInfo = getMembers($passengerData->first(), Auth::user()->company_id, 1) ?? [];
        $hostInfo = getMembers($passengerData->first(), Auth::user()->company_id, 2) ?? [];
        $routeName = routeName($request->schedule_id);
        $routeId = Schedule::where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first()->route_id;
        $busNo = Schedule::with('bus_class:id,name')->where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first('bus_class_id');
        $date = date_format(date_create($uniqueDate . ' ' . $scheduleTime), "l") . ' , ' . date_format(date_create($uniqueDate . ' ' . $scheduleTime), "d F Y H:i:s A");
        $eltAmount = 0;
        foreach ($passengerData as $passenger) {
            $eltAmount += $passenger->elt != null ? $passenger->elt->elt_price : 0;
        }
        $passengerData = ['record' => $passengerData, 'driverInfo' => $driverInfo, 'hostInfo' => $hostInfo, 'routeName' => $routeName, 'busNo' => $busNo, 'date' => $date, 'terminalGross' => $passengerData->sum('seat_fare'), 'totalElt' => $eltAmount, 'commission' => $commission];
        $format = TicketsTemplate::with('terminal')->where('company_id', Auth::user()->company_id)->orWhere('terminal_id', Auth::user()->terminal_id)->where('status', 1)->first();

        return view('pdf/TerminalPaxDetails', ['data' => $passengerData, 'format' => $format]);
    }

    public function busInvoice(Request $request)
    {
        // dd($request->all());
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
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

        $mainData = Ticket::where([
            'tickets.company_id' => Auth::user()->company_id,
            'tickets.schedule_id' => $request->schedule_id,
            'tickets.schedule_date' => $uniqueDate,
        ])
            ->with("terminal:id,name", "destination_city:id,name")
            ->with(["commission" => function ($q) use ($route) {
                return $q->where("route_id", $route->id);
            }])
            ->leftJoin("ticket_e_l_t_s", "ticket_e_l_t_s.ticket_id", "tickets.id") //this for if elt exist show else null
            ->select("tickets.*", "ticket_e_l_t_s.elt_price")
            ->get()->groupBy(["terminal_id", "destination_city_id"]);

        $busData = TicketClosing::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])
            ->with("bus:id,bus_number", "members:id,user_id,ticket_closing_id,type", "members.driver_name:id,name,contact", "members.host_name:id,name,contact")
            ->first(["id", "bus_id"]);

        $infoData->bus_data = $busData;
        // return $mainData;
        return view('pdf/PrintBusInvoice', ["infoData" => $infoData, "mainData" => $mainData]);
    }

    public function ticketPdf(Request $request)
    {
        if ((int)$request->duplicate == 0) {
            $ids = explode("-", $request->ticket_ids);
        } else {
            $ids = [$request->ticket_id];
        }
        $tickets = Ticket::with('customer', 'scheduleDetail:id,departure_time', 'schedule', 'seatClass', 'destination_city', 'departure_city')->where('company_id', Auth::user()->company_id)->whereIn('id', $ids)->get();
        $tickets->map(function ($item) {
            $checkTerminal = ScheduleTerminalSequence::where(['company_id' => $item->company_id, 'city_id' => $item->departure_city_id, 'schedule_id' => $item->schedule_id])->orderBy('id', 'DESC')->get();
            $subTime = 0; // how many times difference will affect to departure time according to terminal time difference
            //            Check departure city have more than one terminal

            $item->acutal_time = $item->date . " " . $item->scheduleDetail->departure_time; //if ticket booked from another terminal

            $ticketTerminal = Terminal::find($item->terminal_id);
            // dd($ticketTerminal);
            if ($ticketTerminal->city_id == $item->departure_city_id) {
                if ($checkTerminal->count() > 0) {
                    //              if ticket terminal id at last of sequence it mean no need to calculation
                    if ($checkTerminal->first()->terminal_id != $item->terminal_id) {
                        //                    dd($checkTerminal);
                        foreach ($checkTerminal as $key => $single) {
                            if ($item->terminal_id == $single->terminal_id) {
                                break;
                            } else {
                                $terminalTime = TerminalTimeDifference::where(['company_id' => $item->company_id, 'terminal_from_id' => $single->terminal_id, 'terminal_to_id' => $checkTerminal[$key + 1]->terminal_id])->first();
                                if ($terminalTime) {
                                    $time = explode(":", $terminalTime->time_difference);
                                    $subTime += ($time[0] * 60 * 60) + ($time[1] * 60);
                                }
                            }
                        }
                    }
                }
                $item->acutal_time = date("Y-m-d H:i:00", strtotime($item->date . " " . $item->scheduleDetail->departure_time) - $subTime);
            }
        });
        $format = TicketsTemplate::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id])->first();
        $finalData = [
            'tickets' => $tickets,
            'format' => $format,
            'duplicate' => (int)$request->duplicate,
        ];
        return view('pdf/pdf', ['data' => $finalData]);
    }

    public function eltPdf(Request $request)
    {
        $ticketsElt = TicketELT::with('schedule', 'customer', 'ticket.seatClass:id,name', 'destination', 'departure')->where(['company_id' => Auth::user()->company_id, 'id' => $request->elt_ids])->first();
        $format = TicketsTemplate::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id])->first();
        $finalData = [
            'elt' => $ticketsElt,
            'format' => $format,
        ];
        return view('pdf/eltPdf', ['data' => $finalData]);
    }

    public function getPassengersList(Request $request)
    {
        $customers_id = Ticket::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_city_id' => $request->departure_city_id,
            'destination_city_id' => $request->destination_city_id,
            'date' => $request->date,
        ])->pluck('customer_id')->toArray();
        return implode('-', array_unique($customers_id));
    }

    public function passengerListPdf(Request $request)
    {
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_date' => $request->date,
            'departure_id' => $request->departure_city_id,
            'destination_id' => $request->destination_city_id,
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
        $format = TicketsTemplate::where('company_id', Auth::user()->company_id)->orWhere('terminal_id', Auth::user()->terminal_id)->where('status', 1)->first();
        $countPassenger = count($passengerData);
        $actualDeparture = date('m/d/Y h:i A', strtotime($uniqueDate . ' ' . $scheduleTime));
        $driverInfo = getMembers($passengerData->first(), Auth::user()->company_id, 1) ?? [];
        $hostInfo = getMembers($passengerData->first(), Auth::user()->company_id, 2) ?? [];
        $scheduleName = Schedule::where('id', $request->schedule_id)->first()->name;
        $busNo = Schedule::with('bus_class:id,name')->where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first('bus_class_id');
        $remainData = ['passengerCount' => $countPassenger, 'actualDepart' => $actualDeparture, 'driverInfo' => $driverInfo, 'hostInfo' => $hostInfo, 'scheduleName' => $scheduleName, 'busNo' => $busNo];
        return view('pdf/passengerList', ['data' => $passengerData, 'format' => $format, 'terminalData' => $terminalGroup, 'departureData' => $departureGroup, 'destinationData' => $destinationGroup, 'remain' => $remainData]);
    }
}




// try{
//     DB::transaction(function() use ($request){
//      //your query
//      });
// } 
// catch (\Exception $e){
//     return 'Opps! Some thing went wrong';  
//     // return "error------->".$e->getMessage();
// }