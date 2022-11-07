<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\City;
use App\Models\Customer;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function index()
    {
        $bookings = Ticket::select('schedule_id','date')->with('schedule:id,name')->whereDate('date', date("Y-m-d"))
        ->where('company_id', $this->company_id)->get()->groupBy(['date','schedule_id']);
        $allBooking = [];
        foreach ($bookings as $i => $singleBooking) {
            $bookingWithDetails = $singleBooking->map(function($booking) use ($i){
                $booking[0]->count=$booking->count();
                $booking[0]->date = $i;
                return $booking[0];
            });
            $allBooking[]=$bookingWithDetails->first();
        }
        return $allBooking;
    }

    public function store(Request $request)
    {

        $schedule = Schedule::where('id', $request->schedule)
        ->where('company_id',$this->company_id)
        ->select('id', 'fare_class_id','route_id','bus_class_id')
        ->with('bus_class:id,seat_map','route:id,name','route.fares:id,route_id,departure_city_id,destination_city_id')->first();

        $departure_city_id = $schedule->route->fares->first()->departure_city_id;
        $destination_city_id = $schedule->route->fares->last()->destination_city_id;
        $isPartial = 0;
        if ( $request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id ) {
            $isPartial=1;
        }
        // $schedule = Schedule::where('id',$request->schedule)
        // ->select('id','fare_class_id','company_id')->with('bus_class')
        // ->first();
        $cnicFormat = str_replace('-', '', $request->customerCNIC);
        $phoneFormat = str_replace('-', '', $request->contact);

        $customer = Customer::where('cnic',$cnicFormat)->first();

        // Fare Fetching About the Schedule


        if (!$customer) {
            $customer = Customer::create([
                'company_id'=>$this->company_id,
                'added_by'=>Auth::user()->id,
                'name'=>$request->customerName,
                'cnic'=>$cnicFormat,
                'contact'=>$phoneFormat,
            ]);
        }


        // Getting Already Booked Tickets


        $bookingNo = Ticket::latest()->first()->booking_no ?? 0;
        ++$bookingNo;

        foreach ($request->selectedSeats as $i => $seat) {

            Ticket::create([
                'company_id'=>$this->company_id,
                'departure_city_id'=>$request->departureCity,
                'destination_city_id'=>$request->destinationCity,
                // 'bus_class_id'=>$schedule->fare_class_id,
                'seat_no'=>$seat,
                'is_partial'=>$isPartial,
                'booking_no'=>$bookingNo,
                'date'=>$request->date,
                'customer_id'=>$customer->id,
                'schedule_id'=>$schedule->id,
                'remarks'=>$request->remarks,
                'gender'=>$request->gender,
                'type'=>$request->type,
                'added_by'=>Auth::user()->id,
                'discount'=>$request->discount,
            ]);

        }

        return "Successfully Boooking Created";

    }

    public function reschedule( Request $request ){

        $request->bookingSeats = collect($request->bookingSeats);
        foreach ($request->bookingSeats as $i => $bookedSeat) {
            Ticket::where( 'id',$bookedSeat['id'] )->update([
                'date'=>$request->date,
                'schedule_id'=>$request->schedule,
                'seat_no'=>$request->selectedSeats[$i],
            ]);
        }
        return response()->json("Seats Rescheduled Successfully",200);

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
        $routes = RouteFare::where('departure_city_id', $request->departure_city_id )->where('destination_city_id', $request->destination_city_id)->get();
        $routes_id = [];
        foreach ($routes as $key => $route){
            $routes_id[] = $route->route_id;
        }

       return Schedule::whereIn('route_id',array_unique($routes_id))
       ->whereDate('start_date', '<=', $request->date)
       ->whereDate('end_date', '>=',$request->date)
       ->get();
    }
    public function fetchSpecificDestination(Request $request)
    {
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id',$this->company_id)->pluck('destination_city_id')->toArray();
        return City::whereIn('id', array_unique($depart_city))->where('company_id', $this->company_id)->get(['id', 'name']);
    }

    public function getCnic(Request  $request)
    {
        $cnicFormat = str_replace('-', '', $request['cnicNumber']);
        return Customer::where('company_id', $this->company_id)->where('cnic',  $cnicFormat)->first();
    }
    public function detailTicket(Request $request)
    {
        $bookings = Ticket::with('addedBy','customer')->where('company_id', $this->company_id)
        ->whereDate('date',$request->date)
        ->where('schedule_id',$request->schedule_id)
        ->get();
        return $bookings;
//        $allBooking = $bookings->map(function($booking){
//            $booking[0]->count=$booking->count();
//            return $booking[0];
//        });
//        return $allBooking;
    }
}
