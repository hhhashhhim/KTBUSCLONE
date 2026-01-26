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
        if (!checkForSubmenu("counter-expenses")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return CounterExpense::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id])->get();
    }

    public function store(Request $request)
    {
        if (!checkPermissionButtons("add-counter-expenses")) {
            return response()->json([
                "errors" => ["Error" => ['You are not authorized']]
            ], 403);
        }

        DB::beginTransaction();

        try {
            $request->validate([
                'amount' => 'required|integer',
                'narration' => 'required',
                'payment_method' => 'required|in:cash,bank',
                'bill_post' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,txt,doc,docx|max:2048',
            ], [
                'amount.required' => 'Expenses Amount is Required!',
                'narration.required' => 'Expenses Narration is Required!',
                'payment_method.required' => 'Payment Method is Required!',
            ]);

            $billPath = null;

            // 📸 Upload bill image
            if ($request->hasFile('bill_post')) {
                $file = $request->file('bill_post');
                $originalName = $file->getClientOriginalName();
                $filename = $originalName;
                $billPath = $file->storeAs('counter_expenses', $filename, 'public');
            }

            $data = CounterExpense::create([
                'company_id' => Auth::user()->company_id,
                'terminal_id' => Auth::user()->terminal_id,
                'amount' => $request->amount,
                'narration' => $request->narration,
                'payment_method' => $request->payment_method,
                'bill_post' => $billPath,
                'added_by' => Auth::user()->id,
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name .
                    " | stored counter expense ({$request->amount}) | {$request->narration}",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json($data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                "errors" => ["Error" => ['An error occurred during the database transaction']]
            ], 422);
        }
    }


    public function update(Request $request)
    {
        if (!checkPermissionButtons("edit-counter-expenses")) {
            return response()->json(["errors" => ["Error" => ['Not authorized']]], 403);
        }

        DB::beginTransaction();
        try {
            $request->validate([
                'id' => 'required|integer|exists:counter_expenses,id',
                'amount' => 'required|integer',
                'narration' => 'required',
                'payment_method' => 'required|in:cash,bank',
                'bill_post' => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,txt,doc,docx|max:2048',

            ]);

            $expense = CounterExpense::findOrFail($request->id);

            if ($request->hasFile('bill_post')) {
                $file = $request->file('bill_post');
                $originalName = $file->getClientOriginalName();
                $filename =  $originalName;
                $billPath = $file->storeAs('counter_expenses', $filename, 'public');
            }

            $expense->update([
                'amount' => $request->amount,
                'narration' => $request->narration,
                'payment_method' => $request->payment_method,
                'bill_post' => $billPath,
                'updated_by' => Auth::id(),
            ]);

            ActivityLog::create([
                "activity_by" => Auth::id(),
                "message" => Auth::user()->name . " | updated counter expense ({$request->amount}) | {$request->narration}",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json($expense, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during update']]], 422);
        }
    }
}
