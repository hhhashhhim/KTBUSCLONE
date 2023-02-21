<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllBookingController extends Controller
{

    public function routes()
    {
        return Route::where('company_id', Auth::user()->company_id)->get();

    }

    public function terminals()
    {
        return Terminal::where('company_id', Auth::user()->company_id)->get();
    }

    public function buses()
    {
        return Bus::where('company_id', Auth::user()->company_id)->get();
    }

    public function filter(Request $request)
    {
        return Ticket::whereHas('customer', function ($q) use ($request) {
            if (!is_null($request->cnicFilter)) {
                $q->where('cnic', str_replace('-', '', $request->cnicFilter));
            }
            if (!is_null($request->phoneFilter)) {
                $q->where('contact', str_replace('-', '', $request->cnicFilter));
            }
            if (!is_null($request->nameFilter)) {
                $q->where('name', 'like', '%' . $request->nameFilter . '%');
            }
        })->get();
    }

}
