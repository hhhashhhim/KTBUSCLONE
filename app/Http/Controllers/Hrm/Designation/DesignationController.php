<?php

namespace App\Http\Controllers\Hrm\Designation;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Hrm\Designation\Designation;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DesignationController extends Controller
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
        return Department::withCount('designation')->with('addedBy', 'terminal:id,name,city_id', 'terminal.city:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function edit(Request $request)
    {
        return Designation::with('addedBy')->where('department_id', $request->id)->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('designations', 'name')->where('department_id', $request->department)->where('terminal_id', $request->terminal)->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'name.required' => 'Designation Name is Required!',
                    'name.unique' => 'Designation Name Already Registered Against this Department/Terminal! Please Select other Terminal or Department',
                ];
                $this->validate($request, $rules, $customMessages);
                $designation =  Designation::create([
                    'terminal_id' => $request->terminal,
                    'department_id' => $request->department,
                    'name' => $request->name,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added designation (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $designation;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function update(Request $request)
    {
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('designations', 'name')->where('department_id', $request->department_id)->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],
                ];

                $customMessages = [
                    'name.required' => 'Department Name is Required!',
                    'name.unique' => 'Designation Name Already Registered Against this Department/Company !',
                ];
                $this->validate($request, $rules, $customMessages);
                $designation =  Designation::where('id', $request->id)->update([
                    'department_id' => $request->department_id,
                    'name' => $request->name,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated designation (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $designation;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function delete(Request $request)
    {
        return Designation::find($request->id)->delete();
    }

    public function selective(Request $request)
    {
        return Designation::where('department_id', $request->id)->get(['id', 'name', 'terminal_id', 'department_id']);
    }

    public function getTerminal(Request $request)
    {
      return  Department::where([
            'company_id' => Auth::user()->company_id,
            'terminal_id' => $request->id,
            ])->get(['id', 'name', 'terminal_id']);
    }


}
