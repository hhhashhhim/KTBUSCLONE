<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusSeatMap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusController extends Controller
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
        return Bus::orderBy('id')->where('company_id', $this->company_id)->get();
    }

    public function storeBus(Request $request)
    {
        $rules = [
            'busNumber' => 'required',
            'fare_class' => 'required|integer',
            'chassisNumber' => 'required',
            'insuranceNumber' => 'required',
            'noOfSeats' => 'required',
            'routePermit' => 'required',
            'noOfRows' => 'required|integer',
            'noOfCols' => 'required|integer',
        ];

        $customMessages = [
            'busNumber.required' => 'Bus Number is Required!',
            'fare_class.required' => 'Fare Class is Required!',
            'chassisNumber.required' => 'Chassis Number is Required!',
            'insuranceNumber.required' => 'Insurance Number is Required!',
            'noOfSeats.required' => 'Number Of Seats is Required!',
            'routePermit.required' => 'Route Permit is Required!',
            'noOfRows.required' => 'No of Rows of Bus  is Required!',
            'noOfCols.required' => 'No of Cols of Bus  is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        $bus =  Bus::create([
            'bus_number' => $request->busNumber,
            'chassis_number' => $request->chassisNumber,
            'insurance_number' => $request->insuranceNumber,
            'no_of_seats' => $request->noOfSeats,
            'route_permit_number' => $request->routePermit,
            'fare_class_id' => $request->fare_class,
            'seat_map' => $request->seatMap,
            'no_of_rows' => $request->noOfRows,
            'no_of_cols' => $request->noOfCols,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
        return BusSeatMap::create([
            'bus_id' => $bus->id,
            'seat_map' => $request->seatMap,
            'no_of_rows' => $request->noOfRows,
            'no_of_cols' => $request->noOfCols,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateBus(Request $request)
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
        return Bus::where('id', $request->id)->update([
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

    public function deleteBus(Request $request)
    {
        return Bus::find($request->id)->delete();
    }
    public function getBusData(Request $request)
    {
        return Bus::where('id',$request->id)->where('company_id', $this->company_id)->first();
    }
}
