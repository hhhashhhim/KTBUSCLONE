<?php

namespace App\Http\Controllers\Maintenance\Part;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\Part\MaintenancePart;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetMaintenancePartController extends Controller
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
            return Department::with('addedBy', 'company')->where('company_id', $this->company_id)->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('fleet_maintenance_parts', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Part Name is Required!',
            'name.unique' => 'Part Name Already Registred !',
        ];
        $this->validate($request, $rules, $customMessages);

        return MaintenancePart::create([
            'name' => $request->name,
            'added_by' => Auth::user()->id,
            'company_id' => $this->company_id,
        ]);

    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required', Rule::unique('departments', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Department Name is Required!',
            'name.unique' => 'Department Name Already Registred !',
        ];
        $this->validate($request, $rules, $customMessages);
        return Department::where('id', $request->id)->update([
            'name' => $request->name,
        ]);


    }

    public function delete(Request $request)
    {
        return Department::find($request->id)->delete();
    }
}
