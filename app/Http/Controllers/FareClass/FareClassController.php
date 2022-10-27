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
        return FareClass::with('addedBy')->where('is_active', 1)
        ->where('company_id', $this->company_id)->orderBy('id')
        ->get();
    }

    public function storeFareClass(Request $request)
    {
        $rules = [
            'FareClassName' => ['required', Rule::unique('fare_classes', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
            'FareClassColor' => 'required',
        ];

        $customMessages = [
            'FareClassName.required' => 'Fare Class Name is Required!',
            'name.unique' => 'Fare Class Name is already available!',
            'FareClassColor.required' => 'Fare Class Color is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::create([
            'name' => $request->FareClassName,
            'color' => $request->FareClassColor,
            'is_active' => $request->isActive,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateFareClass(Request $request)
    {
        $rules = [
            'name' => 'required',
            'color' => 'required',
        ];

        $customMessages = [
            'name.required' => 'FareClass Name is Required!',
            'color.required' => 'FareClass Color is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::where('id', $request->id)->update([
            'name' => $request->name,
            'color' => $request->color,
            'is_active' => !isset($request->is_Active) ? 0 : $request->is_Active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteFareClass(Request $request)
    {
        return FareClass::find($request->id)->delete();
    }
}
