<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusController extends Controller
{
    public function index()
    {
        return Bus::orderBy('id')->select('bus_number', 'chassis_number', 'insurance_number', 'no_of_seats', 'route_permit_number', 'added_by', 'updated_by', 'created_at')->get();
    }

    public function storeBus(Request $request)
    {
        dd($request->all());
        $rules = [
            'busNumber' => 'required',
            'fare_class' => 'required|integer',
            'chassisNumber' => 'required',
            'insuranceNumber' => 'required',
            'noOfSeats' => 'required',
            'routePermit' => 'required',
            'noOfRows' => 'required|integer|min:0',
        ];

        $customMessages = [
            'busNumber.required' => 'Bus Number is Required!',
            'fare_class.required' => 'Fare Class is Required!',
            'chassisNumber.required' => 'Chassis Number is Required!',
            'insuranceNumber.required' => 'Insurance Number is Required!',
            'noOfSeats.required' => 'Number Of Seats is Required!',
            'routePermit.required' => 'Route Permit is Required!',
            'noOfRows.required' => 'No of Rows of Bus  is Required!',
            'noOfRows.min' => 'Default Value is 0 ',
        ];
        $this->validate($request, $rules, $customMessages);
        return Bus::create([
            'bus_number' => $request->busNumber,
            'chassis_number' => $request->chassisNumber,
            'insurance_number' => $request->insuranceNumber,
            'no_of_seats' => $request->noOfSeats,
            'route_permit_number' => $request->routePermit,
            'fare_class_id' => $request->fare_class,
            'seat_map' => null,
            'no_of_rows' => $request->noOfRows,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateBus(Request $request)
    {
        $rules = [
            'dept_date' => 'required|date',
            'dest_date' => 'required|date',
            'trip_duration' => 'required|date_format:H:i',
            'dept_time' => 'required|date_format:H:i',
        ];

        $customMessages = [
            'dept_date.required' => 'From Date is Required!',
            'dest_date.required' => 'To Date is Required!',
            'dept_time.required' => 'Departure Time is Required',
            'trip_duration.required' => 'Trip Duration is Required',
        ];
        $this->validate($request, $rules, $customMessages);
        return Bus::where('id', $request->id)->update([
            'departure_date' => $request->dept_date,
            'departure_time' => $request->dept_time,
            'destination_date' => $request->dest_date,
            'destination_time' => $request->trip_duration,
            'trip_duration' => $request->trip_duration,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteBus(Request $request)
    {
        return Bus::find($request->id)->delete();
    }
}
