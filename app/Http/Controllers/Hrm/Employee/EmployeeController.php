<?php

namespace App\Http\Controllers\Hrm\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Employee::with('addedBy', 'company', 'department', 'designation')->where('company_id', $this->company_id)->get();

    }

    public function store(Request $request)
    {
        $rules = [
            'EmployeeName' => 'required',
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
        return Employee::create([
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
            'profile_Img' => !is_null($request->ImgEmployeeRecord) ? $request->ImgEmployeeRecord['profile'] : null,
            'cnic_back_img' => !is_null($request->ImgEmployeeRecord) ? $request->ImgEmployeeRecord['back'] : null,
            'cnic_front_img' => !is_null($request->ImgEmployeeRecord) ? $request->ImgEmployeeRecord['front'] : null,
            'status' => 'W',
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => 'required',
            'f_name' => 'required',
            'contact' => 'required',
            'cnic' => 'required',
            'dob' => 'required',
            'hiring_date' => 'required',
            'address' => 'required',
            'working_days' => 'required',
            'paid_leaves' => 'required',
            'blood_group' => 'required',
            'salary' => 'required',
        ];

        $customMessages = [
            'name.required' => 'Employee Name is Required!',
            'f_name.required' => 'Employees Father Name is Required!',
            'contact.required' => 'Employee Contact Number is Required!',
            'cnic.required' => 'Employee CNIC Number  is Required!',
            'dob.required' => 'Employee Date of Birth is Required!',
            'hiring_date.required' => 'Employee Hiring Date is Required!',
            'address.required' => 'Employee Mailing Address is Required!',
//            'department.required' => 'Employee Department is Required!',
//            'designation.required' => 'Employee Designation is Required!',
            'working_days.required' => 'Working Days is Required!',
            'paid_leaves.required' => 'Paid Leaves is Required!',
            'blood_group.required' => 'Blood Group is Required!',
            'salary.required' => 'Employee Salary is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        $getImg = Employee::where('id', $request->all())->get();
        return Employee::where('id', $request->id)->update([
            'name' => $request->name,
            'f_name' => $request->f_name,
            'cnic' => str_replace('-', '', $request->cnic),
            'contact' => str_replace('-', '', $request->contact),
            'address' => $request->address,
            'reference' => $request->reference,
            'hiring_date' => $request->hiring_date,
            'dob' => $request->dob,
            'salary' => $request->salary,
            'salary_type' => $request->salary_type,
            'working_days' => $request->working_days,
            'paid_leaves' => $request->paid_leaves,
            'blood_group' => $request->blood_group,
            'emergency_contact' => $request->emergency_contact,
            'job_description' => $request->job_description,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'profile_Img' => !is_null($request->ImgEmployeeRecordEdit) ? (!is_null($request->ImgEmployeeRecordEdit['profile']) ? $request->ImgEmployeeRecordEdit['profile'] : $getImg->profile_Img) : null,
            'cnic_back_img' => !is_null($request->ImgEmployeeRecordEdit) ? (!is_null($request->ImgEmployeeRecordEdit['back']) ? $request->ImgEmployeeRecordEdit['back'] : $getImg->cnic_back_img) : null,
            'cnic_front_img' => !is_null($request->ImgEmployeeRecordEdit) ? (!is_null($request->ImgEmployeeRecordEdit['front']) ? $request->ImgEmployeeRecordEdit['front'] : $getImg->cnic_front_img) : null,
            'status' => $request->status,
            'company_id' => $this->company_id,
        ]);
    }

    public function delete(Request $request)
    {
        return Employee::find($request->id)->delete();

    }

    public function logoUpload(Request $request)
    {
        $finalArray = [];
        if ($request->profile != 'undefined') {
            $finalArray['profile'] = $this->image($request->profile, 'profile');
        } else {
            $finalArray['profile'] = null;
        }
        if ($request->cnicBack != 'undefined') {
            $finalArray['back'] = $this->image($request->cnicBack, 'cnicback');
        } else {
            $finalArray['back'] = null;
        }
        if ($request->cnicFront != 'undefined') {
            $finalArray['front'] = $this->image($request->cnicFront, 'cnicfront');
        } else {
            $finalArray['font'] = null;
        }
        return response($finalArray, 200);
    }

    public function image($image, $flag)
    {
        if ($flag == 'profile') {
            $imageProfile = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . "_" . time() . '.' . $image->extension();
            $image->move(public_path('uploads/hrm/employee/profile/'), $imageProfile);
            return $imageProfile;
        }
        if ($flag == 'cnicback') {
            $imageBack = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . "_" . time() . '.' . $image->extension();
            $image->move(public_path('uploads/hrm/employee/cnicback/'), $imageBack);
            return $imageBack;
        }
        if ($flag == 'cnicfront') {
            $imageFront = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . "_" . time() . '.' . $image->extension();
            $image->move(public_path('uploads/hrm/employee/cnicfront/'), $imageFront);
            return $imageFront;
        }
    }


}
