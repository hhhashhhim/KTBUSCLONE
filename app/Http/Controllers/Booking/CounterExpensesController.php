<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\CounterExpense;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterExpensesController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("counter-expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return CounterExpense::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id])->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-counter-expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'amount' => 'required | integer',
                    'narration' => 'required',
                ];

                $customMessages = [
                    'amount.required' => 'Expenses Amount is Required!',
                    'narration.required' => 'Expenses Narration is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $data =  CounterExpense::create([
                    'company_id' => Auth::user()->company_id,
                    'terminal_id' => Auth::user()->terminal_id,
                    'amount' => $request->amount,
                    'narration' => $request->narration,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | stored counter expense ($request->amount) | $request->narration",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $data;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-counter-expenses"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'amount' => 'required | integer',
                    'narration' => 'required',
                ];

                $customMessages = [
                    'amount.required' => 'Expenses Amount is Required!',
                    'narration.required' => 'Expenses Narration is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $data =  CounterExpense::find($request->id)->update([
                    'amount' => $request->amount,
                    'narration' => $request->narration,
                    'updated_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated counter expense ($request->amount) | $request->narration",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $data;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
}
