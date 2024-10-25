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
        return ExpenseCategory::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required'=> Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Category Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        if(!checkPermissionButtons("add-category"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                

                $category = ExpenseCategory::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);

                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added expense category (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $category;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-category"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required'=> Rule::unique('account_categories', 'name')->where('company_id', Auth::user()->company_id)->where("first_level_id",5)->where("second_level_id",18)->whereNull('deleted_at')->ignore($request->id),'required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],

                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Category Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                $expCtg = ExpenseCategory::find($request->id);

                $data =  $expCtg->update([
                    'name' => $request->name,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated expense category (".$request->name.")",
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
