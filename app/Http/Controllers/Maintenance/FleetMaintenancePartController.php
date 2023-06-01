<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FleetMaintenancePartController extends Controller
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
        return MaintenancePart::with('addedBy', 'company')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
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
                $part = MaintenancePart::where('id', $request->id)->update([
                    'name' => $request->name,
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
