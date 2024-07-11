<?php

namespace App\Http\Controllers;

use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function index()
    {
        return Company::orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        try {
                DB::beginTransaction();
                $request->validate([
                    'name' => ['required', Rule::unique('companies', 'name')],
                    'contact' => 'required',
                    'userName' => 'required',
                    'email' => ['required', Rule::unique('users', 'email')],
                    'password' => 'required',
                ]);

                $company = Company::create([
                    'name' => $request->name,
                    'user_name' => $request->userName,
                    'contact' => plainContactAndCnic($request->contact),
                    'location' => $request->location,
                    'modules' => $request->modules,
                    'logo' => $request->logo,
                    'added_by' => auth()->user()->id,
                ]);

                $role = Role::create([
                    'name' => 'admin',
                    'company_id' => $company->id,
                    'permissions' => $request->modules,
                ]);

                $user = User::create([
                    'name' => $request->userName,
                    'email' => $request->email,
                    'contact' => plainContactAndCnic($request->contact),
                    'password' => Hash::make($request->password),
                    'role_id' => $role->id,
                    'destination_city_ids' => "all",
                    'departure_city_ids' => "all",
                    'company_id' => $company->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added company ($request->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $company;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }


    public function logoUpload(Request $request)
    {
        if ($request->logo) {
            $name = $this->image($request->logo);
            return response(['name' => $name], 200);
        } else {
            return;
        }

    }

    public function update(Request $request)
    {
        try {
                DB::beginTransaction();
                $request->validate([
                    'name' => 'required',
                    'contact' => 'required',
                    'email' => 'required|email',
                ]);
                $company = Company::find($request->id);
                $company->update([
                    'name' => $request->name,
                    'user_name' => $request->user_name,
                    'contact' => plainContactAndCnic($request->contact),
                    'location' => $request->location,
                    'modules' => $request->modules,
                    'whatsapp_auth_key' => $request->whatsapp_auth_key,
                    'added_by' => auth()->user()->id,
                ]);
                if ($request->logo) {
                    Company::find($request->id)->update([
                        'logo' => $request->logo,
                    ]);
                }
                User::where('company_id', $request->id)->first()->update([
                    'name' => $request->name,
                    'contact' => plainContactAndCnic($request->contact),
                    'email' => $request->email,
                ]);
                if ($request->password) {
                    User::where('company_id', $request->id)->first()->update([
                        'password' => Hash::make($request->password),
                    ]);
                }
                Role::where('company_id', $request->id)->where('name', 'admin')->update([
                    'permissions' => $request->modules,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated company ($company->name)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return response()->json([
                    'message' => "Updated Successfully",
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }

    }

    public function delete(Request $request)
    {
        return Company::find($request->id)->delete();
    }

    public function company_roles(Request $request)
    {
        return Role::where('company_id', Auth::user()->company_id)->get();
    }

    public function company(Request $request)
    {
        return Company::where('companies.id', $request->id)
            ->join('users', 'companies.id', 'users.company_id')
            ->select('companies.*', 'users.name as userName', 'users.email')
            ->first();
    }

    public function image($image)
    {
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
            . "_" . time() . '.' . $image->extension();
        $image->move(public_path('uploads/company/logo/'), $imageName);
        return $imageName;
    }

}
