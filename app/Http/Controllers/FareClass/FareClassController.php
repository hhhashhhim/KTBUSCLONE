<?php

namespace App\Http\Controllers\FareClass;

use App\Http\Controllers\Controller;
use App\Models\FareClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class FareClassController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    protected function index()
    {
        return FareClass::orderBy('id')->where('company_id', $this->company_id)->select('id', 'name', 'is_active')->get(['id', 'name', 'is_active']);
    }

    public function storeFareClass(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('fare_classes', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')]
        ];

        $customMessages = [
            'name.required' => 'Fare Class Name is Required!',
            'name.unique' => 'Fare Class Name is already available!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::create([
            'name' => $request->name,
            'is_active' => $request->active,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateFareClass(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $customMessages = [
            'name.required' => 'FareClass Name is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::where('id', $request->id)->update([
            'name' => $request->name,
            'company_id' => $this->company_id,
            'is_active' => !isset($request->is_Active) ? 0 : $request->is_Active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteFareClass(Request $request)
    {
        return FareClass::find($request->id)->delete();
    }
}
