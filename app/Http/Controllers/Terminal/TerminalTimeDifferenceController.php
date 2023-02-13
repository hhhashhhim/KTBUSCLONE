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
        return City::where('company_id', Auth::user()->company_id)->withCount('terminal')->having('terminal_count', '>=', 2)->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        if ($request->created == 0) {
            TerminalTimeDifference::create([
                'city_id' => $request->city,
                'terminal_from_id' => $request->from,
                'terminal_to_id' => $request->to,
                'time_difference' => $request->time_difference,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            TerminalTimeDifference::create([
                'city_id' => $request->city,
                'terminal_from_id' => $request->to,
                'terminal_to_id' => $request->from,
                'time_difference' => $request->time_difference,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            return response()->json([
                "success" => ["Time Difference Added Successfully"],
            ], 200);
        }
        if ($request->created == 1) {
            TerminalTimeDifference::where('id', $request->id)->update([
                'city_id' => $request->city_id,
                'terminal_from_id' => $request->terminal_from_id,
                'terminal_to_id' => $request->terminal_to_id,
                'time_difference' => $request->time_difference,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            TerminalTimeDifference::where(['id' => $request->id, 'terminal_from_id' => $request->to, 'terminal_to_id' => $request->from])->update([
                'city_id' => $request->city_id,
                'terminal_from_id' => $request->terminal_to_id,
                'terminal_to_id' => $request->terminal_from_id,
                'time_difference' => $request->time_difference,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
            return response()->json([
                "success" => ["Time Difference Updated Successfully"],
            ], 200);
        }
    }


    public function check(Request $request)
    {
        return TerminalTimeDifference::where(['company_id' => Auth::user()->company_id, 'city_id' => $request->city, 'terminal_from_id' => $request->from, 'terminal_to_id' => $request->to])->first();

    }

    public function getTerminals(Request $request)
    {
        $terminals = Terminal::where(['company_id' => Auth::user()->company_id, 'city_id' => $request->city])->get(['city_id', 'id', 'name']);
        return $terminals->map(function ($single) use ($request) {
            $single->allTerminals = Terminal::where(['company_id' => Auth::user()->company_id, 'city_id' => $request->city])->get(['city_id', 'id', 'name']);
            foreach ($single->allTerminals as $key => $item) {
                if ($single->id != $item->id) {
                    $item['difference'] = TerminalTimeDifference::where('company_id', Auth::user()->company_id)->where('city_id', $request->city)
                        ->where('terminal_from_id', $single->id)
                        ->where('terminal_to_id', $item->id)
                        ->value('time_difference') ?? 'No Added';
                } else {
                    $item['difference'] = 'Not Added';
                }
            }
            return $single;
        });
    }
}
