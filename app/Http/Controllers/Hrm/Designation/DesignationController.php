<?php

namespace App\Http\Controllers\Hrm\Designation;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Hrm\Designation\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
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
        return Department::withCount('designation')->with('addedBy')->where('company_id', $this->company_id)->get();
    }

    public function edit(Request $request)
    {
        return Designation::with('addedBy')->where('department_id', $request->id)->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('designations', 'name')->where('department_id', $request->department)->where('company_id', $this->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Designation Name is Required!',
            'name.unique' => 'Designation Name Already Registered Against this Department/Company !',
        ];
        $this->validate($request, $rules, $customMessages);
        return Designation::create([
            'department_id' => $request->department,
            'name' => $request->name,
            'added_by' => Auth::user()->id,
            'company_id' => $this->company_id,
        ]);

    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('designations', 'name')->where('department_id', $request->department_id)->where('company_id', $this->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Department Name is Required!',
            'name.unique' => 'Designation Name Already Registered Against this Department/Company !',
        ];
        $this->validate($request, $rules, $customMessages);
        return Designation::where('id', $request->id)->update([
            'department_id' => $request->department_id,
            'name' => $request->name,
        ]);
    }

    public function delete(Request $request)
    {
        return Designation::find($request->id)->delete();
    }

    public function selective(Request $request)
    {
        return Designation::where('department_id', $request->id)->get();
    }
}
