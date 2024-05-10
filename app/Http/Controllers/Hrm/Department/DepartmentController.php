<?php

namespace App\Http\Controllers\Hrm\Department;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Terminal;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("departments"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Department::with('addedBy', 'company', 'terminal:id,name,city_id', 'terminal.city:id,name')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-department"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('departments', 'name')->where('company_id', Auth::user()->company_id,)->where('terminal_id', $request->terminal)->whereNull('deleted_at')],

                ];

                $customMessages = [
                    'name.required' => 'Department Name is Required!',
                    'name.unique' => 'Department Name Already Registered Against this Terminal!',
                ];
                $this->validate($request, $rules, $customMessages);
                $department =  Department::create([
                    'name' => $request->name,
                    'terminal_id' => $request->terminal,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added department (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $department;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-department"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('departments', 'name')->where('company_id', Auth::user()->company_id)->where('terminal_id', $request->terminal_id)->whereNull('deleted_at')->ignore($request->id)],

                ];

                $customMessages = [
                    'name.required' => 'Department Name is Required!',
                    'name.unique' => 'Department Name Already Registered Against this Terminal !',
                ];
                $this->validate($request, $rules, $customMessages);
                $department =  Department::where('id', $request->id)->update([
                    'name' => $request->name,
                    'terminal_id' => $request->terminal_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated department (".$request->name.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $department;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }


    }

    public function delete(Request $request)
    {
        if(!checkForSubmenu("departments"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Department::find($request->id)->delete();
    }

    public function selective(Request $request)
    {
        if(!checkForSubmenu("departments"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Department::where('terminal_id', $request->id)->get(['id', 'name', 'terminal_id']);
    }

    public function allTerminals()
    {
        if(!checkForSubmenu("departments"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::with('city')->where('company_id', Auth::user()->company_id)->get(['id', 'name', 'city_id']);
    }
}
