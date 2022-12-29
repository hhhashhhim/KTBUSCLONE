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
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
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
        $closings = TicketClosing::
            where('company_id', $this->company_id)
            ->with("bus:id,bus_number","schedule:id,name")
            ->get()
            ->groupBy('ticket_merge_id');
        $data = [
            "buses" => $buses,
            "hosts" => $hosts,
            "drivers" => $drivers,
            "closings" => $closings,
        ];
        return $data;
    }

    public function fetchSchedule(Request $request)
    {
        return Schedule::
            where('start_date','<=', $request->date)
            ->where('end_date','>=', $request->date)
            ->where('company_id', $this->company_id)
            ->with(["scheduleDetail"=>function($q) use ($request){
                return $q->where("schedule_date",$request->date);
            }])
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
        $depTime = ScheduleDetail::
        where(["schedule_id"=>$request->schedule,
                "departure_id"=>$departure->departure_city_id,
                "destination_id"=>$departure->destination_city_id,
                "departure_date"=>$request->date,
                "company_id"=>$this->company_id
                ])
        ->first();

        $checkMergeRecord = TicketClosingMerge::
        where(["company_id"=>$this->company_id,"bus_id"=>$request->bus,"schedule_complete"=>0])
        ->latest("id")->first();

        if($checkMergeRecord)
        {
            TicketClosingMerge::where("id",$checkMergeRecord->id)->update([
                "schedule_return_date" => $request->date,
                "schedule_complete" => 1,
            ]);
        }
        else
        {
            $newRecord = TicketClosingMerge::create([
                "bus_id" => $request->bus,
                "schedule_departure_date" => $request->date,
                "schedule_complete" => 0,
                'company_id' => $this->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }


        $closingRecord = TicketClosing::create([
            "bus_id" => $request->bus,
            "ticket_merge_id" => $checkMergeRecord ? $checkMergeRecord->id : $newRecord->id,
            "schedule_id" => $request->schedule,
            "schedule_date" => $request->date,
            "schedule_time" => $depTime->departure_time,
            "schedule_start" => $departure->departure_city_id,
            "schedule_end" => $destination->destination_city_id,
            "schedule_return" => $checkMergeRecord ? 1 : 0,
            "description" => $request->description,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);

        // for driver
        foreach($request->drivers as $value)
        {
            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 1,
                "ticket_closing_id" => $closingRecord->id,
                "bus_id" => $request->bus,
                'company_id' => $this->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }
        // for host
        foreach($request->hosts as $value)
        {
            TicketClosingMember::create([
                "user_id" => $value,
                "type" => 2,
                "ticket_closing_id" => $closingRecord->id,
                "bus_id" => $request->bus,
                'company_id' => $this->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        Ticket::where(["schedule_id"=>$request->schedule,"schedule_date"=>$request->date])->update([
            "bus_id" => $request->bus,
            "ticket_closing_id" => $closingRecord->id
        ]);
        return  $closingRecord;
    }

}
