<?php

namespace App\Http\Controllers\Surcharge;

use App\Http\Controllers\Controller;
use App\Models\Surcharge\Surcharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SurchargeController extends Controller
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
        return Surcharge::orderBy('id')->select('name', 'id', 'percentage', 'is_active')->get();
    }

    public function storeSurcharge(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('schedules', 'name')->whereNull('deleted_at')],
            'percentage' => 'required|numeric|min:0|max:100',
        ];

        $customMessages = [
            'name.required' => 'Surcharge Name is Required!',
            'name.unique' => 'Surcharge Name not be Repeated!',
            'percentage.required' => 'Surcharge percentage is Required!',
            'percentage.min' => 'Surcharge percentage never be less then 0',
            'percentage.max' => 'Surcharge percentage never be greater then 100',
        ];
        $this->validate($request, $rules, $customMessages);
        return Surcharge::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'company_id' => Auth::user()->company_id,
            'is_active' => $request->active,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateSurcharge(Request $request)
    {
        $rules = [
            'name' => 'required',
            'percentage' => 'required|numeric|min:0|max:100',
        ];

        $customMessages = [
            'name.required' => 'Surcharge Name is Required!',
            'percentage.required' => 'Surcharge percentage is Required!',
            'percentage.min' => 'Surcharge percentage never be less then 0',
            'percentage.max' => 'Surcharge percentage never be greater then 100',
        ];
        $this->validate($request, $rules, $customMessages);
        return Surcharge::where('id', $request->id)->update([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'company_id' => Auth::user()->company_id,
            'is_active'=> !isset($request->is_Active) ? 0 : $request->is_Active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSurcharge(Request $request)
    {
        return Surcharge::find($request->id)->delete();
    }

    public function selectiveSurcharge()
    {
        return Surcharge::where('company_id', Auth::user()->company_id)->where('is_active', 1)->get();
    }
}
