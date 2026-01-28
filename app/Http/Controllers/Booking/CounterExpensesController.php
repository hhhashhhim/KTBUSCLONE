<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Account\AccountHead;
use App\Models\Account\AccountTransaction;
use App\Models\CounterExpense;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Expense\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterExpensesController extends Controller
{
public function index(Request $request)
{
    if (!checkForSubmenu("counter-expenses")) {
        return response()->json(["Error" => ['You are not authorized to access this url']], 403);
    }

    $query = CounterExpense::with('category')
        ->where('company_id', Auth::user()->company_id)
        ->where('terminal_id', Auth::user()->terminal_id);

    // Apply filters if provided
    if ($request->cash_id) {
        $query->where('cash_id', $request->cash_id);
    }

    if ($request->bank_id) {
        $query->where('bank_id', $request->bank_id);
    }

    if ($request->category_id) {
        $query->where('category_id', $request->category_id);
    }

   if ($request->amount) {
    $query->where(function($q) use ($request) {
        $q->where('cash_payment', $request->amount)
          ->orWhere('bank_payment', $request->amount);
    });
}

    if ($request->narration) {
        $query->where('narration', 'like', '%' . $request->narration . '%');
    }

    return $query->get();
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

        // ================= VALIDATION =================
        $request->validate([
            'total'        => 'required|numeric|min:1',
            'narration'    => 'required|string',
            'category_id'  => 'required|integer',
            'bill_post'    => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,txt,doc,docx|max:2048',

            'cash_payment' => 'nullable|numeric|min:0',
            'cash_id'      => 'nullable|integer',
            'bank_payment' => 'nullable|numeric|min:0',
            'bank_id'      => 'nullable|integer',
        ]);

        $cashPayment = $request->cash_payment ?? 0;
        $bankPayment = $request->bank_payment ?? 0;

        if ($cashPayment <= 0 && $bankPayment <= 0) {
            return response()->json([
                "errors" => ["Error" => ['Enter either Cash or Bank Payment']]
            ], 422);
        }

        if (($cashPayment + $bankPayment) != $request->total) {
            return response()->json([
                "errors" => ["Error" => ['Cash + Bank payment must equal Total']]
            ], 422);
        }

        // ================= FILE UPLOAD =================
        $billPath = null;
        if ($request->hasFile('bill_post')) {
            $billPath = $request->file('bill_post')->storeAs(
                'counter_expenses',
                time() . '_' . $request->file('bill_post')->getClientOriginalName(),
                'public'
            );
        }
        $expense = CounterExpense::create([
                'company_id'   => Auth::user()->company_id,
                'terminal_id'  => Auth::user()->terminal_id,
                'total'        => $request->total,
                'narration'    => $request->narration,
                'cash_payment' => $cashPayment,
                'bank_payment' => $bankPayment,
                'cash_id'      => $cashPayment > 0 ? $request->cash_id : null,
                'bank_id'      => $bankPayment > 0 ? $request->bank_id : null,
                'category_id'  => $request->category_id,
                'bill_post'    => $billPath,
                'added_by'     => Auth::id(),
            ]);
        $category = ExpenseCategory::findOrFail($request->category_id);
$expenseLedger = accountHeadCreate(
                strtoupper($category->name) . ' | EXPENSE',
                5,   // EXPENSES
                15,  // OPERATING EXPENSES
                48,  // OTHER EXPENSES
                195  // GENERAL EXPENSE
            );
        // ================= SAVE EXPENSE =================
       // ================= CASH PAYMENT =================
if ($cashPayment > 0) {
    $doc = AccountTransaction::where([
        'company_id' => Auth::user()->company_id,
        'type' => 'CP'
    ])->orderBy('document_id', 'DESC')->first();

    $documentId = $doc ? $doc->document_id + 1 : 1;

    // Debit Expense (Increases Expense)
    AccountTransaction::create([
        'added_by'            => Auth::user()->id,
        'company_id'            => Auth::user()->company_id,
        'terminal_id'           => Auth::user()->terminal_id,
        'account_head_id'       => $expenseLedger->id,
        'other_account_head_id' => $request->cash_id,
        'debit'                 => $cashPayment,
        'credit'                => 0,
        'document_id'           => $documentId,
        'type'                  => 'CP',
        'narration'             => $request->narration,
        'posting_type'          => 'CE',
        'posting_id'            => $expense->id,
    ]);

    // Credit Cash (Decreases Cash Asset)
    AccountTransaction::create([
         'added_by'            => Auth::user()->id,
        'company_id'            => Auth::user()->company_id,
        'terminal_id'           => Auth::user()->terminal_id,
        'account_head_id'       => $request->cash_id,
        'other_account_head_id' => $expenseLedger->id,
        'debit'                 => 0,
        'credit'                => $cashPayment,
        'document_id'           => $documentId,
        'type'                  => 'CP',
        'narration'             => $request->narration,
        'posting_type'          => 'CE',
        'posting_id'            => $expense->id,
    ]);
}

// ================= BANK PAYMENT =================
if ($bankPayment > 0) {
    $doc = AccountTransaction::where([
        'company_id' => Auth::user()->company_id,
        'type' => 'BP'
    ])->orderBy('document_id', 'DESC')->first();

    $documentId = $doc ? $doc->document_id + 1 : 1;

    // Debit Expense
    AccountTransaction::create([
         'added_by'            => Auth::user()->id,
        'company_id'            => Auth::user()->company_id,
        'terminal_id'           => Auth::user()->terminal_id,
        'account_head_id'       => $expenseLedger->id,
        'other_account_head_id' => $request->bank_id,
        'debit'                 => $bankPayment,
        'credit'                => 0,
        'document_id'           => $documentId,
        'type'                  => 'BP',
        'narration'             => $request->narration,
        'posting_type'          => 'CE',
        'posting_id'            => $expense->id,
    ]);

    // Credit Bank
    AccountTransaction::create([
         'added_by'            => Auth::user()->id,
        'company_id'            => Auth::user()->company_id,
        'terminal_id'           => Auth::user()->terminal_id,
        'account_head_id'       => $request->bank_id,
        'other_account_head_id' => $expenseLedger->id,
        'debit'                 => 0,
        'credit'                => $bankPayment,
        'document_id'           => $documentId,
        'type'                  => 'BP',
        'narration'             => $request->narration,
        'posting_type'          => 'CE',
        'posting_id'            => $expense->id,
    ]);
}

        // ================= ACTIVITY LOG =================
        ActivityLog::create([
            "activity_by"   => Auth::id(),
            "message"       => Auth::user()->name .
                " | Counter Expense ({$request->total}) | {$request->narration}",
            "requested_host" => $request->ip(),
            "company_id"    => Auth::user()->company_id
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Counter Expense saved successfully',
            'data' => $expense
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error($e->getMessage());

        return response()->json([
            "errors" => ["Error" => ['Transaction failed']]
        ], 422);
    }
}







   public function update(Request $request)
{
    if (!checkPermissionButtons("edit-counter-expenses")) {
        return response()->json([
            "errors" => ["Error" => ['You are not authorized']]
        ], 403);
    }

    DB::beginTransaction();

    try {
        // ✅ Base validation
        $request->validate([
            'id'          => 'required|integer|exists:counter_expenses,id',
            'total'       => 'required|numeric|min:1',
            'narration'   => 'required|string',
            'category_id' => 'required|integer',
            'bill_post'   => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,txt,doc,docx|max:2048',
            'cash_payment'=> 'nullable|numeric|min:0',
            'bank_payment'=> 'nullable|numeric|min:0',
        ], [
            'total.required'       => 'Total Amount is Required!',
            'narration.required'   => 'Expenses Narration is Required!',
            'category_id.required' => 'Expense Category is Required!',
        ]);

        $expense = CounterExpense::findOrFail($request->id);

        $cashPayment = $request->cash_payment ?? 0;
        $bankPayment = $request->bank_payment ?? 0;

        if ($cashPayment <= 0 && $bankPayment <= 0) {
            return response()->json([
                "errors" => ["Error" => ['Enter either Cash or Bank Payment']]
            ], 422);
        }

        if (($cashPayment + $bankPayment) != $request->total) {
            return response()->json([
                "errors" => ["Error" => ['Cash + Bank payment must equal Total']]
            ], 422);
        }

        // 📎 Handle bill upload
        $billPath = $expense->bill_post;
        if ($request->hasFile('bill_post')) {
            $file = $request->file('bill_post');
            $filename = time() . '_' . $file->getClientOriginalName();
            $billPath = $file->storeAs('counter_expenses', $filename, 'public');
        }

        // ✅ Prepare update data
        $updateData = [
            'total'       => $request->total,
            'narration'   => $request->narration,
            'cash_payment'=> $cashPayment,
            'bank_payment'=> $bankPayment,
            'category_id' => $request->category_id,
            'bill_post'   => $billPath,
            'updated_by'  => Auth::id(),
        ];

        // Only store IDs if payment > 0
        if ($cashPayment > 0) {
            $updateData['cash_id'] = $request->cash_id;
        } else {
            $updateData['cash_id'] = null;
        }

        if ($bankPayment > 0) {
            $updateData['bank_id'] = $request->bank_id;
        } else {
            $updateData['bank_id'] = null;
        }

        $expense->update($updateData);

        // 🧾 Log activity
        ActivityLog::create([
            "activity_by"   => Auth::id(),
            "message"       => Auth::user()->name . " | updated counter expense ({$request->total}) | {$request->narration}",
            "requested_host"=> $request->ip(),
            "company_id"    => Auth::user()->company_id
        ]);

        DB::commit();
        return response()->json($expense, 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error($e->getMessage());
        return response()->json([
            "errors" => ["Error" => ['An error occurred during the update']]
        ], 422);
    }


    }
}
