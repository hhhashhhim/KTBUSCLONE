<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Hrm\Employee\Employee;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMemeber;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleClosingController extends Controller
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
        $buses = Bus::where('company_id', $this->company_id)->orderBy('id')->get();
        $hosts = Employee::where('company_id', $this->company_id)->orderBy('id')->get(["user_id","name","cnic"]);
        $drivers = Employee::where('company_id', $this->company_id)->orderBy('id')->get(["id","user_id","name","cnic"]);
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
        ];
        return $data;
    }
    
    public function fetchSchedule(Request $request)
    {
        return Schedule::
            where('start_date','<=', $request->date)
            ->where('end_date','>=', $request->date)
            ->where('company_id', $this->company_id)
            // ->with(["scheduleDetail"=>function($q) use ($request){
            //     return $q->where("departure_date",$request->date);
            // }])
            ->orderBy('id')
            ->get(["id","name"]);
    }
    
    public function store(Request $request)
    {
        // this is for get route id that will be followed by schedule
        $route = Schedule::find($request->schedule)->route_id;
        // this is for get schedule start city
        $departure = RouteFare::where("route_id",$route)->orderBy('id','ASC')->first(); 
        // this is for get schedule end city
        $destination = RouteFare::where("route_id",$route)->orderBy('id','DESC')->first();
        // this is for get schedule departure time
        return $depTime = ScheduleDetail::
        where(["schedule_id"=>$request->schedule,"departure_id"=>$departure->departure_city_id,
                "destination_id"=>$departure->destination_city_id,
                "departure_date"=>$request->date
                ])
        ->first();

        TicketClosing::create([
            "bus_id" => $request->bus,
            "schedule_id" => $request->schedule,
            "schedule_date" => $request->date,
            "schedule_time" => $depTime->departure_time,
            "schedule_start" => $departure->departure_city_id,
            "schedule_end" => $destination->destination_city_id,
            "description" => $request->description,
            "schedule_type" => 0 // Not Returned 
        ]);
    }

}
