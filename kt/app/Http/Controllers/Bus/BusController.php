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
        return Schedule::orderBy('id')->select('departure_date', 'destination_date', 'destination_time', 'departure_time', 'trip_duration', 'added_by', 'updated_by', 'created_at')->get();
    }

    public function storeBus(Request $request)
    {
        dd($request->all());
        $rules = [
            'dept_date_time' => 'required',
            'trip_duration' => 'required',
        ];

        $customMessages = [
            'dept_date_time.required' => 'Departure Date and Time  is Required!',
            'trip_duration.required' => 'Trip Duration is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        $date_arr = explode("T", $request->dept_date_time);
        $trip_arr = explode(":", $request->trip_duration);
        $trp = isset($trip_arr[0]) ? Carbon::parse($request->dept_date_time)->addHour($trip_arr[0])->format('Y-m-d') : $date_arr[0];
        dd(isset($trip_arr[0]), isset($trip_arr[1]));
        return Bus::create([
            'departure_date' => $date_arr[0],
            'departure_time' => $date_arr[1],
            'destination_date' => $trp,
            'destination_time' => isset($trip_arr[1]) ? Carbon::parse($request->dept_date_time)->addMinute($trip_arr[1])->format('H:i:s') : $date_arr[1],
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
