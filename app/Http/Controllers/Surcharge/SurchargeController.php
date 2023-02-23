<?php

namespace App\Http\Controllers\Surcharge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surcharge\StoreSurchargeRequest;
use App\Models\Surcharge\Surcharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SurchargeController extends Controller
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
        return Surcharge::with('addedBy')->orderBy('id')->where('company_id', Auth::user()->company_id)->get();
    }

    public function storeSurcharge(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('schedules', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Surcharge Name is Required!',
            'name.unique' => 'Surcharge Name not be Repeated!',
        ];
        $this->validate($request, $rules, $customMessages);
        return Surcharge::create([
            'name' => $request->name,
            'type' => $request->type,
            'percentage' => $request->type == "percentage" ? $request->percentage : null,
            'flat' => $request->type == "flat" ? $request->flat : null,
            'company_id' => Auth::user()->company_id,
            'is_active' => $request->active,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateSurcharge(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Surcharge Name is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return Surcharge::where('id', $request->id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'percentage' => $request->type == "percentage" ? $request->percentage: null,
            'flat' => $request->type == "flat" ? $request->flat : null,
            'is_active'=> $request->is_active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteSurcharge(Request $request)
    {
        return Surcharge::find($request->id)->delete();
    }
}
