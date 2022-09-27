<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Terminal;
use Carbon\Carbon;
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
        return Schedule::create([
            'departure_date' => $date_arr[0],
            'departure_time' => $date_arr[1],
            'destination_date' => $trp,
            'destination_time' => isset($trip_arr[1]) ? Carbon::parse($request->dept_date_time)->addMinute($trip_arr[1])->format('H:i:s') : $date_arr[1],
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

    public function getRoutes()
    {
        return Route::get();
    }

    public function getCity(Request $request)
    {
        $routeFares = RouteFare::where('route_id', $request->id)->select('departure_city_id', 'destination_city_id')->get();
        $data = [];
        foreach ($routeFares as $i => $routeFare) {
            if ($i == 0) {
                $data[] = $routeFare->departure_city_id;
            }
            $data[] = $routeFare->destination_city_id;
        }
        $data = collect($data)->unique();
        return City::whereIn('id', $data)->get();
    }

    public function getTerminal(Request $request)
    {
        return Terminal::where('city_id', $request->id)->select('id', 'name')->get();
    }
}
