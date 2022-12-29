<?php

namespace App\Http\Controllers\Hrm\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\isNull;

class EmployeeController extends Controller
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

        return Employee::with('addedBy', 'company', 'department', 'designation', 'user')->where('company_id', $this->company_id)->get();

    }

    public function store(Request $request)
    {
        $rules = [
            'EmployeeName' => 'required',
            "email" => 'required|email|unique:users',
            "password" => 'required',
            'EmployeeFatherName' => 'required',
            'EmployeeContact' => ['required', Rule::unique('employees', 'contact')->where('company_id', $this->company_id)->whereNull('deleted_at')],
            'EmployeeCNIC' => ['required', Rule::unique('employees', 'cnic')->where('company_id', $this->company_id)->whereNull('deleted_at')],
            'EmployeeDob' => 'required',
            'HiringDate' => 'required',
            'EmployeeAddress' => 'required',
            'workingDays' => 'required',
            'paidLeaves' => 'required',
            'bloodGroup' => 'required',
            'EmployeeSalary' => 'required',
        ];

        $customMessages = [
            'EmployeeName.required' => 'Employee Name is Required!',
            'EmployeeFatherName.required' => 'Employees Father Name is Required!',
            'EmployeeContact.required' => 'Employee Contact Number is Required!',
            'EmployeeContact.unique' => 'Employee Contact Number Already Taken!',
            'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
            'EmployeeCNIC.unique' => 'Every Employee Must Have Unique CNIC NUmber',
            'EmployeeDob.required' => 'Employee Date of Birth is Required!',
            'HiringDate.required' => 'Employee Hiring Date is Required!',
            'EmployeeAddress.required' => 'Employee Mailing Address is Required!',
            'workingDays.required' => 'Working Days is Required!',
            'paidLeaves.required' => 'Paid Leaves is Required!',
            'bloodGroup.required' => 'Blood Group is Required!',
            'EmployeeSalary.required' => 'Employee Salary is Required!',
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::create([
            "name" => $request->EmployeeName,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "contact" => str_replace('-', '', $request->EmployeeContact),
            "role_id" => 0,
            'company_id' => $this->company_id,
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'name' => $request->EmployeeName,
            'f_name' => $request->EmployeeFatherName,
            'cnic' => str_replace('-', '', $request->EmployeeCNIC),
            'contact' => str_replace('-', '', $request->EmployeeContact),
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
            'job_description' => $request->jobDescription,
            'department_id' => $request->EmployeeDepartment,
            'designation_id' => $request->EmployeeDesignation,
            'profile_Img' =>  $request->profile ? $this->image($request->profile) : null,
            'attachments' =>  $request->attachment ? $this->attachment($request->attachment) : null,
            'status' => 'W',
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'EmployeeName' => 'required',
            "email" => 'required|email|unique:users,email,'.$request->userId,
            'EmployeeFatherName' => 'required',
            'EmployeeContact' => 'required',
            'EmployeeCNIC' => 'required',
            'EmployeeDob' => 'required',
            'HiringDate' => 'required',
            'EmployeeAddress' => 'required',
            'workingDays' => 'required',
            'paidLeaves' => 'required',
            'bloodGroup' => 'required',
            'EmployeeSalary' => 'required',
        ];

        $customMessages = [
            'EmployeeName.required' => 'Employee Name is Required!',
            'EmployeeFatherName.required' => 'Employees Father Name is Required!',
            'EmployeeContact.required' => 'Employee Contact Number is Required!',
            'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
            'EmployeeDob.required' => 'Employee Date of Birth is Required!',
            'HiringDate.required' => 'Employee Hiring Date is Required!',
            'EmployeeAddress.required' => 'Employee Mailing Address is Required!',
            'workingDays.required' => 'Working Days is Required!',
            'paidLeaves.required' => 'Paid Leaves is Required!',
            'bloodGroup.required' => 'Blood Group is Required!',
            'EmployeeSalary.required' => 'Employee Salary is Required!',
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::where("id",$request->userId)->update([
            "name" => $request->EmployeeName,
            "email" => $request->email,
            "contact" => str_replace('-', '', $request->EmployeeContact),
            "role_id" => 0,
        ]);

        if($request->password)
        {
            $user = User::where("id",$request->userId)->update([
                "password" => Hash::make($request->password),
            ]);
        }

        Employee::where("user_id",$request->userId)->update([
            'name' => $request->EmployeeName,
            'f_name' => $request->EmployeeFatherName,
            'cnic' => str_replace('-', '', $request->EmployeeCNIC),
            'contact' => str_replace('-', '', $request->EmployeeContact),
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
            'job_description' => $request->jobDescription,
            'department_id' => $request->EmployeeDepartment,
            'designation_id' => $request->EmployeeDesignation,
            'status' => $request->status,
        ]);

        if($request->profile)
        {
            Employee::where("user_id",$request->userId)->update([
                'profile_Img' =>  $this->image($request->profile),
            ]);
        }

        if($request->attachment)
        {
            Employee::where("user_id",$request->userId)->update([
                'attachments' =>  $this->attachment($request->attachment),
            ]);
        }
    }

    public function delete(Request $request)
    {
        return Employee::find($request->id)->delete();

    }

    // Image Upload
    public function image($image){

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename        = pathinfo($filenameWithExt);
        //get just extension
        $extension       = $image->extension();
        $nameToStore     = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path            = $image->move(public_path('uploads/hrm/employee/profile/'), $nameToStore);
        return $nameToStore;
    }

    public function attachment($image){

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename        = pathinfo($filenameWithExt);
        //get just extension
        $extension       = $image->extension();
        $nameToStore     = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path            = $image->move(public_path('uploads/hrm/employee/attachment/'), $nameToStore);
        return $nameToStore;
    }


}
