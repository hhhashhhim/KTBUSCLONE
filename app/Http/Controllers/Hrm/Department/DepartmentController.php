<?php

namespace App\Http\Controllers\Hrm\Department;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
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
        return Department::with('addedBy', 'company', 'terminal:id,name,city_id', 'terminal.city:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('departments', 'name')->where('company_id', Auth::user()->company_id,)->where('terminal_id', $request->terminal)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Department Name is Required!',
            'name.unique' => 'Department Name Already Registered Against this Terminal!',
        ];
        $this->validate($request, $rules, $customMessages);
        return Department::create([
            'name' => $request->name,
            'terminal_id' => $request->terminal,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);

    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('departments', 'name')->where('company_id', Auth::user()->company_id)->where('terminal_id', $request->terminal_id)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Department Name is Required!',
            'name.unique' => 'Department Name Already Registered Against this Terminal !',
        ];
        $this->validate($request, $rules, $customMessages);
        return Department::where('id', $request->id)->update([
            'name' => $request->name,
            'terminal_id' => $request->terminal_id,
        ]);


    }

    public function delete(Request $request)
    {
        return Department::find($request->id)->delete();
    }

    public function selective(Request $request)
    {
        return Department::where('terminal_id', $request->id)->get(['id', 'name', 'terminal_id']);
    }
}
