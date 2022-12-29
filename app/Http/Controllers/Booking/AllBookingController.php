<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Bus\Bus;
use App\Models\Route\Route;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllBookingController extends Controller
{

    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function routes()
    {
        return Route::where('company_id', $this->company_id)->get();

    }

    public function terminals()
    {
        return Terminal::where('company_id', $this->company_id)->get();
    }

    public function buses()
    {
        return Bus::where('company_id', $this->company_id)->get();
    }

}
