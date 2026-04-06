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
            $query->where(function ($q) use ($request) {
                $q->where('cash_payment', $request->amount)
                    ->orWhere('bank_payment', $request->amount);
            });
        }

        if ($request->narration) {
            $query->where('narration', 'like', '%' . $request->narration . '%');
        }

        return $query->orderBy('id', 'desc')->get();
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
                'type' => 'required|in:expense,income',
                'category_id'  => 'required_if:type,expense|nullable|integer',
                'other_income' => 'required_if:type,income|nullable|string',

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
                'company_id'     => Auth::user()->company_id,
                'terminal_id'    => Auth::user()->terminal_id,
                'total'          => $request->total,
                'narration'      => $request->narration,
                'cash_payment'   => $cashPayment,
                'date'           => $request->date,
                'bank_payment'   => $bankPayment,
                'cash_id'        => $cashPayment > 0 ? $request->cash_id : null,
                'bank_id'        => $bankPayment > 0 ? $request->bank_id : null,

                // ✅ NEW
                'type'   => $request->type,
                'category_id'    => $request->type === 'expense' ? $request->category_id : null,
                'other_income'  => $request->type === 'income' ? $request->other_income : null,

                'bill_post'      => $billPath,
                'added_by'       => Auth::id(),
            ]);
            // Determine ledger name and type
            if ($request->type === 'expense') {
                $category = ExpenseCategory::findOrFail($request->category_id);
                $ledgerName = strtoupper($category->name) . ' | EXPENSE';
                $ledger = accountHeadCreate(
                    $ledgerName,
                    5,   // EXPENSE
                    15,  // OPERATING EXPENSE
                    48,  // OTHER EXPENSES
                    195  // GENERAL EXPENSE
                );
            } else {
                // For other_income, store as INCOME
                $ledgerName = 'Commission | INCOME';
                $ledger = accountHeadCreate(
                    $ledgerName,
                    4,   // REVENUE
                    12,  // OTHER INCOME
                    283,  // COMMISSIONS
                    284  // THIRD PARTY COMMISSION
                );
            }

            // ================= CASH PAYMENT =================
            if ($cashPayment > 0) {
                $doc = AccountTransaction::where([
                    'company_id' => Auth::user()->company_id,
                    'type' => 'CP'
                ])->orderBy('document_id', 'DESC')->first();
                $documentId = $doc ? $doc->document_id + 1 : 1;

                if ($request->type == 'expense') {
                    // Expense: Debit Expense, Credit Cash
                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->cash_id,
                        'debit' => $cashPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CE',
                        'posting_id' => $expense->id,
                    ]);

                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->cash_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => 0,
                        'credit' => $cashPayment,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CE',
                        'posting_id' => $expense->id,
                    ]);
                } else {
                    // Income: Debit Cash, Credit Income
                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->cash_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => $cashPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CI', // Counter Income
                        'posting_id' => $expense->id,
                    ]);

                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->cash_id,
                        'debit' => 0,
                        'credit' => $cashPayment,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'approved' => '1',
                        'narration' => $request->narration,
                        'posting_type' => 'CI',
                        'posting_id' => $expense->id,
                    ]);
                }
            }

            // ================= BANK PAYMENT =================
            // Same logic as above, just replace cash_id with bank_id and type 'BP'
            if ($bankPayment > 0) {
                $doc = AccountTransaction::where([
                    'company_id' => Auth::user()->company_id,
                    'type' => 'BP'
                ])->orderBy('document_id', 'DESC')->first();
                $documentId = $doc ? $doc->document_id + 1 : 1;

                if ($request->type === 'expense') {
                    // Expense: Debit Expense, Credit Bank
                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->bank_id,
                        'debit' => $bankPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CE',
                        'posting_id' => $expense->id,
                    ]);

                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->bank_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => 0,
                        'credit' => $bankPayment,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CE',
                        'posting_id' => $expense->id,
                    ]);
                } else {
                    // Income: Debit Bank, Credit Income
                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->bank_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => $bankPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CI', // Counter Income
                        'posting_id' => $expense->id,
                    ]);

                    AccountTransaction::create([
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->bank_id,
                        'debit' => 0,
                        'credit' => $bankPayment,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => 'CI',
                        'posting_id' => $expense->id,
                    ]);
                }
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

            return response()->json([
                "errors" => ["Error" => [$e->getMessage()]]
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

            // ✅ Validation
            $request->validate([
                'id'            => 'required|integer|exists:counter_expenses,id',
                'total'         => 'required|numeric|min:1',
                'narration'     => 'required|string',
                'type'          => 'required|in:expense,income',
                'category_id'   => 'required_if:type,expense|nullable|integer',
                'other_income'  => 'required_if:type,income|nullable|string',
                'bill_post'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,xls,xlsx,txt,doc,docx|max:2048',
                'cash_payment'  => 'nullable|numeric|min:0',
                'bank_payment'  => 'nullable|numeric|min:0',
            ]);

            $expense = CounterExpense::findOrFail($request->id);

            $cashPayment = $request->cash_payment ?? 0;
            $bankPayment = $request->bank_payment ?? 0;

            if ($cashPayment <= 0 && $bankPayment <= 0) {
                return response()->json([
                    "errors" => ["Error" => ['Enter either Cash or Bank Payment']]
                ], 422);
            }


            // ================= REMOVE OLD TRANSACTIONS =================
            AccountTransaction::where([
                'posting_type' => in_array($expense->type, ['expense', 'income']) ?
                    ($expense->type == 'expense' ? 'CE' : 'CI') : 'CE',
                'posting_id'   => $expense->id
            ])->delete();

            // ================= FILE UPLOAD =================
            $billPath = $expense->bill_post;
            if ($request->hasFile('bill_post')) {
                $file = $request->file('bill_post');
                $filename = time() . '_' . $file->getClientOriginalName();
                $billPath = $file->storeAs('counter_expenses', $filename, 'public');
            }

            // ================= UPDATE MAIN RECORD =================
            $expense->update([
                'total'        => $request->total,
                'narration'    => $request->narration,
                'cash_payment' => $cashPayment,
                'bank_payment' => $bankPayment,
                'type'         => $request->type,
                'category_id'    => $request->type == 'expense' ? $request->category_id : null,
                'other_income'  => $request->type == 'income' ? $request->other_income : null,
                'cash_id'      => $cashPayment > 0 ? $request->cash_id : null,
                'bank_id'      => $bankPayment > 0 ? $request->bank_id : null,
                'date'         => $request->date,
                'bill_post'    => $billPath,
                'updated_by'   => Auth::id(),
            ]);

            // ================= CREATE LEDGER =================
            if ($request->type === 'expense') {

                $category = ExpenseCategory::findOrFail($request->category_id);
                $ledgerName = strtoupper($category->name) . ' | EXPENSE';

                $ledger = accountHeadCreate(
                    $ledgerName,
                    5,
                    15,
                    48,
                    195
                );

                $postingType = 'CE';
            } else {

                $ledgerName = 'Commission | INCOME';

                $ledger = accountHeadCreate(
                    $ledgerName,
                    4,   // REVENUE
                    12,  // OTHER INCOME
                    283,  // COMMISSIONS
                    284  // THIRD PARTY COMMISSION

                );

                $postingType = 'CI';
            }

            // ================= CASH TRANSACTION =================
            if ($cashPayment > 0) {

                $doc = AccountTransaction::where([
                    'company_id' => Auth::user()->company_id,
                    'type' => 'CP'
                ])->orderBy('document_id', 'DESC')->first();

                $documentId = $doc ? $doc->document_id + 1 : 1;

                if ($request->type == 'expense') {

                    // Debit Expense
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->cash_id,
                        'debit' => $cashPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);

                    // Credit Cash
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->cash_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => 0,
                        'credit' => $cashPayment,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);
                } else {

                    // Debit Cash
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->cash_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => $cashPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);

                    // Credit Income
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->cash_id,
                        'debit' => 0,
                        'credit' => $cashPayment,
                        'document_id' => $documentId,
                        'type' => 'CP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);
                }
            }

            // ================= BANK TRANSACTION =================
            if ($bankPayment > 0) {

                $doc = AccountTransaction::where([
                    'company_id' => Auth::user()->company_id,
                    'type' => 'BP'
                ])->orderBy('document_id', 'DESC')->first();

                $documentId = $doc ? $doc->document_id + 1 : 1;

                if ($request->type == 'expense') {

                    // Debit Expense
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->bank_id,
                        'debit' => $bankPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);

                    // Credit Bank
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->bank_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => 0,
                        'credit' => $bankPayment,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);
                } else {

                    // Debit Bank
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $request->bank_id,
                        'other_account_head_id' => $ledger->id,
                        'debit' => $bankPayment,
                        'credit' => 0,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);

                    // Credit Income
                    AccountTransaction::create([
                        'added_by' => Auth::id(),
                        'company_id' => Auth::user()->company_id,
                        'terminal_id' => Auth::user()->terminal_id,
                        'account_head_id' => $ledger->id,
                        'other_account_head_id' => $request->bank_id,
                        'debit' => 0,
                        'credit' => $bankPayment,
                        'document_id' => $documentId,
                        'type' => 'BP',
                        'narration' => $request->narration,
                        'approved' => '1',
                        'posting_type' => $postingType,
                        'posting_id' => $expense->id,
                    ]);
                }
            }

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
