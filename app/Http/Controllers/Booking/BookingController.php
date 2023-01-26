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
use App\Models\Customer;
use App\Models\Route\RouteFare;
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

    public function store(Request $request)
    {
        if (is_null(Auth::user()->terminal_id)) {
            return response()->json(["errors" => ["Booking Error" => ["If You Are Company Admin Please Assign Terminal To Your Account  For Booking the Ticket, If You Are Employee Of Company Please Contact Your Administrator Or IT Team! "]]], 422);
        }
        if (count($request->selectedSeats) == 0) {
            return response()->json(["errors" => ["Error" => ["One of Your Selected Seat is Already Booked ! Please Refresh the page"]]], 422);
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
                    'terminal_id' => Auth::user()->terminal_id,
                    'remarks' => $request->remarks,
                    'gender' => $request->gender,
                    'type' => $request->type,
                    'added_by' => Auth::user()->id,
                    'discount' => $request->discount,
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
//            if($request->type == 'booked') {
//                $printers = Printing::printers();
//                Session::put('printerId', $printers->first()->id());
//                printTicket($allTicket, Auth::user()->company_id);
//            }
        }
        return [
            'ids' => implode('-', $allTicket),
            'ticket' => Ticket::where('company_id', Auth::user()->company_id)->whereIn('id', $allTicket)->get(),
        ];
//        } catch (\Exception $e) {
//            DB::rollBack();
//            return response()->json(["errors" => ["Error" => ["OOPS!! Something Went Wrong Please Try Again"]]], 422);
////            return response()->json(["errors" => ["Error" => [$e->getMessage()]]], 422);
//        }
    }

    public function reschedule(Request $request)
    {
        $ticket = $request->dataAll;
        if ($request->existingDate == $request->rescheduleDate) {
            $bookingNo = Ticket::where('date', $request->existingDate)->latest()->first()->booking_no ?? 0;
            ++$bookingNo;
        } else {
            $bookingNo = Ticket::where('date', $request->rescheduleDate)->latest()->first()->booking_no ?? 0;
            ++$bookingNo;
        }
        $scheduleDetail = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'departure_date' => $ticket['date'],
            'departure_id' => $ticket['departure_city_id'],
            'destination_id' => $ticket['destination_city_id'],
            'schedule_id' => $ticket['schedule_id'],
        ])->first();
        $currentTicketData =  Ticket::where('company_id', Auth::user()->company_id)->where('schedule_id', $ticket['schedule_id'])
        ->whereDate('schedule_date', $scheduleDetail->schedule_date)->first ();

        $schedule = Schedule::where('id', $ticket['schedule_id'])->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id')->first();
        $departure_city_id = $schedule->route->fares->first()->departure_city_id;
        $destination_city_id = $schedule->route->fares->last()->destination_city_id;
        $isPartial = 0;
        if ($ticket['departure_city_id'] != $departure_city_id || $ticket['destination_city_id'] != $destination_city_id) {
            $isPartial = 1;
        }

        if ($request->selected_seatFare != $request->dataAll['seat_fare']) {
            RescheduleExtraAmount::create([
                'company_id' => Auth::user()->company_id,
                'old_ticket_id' => $request->dataAll['id'],
                'old_seat_no' => $ticket['seat_no'],
                'new_seat_no' => $request->selected_seatNo,
                'old_seat_class' => $ticket['bus_class_id'],
                'new_seat_class' => $request->selected_seatClass,
                'old_seat_fare' => $ticket['seat_fare'],
                'new_seat_fare' => $request->selected_seatFare,
                'type' => priceDiff($ticket['seat_fare'], $request->selected_seatFare)['type'],
                'diff_amount' => priceDiff($ticket['seat_fare'], $request->selected_seatFare)['diff'],
                'old_departure_city_id' => $ticket['departure_city_id'],
                'new_departure_city_id' => $request->dataDepartureCity,
                'old_destination_city_id' => $ticket['destination_city_id'],
                'new_destination_city_id' => $request->rescheduleDestinationCity,
                'old_schedule_id' => $ticket['schedule_id'],
                'new_schedule_id' => $request->rescheduleSchedule,
                'old_booking_date' => $ticket['date'],
                'new_booking_date' => $request->rescheduleDate,
            ]);
        }
        TicketReschedule::create([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $ticket['schedule_id'],
            'reSchedule_id' => $request->rescheduleSchedule,
            'customer_id' => $ticket['customer_id'],
            'date' => $ticket['date'],
            'reschedule_date' => $request->rescheduleDate,
            'departure_city_id' => $ticket['departure_city_id'],
            'reschedule_departure_city_id' => $request->dataDepartureCity,
            'reschedule_destination_city_id' => $request->rescheduleDestinationCity,
            'destination_city_id' => $ticket['destination_city_id'],
            'reason' => $request->reason,
            'added_by' => Auth::user()->id,
        ]);
        Ticket::create([
            'company_id' => Auth::user()->company_id,
            'departure_city_id' => $request->dataDepartureCity,
            'destination_city_id' => $request->rescheduleDestinationCity,
            'seat_no' => $ticket['seat_no'],
            'terminal_id' => $ticket['terminal_id'],
            'ticket_closing_id' => $currentTicketData->ticket_closing_id,
            'bus_id' => $currentTicketData->bus_id,
            'bus_class_id' => $ticket['bus_class_id'],
            'seat_fare' => $ticket['seat_fare'],
            'is_partial' => $isPartial,
            'booking_no' => $bookingNo,
            'schedule_date' => $scheduleDetail->schedule_date,
            'date' => $request->rescheduleDate,
            'schedule_details_id' => $scheduleDetail->id,
            'customer_id' => $request->dataCustomer,
            'schedule_id' => $request->rescheduleSchedule,
            'remarks' => $ticket['remarks'],
            'gender' => $ticket['gender'],
            'type' => $ticket['type'],
            'added_by' => Auth::user()->id,
            'discount' => $ticket['discount'],
        ]);
        $old_ticket = Ticket::where('company_id', Auth::user()->company_id)->where('id', $ticket['id'])->first();
        $old_ticket->update([
            'type' => 'reschedule'
        ]);
        return $old_ticket->delete();
    }

    public
    function deleteBooking(Request $request)
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
        foreach ($allSchedules as $key => $single) {
            $single->departure_date = date("m/d/Y", strtotime($single->departure_date));
            $single->departure_time = date("h:i A", strtotime($single->departure_time));
        }
        return $allSchedules;

    }

    public
    function fetchSpecificDestination(Request $request)
    {
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id', Auth::user()->company_id)->pluck('destination_city_id')->toArray();
        return City::whereIn('id', array_unique($depart_city))->where('company_id', Auth::user()->company_id)->get(['id', 'name']);
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
        return $ticket->delete();
    }

    public
    function getCnic(Request $request)
    {
        if ($request->status == 'addFormCNIC') {
            return Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request['cnicNumber']))->first();
        }
        if ($request->status == 'addFormContact') {
            return Customer::where('company_id', Auth::user()->company_id)->where('contact', plainContactAndCnic($request['phoneNumber']))->first();
        }
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
        $uniqueDate = ScheduleDetail::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->scheduleId,
            'departure_date' => $request->date,
            'departure_id' => $request->departureCity,
            'destination_id' => $request->destinationCity,
        ])->first(['schedule_date']);
        return Ticket::with('scheduleDetail', 'schedule', 'customer', 'company', 'destination_city', 'departure_city', 'seatClass')->where('company_id', Auth::user()->company_id)->whereIn('seat_no', $request->seatNO)->where('schedule_id', $request->scheduleId)->where('schedule_date', $uniqueDate->schedule_date)->get()->groupBy('seat_no');
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
        $infoData->schedule = date("m/d/Y h:i A",strtotime("$schedule->schedule_date $schedule->departure_time")).' - '.$schedule->schedule->name;
        $infoData->schedule_date = $schedule->schedule_date;
        $infoData->schedule_id = $schedule->schedule_id;
        $infoData->route_name = $schedule->schedule->route->name;
        $infoData->description = $checkAssign ? $checkAssign->description : '';
        $infoData->bus = $checkAssign ? $checkAssign->bus_id : '';
        $infoData->drivers = $checkAssign ? $checkAssign->members->where("type",1)->pluck('user_id') : [];
        $infoData->hosts = $checkAssign ? $checkAssign->members->where("type",2)->pluck('user_id') : [];

        $buses = Bus::where('company_id', Auth::user()->company_id)->orderBy('id')->get();
        $hosts = Employee::where(['employee_type'=>2,'company_id'=>Auth::user()->company_id])->orderBy('id')->get(["user_id", "name", "cnic"]);
        $drivers = Employee::where(['employee_type'=>1,'company_id'=>Auth::user()->company_id])->orderBy('id')->get(["id", "user_id", "name", "cnic"]);
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

        $driverInfo = getMembers($passengerData->first(), Auth::user()->company_id, 1) ?? [];
        $hostInfo = getMembers($passengerData->first(), Auth::user()->company_id, 2) ?? [];
        $routeName = routeName($request->schedule_id);
        $busNo = Schedule::with('bus_class:id,name')->where(["id" => $request->schedule_id, 'company_id' => Auth::user()->company_id])->first('bus_class_id');
        $date = date_format(date_create($uniqueDate . ' ' . $scheduleTime), "l") . ' , ' . date_format(date_create($uniqueDate . ' ' . $scheduleTime), "d F Y H:i:s A");
        $eltAmount = 0;
        foreach ($passengerData as $passenger) {
            $eltAmount += $passenger->elt != null ? $passenger->elt->elt_price : 0;
        }
        $passengerData = ['record' => $passengerData, 'driverInfo' => $driverInfo, 'hostInfo' => $hostInfo, 'routeName' => $routeName, 'busNo' => $busNo, 'date' => $date, 'terminalGross' => $passengerData->sum('seat_fare'), 'totalElt' => $eltAmount];
        $format = TicketsTemplate::with('terminal')->where('company_id', Auth::user()->company_id)->orWhere('terminal_id', Auth::user()->terminal_id)->where('status', 1)->first();

        return view('pdf/terminalPaxDetails', ['data' => $passengerData, 'format' => $format]);
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
        ])->first()->name;

        $mainData = Ticket::where([
            'tickets.company_id' => Auth::user()->company_id,
            'tickets.schedule_id' => $request->schedule_id,
            'tickets.schedule_date' => $uniqueDate,
        ])
            ->with("terminal:id,name", "destination_city:id,name")
            ->leftJoin("ticket_e_l_t_s", "ticket_e_l_t_s.ticket_id", "tickets.id") //this for if elt exist show else null
            ->select("tickets.*", "ticket_e_l_t_s.elt_price")
            ->get()->groupBy(["terminal_id", "destination_city_id"]);

        $busData = TicketClosing::where([
            'company_id' => Auth::user()->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $uniqueDate,
        ])
            ->with("bus:id,bus_number", "members:id,user_id,ticket_closing_id,type", "members.member_name:id,name,contact")
            ->first(["id", "bus_id"]);

        $infoData->bus_data = $busData;

        return view('pdf/PrintBusInvoice', ["infoData" => $infoData, "mainData" => $mainData]);
    }
    public function ticketPdf(Request $request)
    {
        if((int)$request->duplicate == 0){
            $ids = explode("-",$request->ticket_ids);
        }else{
            $ids = [$request->ticket_id];
        }
        $tickets = Ticket::with('customer', 'schedule', 'seatClass', 'destination_city', 'departure_city')->where('company_id', Auth::user()->company_id)->whereIn('id', $ids)->get();
        $format = TicketsTemplate::where(['company_id'=> Auth::user()->company_id, 'terminal_id'=> Auth::user()->terminal_id])->first();
        $finalData = [
            'tickets' => $tickets,
            'format' => $format,
            'duplicate' => (int) $request->duplicate,
        ];
        return view('pdf/pdf', ['data' => $finalData]);
    }
    public function eltPdf(Request $request)
    {
//        dd($request->elt_ids);
        $ticketsElt = TicketELT::with('schedule', 'customer', 'ticket.seatClass:id,name', 'destination', 'departure')->where(['company_id'=> Auth::user()->company_id,'id' => $request->elt_ids])->first();
        $format = TicketsTemplate::where(['company_id'=> Auth::user()->company_id, 'terminal_id'=> Auth::user()->terminal_id])->first();
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
