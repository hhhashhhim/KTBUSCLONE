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
 public function dropReport(Request $request)
{
    if (!checkForSubmenu("schedule-drop")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    // Drop schedules query
    $query = DropSchedule::where('company_id', Auth::user()->company_id)
        ->with([
            "schedule:id,name,route_id",
            "schedule.route:id,name,via",
            "added_by:id,name"
        ]);

    // Filter by schedule ID (optional)
    if ($request->schedule_id) {
        $query->where('schedule_id', $request->schedule_id);
    }

    // Filter by bus number (optional)
    if ($request->bus_number) {
        $query->whereHas('schedule', function($q) use ($request) {
            $q->where('bus_id', $request->bus_number);
        });
    }

    // Date range filter
    if ($request->from_date) {
        $query->whereDate('schedule_date', '>=', $request->from_date);
    }
    if ($request->to_date) {
        $query->whereDate('schedule_date', '<=', $request->to_date);
    }

    $dropSchedules = $query->get();

    // Fetch schedules for dropdown (hide = 0 only)
    $schedules = Schedule::where('company_id', Auth::user()->company_id)
        ->where('hide', 0)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

    return response()->json([
        'dropSchedules' => $dropSchedules,
        'schedules' => $schedules
    ]);
}



}
