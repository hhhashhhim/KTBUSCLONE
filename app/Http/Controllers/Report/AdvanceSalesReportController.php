<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Route\Route;
use App\Models\Schedule\Schedule;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvanceSalesReportController extends Controller
{
    public function getUserNames()
    {
        return User::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function getTerminals()
    {
        return Terminal::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function getRoutes()
    {
        return Route::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }

    public function filterData(Request $request)
    {
//        dd($request->all());
        $tickets = Ticket::with('addedBy:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name', 'schedule:id,name,time')
            ->where('company_id', Auth::user()->company_id)
            ->where('type', 'booked')
            ->when($request->terminal, function ($query) use ($request) {
                return $query->where('terminal_id', $request->terminal);
            })
            ->when($request->user, function ($query) use ($request) {
                return $query->where('added_by', $request->user);
            })
            ->when($request->route, function ($query) use ($request) {
                $scheduleIds = Schedule::where('route_id', $request->route)->pluck('id');
                return $query->whereIn('schedule_id', $scheduleIds);
            })->get();

        $tickets->transform(function ($single) {
            $single->schedule_date_time = date('Y-m-d H:i:s', strtotime($single->schedule_date . ' ' . $single->schedule->time));
            return $single;
        });

        $tickets = $tickets->when($request->fromDateTime, function ($query) use ($request) {
            return $query->where('schedule_date_time', '>=', $request->fromDateTime);
        })
            ->when($request->toDateTime, function ($query) use ($request) {
                return $query->where('schedule_date_time', '<=', $request->toDateTime);
            })
            ->groupBy(['schedule_date_time', 'added_by']);
        return [
            'record' => $tickets,
        ];

    }


}
