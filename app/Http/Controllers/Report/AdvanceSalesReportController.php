<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Route\Route;
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
        $tickets = Ticket::with('addedBy:id,name', 'ticketElt:id,ticket_id,elt_price', 'terminal:id,name', 'busClass:id,name')->where('company_id', Auth::user()->company_id)->where('type', 'booked')->get()->groupBy('schedule_date');

        return [
            'record' => $tickets,
        ];

    }


}
