<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Account\AccountCategory;
use App\Models\ActivityLog;
use App\Models\Expense\ExpenseCategory;
use App\Models\ReportHeaderLink;
use App\Models\ReportsHeader;
use App\Models\Route\RouteFare;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("categories"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return ExpenseCategory::with('addedBy', 'reportHeader')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
{
    $rules = [
        'name' => [
            'required',
            Rule::unique('expense_categories', 'name')
                ->where('company_id', Auth::user()->company_id)
                ->whereNull('deleted_at'),
        ],
        'include_in_closing_summary' => ['nullable', 'in:0,1'],
        'report_header_id' => ['nullable', 'exists:reports_headers,id'],
    ];

    $customMessages = [
        'name.required' => 'Name Field is Required!',
        'name.unique' => 'Category Name is Already Exist',
        'report_header_id.exists' => 'Selected header is invalid',
    ];

    $this->validate($request, $rules, $customMessages);

    if (!checkPermissionButtons("add-category")) {
        return response()->json([
            "Error" => ['You are not authorized to access this url']
        ], 403);
    }

    try {
        DB::beginTransaction();

        $category = ExpenseCategory::create([
            'name' => $request->name,
            'include_in_closing' => $request->include_in_closing_summary,
            'report_header_id' => $request->report_header_id,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ]);

        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name . " | added expense category (" . $request->name . ")",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);

        DB::commit();

        return response()->json($category, 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Database transaction error: ' . $e->getMessage());

        return response()->json([
            "errors" => [
                "Error" => ['An error occurred during the database transaction.']
            ]
        ], 422);
    }
}
 public function reportHeader()
    {

        return ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
    }
     public function getCategory()
    {
        return ExpenseCategory::with('addedBy', 'reportHeader')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }
 public function reportHeaderLinkGet(Request $request)
    {
        $links = ReportHeaderLink::where(['company_id'=> Auth::user()->company_id,'ticket_merge_id'=>$request->ticket_merge_id])->get();
        $headers = ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
        if($links->count() > 0)
        {
            return [
                "headers" => $headers,
                "links" => $links
            ];
        }
        else
        {
            return [
                "headers" => $headers,
                "links" => null
            ];
        }
    }
    public function expenseHeaderLink(Request $request)
    {
       try {
                DB::beginTransaction();
                ReportHeaderLink::where("ticket_merge_id", $request->ticket_merge_id)->delete();
                foreach ($request->headIds as $key => $value) {
                    ReportHeaderLink::create([
                        'ticket_merge_id' => $request->ticket_merge_id,
                        'header_id' => $request->headIds[$key],
                        'value' => $request->values[$key],
                        'company_id' => Auth::user()->company_id,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | linked report header",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
   public function update(Request $request)
{
    if (!checkPermissionButtons("edit-category")) {
        return response()->json([
            "Error" => ['You are not authorized to access this url']
        ], 403);
    }

    try {
        DB::beginTransaction();

        $rules = [
            'id' => ['required', 'exists:expense_categories,id'],
            'name' => [
                'required',
                Rule::unique('account_categories', 'name')
                    ->where('company_id', Auth::user()->company_id)
                    ->where("first_level_id", 5)
                    ->where("second_level_id", 18)
                    ->whereNull('deleted_at')
                    ->ignore($request->id),

                Rule::unique('expense_categories', 'name')
                    ->where('company_id', Auth::user()->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($request->id),
            ],
            'include_in_closing_summary' => ['nullable', 'in:0,1'],
            'report_header_id' => ['nullable', 'exists:reports_headers,id'],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Category Name is Already Exist',
            'report_header_id.exists' => 'Selected header is invalid',
        ];

        $this->validate($request, $rules, $customMessages);

        $expCtg = ExpenseCategory::where('company_id', Auth::user()->company_id)
            ->findOrFail($request->id);

        $data = $expCtg->update([
            'name' => $request->name,
            'include_in_closing' => $request->include_in_closing_summary,
            'report_header_id' => $request->report_header_id,
            'updated_by' => Auth::user()->id,
        ]);

        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name . " | updated expense category (" . $request->name . ")",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $expCtg->fresh(['addedBy', 'reportHeader'])
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Database transaction error: ' . $e->getMessage());

        return response()->json([
            "errors" => [
                "Error" => ['An error occurred during the database transaction.']
            ]
        ], 422);
    }
}
}
