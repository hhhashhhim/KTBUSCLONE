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
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function index()
    {
        return Discount::with('addedBy')->orderBy('id')->where('company_id', $this->company_id)->get();
    }

    public function storeDiscount(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('discounts', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Discount Name is Required!',
            'name.unique' => 'Discount Name not be Repeated!',
        ];
        $this->validate($request, $rules, $customMessages);
        $discount = Discount::create([
            'name' => $request->name,
            'type' => $request->type,
            'percentage' => $request->type == "percentage" ?  $request->percentage : null,
            'flat' => $request->type == "flat" ? $request->flat : null,
            'company_id' => $this->company_id,
            'is_active' => $request->active,
            'added_by' => Auth::user()->id,
        ]);
        return Discount::with('addedBy')->find($discount->id);
    }

    public function updateDiscount(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Discount Name is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return Discount::where('id', $request->id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'percentage' => $request->type == "percentage" ? $request->percentage: null,
            'flat' => $request->type == "flat" ? $request->flat: null,
            'is_active' => $request->is_active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteDiscount(Request $request)
    {
        return Discount::find($request->id)->delete();
    }
    public function selectiveDiscount()
    {
        return Discount::where('company_id', $this->company_id)/*->where('is_active', 1)*/->get();
    }
}
