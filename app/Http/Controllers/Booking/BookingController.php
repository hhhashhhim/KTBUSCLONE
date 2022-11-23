<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\City;
use App\Models\Customer;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Models\TicketsOverIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $bookings = Ticket::select('schedule_id', 'date')->with('schedule:id,name')->whereDate('date', isset($request->date) ? $request->date : date("Y-m-d"))
            ->where('company_id', $this->company_id)->get()->groupBy(['date', 'schedule_id']);
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

        $schedule = Schedule::where('id', $request->schedule)
            ->where('company_id', $this->company_id)
            ->select('id', 'fare_class_id', 'route_id', 'bus_class_id')
            ->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();

        $departure_city_id = $schedule->route->fares->first()->departure_city_id;
        $destination_city_id = $schedule->route->fares->last()->destination_city_id;
        $isPartial = 0;
        if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
            $isPartial = 1;
        }
        // $schedule = Schedule::where('id',$request->schedule)
        // ->select('id','fare_class_id','company_id')->with('bus_class')
        // ->first();
        $cnicFormat = str_replace('-', '', $request->customerCNIC);
        $phoneFormat = str_replace('-', '', $request->contact);

        $customer = Customer::where('cnic', $cnicFormat)->first();

        // Fare Fetching About the Schedule


        if (!$customer) {
            $customer = Customer::create([
                'company_id' => $this->company_id,
                'added_by' => Auth::user()->id,
                'name' => $request->customerName,
                'cnic' => $cnicFormat,
                'contact' => $phoneFormat,
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

        foreach ($request->selectedSeats as $i => $seat) {

            $ticket = Ticket::create([
                'company_id' => $this->company_id,
                'departure_city_id' => $request->departureCity,
                'destination_city_id' => $request->destinationCity,
                // 'bus_class_id'=>$schedule->fare_class_id,
                'seat_no' => $seat,
                'is_partial' => $isPartial,
                'booking_no' => $bookingNo,
                'date' => $request->date,
                'customer_id' => $customer->id,
                'schedule_id' => $schedule->id,
                'remarks' => $request->remarks,
                'gender' => $request->gender,
                'type' => $request->type,
                'added_by' => Auth::user()->id,
                'discount' => $request->discount,
            ]);

        }

        dd('done');

    }




    public function reschedule(Request $request)
    {

        $request->bookingSeats = collect($request->bookingSeats);
        foreach ($request->bookingSeats as $i => $bookedSeat) {
            Ticket::where('id', $bookedSeat['id'])->update([
                'date' => $request->date,
                'schedule_id' => $request->schedule,
                'seat_no' => $request->selectedSeats[$i],
            ]);
        }
        return response()->json("Seats Rescheduled Successfully", 200);

    }

    public function deleteBooking(Request $request)
    {
        return Ticket::find($request->id)->delete();
    }

    public function fetchSpecificSchedule(Request $request)
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

    public function fetchSpecificDestination(Request $request)
    {
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id', $this->company_id)->pluck('destination_city_id')->toArray();
        return City::whereIn('id', array_unique($depart_city))->where('company_id', $this->company_id)->get(['id', 'name']);
    }

    public function fetchSpecificOverIssueSeat(Request $request)
    {
        $ticket = Ticket::where(["company_id" => $this->company_id, "date" => $request->date, "seat_no" => $request->seat_no, "schedule_id" => $request->schedule_id, "destination_city_id" => $request->destinationCity, "departure_city_id" => $request->departureCity])->select('booking_no', 'customer_id', 'gender', 'is_partial', 'type', 'remarks')->first();
        $customer = Customer::where(["company_id" => $this->company_id, "id" => $ticket->customer_id])->first();
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
        $schedule = Schedule::where('id', $request->schedule_id)
            ->where('company_id', $this->company_id)
            ->select('id', 'fare_class_id', 'route_id', 'bus_class_id')
            ->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();

        $departure_city_id = $schedule->route->fares->first()->departure_city_id;
        $destination_city_id = $schedule->route->fares->last()->destination_city_id;
        $isPartial = 0;
        if ($request->departure_city != $departure_city_id || $request->destination_city != $destination_city_id) {
            $isPartial = 1;
        }

        $oldBooking = Ticket::where('company_id', $this->company_id)->where('date', $request->date)->where('schedule_id', $request->schedule_id)->where('departure_city_id', $request->departure_city)->where('destination_city_id', $request->destination_city)->first();
        $cnicFormat = strpos($request->cnic, '-') ? str_replace('-', '', $request->cnic) : $request->cnic;
        $phoneFormat = strpos($request->contact, '-') ? str_replace('-', '', $request->contact) : $request->contact;
        $old_customer = Customer::where('cnic', $cnicFormat)->first();
        if ($old_customer->id == $oldBooking->customer_id) {
            return response()->json([
                "errors" => [
                    "message" => ["This Seat already booked against this customer"]
                ]
//                'message' => ["This Seat already booked against this customer"],
            ], 422);
        } else {
            if (!$old_customer) {
                $new_customer = Customer::create([
                    'company_id' => $this->company_id,
                    'added_by' => Auth::user()->id,
                    'name' => $request->name,
                    'cnic' => $cnicFormat,
                    'contact' => $phoneFormat,
                ]);
            }

            if ($request->date == date('Y-m-d')) {
                $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            } else {
                $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            }

//        foreach ($request->selectedSeats as $i => $seat) {

            $new_ticket = Ticket::create([
                'company_id' => $this->company_id,
                'departure_city_id' => $oldBooking->departure_city_id,
                'destination_city_id' => $oldBooking->destination_city_id,
                // 'bus_class_id'=>$schedule->fare_class_id,
                'seat_no' => $oldBooking->seat_no,
                'is_partial' => $isPartial,
                'booking_no' => $bookingNo,
                'date' => $request->date,
                'customer_id' => isset($old_customer) ? $old_customer->id : $new_customer->id,
                'schedule_id' => !isset($request->schedule_id) ? $oldBooking->schedule_id : $request->schedule_id,
                'remarks' => $request->remarks,
                'gender' => $request->gender,
                'type' => $oldBooking->type,
                'added_by' => Auth::user()->id,
                'discount' => $oldBooking->discount,
            ]);

//        }

//        Log for over Issue
            return TicketsOverIssue::create([
                'company_id' => $this->company_id,
                'old_customer_id' => $oldBooking->customer_id,
                'new_customer_id' => isset($new_customer) ? $new_customer->id : null,
                'schedule_id' => !isset($request->schedule_id) ? $oldBooking->schedule_id : $request->schedule_id,
                'seat_no' => !isset($request->seat_no) ? $oldBooking->seat_no : $request->seat_no,
                'old_booking_no' => $oldBooking->id,
                'new_booking_no' => $new_ticket->id,
                'added_by' => Auth::user()->id,
            ]);
        }
    }

    public function getCnic(Request $request)
    {
        if ($request->status == 'addFormCNIC') {
            $cnicFormat = str_replace('-', '', $request['cnicNumber']);
            return Customer::where('company_id', $this->company_id)->where('cnic', $cnicFormat)->first();
        }
        if ($request->status == 'addFormContact') {
            $phoneFormat = str_replace('-', '', $request['phoneNumber']);
            return Customer::where('company_id', $this->company_id)->where('contact', $phoneFormat)->first();
        }
        if ($request->status == 'overIssueCNIC') {
            $cnicFormat = str_replace('-', '', $request['cnicNumber']);
            return Customer::where('company_id', $this->company_id)->where('cnic', $cnicFormat)->first();
        }
        if ($request->status == 'overIssueContact') {
            $phoneFormat = str_replace('-', '', $request['phoneNumber']);
            return Customer::where('company_id', $this->company_id)->where('contact', $phoneFormat)->first();
        }
    }

    public function detailTicket(Request $request)
    {
        return Ticket::with('addedBy', 'customer')->where('company_id', $this->company_id)
            ->whereDate('date', $request->date)
            ->where('schedule_id', $request->schedule_id)
            ->get();
//        $allBooking = $bookings->map(function($booking){
//            $booking[0]->count=$booking->count();
//            return $booking[0];
//        });
//        return $allBooking;
    }




}
