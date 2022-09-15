<?php

namespace App\Http\Controllers\Discount;

use App\Http\Controllers\Controller;
use App\Models\Discount\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = auth()->user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return Discount::orderBy('id')->select('name', 'id', 'percentage', 'is_active')->get();
    }

    public function storeDiscount(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('discounts', 'name')->whereNull('deleted_at')],
            'percentage' => 'required|numeric|min:0|max:100',
        ];

        $customMessages = [
            'name.required' => 'Discount Name is Required!',
            'name.unique' => 'Discount Name not be Repeated!',
            'percentage.required' => 'Discount percentage is Required!',
            'percentage.min' => 'Discount percentage never be less then 0',
            'percentage.max' => 'Discount percentage never be greater then 100',
        ];
        $this->validate($request, $rules, $customMessages);
        return Discount::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'company_id' => Auth::user()->company_id,
            'is_active' => $request->active,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateDiscount(Request $request)
    {
        $rules = [
            'name' => 'required',
            'percentage' => 'required|numeric|min:0|max:100',
        ];

        $customMessages = [
            'name.required' => 'Discount Name is Required!',
            'percentage.required' => 'Discount percentage is Required!',
            'percentage.min' => 'Discount percentage never be less then 0',
            'percentage.max' => 'Discount percentage never be greater then 100',
        ];
        $this->validate($request, $rules, $customMessages);
        return Discount::where('id', $request->id)->update([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'company_id' => Auth::user()->company_id,
            'is_active' => !isset($request->is_Active) ? 0 : $request->is_Active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteDiscount(Request $request)
    {
        return Discount::find($request->id)->delete();
    }
}
