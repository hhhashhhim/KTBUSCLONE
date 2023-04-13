<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Terminal;
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

    public function filterData(Request $request)
    {
        dd($request->all());
    }


}
