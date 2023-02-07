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
//        dd($request->all());
        if ($request->created == 0) {
            TerminalTimeDifference::create([
                'city_id' => $request->city,
                'terminal_from_id' => $request->from,
                'terminal_to_id' => $request->to,
                'time_difference' => $request->time_difference,
                'company_id' => Auth::user()->company_id,
            ]);
            return response()->json([
                "success" => [
                    "message" => ["Time Difference Added Successfully"]
                ]
            ], 200);

        }
        if ($request->created == 1) {
            TerminalTimeDifference::where(['city_id' => $request->city, 'terminal_from_id' => $request->from, 'terminal_to_id' => $request->to])->update([
                'time_difference' => $request->time_difference,
            ]);
            return response()->json([
                "success" => [
                    "message" => ["Time Difference Updated Successfully"]
                ]
            ], 200);
        }
    }


    public function check(Request $request)
    {
//        dd($request->all());
        $checkFare = TerminalTimeDifference::where(['company_id'=> Auth::user()->company_id, 'city_id'=>$request->city, 'terminal_from_id' => $request->from, 'terminal_to_id'=>$request->to])->select('id', 'time_difference')->first();
        return response($checkFare, 200);
    }
}
