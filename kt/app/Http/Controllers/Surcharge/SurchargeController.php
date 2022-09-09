<?php

namespace App\Http\Controllers\Surcharge;

use App\Http\Controllers\Controller;
use App\Models\Surcharge\Surcharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurchargeController extends Controller
{
    public function index()
    {
        return Surcharge::orderBy('id')->select('name', 'id', 'percentage', 'is_active')->get();
    }

    public function storeSurcharge(Request $request)
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
        return Surcharge::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'is_active' => $request->active === "yes" ? 1 : 0,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateSurcharge(Request $request)
    {
        dd($request->all());
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
            'is_active' => $request->active === "yes" ? 1 : 0,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSurcharge(Request $request)
    {
        return Surcharge::find($request->id)->delete();
    }
}
