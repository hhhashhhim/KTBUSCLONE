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
                                    v-if="checkForSubmenuButtons('add-employee')"
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
                                                        <th>Contact</th>
                                                        <th>Company</th>
                                                        <th>Department</th>
                                                        <th>Designation</th>
                                                        <th>Hiring Date</th>
                                                        <th>CNIC</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(employee, i) in employees" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td v-if="employee.profile_Img != null"><a
                                                            :href="$store.state.api_url +'uploads/hrm/employee/profile/'+ employee.profile_Img"
                                                            target="_blank">
                                                            <img
                                                                :src="$store.state.api_url +'uploads/hrm/employee/profile/'+ employee.profile_Img"
                                                                style="width:90px;height:100px;" alt="">
                                                        </a>
                                                        </td>
                                                        <td v-else><a
                                                            :href="$store.state.api_url +'uploads/no-user.png'"
                                                            target="_blank">
                                                            <img :src="$store.state.api_url +'uploads/no-user.png'"
                                                                 style="width:90px;height:100px;" alt="">
                                                        </a>
                                                        </td>
                                                        <td>{{ employee.name }}</td>
                                                        <td>{{ phoneFormat(employee.contact) }}</td>
                                                        <td>{{ employee.company.name }}</td>
                                                        <td>{{
                                                                employee.department ? employee.department.name : 'N/A'
                                                            }}
                                                        </td>
                                                        <td>{{
                                                                employee.designation ? employee.department.name : 'N/A'
                                                            }}
                                                        </td>
                                                        <td>{{ employee.hiring_date ?? 'N/A' }}</td>
                                                        <td>{{ cnicFormat(employee.cnic) }}</td>
                                                        <td>
                                                            <div :class="getStatusClass(employee.status)">
                                                                {{ getStatusName(employee.status) }}
                                                            </div>
                                                        </td>
                                                        <td>{{ employee.added_by.name }}</td>
                                                        <td>
                                                            <button data-target="#addUser" data-toggle="modal"
                                                                    v-if="employee.user_id == '0' || employee.user_id == null"
                                                                    @click="this.userData.employee_id = employee.id"
                                                                    class="btn btn-info mx-1" title="Add User Account">
                                                                <i class="fa fa-user-plus"></i>
                                                            </button>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editEmployee(employee)"
                                                                    v-if="checkForSubmenuButtons('edit-employee')"
                                                                    class="btn btn-primary mx-1" title="Edit Employee">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button title="Delete Terminal"
                                                                    :data-target="'#' + hideFormID" @click="delId = employee.id" data-toggle="modal"
                                                                    class="btn btn-danger mx-2"
                                                                    v-if="checkForSubmenuButtons('delete-employee')"
                                                            >
                                                                <i class="far fas fa-trash"></i>
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
                <div class="m-3 row">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="createAccount"
                               @click="accountCreate($event)" value="0" name="createAccount"
                               :checked="addForm.createAccount == 1">
                        <label class="custom-control-label" for="createAccount">Want to Create An Account For
                            Employee</label>
                    </div>
                </div>
                <div v-if="createDiv" class="row mt-3">
                    <div class="form-group col-md-4">
                        <label for="email">Email <span class="text-danger ml-1">*</span></label>
                        <input type="email" id="email" class="form-control" v-model="addForm.email"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="password">Password <span class="text-danger ml-1">*</span></label>
                        <input type="password" id="password" class="form-control" v-model="addForm.password"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="role">Role<span class="text-danger ml-1">*</span></label>
                        <select
                            type="text"
                            class="form-control"
                            id=""
                            v-model="addForm.role"
                        >
                            <option value="">Select Role</option>
                            <option v-for="(role, i) in roles" :value="role.id" :key="i">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="form-group col-md-4">
                        <label for="EmployeeName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" id="EmployeeName" class="form-control" v-model="addForm.EmployeeName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="FatherName">Father Name</label>
                        <input type="text" id="FatherName" class="form-control" v-model="addForm.EmployeeFatherName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="department">Employee Type<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addForm.EmployeeType">
                            <option value="">Select Employee Type</option>
                            <option value="1">Driver</option>
                            <option value="2">Bus Hostess</option>
                            <option value="0">Other...</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CNIC">CNIC<span class="text-danger ml-1">*</span></label>
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
                        <label for="phone">Contact #<span class="text-danger ml-1">*</span></label>
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
                        <label for="dob">Date of Birth<span class="text-danger ml-1">*</span></label>
                        <input type="date" id="dob" class="form-control" v-model="addForm.EmployeeDob"
                               :max="minDateFilter()">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="refOfHiring">Reference of Hiring</label>
                        <input type="text" id="refOfHiring" class="form-control" v-model="addForm.RefHiring">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="hiringDate">Hiring Date</label>
                        <input type="date" id="hiringDate" class="form-control" v-model="addForm.HiringDate">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="address">Address</label>
                        <textarea type="text" class="form-control" id="address" cols="30" rows="10"
                                  v-model="addForm.EmployeeAddress"></textarea>
                    </div>
                    <div class="form-group col-md-4" v-if="this.addForm.EmployeeType == '0'">
                        <label for="department">Terminal<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addForm.EmployeeTerminal" @change="getDepartment()">
                            <option value="0" selected>Select Terminal</option>
                            <option
                                v-for="(terminal, i) in terminals"
                                :value="terminal.id"
                                :key="i"
                            >{{ terminal.name }} ({{ terminal.city.name }})
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4" v-if="this.addForm.EmployeeType == '0'">
                        <label for="department">Department<span class="text-danger ml-1">*</span></label>
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
                    <div class="form-group col-md-4" v-if="this.addForm.EmployeeType == '0'">
                        <label for="designation">Designation<span class="text-danger ml-1">*</span></label>
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
                        <label for="workingDays">Working Days</label>
                        <input type="text" class="form-control" id="workingDays" maxlength="3"
                               v-model="addForm.workingDays" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves</label>
                        <input type="text" class="form-control" id="paidLeaves" maxlength="3"
                               v-model="addForm.paidLeaves" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bloodGroup">Blood Group</label>
                        <input type="text" class="form-control" id="bloodGroup" v-model="addForm.bloodGroup"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="salary">Salary</label>
                        <input type="text" class="form-control" id="salary" v-model="addForm.EmployeeSalary"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="attachments">Upload Attachments</label>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'attachments')"
                                   accept=".pdf, .docx, .doc, .png, .jpeg, .jpg"
                                   class="custom-file-input" id="attachments">
                            <label class="custom-file-label overflow-hidden"
                                   for="attachments">{{
                                    attachments != '' ? attachments : 'Choose .pdf, .docx, .doc, .png, .jepg, .jpg File'
                                }}</label>
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
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addEmployee()" :disabled="loading">
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
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="closeDep()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger ml-1">*</span></label>
                                    <input type="text" id="name" class="form-control" v-model="departmentName"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="addDepartment()"
                                    :disabled="loadingDepart">
                                {{ loadingDepart ? 'Loading...' : ' Add Department' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeDep()">
                                Close
                            </button>
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
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="closeDes()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger ml-1">*</span></label>
                                    <input type="text" id="name" class="form-control" v-model="designationName"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="addDesignation()"
                                    :disabled="loadingDesignation">
                                {{ loadingDesignation ? 'Loading...' : ' Add Designation' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeDes()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!--            Add User Account -->
            <div class="modal fade" id="addUser" tabindex="-1" aria-labelledby="addUserLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add User Account</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="closeUser()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Name"
                                        id="name"
                                        autocomplete="off"
                                        v-model="userData.name"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Email"
                                        id="email"
                                        autocomplete="off"
                                        v-model="userData.email"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                                    <vue-mask id="phone"
                                              class="form-control"
                                              v-model="userData.contact"
                                              mask="0000-0000000"
                                              :raw="false"
                                              :options="optionsContact"
                                    >
                                    </vue-mask>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="password">Password <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        placeholder="Enter Password"
                                        id="password"
                                        autocomplete="off"
                                        v-model="userData.password"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="departure">Departure City </label>
                                    <select class="form-control" id="departure" multiple
                                            v-model="userData.departure">
                                        <option
                                            v-for="(singleDeparture, i) in departureCities"
                                            :value="singleDeparture.id"
                                            :key="i"
                                        >{{ singleDeparture.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="destinations">Destination City </label>
                                    <select class="form-control" id="destinations" multiple
                                            v-model="userData.destination">
                                        <option
                                            v-for="(singleDestination, i) in destinationCities"
                                            :value="singleDestination.id"
                                            :key="i"
                                        >{{ singleDestination.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="terminals">Terminal <span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" id="terminals"
                                            v-model="userData.terminal_id">
                                        <option value="0">Select Terminal</option>
                                        <option
                                            v-for="(terminal, i) in terminals"
                                            :value="terminal.id"
                                            :key="i"
                                        >{{ terminal.name }} ({{ terminal.city.name }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" :disabled="this.loading"
                                    @click="addUserAccount()">
                                {{ this.loading ? 'Loading...' : 'Add User' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeUser()">
                                Close
                            </button>
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
                    <div class="form-group col-md-4">
                        <label for="EmployeeName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" id="EmployeeName" class="form-control" v-model="editEmp.EmployeeName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="FatherName">Father Name </label>
                        <input type="text" id="FatherName" class="form-control" v-model="editEmp.EmployeeFatherName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="department">Employee Type<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editEmp.EmployeeType">
                            <option value="">Select Employee Type</option>
                            <option value="1">Driver</option>
                            <option value="2">Bus Hostess</option>
                            <option value="0">Other...</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CNIC">CNIC<span class="text-danger ml-1">*</span></label>
                        <vue-mask id="CNIC"
                                  class="form-control"
                                  v-model="editEmp.EmployeeCNIC"
                                  mask="00000-0000000-0"
                                  :raw="false"
                                  :options="options"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phone">Contact #<span class="text-danger ml-1">*</span></label>
                        <vue-mask id="phone"
                                  class="form-control"
                                  v-model="editEmp.EmployeeContact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="dob">Date of Birth<span class="text-danger ml-1">*</span></label>
                        <input type="date" id="dob" class="form-control" v-model="editEmp.EmployeeDob">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="refOfHiring">Reference of Hiring</label>
                        <input type="text" id="refOfHiring" class="form-control" v-model="editEmp.RefHiring">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="hiringDate">Hiring Date</label>
                        <input type="date" id="hiringDate" class="form-control" v-model="editEmp.HiringDate">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="address">Address</label>
                        <textarea type="text" class="form-control" id="address" cols="30" rows="10"
                                  v-model="editEmp.EmployeeAddress"></textarea>
                    </div>
                    <div class="form-group col-md-4" v-if="this.editEmp.EmployeeType == '0'">
                        <label for="department">Terminal<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editEmp.EmployeeTerminal" @change="getDepartment()">
                            <option value="0" selected>Select Terminal</option>
                            <option
                                v-for="(terminal, i) in terminals"
                                :value="terminal.id"
                                :key="i"
                            >{{ terminal.name }} ({{ terminal.city.name }})
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4" v-if="this.editEmp.EmployeeType == '0'">
                        <label for="department">Department<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editEmp.EmployeeDepartment"
                                @change="getEditDesignation()">
                            <option value="0">Select Department</option>
                            <option v-for="(department, i) in editDepartments" :key="i" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4" v-if="this.editEmp.EmployeeType == '0'">
                        <label for="designation">Designation<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editEmp.EmployeeDesignation">
                            <option value="0" selected>Select Designation</option>
                            <option v-for="(designation, i) in editDesignations" :key="i" :value="designation.id">
                                {{ designation.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="workingDays">Working Days</label>
                        <input type="text" class="form-control" id="workingDays" v-model="editEmp.workingDays"
                               @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves</label>
                        <input type="text" class="form-control" id="paidLeaves" v-model="editEmp.paidLeaves"
                               @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bloodGroup">Blood Group</label>
                        <input type="text" class="form-control" id="bloodGroup" v-model="editEmp.bloodGroup"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Salary</label>
                        <input type="text" class="form-control" id="salary" v-model="editEmp.EmployeeSalary"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="status">Status<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" id="status" v-model="editEmp.status">
                            <option value="0">Select Employee Status</option>
                            <option value="W">Working</option>
                            <option value="R">Resigned</option>
                            <option value="T">Terminated</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload Attachments <small>(Empty field will save attachment
                            same)</small></label>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'attachmentsEdit')"
                                   accept=".pdf, .docx, .doc"
                                   class="custom-file-input" id="attachmentsEdit`">
                            <label class="custom-file-label overflow-hidden"
                                   for="attachmentsEdit">{{
                                    attachmentsEdit != '' ? attachmentsEdit : 'Choose .pdf, .docx, .doc, .png, .jpg, .jepg File'
                                }}</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Employee Picture <small>(Empty field
                            will save picture same)</small></label>
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
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateEmployees()"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Employees' }}
                    </button>
                </template>
            </Edit>


            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This City ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideEmployee"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Delete' }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>

<script>
import Add from "../../../components/Add.vue";
import Edit from "../../../components/Edit.vue";
import Hide from "../../../components/Hide.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "EmployeesPage",
    components: {
        Add,
        Edit,
        Hide,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },

            addForm: {
                paidLeaves: '0',
                RadioSalaryTypeAdd: 'cash',
                profile: null,
                attachments: null,
                email: null,
                createAccount: 0,
                password: null,
                role: "",
                EmployeeName: null,
                EmployeeFatherName: null,
                EmployeeCNIC: null,
                EmployeeContact: null,
                EmployeeAddress: null,
                RefHiring: null,
                HiringDate: null,
                EmployeeDob: null,
                EmployeeSalary: null,
                workingDays: null,
                bloodGroup: null,
                EmergencyContact: null,
                jobDescription: null,
                EmployeeDepartment: 0,
                EmployeeType: "",
                EmployeeTerminal: 0,
                EmployeeDesignation: 0,
            },
            userData: {
                name: "",
                email: "",
                contact: "",
                password: "",
                company_id: "",
                terminal_id: 0,
                employee_id: '',
                destination: [],
                departure: [],
            },
            editEmp: {
                userId: null,
                paidLeaves: '0',
                RadioSalaryTypeAdd: 'cash',
                profile: null,
                attachments: null,
                email: null,
                password: null,
                EmpyeeName: null,
                EmployeeFatherName: null,
                EmployeeCNIC: null,
                EmployeeContact: null,
                EmployeeAddress: null,
                RefHiring: null,
                HiringDate: null,
                EmployeeDob: null,
                EmployeeSalary: null,
                workingDays: null,
                bloodGroup: null,
                EmergencyContact: null,
                jobDescription: null,
                EmployeeDepartment: 0,
                EmployeeType: 0,
                EmployeeTerminal: 0,
                EmployeeDesignation: 0,
                status: null,

            },
            departmentName: '',
            designationName: '',
            roles: [],
            employees: [],
            terminals: [],
            departments: [],
            permissions: [],
            editDepartments: [],
            designations: [],
            departureCities: [],
            destinationCities: [],
            editDesignations: [],
            urlProfile: '',
            urlProfileEdit: '',
            nameProfile: '',
            nameProfileEdit: '',
            attachments: '',
            attachmentsEdit: '',
            profileEdit: '',
            loading: false,
            loadingDepart: false,
            loadingDesignation: false,
            formID: "employees_form",
            editFormID: "edit_employees_form",
            hideFormID: "hide_employees_form",
            validationErrors: [],
            success: false,
            error: false,
            createDiv: false,
            delId: "",
            dataEdit: {},
            editImg: {},
        };
    },
    async created() {
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        await this.fetchEmployees();
        this.permissions = this.$store.state.permissions;
    },


    methods: {
        closeDep() {
            $("#addDepartmentModal").click();
        },
        closeDes() {
            $("#addDesignationModal").click();
        },
        closeUser() {
            $("#addUser").click();
        },
        async addUserAccount() {
            if (this.userData.name == "" || typeof this.userData.name == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.userData.email == "" || typeof this.userData.email == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Email is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.userData.password == "" || typeof this.userData.password == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Password is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.userData.departure.length == 0)
                return swal({
                    title: "Required!!",
                    text: "Please Select Departure City/Cities",
                    icon: "error",
                    timer: 2000
                });
                if (this.userData.destination.length == 0 )
                return swal({
                    title: "Required!!",
                    text: "Please Select Destination City/Cities",
                    icon: "error",
                    timer: 2000
                });
            if (this.userData.terminal_id == 0)
                return swal({
                    title: "Required!!",
                    text: "Please Select Terminal",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const res = await this.callApi("post", "hrm/employee/user/store", this.userData);
            if (res.status == 200) {
                this.loading = false;
                swal({
                    title: "Success!!",
                    text: "User Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.closeUser();
                this.fetchEmployees();
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += ((++count) + " - " + element + "\n");
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });
                    }
                }
            }
        },
        accountCreate: function (e) {
            if (e.target.checked) {
                this.addForm.createAccount = 1;
                this.createDiv = true;
            } else {
                this.addForm.createAccount = 0;
                this.createDiv = false;
            }
        },
        minDateFilter: function () {
            var dtToday = new Date();
            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();
            return year + '-' + month + '-' + day;
        },

        async getDesignation() {
            if (this.addForm.EmployeeDepartment == '0') {
                this.addForm.EmployeeDesignation = 0;
                this.designations = '';
            }
            const resSelectiveDesignation = await this.callApi("post", 'hrm/designation/selective', {id: this.addForm.EmployeeDepartment});
            if (resSelectiveDesignation.status == 200) {
                if (resSelectiveDesignation.data.length == 0) {
                    this.addForm.EmployeeDesignation = 0;
                } else {
                    this.addForm.EmployeeDesignation = 0;
                    this.designations = resSelectiveDesignation.data;
                }
            }
        },

        async getDepartment() {
            if (this.addForm.EmployeeTerminal == '0') {
                this.addForm.EmployeeDepartment = 0;
                this.addForm.EmployeeDesignation = 0;
                this.designations = '';
                this.departments = '';
            }
            const resSelectiveDesignation = await this.callApi("post", 'hrm/department/selective', {id: this.addForm.EmployeeTerminal});

            if (resSelectiveDesignation.status == 200) {
                if (resSelectiveDesignation.data.length == 0) {
                    this.addForm.EmployeeDesignation = 0;
                    this.addForm.EmployeeDepartment = 0;
                } else {
                    this.addForm.EmployeeDesignation = 0;
                    this.addForm.EmployeeDepartment = 0;
                    this.departments = resSelectiveDesignation.data;
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
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png|pdf|docx|doc)$/i)) {
                if (imgTag == 'profile') {
                    const profile = e.target.files[0];
                    this.nameProfile = profile.name;
                    this.urlProfile = URL.createObjectURL(profile);
                    this.addForm.profile = profile;

                }
                if (imgTag == 'profileEdit') {
                    const profile = e.target.files[0];
                    this.nameProfileEdit = profile.name;
                    this.urlProfileEdit = URL.createObjectURL(profile);
                    this.profileEdit = profile;

                }
                if (imgTag == 'attachments') {
                    const attachment = e.target.files[0];
                    this.attachments = attachment.name;
                    this.addForm.attachments = attachment;
                }
                if (imgTag == 'attachmentsEdit') {
                    const attachmentsEditRes = e.target.files[0];
                    this.attachmentsEdit = attachmentsEditRes;
                }
            } else {
                e.target.value = '';
                this.nameProfile = '';
                this.nameProfileEdit = '';
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png, .pdf, .docx, .doc",
                    icon: "error",
                    timer: 2000
                });
            }
        },

        clearDepartmentForm() {
            this.departmentName = '';
        },

        clearDesignationForm() {
            this.designationName = '';
        },

        async addDepartment() {
            if (this.addForm.EmployeeTerminal == '0')
                return swal({
                    title: "Required!",
                    text: "Please Select Terminal",
                    icon: "error",
                    timer: 2000
                });
            if (this.departmentName == '' || typeof this.departmentName == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loadingDepart = true;
            const resDepartmentStore = await this.callApi("post", 'hrm/department/store', {
                terminal: this.addForm.EmployeeTerminal,
                name: this.departmentName
            });
            if (resDepartmentStore.status == 201) {
                this.closeDep();
                swal({
                    title: "Success",
                    text: "Department Name Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.loadingDepart = false;
                this.departmentName == '';
                if (this.departments.indexOf(resDepartmentStore.data) == -1) {
                    this.departments.push(resDepartmentStore.data);
                }
                if (this.editDepartments.indexOf(resDepartmentStore.data) == -1) {
                    this.editDepartments.push(resDepartmentStore.data);
                }
            }
            if (resDepartmentStore.status == 422) {
                this.loadingDepart = false;
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
                        timer: 2000
                    });

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
                this.closeDes();
                this.designations = '';
                this.editDesignations = '';
                swal({
                    title: "Success",
                    text: "Designation Successfully Added Against Selected Department",
                    icon: "success",
                    timer: 2000
                });
                this.loadingDesignation = false;
                if (this.designations.indexOf(resDesignationStore.data) == -1) {
                    this.designations.push(resDesignationStore.data);
                }
                if (this.editDesignations.indexOf(resDesignationStore.data) == -1) {
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
                            timer: 2000
                        });

                    }
                }
            }
        },

        async fetchEmployees() {
            const resAllTerminals = await this.callApi("post", 'settings/tickets/terminals');
            const resCities = await this.callApi("post", "hrm/employee/cities");
            if (resAllTerminals.status == 200) {
                this.terminals = resAllTerminals.data
            } else {
                console.log(resAllTerminals);
            }
            if (resCities.status == 200) {
                this.destinationCities = resCities.data;
                this.departureCities = resCities.data;
            } else {
                console.log(resCities);
            }
            const resEmployeeIndex = await this.callApi("post", 'hrm/employee');
            if (resEmployeeIndex.status == 200) {
                this.employees = resEmployeeIndex.data

            } else {
                console.log(resEmployeeIndex);
            }
            const resFetchDepartment = await this.callApi("post", 'hrm/department');
            if (resFetchDepartment.status == 200) {
                this.editDepartments = resFetchDepartment.data

            } else {
                console.log(resFetchDepartment);
            }
            const roleRes = await this.callApi("post", "role");
            if (roleRes.status == 200) {
                this.roles = roleRes.data;
            } else {
                console.log(roleRes)
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
            this.addForm.createAccount = 0;
            this.createDiv = false;
            this.addForm = {
                paidLeaves: '0',
                RadioSalaryTypeAdd: 'cash',
                profile: null,
                attachments: null,
                email: '',
                password: '',
                role: '',
                EmployeeName: '',
                EmployeeFatherName: '',
                EmployeeCNIC: '',
                EmployeeContact: '',
                EmployeeAddress: '',
                RefHiring: '',
                HiringDate: '',
                EmployeeDob: '',
                EmployeeSalary: '',
                workingDays: '',
                bloodGroup: '',
                EmergencyContact: '',
                jobDescription: '',
                EmployeeDepartment: 0,
                EmployeeType: "",
                EmployeeTerminal: 0,
                EmployeeDesignation: 0,
            }
            this.designations = '';
            this.nameProfile = '';
            this.urlProfile = '';
            this.attachments = '';
            this.attachmentsEdit = '';
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
            if (this.addForm.profile != '') {
                formData.append('profile', this.addForm.profile ?? '');
            }
            if (this.addForm.attachments != '') {
                formData.append('attachment', this.addForm.attachments ?? '');
            }

            this.validationErrors = [];
            if (this.addForm.createAccount == 1 && !this.addForm.email)
                return swal({
                    title: "Required!",
                    text: "Email Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.createAccount == 1 && !this.addForm.password)
                return swal({
                    title: "Required!",
                    text: "Password's Father Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.EmployeeName)
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeType == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Employee's Type",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.EmployeeCNIC)
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.EmployeeContact)
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.password && !this.addForm.email && this.addForm.EmployeeType == "2")
                return swal({
                    title: "Required!",
                    text: "Employee's Type is Bus Host, Please Create Its Account For Refreshment Panel",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeTerminal == "0" && this.addForm.EmployeeType == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Employee's Terminal",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDepartment == "0" && this.addForm.EmployeeType == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Employee's Department",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDesignation == "0" && this.addForm.EmployeeType == "0")
                return swal({
                    title: "Required!",
                    text: "Employee's Designation is Required",
                    icon: "error",
                    timer: 2000
                });
            // if (!this.addForm.profile)
            //     return swal({
            //         title: "Required!",
            //         text: "Employee's Profile is Required",
            //         icon: "error",
            //         timer: 2000
            //     });


            formData.append('email', this.addForm.email);
            formData.append('password', this.addForm.password);
            formData.append('role', this.addForm.role);
            formData.append('EmployeeName', this.addForm.EmployeeName);
            formData.append('EmployeeFatherName', this.addForm.EmployeeFatherName);
            formData.append('EmployeeCNIC', this.addForm.EmployeeCNIC);
            formData.append('EmployeeTerminal', this.addForm.EmployeeTerminal);
            formData.append('EmployeeType', this.addForm.EmployeeType);
            formData.append('EmployeeContact', this.addForm.EmployeeContact);
            formData.append('EmployeeAddress', this.addForm.EmployeeAddress);
            formData.append('RefHiring', this.addForm.RefHiring);
            formData.append('HiringDate', this.addForm.HiringDate);
            formData.append('EmployeeDob', this.addForm.EmployeeDob);
            formData.append('EmployeeSalary', this.addForm.EmployeeSalary);
            formData.append('RadioSalaryTypeAdd', this.addForm.RadioSalaryTypeAdd);
            formData.append('workingDays', this.addForm.workingDays);
            formData.append('paidLeaves', this.addForm.paidLeaves);
            formData.append('bloodGroup', this.addForm.bloodGroup);
            formData.append('EmergencyContact', this.addForm.EmergencyContact);
            formData.append('jobDescription', this.addForm.jobDescription);
            formData.append('EmployeeDepartment', this.addForm.EmployeeDepartment);
            formData.append('EmployeeDesignation', this.addForm.EmployeeDesignation);
            formData.append('createAccount', this.addForm.createAccount);
            this.loading = true;
            const resEmployeeAdd = await this.callApi("post", "hrm/employee/store", formData, config);
            if (resEmployeeAdd.status == 201) {
                $(".modal").click();
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
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEmployeeAdd.data.errors) {
                        resEmployeeAdd.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
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
            if (this.profileEdit != '') {
                formData.append('profile', this.profileEdit ?? '');
            }
            if (this.attachmentsEdit != '') {
                formData.append('attachment', this.attachmentsEdit ?? '');
            }
            this.validationErrors = [];
            if (!this.editEmp.EmployeeName)
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });

            if (!this.editEmp.EmployeeCNIC)
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.editEmp.EmployeeContact)
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.editEmp.EmployeeDob)
                return swal({
                    title: "Required!",
                    text: "Employee's Date of Birth is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.editEmp.EmployeeTerminal == '0' && this.editEmp.EmployeeType == '0')
            return swal({
                title: "Required!",
                text: "Employee's Terminal is Required",
                icon: "error",
                timer: 2000
            });
            if (this.editEmp.EmployeeDepartment == "0" && this.editEmp.EmployeeType == '0')
            return swal({
                title: "Required!",
                text: "Please Select Employee's Department",
                icon: "error",
                timer: 2000
            });
            if (this.editEmp.EmployeeDesignation == '0' && this.editEmp.EmployeeType == '0')
            return swal({
                title: "Required!",
                text: "Employee's Designation is Required",
                icon: "error",
                timer: 2000
            });
            if (!this.editEmp.status)
                return swal({
                    title: "Required!",
                    text: "Employee Status is Required",
                    icon: "error",
                    timer: 2000
                });
            
            formData.append('userId', this.editEmp.userId);
            formData.append('id', this.editEmp.id);
            formData.append('email', this.editEmp.email);
            formData.append('password', this.editEmp.password);
            formData.append('EmployeeName', this.editEmp.EmployeeName);
            formData.append('EmployeeFatherName', this.editEmp.EmployeeFatherName);
            formData.append('EmployeeCNIC', this.editEmp.EmployeeCNIC);
            formData.append('EmployeeContact', this.editEmp.EmployeeContact);
            formData.append('EmployeeAddress', this.editEmp.EmployeeAddress);
            formData.append('RefHiring', this.editEmp.RefHiring);
            formData.append('HiringDate', this.editEmp.HiringDate);
            formData.append('EmployeeDob', this.editEmp.EmployeeDob);
            formData.append('EmployeeSalary', this.editEmp.EmployeeSalary);
            formData.append('RadioSalaryTypeAdd', this.editEmp.RadioSalaryTypeAdd);
            formData.append('workingDays', this.editEmp.workingDays);
            formData.append('paidLeaves', this.editEmp.paidLeaves);
            formData.append('bloodGroup', this.editEmp.bloodGroup);
            formData.append('EmergencyContact', this.editEmp.EmergencyContact);
            formData.append('jobDescription', this.editEmp.jobDescription);
            formData.append('EmployeeDepartment', this.editEmp.EmployeeDepartment);
            formData.append('EmployeeTerminal', this.editEmp.EmployeeTerminal);
            formData.append('EmployeeDesignation', this.editEmp.EmployeeDesignation);
            formData.append('status', this.editEmp.status);

            this.loading = true;
            
            const resEmployeeUpdate = await this.callApi("post", 'hrm/employee/update', formData, config);
            if (resEmployeeUpdate.status == 200) {
                $(".modal").click();
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
                if (resEmployeeUpdate.status == 422) {
                    $("#" + formID).scrollTop(0, 0);
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEmployeeUpdate.data.errors) {
                        resEmployeeUpdate.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        },

        async editEmployee(employ) {
            this.editEmp.userId = employ.user_id;
            this.editEmp.id = employ.id;
            this.editEmp.paidLeaves = employ.paid_leaves;
            this.editEmp.RadioSalaryTypeAdd = employ.salary_type;
            // this.editEmp.email = employ.user.email;
            this.editEmp.EmployeeName = employ.name;
            this.editEmp.EmployeeFatherName = employ.f_name;
            this.editEmp.EmployeeCNIC = employ.cnic;
            this.editEmp.EmployeeContact = employ.contact;
            this.editEmp.EmployeeAddress = employ.address;
            this.editEmp.RefHiring = employ.reference;
            this.editEmp.HiringDate = employ.hiring_date;
            this.editEmp.EmployeeDob = employ.dob;
            this.editEmp.EmployeeSalary = employ.salary;
            this.editEmp.workingDays = employ.working_days;
            this.editEmp.paidLeaves = employ.paid_leaves;
            this.editEmp.bloodGroup = employ.blood_group;
            this.editEmp.EmergencyContact = employ.emergency_contact;
            this.editEmp.jobDescription = employ.job_description;
            this.editEmp.EmployeeDepartment = employ.department_id;
            this.editEmp.EmployeeTerminal = employ.terminal_id;
            this.editEmp.EmployeeType = employ.employee_type;
            this.editEmp.EmployeeDesignation = employ.designation_id;
            this.editEmp.status = employ.status;

            const resEditSelective = await this.callApi("post", 'hrm/designation/selective', {id: employ.department_id});

            if (resEditSelective.status == 200) {
                if (resEditSelective.data.length == 0) {
                    this.editDesignations = '';
                    this.dataEdit.designation_id = 0;
                }
                this.editDesignations = resEditSelective.data;
            }
        },
        async hideEmployee() {
            this.loading = true;
            const resHide = await this.callApi("post", 'hrm/employee/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Employee Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#employee_table").DataTable().destroy();
                await this.fetchEmployees();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },

    },
    
};
</script>
<style scoped>

</style>
