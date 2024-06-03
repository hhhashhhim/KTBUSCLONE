<?php

namespace App\Http\Controllers\Hrm\Employee;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hrm\Employee\Employee;
use App\Models\User;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\isNull;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("employees"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Employee::with('addedBy', 'company', 'department', 'designation', 'user', 'terminal.city')->where(['company_id'=> Auth::user()->company_id,"hide"=>0])->get();

    }

    public function getCities()
    {
        if(!checkForSubmenu("employees"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $cities = City::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
        foreach ($cities as $single) {
            $single->name = ucfirst($single->name);
        }
        return $cities;
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-employee"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                if ($request->createAccount == 1) {
                    $rulesAcc = [
                        "email" => 'required|email|unique:users',
                        "password" => 'required',
                    ];
                    $this->validate($request, $rulesAcc);
                }

                $rules = [
                    'EmployeeName' => 'required',
                    'EmployeeContact' => ['required', Rule::unique('employees', 'contact')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'EmployeeCNIC' => ['required', Rule::unique('employees', 'cnic')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                    'EmployeeDob' => 'required',
                ];
                $customMessages = [
                    'EmployeeName.required' => 'Employee Name is Required!',
                    'EmployeeContact.required' => 'Employee Contact Number is Required!',
                    'EmployeeContact.unique' => 'Employee Contact Number Already Taken!',
                    'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
                    'EmployeeCNIC.unique' => 'Every Employee Must Have Unique CNIC NUmber',
                    'EmployeeDob.required' => 'Employee Date of Birth is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                if ($request->createAccount == 1) {
                    $user = User::create([
                        "name" => $request->EmployeeName,
                        "email" => $request->email,
                        "password" => Hash::make($request->password),
                        "terminal_id" => $request->EmployeeTerminal,
                        "contact" => plainContactAndCnic($request->EmployeeContact),
                        "role_id" => $request->role ?? 0,
                        'company_id' => Auth::user()->company_id,
                    ]);
                    UserPassword::create([
                        'user_id' => $user->id,
                        'user_password' => $request->password,
                        'added_by' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id,
                    ]);
                }
                $employee =  Employee::create([
                    'user_id' => $request->createAccount == 1 ? $user->id : 0,
                    'name' => $request->EmployeeName,
                    'f_name' => $request->EmployeeFatherName,
                    'cnic' => plainContactAndCnic($request->EmployeeCNIC),
                    'contact' => plainContactAndCnic($request->EmployeeContact),
                    'address' => $request->EmployeeAddress,
                    'reference' => $request->RefHiring,
                    'hiring_date' => $request->HiringDate,
                    'dob' => $request->EmployeeDob,
                    'salary' => $request->EmployeeSalary,
                    'salary_type' => $request->RadioSalaryTypeAdd,
                    'working_days' => $request->workingDays,
                    'paid_leaves' => $request->paidLeaves,
                    'blood_group' => $request->bloodGroup,
                    'emergency_contact' => $request->EmergencyContact,
                    "terminal_id" => $request->EmployeeTerminal,
                    "employee_type" => $request->EmployeeType,
                    'job_description' => $request->jobDescription,
                    'department_id' => $request->EmployeeDepartment,
                    'designation_id' => $request->EmployeeDesignation,
                    'profile_Img' => $request->profile ? $this->image($request->profile) : null,
                    'attachments' => $request->attachment ? $this->attachment($request->attachment) : null,
                    'status' => 'W',
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added employee cnic (".$request->EmployeeCNIC.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $employee;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-employee"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        
        try {
                DB::beginTransaction();
                $rules = [
                    'EmployeeName' => 'required',
                    'EmployeeContact' => 'required',
                    'EmployeeCNIC' => 'required',
                    'EmployeeDob' => 'required',
                ];

                $customMessages = [
                    'EmployeeName.required' => 'Employee Name is Required!',
                    'EmployeeContact.required' => 'Employee Contact Number is Required!',
                    'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
                    'EmployeeDob.required' => 'Employee Date of Birth is Required!',
                ];
                $this->validate($request, $rules, $customMessages);

                User::where("id", $request->userId)->update([
                    "name" => $request->EmployeeName,
                    "contact" => plainContactAndCnic($request->EmployeeContact),
                    "terminal_id" => $request->EmployeeTerminal,
                    "role_id" => 0,
                ]);

                if ($request->password) {
                    User::where("id", $request->userId)->update([
                        "password" => Hash::make($request->password),
                    ]);

                    UserPassword::where("user_id", $request->id)->update([
                        'user_password' => $request->password,
                    ]);
                }
                
                $convertToNull = function($value) {
                    return ($value === 'null' ? null : $value);
                };

                Employee::find($request->id)->update([
                    'name' => $request->EmployeeName,
                    'f_name' => $request->EmployeeFatherName,
                    'cnic' => plainContactAndCnic($request->EmployeeCNIC),
                    'contact' => plainContactAndCnic($request->EmployeeContact),
                    'address' => $request->EmployeeAddress,
                    'reference' => $request->RefHiring,
                    'hiring_date' => $convertToNull($request->HiringDate),
                    'dob' => $request->EmployeeDob,
                    'salary' => $convertToNull($request->EmployeeSalary),
                    'salary_type' => $request->RadioSalaryTypeAdd,
                    'working_days' => $convertToNull($request->workingDays),
                    'paid_leaves' => $request->paidLeaves,
                    'blood_group' => $request->bloodGroup,
                    'emergency_contact' => $request->EmergencyContact,
                    'job_description' => $request->jobDescription,
                    'department_id' => $request->EmployeeDepartment,
                    'designation_id' => $request->EmployeeDesignation,
                    'status' => $request->status,
                    "terminal_id" => $request->EmployeeTerminal,
                ]);
                if ($request->profile) {
                    Employee::where("id", $request->id)->update([
                        'profile_Img' => $this->image($request->profile),
                    ]);
                }
                if ($request->attachment) {
                    Employee::where("user_id", $request->userId)->update([
                        'attachments' => $this->attachment($request->attachment),
                    ]);
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated employee cnic (".$request->EmployeeCNIC.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function hideEmployee(Request $request)
    {
        if(!checkPermissionButtons("delete-employee"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $employee = Employee::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | deleted employee cnic (".$employee->cnic.")",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $employee->update([
            "hide" => 1
        ]);
    }

    public function userStore(Request $request)
    {
        if(!checkPermissionButtons("add-employee"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'contact' => formatContact($request->contact),
                    'password' => Hash::make($request->password),
                    'terminal_id' => $request->terminal_id,
                    'destination_city_ids' => json_encode($request->destination),
                    'departure_city_ids' => json_encode($request->departure),
                    'company_id' => Auth::user()->company_id,
                ]);
                $employee = Employee::where('id', $request->employee_id)->first();
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | created user (".$request->email.") of employee cnic (".$employee->cnic.")",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                $employee->update([
                    'user_id' => $user->id,
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    // Image Upload
    public function image($image)
    {
        $filenameWithExt = $image->getClientOriginalName();
        $filename = pathinfo($filenameWithExt);
        $extension = $image->extension();
        $nameToStore = $filename['filename'] . "_" . time() . "." . $extension;
        $image->move(public_path('uploads/hrm/employee/profile/'), $nameToStore);
        return $nameToStore;
    }

    public function attachment($image)
    {
        $filenameWithExt = $image->getClientOriginalName();
        $filename = pathinfo($filenameWithExt);
        $extension = $image->extension();
        $nameToStore = $filename['filename'] . "_" . time() . "." . $extension;
        $path = $image->move(public_path('uploads/hrm/employee/attachment/'), $nameToStore);
        return $nameToStore;
    }


}
