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
        $role = Role::with('company:id,name')->find($request->id);
        $permissions = collect($this->permissions());
        $company = Company::find($role->company_id);
        if ($company) {
            return response()->json([
                'role' => $role,
                'permissions' => $company->modules,
            ], 200);
        }else{
            return response()->json([
                'Message' => "Company Not Found !!!!",
            ], 400);
        }
        
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
            ]
        ];
    }
}
// :checked="mod"
// :value="true"