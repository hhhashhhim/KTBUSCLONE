<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use App\Models\CounterExpense;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\DropSchedule;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleDropReportController extends Controller
{
    public function dropReport()
    {
        if(!checkForSubmenu("schedule-drop"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return DropSchedule::where('company_id', Auth::user()->company_id)->with("schedule:id,name,route_id","schedule.route:id,name,via","added_by:id,name")->get();
    }

}
