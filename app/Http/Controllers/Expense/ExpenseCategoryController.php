<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Expense\ExpenseCategory;
use App\Models\Route\RouteFare;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{

//    public $company_id;
//
//    public function __construct()
//    {
//        $this->middleware(function ($request, $next) {
//            Auth::user()->company_id = Auth::user()->company_id;
//            return $next($request);
//        });
//    }

    public function index()
    {
        return ExpenseCategory::with('addedBy')->where('company_id', Auth::user()->company_id)->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
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

        return $category;
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('expense_categories', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Category Name is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        return ExpenseCategory::find($request->id)->update([
            'name' => $request->name,
        ]);
    }

    // public function delete(Request $request)
    // {
    //     return City::find($request->id)->delete();
    // }
}
