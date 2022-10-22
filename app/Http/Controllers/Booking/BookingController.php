<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Customer;
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
        $bookings = Ticket::select('schedule_id','date')->with('schedule:id,name')
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
        $schedule = Schedule::where('id',$request->schedule)
        ->select('id','selected_bus_class_id','company_id')->with('bus_class')
        ->first();
        $cnicFormat = str_replace('-', '', $request->customerCNIC);
        $customer = Customer::where('cnic',$cnicFormat)->first();
        if (!$customer) {
            $customer = Customer::create([
                'company_id'=>$this->company_id,
                'added_by'=>Auth::user()->id,
                'name'=>$request->customerName,
                'cnic'=>$cnicFormat,
                'contact'=>$request->contact,
            ]);
        }
        $bookingNo = Ticket::latest()->first()->booking_no ?? 0;
        ++$bookingNo;
        foreach ($request->selectedSeats as $i => $seat) {
            Ticket::create([
                'company_id'=>$schedule->company_id,
                // 'bus_class_id'=>$schedule->selected_bus_class_id,
                'seat_no'=>$seat,
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
        ->get()->groupBy('booking_no');
        $allBooking = $bookings->map(function($booking){
            $booking[0]->count=$booking->count();
            return $booking[0];
        });
        return $allBooking;
    }
}
