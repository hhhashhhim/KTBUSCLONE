<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Employees</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Employee
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                        @click="error = !error"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table dataTables table-striped table-hover"
                                                       id="employee_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Profile</th>
                                                        <th>Name</th>
                                                        <th>Contact #</th>
                                                        <th>Company</th>
                                                        <th>Department</th>
                                                        <th>Designation</th>
                                                        <th>Hiring Date</th>
                                                        <th>CNIC #</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(employee, i) in employees" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td><a
                                                            :href="$store.state.app_url +'uploads/hrm/employee/profile/'+ employee.profile_Img"
                                                            target="_blank">
                                                            <img
                                                                :src="$store.state.app_url +'uploads/hrm/employee/profile/'+ employee.profile_Img"
                                                                style="width:90px;height:100px;" alt="">
                                                        </a>
                                                        </td>
                                                        <td>{{ employee.name }}</td>
                                                        <td>{{ phoneFormat(employee.contact) }}</td>
                                                        <td>{{ employee.company.name }}</td>
                                                        <td>{{ employee.department.name }}</td>
                                                        <td>{{ employee.designation.name }}</td>
                                                        <td>{{ employee.hiring_date }}</td>
                                                        <td>{{ cnicFormat(employee.cnic) }}</td>
                                                        <td>
                                                            <div :class="getStatusClass(employee.status)">
                                                                {{ getStatusName(employee.status) }}
                                                            </div>
                                                        </td>
                                                        <td>{{ employee.added_by.name }}</td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editEmployee(employee)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button :data-target="'#' + deleteFormID"
                                                                    data-toggle="modal"
                                                                    @click="deleteModal(employee,i)"
                                                                    class="btn btn-danger">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <Add
                :heading="'Employee Information'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="EmployeeName">Name <span class="text-danger">*</span></label>
                        <input type="text" id="EmployeeName" class="form-control" v-model="addForm.EmployeeName"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="FatherName">Father Name <span class="text-danger">*</span></label>
                        <input type="text" id="FatherName" class="form-control" v-model="addForm.EmployeeFatherName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CNIC">CNIC<span class="text-danger">*</span></label>
                        <vue-mask id="CNIC"
                                  class="form-control"
                                  v-model="addForm.EmployeeCNIC"
                                  mask="00000-0000000-0"
                                  :raw="false"
                                  :options="options"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phone">Contact #<span class="text-danger">*</span></label>
                        <vue-mask id="phone"
                                  class="form-control"
                                  v-model="addForm.EmployeeContact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="dob">Date of Birth<span class="text-danger">*</span></label>
                        <input type="date" id="dob" class="form-control" v-model="addForm.EmployeeDob">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="refOfHiring">Reference of Hiring</label>
                        <input type="text" id="refOfHiring" class="form-control" v-model="addForm.RefHiring">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="hiringDate">Hiring Date<span class="text-danger">*</span></label>
                        <input type="date" id="hiringDate" class="form-control" v-model="addForm.HiringDate">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="jobDesp">Job Description</label>
                        <input type="text" id="jobDesp" class="form-control" v-model="addForm.jobDescription">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emergencyContact">Emergency Contact #</label>
                        <vue-mask id="emergencyContact"
                                  class="form-control"
                                  v-model="addForm.EmergencyContact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="address">Address <span class="text-danger">*</span></label>
                        <textarea type="text" class="form-control" id="address" cols="30" rows="10"
                                  v-model="addForm.EmployeeAddress"></textarea>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="department">Department<span class="text-danger">*</span></label>
                        <div class="float-right badge badge-primary mx-0 mb-1" style="cursor: pointer"
                             data-toggle="modal" data-target="#addDepartmentModal" @click="clearDepartmentForm()"> Add
                            New
                        </div>
                        <select class="form-control" v-model="addForm.EmployeeDepartment" @change="getDesignation()">
                            <option value="0" selected>Select Department</option>
                            <option v-for="(department, i) in departments" :key="i" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="designation">Designation<span class="text-danger">*</span></label>
                        <div class="float-right badge badge-primary mx-0 mb-1" style="cursor: pointer"
                             data-toggle="modal" data-target="#addDesignationModal" @click="clearDesignationForm()"> Add
                            New
                        </div>
                        <select class="form-control" v-model="addForm.EmployeeDesignation">
                            <option value="0">Select Designation</option>
                            <option v-for="(designation, i) in designations" :key="i" :value="designation.id">
                                {{ designation.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="workingDays">Working Days<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="workingDays" maxlength="3"
                               v-model="addForm.workingDays" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paidLeaves" maxlength="3"
                               v-model="addForm.paidLeaves" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bloodGroup">Blood Group<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bloodGroup" v-model="addForm.bloodGroup"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="salary">Salary<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="salary" v-model="addForm.EmployeeSalary"/>
                    </div>
                    <div class="form-group col-md-6 mt-4 pt-3">
                        <label for="salaryType" class="mr-3">Salary Type</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="bank" name="salaryTypeAdd" class="custom-control-input" value="bank"
                                   v-model="addForm.RadioSalaryTypeAdd">
                            <label class="custom-control-label" for="bank">Bank</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="cash" checked="" name="salaryTypeAdd" class="custom-control-input"
                                   value="cash" v-model="addForm.RadioSalaryTypeAdd">
                            <label class="custom-control-label" for="cash">Cash</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Employee Picture</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlProfile" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlProfile" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'profile')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="profilePic">
                            <label class="custom-file-label overflow-hidden" for="profilePic">{{
                                    nameProfile != '' ? nameProfile : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Front</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICFront" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICFront" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicFront')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cincBack">
                            <label class="custom-file-label overflow-hidden" for="cincBack">{{
                                    nameFront != '' ? nameFront : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Back</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICBack" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICBack" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicBack')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cnicBack`">
                            <label class="custom-file-label overflow-hidden" for="cnicBack">{{
                                    nameBack != '' ? nameBack : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="salary">Upload Attachments</label>
                            <div class="custom-file">
                                <input type="file" @change="onFileChange($event, 'attachments')" accept=".pdf, .docx, .doc"
                                       class="custom-file-input" id="attachments">
                                <label class="custom-file-label overflow-hidden"
                                       for="attachments">{{ attachments != '' ? attachments : 'Choose .pdf, .docx, .doc File' }}</label>
                            </div>

                        </div>

                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addEmployee" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Add Employee' }}
                    </button>
                </template>
            </Add>
            <!-- Add Modal End -->
            <!--            Add NEW Department-->
            <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Department</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger">*</span></label>
                                    <input type="text" id="name" class="form-control" v-model="departmentName"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="addDepartment()"
                                    :disabled="loadingDepart">
                                {{ loadingDepart ? 'Loading...' : ' Add Department' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!--            End Add New Department-->
            <!--            Add NEW Designation-->
            <div class="modal fade" id="addDesignationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Designation</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger">*</span></label>
                                    <input type="text" id="name" class="form-control" v-model="designationName"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="addDesignation()"
                                    :disabled="loadingDesignation">
                                {{ loadingDesignation ? 'Loading...' : ' Add Designation' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!--            End Add New Department-->
            <!--            Edit Model-->
            <Edit
                heading="Edit Employee Information"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="EmployeeName">Name <span class="text-danger">*</span></label>
                        <input type="text" id="EmployeeName" class="form-control" v-model="dataEdit.name"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="FatherName">Father Name <span class="text-danger">*</span></label>
                        <input type="text" id="FatherName" class="form-control" v-model="dataEdit.f_name"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CNIC">CNIC<span class="text-danger">*</span></label>
                        <vue-mask id="CNIC"
                                  class="form-control"
                                  v-model="dataEdit.cnic"
                                  mask="00000-0000000-0"
                                  :raw="false"
                                  :options="options"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phone">Contact #<span class="text-danger">*</span></label>
                        <vue-mask id="phone"
                                  class="form-control"
                                  v-model="dataEdit.contact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="dob">Date of Birth<span class="text-danger">*</span></label>
                        <input type="date" id="dob" class="form-control" v-model="dataEdit.dob">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="refOfHiring">Reference of Hiring</label>
                        <input type="text" id="refOfHiring" class="form-control" v-model="dataEdit.reference">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="hiringDate">Hiring Date<span class="text-danger">*</span></label>
                        <input type="date" id="hiringDate" class="form-control" v-model="dataEdit.hiring_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="jobDesp">Job Description</label>
                        <input type="text" id="jobDesp" class="form-control" v-model="dataEdit.job_description">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emergencyContact">Emergency Contact #</label>
                        <vue-mask id="emergencyContact"
                                  class="form-control"
                                  v-model="dataEdit.emergency_contact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="address">Address <span class="text-danger">*</span></label>
                        <textarea type="text" class="form-control" id="address" cols="30" rows="10"
                                  v-model="dataEdit.address"></textarea>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="department">Department<span class="text-danger">*</span></label>
                        <div class="float-right badge badge-primary mx-0 mb-1" style="cursor: pointer"
                             data-toggle="modal" data-target="#addDepartment" @click="clearDepartmentForm()"> Add New
                        </div>
                        <select class="form-control" v-model="dataEdit.department_id" @change="getEditDesignation()">
                            <option value="0">Select Department</option>
                            <option v-for="(department, i) in editDepartments" :key="i" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="designation">Designation<span class="text-danger">*</span></label>
                        <div class="float-right badge badge-primary mx-0 mb-1" style="cursor: pointer"
                             data-toggle="modal" data-target="#addDesignation" @click="clearDesignationForm()"> Add New
                        </div>
                        <select class="form-control" v-model="dataEdit.designation_id">
                            <option value="0" selected>Select Designation</option>
                            <option v-for="(designation, i) in editDesignations" :key="i" :value="designation.id">
                                {{ designation.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="workingDays">Working Days<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="workingDays" v-model="dataEdit.working_days"
                               @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paidLeaves" v-model="dataEdit.paid_leaves"
                               @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bloodGroup">Blood Group<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bloodGroup" v-model="dataEdit.blood_group"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="salary">Salary<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="salary" v-model="dataEdit.salary"/>
                    </div>
                    <div class="form-group col-md-6 mt-4 pt-3">
                        <label for="salaryType" class="mr-3">Salary Type</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="bank" :checked=" dataEdit.salary_type == 'bank'"
                                   name="salaryTypeEdit" class="custom-control-input" value="bank"
                                   v-model="dataEdit.salaryTypeRadioEdit">
                            <label class="custom-control-label" for="bank">Bank</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="cash" :checked=" dataEdit.salary_type == 'cash'"
                                   name="salaryTypeEdit" class="custom-control-input" value="cash"
                                   v-model="dataEdit.salaryTypeRadioEdit">
                            <label class="custom-control-label" for="cash">Cash</label>
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status">Status<span class="text-danger">*</span></label>
                        <select class="form-control" id="status" v-model="dataEdit.status">
                            <option value="0">Select Employee Status</option>
                            <option value="W">Working</option>
                            <option value="R">Resigned</option>
                            <option value="T">Terminated</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Employee Picture</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlProfileEdit" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlProfileEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'profileEdit')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="profilePic">
                            <label class="custom-file-label overflow-hidden" for="profilePic">{{
                                    nameProfileEdit != '' ? nameProfileEdit : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Front</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICFrontEdit" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICFrontEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicFrontEdit')"
                                   accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cincBack">
                            <label class="custom-file-label overflow-hidden"
                                   for="cincBack">{{ nameFrontEdit != '' ? nameFrontEdit : 'Choose.jpg, .png, .jpeg Image' }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Back</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICBackEdit" class="img-responsive thumbnail rounded "
                                 style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICBackEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicBackEdit')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cnicBack`">
                            <label class="custom-file-label overflow-hidden"
                                   for="cnicBack">{{ nameBackEdit != '' ? nameBackEdit : 'Choose.jpg, .png, .jpeg Image' }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-12">
                        <label for="salary">Upload Attachments</label>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'attachmentsEdit')" accept=".pdf, .docx, .doc"
                                   class="custom-file-input" id="attachmentsEdit`">
                            <label class="custom-file-label overflow-hidden"
                                   for="attachmentsEdit">{{ attachmentsEdit != '' ? attachmentsEdit : 'Choose .pdf, .docx, .doc File' }}</label>
                        </div>

                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateEmployees"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Employees Record' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Employee Record ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../../components/Add.vue";
import Edit from "../../../components/Edit.vue";
import Delete from "../../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "EmployeesPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsContact: {
                placeholder: "xxxx-xxxxxxx",
            },

            addForm: {
                paidLeaves: '0',
                RadioSalaryTypeAdd: 'cash',
            },
            departmentName: '',
            designationName: '',
            employees: [],
            departments: [],
            editDepartments: [],
            designations: [],
            editDesignations: [],
            urlProfile: '',
            urlCNICBack: '',
            urlCNICFront: '',
            urlProfileEdit: '',
            urlCNICBackEdit: '',
            urlCNICFrontEdit: '',
            back: '',
            front: '',
            profile: '',
            nameProfile: '',
            nameBack: '',
            nameFront: '',
            nameProfileEdit: '',
            nameBackEdit: '',
            nameFrontEdit: '',
            attachmentsEdit: '',
            attachments: '',
            loading: false,
            loadingDepart: false,
            loadingDesignation: false,
            formID: "employees_form",
            editFormID: "edit_employees_form",
            deleteFormID: "delete_employees_form",
            validationErrors: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
            editImg: {},
        };
    },
    async created() {
        await this.fetchEmployees();
    },

    methods: {
        async getDesignation() {
            if (this.addForm.EmployeeDepartment == '0') {
                this.addForm.EmployeeDesignation = 0;
                this.designations = '';
            }
            const resSelectiveDesignation = await this.callApi("post", 'hrm/designation/selective', {id: this.addForm.EmployeeDepartment});
            console.log(resSelectiveDesignation)
            if (resSelectiveDesignation.status == 200) {
                if (resSelectiveDesignation.data.length == 0) {
                    this.addForm.EmployeeDesignation = 0;
                    this.addForm.EmployeeDesignation = 0;
                } else {
                    this.designations = resSelectiveDesignation.data;
                }
            }
        },
        async getEditDesignation() {
            if (this.dataEdit.department_id == '0') {
                this.dataEdit.designation_id = 0;
                this.editDesignations = '';
            }
            const resSelectiveDesignation = await this.callApi("post", 'hrm/designation/selective', {id: this.dataEdit.department_id});
            if (resSelectiveDesignation.status == 200) {
                if (resSelectiveDesignation.data.length == 0) {
                    this.dataEdit.designation_id = 0;
                    this.editDesignations = '';
                } else {
                    this.editDesignations = resSelectiveDesignation.data;
                }
            }
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },
        onFileChange: function (e, imgTag) {
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png|pdf|docx|)$/i)) {
                if (imgTag == 'profile') {
                    const profile = e.target.files[0];
                    this.nameProfile = profile.name;
                    this.urlProfile = URL.createObjectURL(profile);
                    this.profile = profile;

                }
                if (imgTag == 'cnicFront') {
                    const front = e.target.files[0];
                    this.nameFront = front.name;
                    this.urlCNICFront = URL.createObjectURL(front);
                    this.front = front;
                }
                if (imgTag == 'cnicBack') {
                    const back = e.target.files[0];
                    this.nameBack = back.name;
                    this.urlCNICBack = URL.createObjectURL(back);
                    this.back = back;
                }
                if (imgTag == 'profileEdit') {
                    const profile = e.target.files[0];
                    this.nameProfileEdit = profile.name;
                    this.urlProfileEdit = URL.createObjectURL(profile);
                    this.editImg.profile = profile;

                }
                if (imgTag == 'cnicFrontEdit') {
                    const front = e.target.files[0];
                    this.nameFrontEdit = front.name;
                    this.urlCNICFrontEdit = URL.createObjectURL(front);
                    this.editImg.front = front;
                }
                if (imgTag == 'cnicBackEdit') {
                    const back = e.target.files[0];
                    this.nameBackEdit = back.name;
                    this.urlCNICBackEdit = URL.createObjectURL(back);
                    this.editImg.back = back;
                }
            } else {
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png",
                    icon: "error",
                    timer: 2000
                });
                e.target.value = '';
                this.nameProfile = '';
                this.nameProfileEdit = '';
                this.nameFrontEdit = '';
                this.nameFront = '';
                this.nameBackEdit = '';
                this.nameBack = '';
            }
        },
        clearDepartmentForm() {
            this.departmentName = '';
        },
        clearDesignationForm() {
            this.designationName = '';
        },
        async addDepartment() {
            if (this.departmentName == '' || typeof this.departmentName == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loadingDepart = true;
            const resDepartmentStore = await this.callApi("post", 'hrm/department/store', {name: this.departmentName});
            if (resDepartmentStore.status == 201) {
                swal({
                    title: "Success",
                    text: "Department Name Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.loadingDepart = false;
                this.departmentName == '';
                if (this.departments.indexOf(resDepartmentStore.data) === -1) {
                    this.departments.push(resDepartmentStore.data);
                }
                if (this.editDepartments.indexOf(resDepartmentStore.data) === -1) {
                    this.editDepartments.push(resDepartmentStore.data);
                }
            } else {
                this.loadingDepart = false;
                if (resDepartmentStore.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in resDepartmentStore.data.errors) {
                        resDepartmentStore.data.errors[key].forEach((element) => {
                            errorContent += ((++count) + " - " + element + "\n");
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 4000
                        });

                    }
                }
            }
        },
        async addDesignation() {
            if (this.addForm.EmployeeDepartment == '0' || this.dataEdit.department_id == '0')
                return swal({
                    title: "Required!",
                    text: "Please Select Department First",
                    icon: "error",
                    timer: 2000
                });
            if (this.designationName == '' || typeof this.designationName == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loadingDesignation = true;
            const resDesignationStore = await this.callApi("post", 'hrm/designation/store', {
                department: this.addForm.EmployeeDepartment ? this.addForm.EmployeeDepartment : this.dataEdit.department_id,
                name: this.designationName
            });
            if (resDesignationStore.status == 201) {
                this.designations = '';
                this.editDesignations = '';
                swal({
                    title: "Success",
                    text: "Designation Successfully Added Against Selected Department",
                    icon: "success",
                    timer: 2000
                });
                this.loadingDesignation = false;
                if (this.designations.indexOf(resDesignationStore.data) === -1) {
                    this.designations.push(resDesignationStore.data);
                }
                if (this.editDesignations.indexOf(resDesignationStore.data) === -1) {
                    this.editDesignations.push(resDesignationStore.data);
                }
            } else {
                this.loadingDesignation = false;
                if (resDesignationStore.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in resDesignationStore.data.errors) {
                        resDesignationStore.data.errors[key].forEach((element) => {
                            errorContent += ((++count) + " - " + element + "\n");
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 4000
                        });

                    }
                }
            }
        },

        async fetchEmployees() {
            const resEmployeeIndex = await this.callApi("post", 'hrm/employee');
            if (resEmployeeIndex.status == 200) {
                this.employees = resEmployeeIndex.data

            } else {
                console.log(resEmployeeIndex);
            }
            const resFetchDepartment = await this.callApi("post", 'hrm/department');
            if (resFetchDepartment.status == 200) {
                this.departments = resFetchDepartment.data
                this.editDepartments = resFetchDepartment.data

            } else {
                console.log(resFetchDepartment);
            }

            setTimeout(function () {
                $("#employee_table").DataTable();
            }, 300);
        },

        getStatusName: function (value) {
            if (value == 'W') {
                return 'Working';
            }
            if (value == 'R') {
                return 'Resigned';
            }
            if (value == 'T') {
                return 'Ternimated';
            }
        },

        getStatusClass: function (value) {
            if (value == 'W') {
                return 'badge badge-success';
            }
            if (value == 'R') {
                return 'badge badge-info';
            }
            if (value == 'T') {
                return 'badge badge-danger';
            }
        },

        clearForm: function () {
            this.loading = false;
            this.addForm = {
                paidLeaves: '0',
                EmployeeDepartment: 0,
                EmployeeDesignation: 0,
                RadioSalaryTypeAdd: 'cash',
            };
            this.designations = '';
            this.nameBack = '';
            this.urlCNICBack = '';
            this.nameFront = '';
            this.urlCNICFront = '';
            this.nameProfile = '';
            this.urlProfile = '';
        },

        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },

        async addEmployee() {
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            if (this.profile != '') {
                formData.append('profile', this.profile);
            }
            if (this.front != '') {
                formData.append('cnicFront', this.front);
            }
            if (this.back != '') {
                formData.append('cnicBack', this.back);
            }

            this.validationErrors = [];
            if (this.addForm.EmployeeName == "" || typeof this.addForm.EmployeeName == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeFatherName == "" || typeof this.addForm.EmployeeFatherName == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Father Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeCNIC == "" || typeof this.addForm.EmployeeCNIC == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeContact == "" || typeof this.addForm.EmployeeContact == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDob == "" || typeof this.addForm.EmployeeDob == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Date of Birth is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.HiringDate == "" || typeof this.addForm.HiringDate == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Hiring Date  is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeAddress == "" || typeof this.addForm.EmployeeAddress == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Address is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDepartment == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Employee's Department",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDesignation == "" || typeof this.addForm.EmployeeDesignation == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Designation is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.workingDays == "" || typeof this.addForm.workingDays == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Working Days is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.paidLeaves == "" || typeof this.addForm.paidLeaves == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.bloodGroup == "" || typeof this.addForm.bloodGroup == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeSalary == "" || typeof this.addForm.EmployeeSalary == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Salary is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            let ImgEmployeeRecord = "";
            if (this.profile || this.front || this.back) {
                const logoRes = await this.callApi("post", "hrm/employee/logo-upload", formData, config);
                ImgEmployeeRecord = logoRes.data ?? "";
            }
            const resEmployeeAdd = await this.callApi("post", "hrm/employee/store", {
                ...this.addForm,
                ImgEmployeeRecord
            });
            if (resEmployeeAdd.status == 201) {
                swal({
                    title: "Success",
                    text: "Employee record Successfully Created!",
                    icon: "success",
                    timer: 2000
                });
                $("#employee_table").DataTable().destroy();
                this.loading = false;
                await this.fetchEmployees();
                this.clearForm();
                window.scrollTo(0, 0);
            } else {
                if (resEmployeeAdd.status == 422) {
                    this.loading = false;
                    for (const key in resEmployeeAdd.data.errors) {
                        resEmployeeAdd.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateEmployees() {
            this.validationErrors = [];
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            if (this.editImg.profile != '') {
                formData.append('profile', this.editImg.profile);
            }
            if (this.editImg.front != '') {
                formData.append('cnicFront', this.editImg.front);
            }
            if (this.editImg.back != '') {
                formData.append('cnicBack', this.editImg.back);
            }
            this.validationErrors = [];
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.f_name == "" || typeof this.dataEdit.f_name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Father Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.cnic == "" || typeof this.dataEdit.cnic == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.contact == "" || typeof this.dataEdit.contact == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.dob == "" || typeof this.dataEdit.dob == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Date of Birth is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.hiring_date == "" || typeof this.dataEdit.hiring_date == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Hiring Date  is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.address == "" || typeof this.dataEdit.address == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Address is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.department == "" || typeof this.dataEdit.department == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Department Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.designation == "" || typeof this.dataEdit.designation == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Designation is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.working_days == "" || typeof this.dataEdit.working_days == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Working Days is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.paid_leaves == "" || typeof this.dataEdit.paid_leaves == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.blood_group == "" || typeof this.dataEdit.blood_group == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.salary == "" || typeof this.dataEdit.salary == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Employee's Salary is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.status == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Employee Status",
                    icon: "error",
                    timer: 2000
                });
            // this.loading = true;
            let ImgEmployeeRecordEdit = "";
            if (this.editImg.profile || this.editImg.front || this.editImg.back) {
                const logoRes = await this.callApi("post", "hrm/employee/logo-upload", formData, config);
                ImgEmployeeRecordEdit = logoRes.data ?? "";
            }

            const resEmployeeUpdate = await this.callApi("post", 'hrm/employee/update', {
                ...this.dataEdit,
                ImgEmployeeRecordEdit
            });
            if (resEmployeeUpdate.status == 200) {
                swal({
                    title: "Success!",
                    text: "Employee Record Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#employee_table").DataTable().destroy();
                this.loading = false;
                await this.fetchEmployees();
            } else {
                if (resEmployeeUpdate.status === 422) {
                    $("#" + formID).scrollTop(0, 0);
                    this.loading = false;
                    for (const key in resEmployeeUpdate.data.errors) {
                        resEmployeeUpdate.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        },

        async deleteModal(emp, i) {
            const deletingObj = {
                url: "hrm/employee/delete",
                data: emp,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        async editEmployee(employ) {
            this.dataEdit = employ;
            const resEditSelective = await this.callApi("post", 'hrm/designation/selective', {id: employ.department_id});
            console.log(resEditSelective);
            if (resEditSelective.status == 200) {
                if (resEditSelective.data.length == 0) {
                    this.editDesignations = '';
                    this.dataEdit.designation_id = 0;
                }
                this.editDesignations = resEditSelective.data;
            }
        },

    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.employees.splice(obj.index, 1)
                $("#employee_table").DataTable().destroy();
                this.fetchEmployees();
            }
        }
    }
};
</script>
<style scoped>

</style>
