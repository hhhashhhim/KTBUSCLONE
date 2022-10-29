<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Leaves</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Apply For Leave
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
                                                <table class="table dataTables table-striped table-hover" id="employee_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Form</th>
                                                        <th>To</th>
                                                        <th>Reason</th>
<!--                                                        <th>Days</th>-->
                                                        <th>Decision Maker</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(leave, i) in leaves" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ leave.from}}</td>
                                                        <td>{{ leave.to}}</td>
                                                        <td>{{ leave.reason}}</td>
<!--                                                        <td>{{ leave.days}}</td>-->
                                                        <td>{{ cnicFormat(leave.cnic)}}</td>
                                                        <td><div :class="getStatusClass(leave.status)">{{ getStatusName(leave.status)}}</div></td>
                                                        <td>{{ leave.added_by.name }}</td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editEmployee(leave)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button :data-target="'#' + deleteFormID"
                                                                    data-toggle="modal"
                                                                    @click="deleteModal(leave,i)"
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
                        <input type="text" class="form-control" id="department" v-model="addForm.EmployeeDepartment"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="designation">Designation<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="designation" v-model="addForm.EmployeeDesignation"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="workingDays">Working Days<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="workingDays" v-model="addForm.workingDays" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paidLeaves" v-model="addForm.paidLeaves" @keypress="isNumber($event)"/>
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
                            <input type="radio" id="bank" name="salaryType" class="custom-control-input" value="bank" v-model="addForm.salaryTypeRadio" @click="salaryType('bank')">
                            <label class="custom-control-label" for="bank">Bank</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="cash" checked="" name="salaryType" class="custom-control-input" value="cash" v-model="addForm.salaryTypeRadio" @click="salaryType('cash')">
                            <label class="custom-control-label" for="cash">Cash</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Employee Picture</label>
                        <div class="border border-dark my-3" style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlProfile" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlProfile" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'profile')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="profilePic">
                            <label class="custom-file-label overflow-hidden" for="profilePic">{{ nameProfile != '' ? nameProfile : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Front</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICFront" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
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
                            <img v-if="urlCNICBack" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICBack" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicBack')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cnicBack`">
                            <label class="custom-file-label overflow-hidden" for="cnicBack">{{
                                    nameBack != '' ? nameBack : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addEmployee" :class="loading?'disabled':''">
                        {{ loading ? 'Loading...' : 'Add Employee' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
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
                        <input type="text" class="form-control" id="department" v-model="dataEdit.department"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="designation">Designation<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="designation" v-model="dataEdit.designation"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="workingDays">Working Days<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="workingDays" v-model="dataEdit.working_days" @keypress="isNumber($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">Paid Leaves<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paidLeaves" v-model="dataEdit.paid_leaves" @keypress="isNumber($event)"/>
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
                            <input type="radio" id="bank" :checked=" dataEdit.salary_type == 'bank'" name="salaryTypeEdit" class="custom-control-input" value="bank" v-model="dataEdit.salaryTypeRadioEdit" @click="editSalaryType('bank')">
                            <label class="custom-control-label" for="bank">Bank</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="cash" :checked=" dataEdit.salary_type == 'cash'" name="salaryTypeEdit" class="custom-control-input" value="cash" v-model="dataEdit.salaryTypeRadioEdit" @click="editSalaryType('cash')">
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
                        <div class="border border-dark my-3" style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlProfileEdit" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlProfileEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'profileEdit')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="profilePic">
                            <label class="custom-file-label overflow-hidden" for="profilePic">{{ nameProfileEdit != '' ? nameProfileEdit : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Front</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICFrontEdit" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICFrontEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicFrontEdit')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cincBack">
                            <label class="custom-file-label overflow-hidden" for="cincBack">{{
                                    nameFrontEdit != '' ? nameFrontEdit : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Back</label>
                        <div class="border border-dark my-3"
                             style="height: 250px;  width: 250px; background-color: #d9d9d9">
                            <img v-if="urlCNICBackEdit" class="img-responsive thumbnail rounded "  style="display: block;  margin-left: auto;  margin-right: auto; margin-top: auto; margin-bottom: auto; height: 248px;  width: 248px;"
                                 :src="urlCNICBackEdit" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file" @change="onFileChange($event, 'cnicBackEdit')" accept=".png, .jpg, .jpeg"
                                   class="custom-file-input" id="cnicBack`">
                            <label class="custom-file-label overflow-hidden" for="cnicBack">{{
                                    nameBackEdit != '' ? nameBackEdit : 'Choose.jpg, .png, .jpeg Image'
                                }}</label>
                        </div>

                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateEmployees"
                            :class="loading?'disabled':''">
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
                salaryTypeRadio: 'cash'
            },
            leaves : [],
            urlProfile: '',
            urlCNICBack: '',
            urlCNICFront: '',
            urlProfileEdit: '',
            urlCNICBackEdit: '',
            urlCNICFrontEdit: '',
            back:'',
            front:'',
            profile:'',
            nameProfile: '',
            nameBack: '',
            nameFront: '',
            nameProfileEdit: '',
            nameBackEdit: '',
            nameFrontEdit: '',
            loading: false,
            formID: "leaves_form",
            editFormID: "edit_leaves_form",
            deleteFormID: "delete_leaves_form",
            validationErrors: [],
            success: false,
            error: false,
            delId: "",
            discountPercentageRadio: 'percentage',
            dataEdit: {},
            editImg: {},
        };
    },
    async created() {
        await this.fetchEmployees();
    },
    methods: {
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },
        onFileChange: function (e, imgTag) {
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png)$/i)) {
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

        async fetchEmployees() {
            const resEmployeeIndex = await this.callApi("post", 'hrm/employee');
            if (resEmployeeIndex.status == 200) {
                this.leaves = resEmployeeIndex.data

            } else {
                console.log(resEmployeeIndex);
            }
            setTimeout(function () {
                $("#employee_table").DataTable();
            }, 300);
        },
        getStatusName: function(value){
            if(value == 'W'){
                return 'Working';
            }
            if(value == 'R'){
                return 'Resigned';
            }
            if(value == 'T'){
                return 'Ternimated';
            }
        },
        getStatusClass: function(value){
            if(value == 'W'){
                return 'badge badge-success';
            }
            if(value == 'R'){
                return 'badge badge-info';
            }
            if(value == 'T'){
                return 'badge badge-danger';
            }
        },
        clearForm: function () {
            this.addForm = {};
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
            if(this.profile != '') {
                formData.append('profile', this.profile);
            }
            if(this.front != '') {
                formData.append('cnicFront', this.front);
            }
            if(this.back != '') {
                formData.append('cnicBack', this.back);
            }

            this.validationErrors = [];
            if (this.addForm.EmployeeName == "" || typeof this.addForm.EmployeeName == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeFatherName == "" || typeof this.addForm.EmployeeFatherName == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Father Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeCNIC == "" || typeof this.addForm.EmployeeCNIC == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeContact == "" || typeof this.addForm.EmployeeContact == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDob == "" || typeof this.addForm.EmployeeDob == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Date of Birth is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.HiringDate == "" || typeof this.addForm.HiringDate == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Hiring Date  is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeAddress == "" || typeof this.addForm.EmployeeAddress == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Address is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDepartment == "" || typeof this.addForm.EmployeeDepartment == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Department Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeDesignation == "" || typeof this.addForm.EmployeeDesignation == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Designation is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.workingDays == "" || typeof this.addForm.workingDays == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Working Days is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.paidLeaves == "" || typeof this.addForm.paidLeaves == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.bloodGroup == "" || typeof this.addForm.bloodGroup == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.EmployeeSalary == "" || typeof this.addForm.EmployeeSalary == 'undefined' )
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
                ImgEmployeeRecord = logoRes.data ??  "";
            }
            const resEmployeeAdd = await this.callApi("post", "hrm/employee/store", {...this.addForm, ImgEmployeeRecord});
            console.log(resEmployeeAdd.data)
            if (resEmployeeAdd.status == 200) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Employee record Successfully Created!",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchEmployees();
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
            if(this.editImg.profile != '') {
                formData.append('profile', this.editImg.profile);
            }
            if(this.editImg.front != '') {
                formData.append('cnicFront', this.editImg.front);
            }
            if(this.editImg.back != '') {
                formData.append('cnicBack', this.editImg.back);
            }
            this.validationErrors = [];
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.f_name == "" || typeof this.dataEdit.f_name == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Father Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.cnic == "" || typeof this.dataEdit.cnic == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's CNIC Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.contact == "" || typeof this.dataEdit.contact == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.dob == "" || typeof this.dataEdit.dob == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Date of Birth is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.hiring_date == "" || typeof this.dataEdit.hiring_date == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Hiring Date  is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.address == "" || typeof this.dataEdit.address == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Address is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.department == "" || typeof this.dataEdit.department == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Department Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.designation == "" || typeof this.dataEdit.designation == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Designation is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.working_days == "" || typeof this.dataEdit.working_days == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Working Days is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.paid_leaves == "" || typeof this.dataEdit.paid_leaves == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.blood_group == "" || typeof this.dataEdit.blood_group == 'undefined' )
                return swal({
                    title: "Required!",
                    text: "Employee's Paid Leaves is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.salary == "" || typeof this.dataEdit.salary == 'undefined' )
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
                ImgEmployeeRecordEdit = logoRes.data ??  "";
            }

            const resEmployeeUpdate = await this.callApi("post", 'hrm/employee/update', {...this.dataEdit, ImgEmployeeRecordEdit});
            if (resEmployeeUpdate.status === 200) {
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Employee Record Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchEmployees();
            } else {
                if (resEmployeeUpdate.status === 422) {
                    $("#"+formID).scrollTop(0,0);
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

        editEmployee(employ) {
            this.dataEdit = employ;
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.leaves.splice(obj.index, 1)
                this.fetchEmployees();
                // setTimeout(function () {
                //     $('#employee_table').DataTable();
                // }, 300);
            }
        }
    }
};
</script>
<style scoped>
</style>
