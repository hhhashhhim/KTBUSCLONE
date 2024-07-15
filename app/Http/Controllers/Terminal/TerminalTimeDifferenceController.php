<?php

namespace App\Http\Controllers\Terminal;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Terminal;
use App\Models\ActivityLog;
use App\Models\Terminal\TerminalTimeDifference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminalTimeDifferenceController extends Controller
{
    public function index()
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return City::where('company_id', Auth::user()->company_id)->withCount('terminal')->having('terminal_count', '>=', 2)->get(['id', 'name']);
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return $request;
        try {
                DB::beginTransaction();
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
                    DB::commit();
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
                    ActivityLog::create([
                        "activity_by" => Auth::user()->id,
                        "message" => Auth::user()->name." | added terminal time difference ",
                        "requested_host" => $request->ip(),
                        "company_id" => Auth::user()->company_id
                    ]);
                    DB::commit();
                    return response()->json([
                        "success" => ["Time Difference Updated Successfully"],
                    ], 200);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }


    public function check(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $data = TerminalTimeDifference::where(['company_id' => Auth::user()->company_id, 'city_id' => $request->city, 'terminal_from_id' => $request->from, 'terminal_to_id' => $request->to])->first();
        if ($data) {
            return $data;
        }
        return response()->json([], 204);
    }

    public function getTerminals(Request $request)
    {
        if(!checkPermissionButtons("edit-terminal"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
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
