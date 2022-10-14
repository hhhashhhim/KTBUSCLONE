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
        return Booking::orderBy('id')->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request)
    {
        $schedule = Schedule::where('id',$request->schedule)
        ->select('id','bus_id','company_id')->with('single_bus')
        ->first();

        $customer = Customer::where('cnic',$request->customerCNIC)->first();
        if (!$customer) {
            $customer = Customer::create([
                'name'=>$request->customerName,
                'cnic'=>$request->customerCNIC,
                'contact'=>$request->contact,
            ]);
        }
        $bookingNo = Ticket::latest()->first()->booking_no ?? 0;
        ++$bookingNo;
        foreach ($request->selectedSeats as $i => $seat) {
            Ticket::create([
                'company_id'=>$schedule->company_id,
                'bus_id'=>$schedule->bus_id,
                'seat_no'=>$seat,
                'booking_no'=>$bookingNo,
                'customer_id'=>$customer->id,
                'schedule_id'=>$schedule->id,
                'remarks'=>$request->remarks,
                'for_female'=>$request->gender,
                'type'=>$request->type,
                'discount'=>$request->discount,
            ]);
        }
        return "Successfully Boooking Created";

    }

    public function updateBooking(Request $request)
    {
        $rules = [
            'bus_number' => 'required',
            'fare_class_id' => 'required|integer',
            'chassis_number' => 'required',
            'insurance_number' => 'required',
            'no_of_seats' => 'required',
            'route_permit_number' => 'required',
            'no_of_rows' => 'required|integer',
        ];

        $customMessages = [
            'bus_number.required' => 'Bus Number is Required!',
            'fare_class_id.required' => 'Fare Class is Required!',
            'chassis_number.required' => 'Chassis Number is Required!',
            'insurance_number.required' => 'Insurance Number is Required!',
            'no_of_seats.required' => 'Number Of Seats is Required!',
            'route_permit_number.required' => 'Route Permit is Required!',
            'no_of_rows.required' => 'No of Rows of Bus  is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return Booking::where('id', $request->id)->update([
            'bus_number' => $request->bus_number,
            'chassis_number' => $request->chassis_number,
            'insurance_number' => $request->insurance_number,
            'no_of_seats' => $request->no_of_seats,
            'route_permit_number' => $request->route_permit_number,
            'fare_class_id' => $request->fare_class_id,
            'seat_map' => $request->seat_map,
            'no_of_rows' => $request->no_of_rows,
            'company_id' => $this->company_id,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteBooking(Request $request)
    {
        return Booking::find($request->id)->delete();
    }
    public function getBookingData(Request $request)
    {
        return Booking::where('id',$request->id)->where('company_id', $this->company_id)->get();
    }
}


// $schedule = Schedule::where('id',$request->schedule)
// ->select('id','bus_id')->with('single_bus')
// ->first();
// $seatMap = collect($schedule->single_bus->seat_map);
// $subCat=[];
// for($i=0;$i<count($seatMap);$i++) {
//     $seatMap[$i] = collect($seatMap[$i]);
//     $results = $seatMap[$i]->whereIn('seatNo',$request->selectedSeats)->pluck('seatNo');
//     if ($results->count()>0) {
//         $subCat = array_merge( $subCat,$results->toArray() );
//     }
// }
// return $subCat;
