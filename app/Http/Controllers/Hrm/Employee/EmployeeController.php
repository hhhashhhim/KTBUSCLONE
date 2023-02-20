<?php

namespace App\Http\Controllers\Hrm\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee\Employee;
use App\Models\User;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\isNull;

class EmployeeController extends Controller
{
    public function index()
    {
        return Employee::with('addedBy', 'company', 'department', 'designation', 'user', 'terminal.city')->where('company_id', Auth::user()->company_id)->get();

    }

    public function store(Request $request)
    {
        if ($request->createAccount == 1) {
            $rulesAcc = [
                "email" => 'required|email|unique:users',
                "password" => 'required',
            ];
            $this->validate($request, $rulesAcc);
        }

        $rules = [
            'EmployeeName' => 'required',
//            'EmployeeFatherName' => 'required',
            'EmployeeContact' => ['required', Rule::unique('employees', 'contact')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            'EmployeeCNIC' => ['required', Rule::unique('employees', 'cnic')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
            'EmployeeDob' => 'required',
//            'HiringDate' => 'required',
//            'EmployeeAddress' => 'required',
//            'workingDays' => 'required',
//            'paidLeaves' => 'required',
//            'bloodGroup' => 'required',
//            'EmployeeSalary' => 'required',
            'profile' => 'required',
        ];

        $customMessages = [
            'EmployeeName.required' => 'Employee Name is Required!',
//            'EmployeeFatherName.required' => 'Employees Father Name is Required!',
            'EmployeeContact.required' => 'Employee Contact Number is Required!',
            'EmployeeContact.unique' => 'Employee Contact Number Already Taken!',
            'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
            'EmployeeCNIC.unique' => 'Every Employee Must Have Unique CNIC NUmber',
            'EmployeeDob.required' => 'Employee Date of Birth is Required!',
//            'HiringDate.required' => 'Employee Hiring Date is Required!',
//            'EmployeeAddress.required' => 'Employee Mailing Address is Required!',
//            'workingDays.required' => 'Working Days is Required!',
//            'paidLeaves.required' => 'Paid Leaves is Required!',
//            'bloodGroup.required' => 'Blood Group is Required!',
//            'EmployeeSalary.required' => 'Employee Salary is Required!',
            'profile.required' => 'Employee Profile is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        if ($request->createAccount == 1) {
            $user = User::create([
                "name" => $request->EmployeeName,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "terminal_id" => $request->EmployeeTerminal,
                "contact" => plainContactAndCnic($request->EmployeeContact),
                "role_id" => 0,
                'company_id' => Auth::user()->company_id,
            ]);
            UserPassword::create([
                'user_id' => $user->id,
                'user_password' => $request->password,
                'added_by' => Auth::user()->id,
                'company_id' => Auth::user()->company_id,
            ]);
        }
        return Employee::create([
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
    }

    public function update(Request $request)
    {
//        dd($request->all());
        $rules = [
            'EmployeeName' => 'required',
//            "email" => 'required|email|unique:users,email,' . $request->userId,
//            'EmployeeFatherName' => 'required',
            'EmployeeContact' => 'required',
            'EmployeeCNIC' => 'required',
            'EmployeeDob' => 'required',
//            'HiringDate' => 'required',
//            'EmployeeAddress' => 'required',
//            'workingDays' => 'required',
//            'paidLeaves' => 'required',
//            'bloodGroup' => 'required',
//            'EmployeeSalary' => 'required',
        ];

        $customMessages = [
            'EmployeeName.required' => 'Employee Name is Required!',
//            'EmployeeFatherName.required' => 'Employees Father Name is Required!',
            'EmployeeContact.required' => 'Employee Contact Number is Required!',
            'EmployeeCNIC.required' => 'Employee CNIC Number  is Required!',
            'EmployeeDob.required' => 'Employee Date of Birth is Required!',
//            'HiringDate.required' => 'Employee Hiring Date is Required!',
//            'EmployeeAddress.required' => 'Employee Mailing Address is Required!',
//            'workingDays.required' => 'Working Days is Required!',
//            'paidLeaves.required' => 'Paid Leaves is Required!',
//            'bloodGroup.required' => 'Blood Group is Required!',
//            'EmployeeSalary.required' => 'Employee Salary is Required!',
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::where("id", $request->userId)->update([
            "name" => $request->EmployeeName,
            "email" => $request->email,
            "contact" => plainContactAndCnic($request->EmployeeContact),
            "terminal_id" => $request->EmployeeTerminal,
            "role_id" => 0,
        ]);

        if ($request->password) {
            $user = User::where("id", $request->userId)->update([
                "password" => Hash::make($request->password),
            ]);

            UserPassword::where("user_id",$request->id)->update([
                'user_password' => $request->password,
            ]);
        }

        Employee::find($request->id)->update([
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
            'job_description' => $request->jobDescription,
            'department_id' => $request->EmployeeDepartment,
            'designation_id' => $request->EmployeeDesignation,
            'status' => $request->status,
            "terminal_id" => $request->EmployeeTerminal,
        ]);

        if ($request->profile) {
            Employee::where("user_id", $request->userId)->update([
                'profile_Img' => $this->image($request->profile),
            ]);
        }

        if ($request->attachment) {
            Employee::where("user_id", $request->userId)->update([
                'attachments' => $this->attachment($request->attachment),
            ]);
        }
    }

    public function delete(Request $request)
    {
        return Employee::find($request->id)->delete();

    }

    // Image Upload
    public function image($image)
    {

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename = pathinfo($filenameWithExt);
        //get just extension
        $extension = $image->extension();
        $nameToStore = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $image->move(public_path('uploads/hrm/employee/profile/'), $nameToStore);
        return $nameToStore;
    }

    public function attachment($image)
    {

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename = pathinfo($filenameWithExt);
        //get just extension
        $extension = $image->extension();
        $nameToStore = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path = $image->move(public_path('uploads/hrm/employee/attachment/'), $nameToStore);
        return $nameToStore;
    }


}
