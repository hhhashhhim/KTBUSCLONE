<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Customer;
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
        $query = Ticket::query();
//        $tickets_ids = [];
        if (!is_null($request->cnicFilter)) {
            $query->with('customer');
//            $customers = Customer::where('cnic', str_replace('-', '', $request->cnicFilter))->pluck('id')->toArray();
//            $tickets = Ticket::whereIn('customer_id', $customers)->pluck('id')->toArray();
//            count($tickets_ids) > 0 ? array_push($tickets_ids, $tickets) : $tickets_ids = $tickets;
        }
//        if (!is_null($request->phoneFilter)) {
//            $customers = Customer::where('contact', str_replace('-', '', $request->phoneFilter))->pluck('id')->toArray();
//            $tickets = Ticket::whereIn('customer_id', $customers)->pluck('id')->toArray();
//            count($tickets_ids) > 0 ? array_push($tickets_ids, $tickets) : $tickets_ids = $tickets;
//        }
//            if (!is_null($request->phoneFilter)) {
//                $q->where('contact', str_replace('-', '', $request->cnicFilter));
//            }
//            if (!is_null($request->nameFilter)) {
//                $q->where('name', 'like', '%' . $request->nameFilter . '%');
        return $query->get();
//        return Ticket::with('terminal', 'schedule', 'customer')->whereIn('id', array_unique($tickets_ids))->get();
    }

}

