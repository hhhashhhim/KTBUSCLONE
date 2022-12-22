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
            ->orderBy('id')
            ->get(["id","name"]);
    }

}
