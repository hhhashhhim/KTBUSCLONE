<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FleetMaintenancePartController extends Controller
{

    public function index()
    {
        if(!checkForSubmenu("part"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return MaintenancePart::with('addedBy', 'company')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-part"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('fleet_maintenance_parts', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

                ];

                $customMessages = [
                    'name.required' => 'Part Name is Required!',
                    'name.unique' => 'Part Name Already Registred !',
                ];
                $this->validate($request, $rules, $customMessages);

                $part =  MaintenancePart::create([
                    'name' => $request->name,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added maintenance part ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $part;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-part"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'name' => ['required', Rule::unique('fleet_maintenance_parts', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')->ignore($request->id)],

                ];

                $customMessages = [
                    'name.required' => 'Part Name is Required!',
                    'name.unique' => 'Part Name Already Registred !',
                ];
                $this->validate($request, $rules, $customMessages);
                $part = MaintenancePart::where('id', $request->id)->update([
                    'name' => $request->name,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated maintenance part ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $part;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }


    }
}
