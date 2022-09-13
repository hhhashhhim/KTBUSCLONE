<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Schedule\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        return Schedule::orderBy('id')->select('departure_date', 'destination_date', 'destination_time', 'departure_time', 'trip_duration', 'added_by', 'updated_by', 'created_at')->get();
    }

    public function storeSchedule(Request $request)
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
        return Schedule::create([
            'departure_date' => $request->dept_date,
            'departure_time' => $request->dept_time,
            'destination_date' => $request->dest_date,
            'destination_time' => $request->trip_duration,
            'trip_duration' => $request->trip_duration,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateSchedule(Request $request)
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
        return Schedule::where('id', $request->id)->update([
            'departure_date' => $request->dept_date,
            'departure_time' => $request->dept_time,
            'destination_date' => $request->dest_date,
            'destination_time' => $request->trip_duration,
            'trip_duration' => $request->trip_duration,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSchedule(Request $request)
    {
        return Schedule::find($request->id)->delete();
    }
}
