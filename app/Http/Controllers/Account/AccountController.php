<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account\Account;
use App\Models\Account\AccountCategory;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Expense\ExpenseCategory;
use App\Models\Expense\TicketMergeExpense;
use App\Models\Route\RouteFare;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{

    public function accountCategories(Request $request)
    {
        return AccountCategory::with("firstLevel:id,name","secondLevel:id,name")->where(["company_id"=>Auth::user()->company_id])->orderBy('id')->get();
    }

    public function getSecondLevel(Request $request)
    {
        return Account::where(["parent_id"=>$request->id])->orderBy('id')->get();
    }
    
    public function categoryStore(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('account_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            'secondLevel' => 'required',
            'firstLevel' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Category Name is Already Exist',
            'secondLevel.required' => 'Tier 2 Field is Required!',
            'firstLevel.required' => 'Tier 1 Field is Required!',
        ];
        $this->validate($request, $rules, $customMessages);

        if($request->secondLevel == 18 && $request->firstLevel == 5)
        {
            $rules = [
                'name' => ['required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            ];
    
            $customMessages = [
                'name.unique' => 'Category Name is Already Exist',
            ];
            $this->validate($request, $rules, $customMessages);

            ExpenseCategory::create([
                'name' => $request->name,
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }

        return AccountCategory::create([
            "name" => $request->name,
            "second_level_id" => $request->secondLevel,
            "first_level_id" => $request->firstLevel,
            "company_id" => Auth::user()->company_id,
            "added_by" => Auth::user()->id,
        ]);
    }
    
    public function categoryUpdate(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('account_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->categoryId)],
            'secondLevel' => 'required',
            'firstLevel' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Category Name is Already Exist',
            'secondLevel.required' => 'Tier 2 Field is Required!',
            'firstLevel.required' => 'Tier 1 Field is Required!',
        ];
        $this->validate($request, $rules, $customMessages);

        if($request->secondLevel == 18 && $request->firstLevel == 5)
        {
            $rules = [
                'name' => ['required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            ];
    
            $customMessages = [
                'name.unique' => 'Category Name is Already Exist',
            ];
            $this->validate($request, $rules, $customMessages);

            $accCtg = AccountCategory::where(["id"=>$request->categoryId,"first_level_id"=>5,"second_level_id"=>18])->first();

            if($accCtg)
            {
                ExpenseCategory::where("name",$accCtg->name)->update([
                    'name' => $request->name,
                ]);
            }
            else
            {
                ExpenseCategory::create([
                    'name' => $request->name,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
            }
        }

        return AccountCategory::where("id",$request->categoryId)->update([
            "name" => $request->name,
            "second_level_id" => $request->secondLevel,
            "first_level_id" => $request->firstLevel,
        ]);
    }
}
