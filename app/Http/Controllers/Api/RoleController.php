<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin\Role;
use App\Models\Company;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with('company:id,name')->latest('id')->get();
    }
    public function role(Request $request)
    {
        $permissions = collect($this->permissions());
        $role = Role::with('company:id,name')->find($request->id);
        $companyPermissions = Company::find($role->company_id)->modules;
        $modules = [];

        foreach ($companyPermissions??[] as $key => $module) {
            foreach ($module as $name => $column) {
                if ($column == true) {
                    $modules[] = $name;
                }
            }
        }
        
        $permissions = $permissions->whereIn('name', $modules);
        return response()->json([
            'role' => $role,
            'permissions' => $permissions,
        ], 200);
    }
    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'company_id' => auth()->user()->is_super_admin == 0 ? auth()->user()->company_id : $request->company_id,
            'permissions' => [],
        ]);
        return Role::with('company')->find($role->id);
    }
    public function update(Request $request)
    {
        return $request;
        Role::find($request->id)->update([
            'name' => $request->name,
            'company_id' => auth()->user()->is_super_admin == 0 ? auth()->user()->company_id : $request->company_id,
            'permissions' => $request->permissions,
        ]);
        return response()->json([
            'message' => 'updated successfully',
        ], 201);
    }
    public function delete(Request $request)
    {
        return Role::find($request->id)->delete();
    }
    public function permissions()
    {
        return $permissions = [
            [
                'name' => "roles",
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
            ],
            [
                'name' => "users",
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
            ],
            [
                'name' => "profile",
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
            ],
            [
                'name' => "hrm",
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
            ],
            [
                'name' => "accounts",
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
            ]
        ];
    }
}
