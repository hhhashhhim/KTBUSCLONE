<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\admin\Role;
use App\Models\Company;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("roles"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Role::with('company:id,name')->where('company_id',Auth::user()->company_id)->latest('id')->get();
    }
    public function role(Request $request)
    {
        if(!checkForSubmenu("roles"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $role = Role::with('company:id,name')->find($request->id);
        $company = Company::find($role->company_id);
        if ($company) {

            if ($role->permissions) {
                $modules = $role->permissions;
            }
            else{
                $modules = [];
                foreach ($company->modules as $i => $module) {

                    if ($module['allow'] == false) {
                        continue;
                        // If Main Module is not in permission then don't check its sub modules and return back to next module.
                    }
                    $module['allow'] = false;
                    // Setting default false value of main module so that the role does't inherit true as per from company permissions
                    foreach ($module['childs'] as $j => $childModule) {

                        if ($module['childs'][$j]['allow'] == false) {
                            unset($module['childs'][$j]);
                            continue;
                            // If sub module is false then vanish it from the permissions also
                        }
                        $module['childs'][$j]['allow'] = false;
                        // Setting default false value so that the role does't inherit true as per from company permissions

                    }
                    $modules[] = $module;

                }

            }

            return response()->json([
                'role' => $role,
                'permissions' => $modules,
            ], 200);

        }else{
            return response()->json([
                'Message' => "Company Not Found !!!!",
            ], 400);
        }

    }
    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-role"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $this->validate($request, [
            'name' => 'required',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'company_id' => Auth::user()->company_id,
            'permissions' => [],
        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | added role ($request->name)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return Role::with('company')->find($role->id);
    }
    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-role"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        Role::find($request->id)->update([
            'name' => $request->name,
            'company_id' => auth()->user()->is_super_admin == 0 ? auth()->user()->company_id : $request->company_id,
            'permissions' => $request->permissions,
        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | updated role ($request->name)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return response()->json([
            'message' => 'updated successfully',
        ], 201);
    }
    // public function delete(Request $request)
    // {
    //     return Role::find($request->id)->delete();
    // }
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
