<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Account\AccountCategory;
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
        return ExpenseCategory::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required'=> Rule::unique('account_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at'),'required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Category Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);

                $category = ExpenseCategory::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);

                AccountCategory::create([
                    "name" => $request->name,
                    "second_level_id" => 18,
                    "first_level_id" => 5,
                    "company_id" => Auth::user()->company_id,
                    "added_by" => Auth::user()->id,
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
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required'=> Rule::unique('account_categories', 'name')->where('company_id', Auth::user()->company_id)->where("first_level_id",5)->where("second_level_id",18)->whereNull('deleted_at'),'required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

                ];

                $customMessages = [
                    'name.required' => 'Name Field is Required!',
                    'name.unique' => 'Category Name is Already Exist',
                ];
                $this->validate($request, $rules, $customMessages);
                $expCtg = ExpenseCategory::find($request->id);
                $accCtg = AccountCategory::where(["name"=>$expCtg->name,"first_level_id"=>5,"second_level_id"=>18])->update([
                    'name' => $request->name,
                ]);

                $data =  $expCtg->update([
                    'name' => $request->name,
                ]);
                DB::commit();
                return $data;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    // public function delete(Request $request)
    // {
    //     return City::find($request->id)->delete();
    // }
}
