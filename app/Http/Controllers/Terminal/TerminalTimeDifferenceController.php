<?php

namespace App\Http\Controllers\Terminal;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Terminal;
use App\Models\Terminal\TerminalTimeDifference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerminalTimeDifferenceController extends Controller
{
    public function index()
    {
        return City::withCount('terminal')->having('terminal_count', '>=', 2)->get(['id', 'name']);
    }

    public function getTerminals(Request $request)
    {
        return Terminal::where(['company_id' => Auth::user()->company_id, 'city_id' => $request->city])->get();
    }

    public function store(Request $request)
    {
            dd($request->all());
//            TerminalTimeDifference::get();
    }

//    public function check(Request $request)
//    {
//            dd($request->all());
////            TerminalTimeDifference::get();
//    }
}
