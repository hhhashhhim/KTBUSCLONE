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
                                        <div class="card-header">
                                            <h4></h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="edit_dis"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Percentage</th>
                                                        <th>Flat Amount</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(discount, i) in discounts" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ discount.name }}</td>
                                                        <td v-if="discount.percentage">{{
                                                                discount.percentage
                                                            }}{{ discount.type == 'percentage' ? '%' : '' }}
                                                        </td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="discount.flat">{{ discount.flat }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ discount.is_active == 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ discount.added_by.name }}</td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(discount)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button :data-target="'#' + deleteFormID"
                                                                    data-toggle="modal"
                                                                    @click="deleteModal(discount,i)"
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
                        <input type="text" class="form-control" id="workingDays" v-model="addForm.workingDays"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paidLeaves">paid Leaves<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paidLeaves" v-model="addForm.paidLeaves"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bloodGroup">Blood Group<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bloodGroup" v-model="addForm.bloodGroup"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Salary<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="salary" v-model="addForm.EmployeeSalary"/>
                    </div>
                    <div class="form-group col-md-6 mt-4 pt-3">
                        <label for="salaryType" class="mr-3">Salary Type</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="bank" name="bank" class="custom-control-input" value="bank"
                                   v-model="addForm.bank" @click="salaryType('bank')">
                            <label class="custom-control-label" for="bank">Bank</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="cash" checked="" name="cash" class="custom-control-input"
                                   value="cash" v-model="addForm.cash" @click="salaryType('cash')">
                            <label class="custom-control-label" for="cash">Percentage</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Employee Picture</label>
                        <div class="border border-dark my-3" style="height: 300px;  width: 300px; background-color: #d9d9d9">
                            <img v-if="urlProfile"  class="img-responsive thumbnail rounded " src="" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file"  @change="onFileChange($event, 'profile')" accept=".png, .jpg, .jpeg" class="custom-file-input" id="profilePic">
                            <label class="custom-file-label" for="profilePic">Choose .jpg, .png, .jpeg Image</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Front</label>
                        <div class="border border-dark my-3" style="height: 300px;  width: 300px; background-color: #d9d9d9">
                            <img v-if="urlCNICFront"  class="img-responsive thumbnail rounded " src="" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file"  @change="onFileChange($event, 'cnicFront')" accept=".png, .jpg, .jpeg" class="custom-file-input" id="cincBack">
                            <label class="custom-file-label" for="cincBack">Choose .jpg, .png, .jpeg Image</label>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <label for="salary">Upload CNIC Back</label>
                        <div class="border border-dark my-3" style="height: 300px;  width: 300px; background-color: #d9d9d9">
                            <img v-if="urlCNICBack"  class="img-responsive thumbnail rounded " src="" alt="">
                        </div>
                        <div class="custom-file">
                            <input type="file"  @change="onFileChange($event, 'cnicBack')" accept=".png, .jpg, .jpeg" class="custom-file-input" id="cnicBack`">
                            <label class="custom-file-label" for="cnicBack">Choose .jpg, .png, .jpeg Image</label>
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
                heading="Edit Discount"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="DiscountName">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                    <div class="form-group col-md-3 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editPercentage" name="editPercentageAmount"
                                   class="custom-control-input" :checked="dataEdit.type == 'percentage'"
                                   value="percentage" v-model="dataEdit.discountPercentageRadio"
                                   @click="discountApply('editPercentage')">
                            <label class="custom-control-label" for="editPercentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editFlat" name="editFlatAmount" class="custom-control-input"
                                   :checked="dataEdit.type == 'flat'" value="flat"
                                   v-model="dataEdit.discountPercentageRadio" @click="discountApply('editFlat')">
                            <label class="custom-control-label" for="editFlat">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="dataEdit.type == 'percentage'">
                        <label for="SurchargePercentage">Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="dataEdit.percentage"
                                   placeholder="Enter Percentage"
                                   @keypress="isNumber($event); numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="dataEdit.type == 'flat'">
                        <label for="SurchargePercentage">Flat Amount <span class="text-danger">*</span><span
                            class="text-muted">max: 10K</span></label>
                        <input type="text" class="form-control" maxlength="5" v-model="dataEdit.flat"
                               placeholder="Enter Flat Amount"
                               @keypress="isNumber($event)">
                    </div>
                    <div class="col-md-12">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" id="editCheckBox"
                                       @change="editCheckBox($event)" v-bind:checked="dataEdit.is_active == 1"/>
                                <span class="colorinput-color bg-success"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDiscount"
                            :class="loading?'disabled':''">
                        {{ loading ? 'Loading...' : 'Update Discount' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Discount ???'
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
            addForm:{},
            loading: false,
            discounts: [],
            isActive: 1,
            formID: "employees_form",
            editFormID: "edit_employees_form",
            deleteFormID: "delete_employees_form",
            validationErrors: [],
            success: false,
            showDiscountDivPercentage: true,
            showDiscountDivFlat: false,
            error: false,
            DiscountName: '',
            DiscountPercentage: '',
            DiscountFlat: '',
            delId: "",
            PercentageName: '',
            discountPercentageRadio: 'percentage',
            dataEdit: {
                id: "",
                name: "",
                percentage: "",
                flat: "",
                is_Active: "",
            },
        };
    },
    async created() {
        await this.fetchDiscount();
    },
    methods: {
        numberRange: function (evt) {
            const val = parseInt(evt.target.value + evt.key);
            if (!isNaN(val) && val > 100) {
                evt.preventDefault();
                return swal({
                    title: "Limited!",
                    text: "Percentage is must be less then 100",
                    icon: "error",
                    timer: 2000
                });
            }

        },

        discountApply(value) {
            if (value == "percentage") {
                this.showDiscountDivPercentage = true;
                this.showDiscountDivFlat = false;
            }
            if (value == "flat") {
                this.showDiscountDivPercentage = false;
                this.showDiscountDivFlat = true;
            }
            if (value == "editPercentage") {
                this.dataEdit.type = 'percentage';
            }
            if (value == "editFlat") {
                this.dataEdit.type = 'flat';

            }
        },
        async fetchDiscount() {
            const res = await this.callApi("post", 'discount');
            if (res.status == 200) {
                this.discounts = res.data
            } else {
                console.log(res);
            }
        },
        clearForm: function () {
            this.DiscountName = '';
            this.DiscountPercentage = '';
            this.DiscountFlat = '';
            this.discountPercentageRadio = "percentage";
            this.isActive = 1;
            this.showDiscountDivPercentage = true;
            this.showDiscountDivFlat = false;
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

        checkBox: function (e) {
            if (e.target.checked) {
                this.isActive = 1;
            } else {
                this.isActive = 0;
            }
        },
        editCheckBox: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_Active = 1;
            } else {
                this.dataEdit.is_Active = 0;
            }
        },

        async addEmployee() {
            this.validationErrors = [];
            if (this.DiscountName == "")
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.discountPercentageRadio == "percentage") {
                if (this.DiscountPercentage == "" || typeof this.DiscountPercentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.discountPercentageRadio == "flat") {
                if (this.DiscountFlat == "" || typeof this.DiscountFlat == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Amount Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            this.loading = true;
            const data = {
                name: this.DiscountName,
                type: this.discountPercentageRadio,
                percentage: this.DiscountPercentage,
                flat: this.DiscountFlat,
                active: this.isActive,
            }

            const res = await this.callApi("post", "discount/store", data);
            if (res.status == 200) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Discount Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchDiscount();
                window.scrollTo(0, 0);

            } else {
                if (res.status == 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateDiscount() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.type == "percentage" || this.dataEdit.discountPercentageRadio == 'percentage') {
                if (this.dataEdit.percentage == "" || this.dataEdit.percentage == null || typeof this.dataEdit.percentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.dataEdit.type == 'flat' || this.dataEdit.discountPercentageRadio == "flat") {
                if (this.dataEdit.flat == "" || this.dataEdit.flat == null || typeof this.dataEdit.flat == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Amount Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            this.loading = true;
            const res = await this.callApi("post", 'discount/update', this.dataEdit);
            if (res.status === 200) {
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Discount Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchDiscount();
            } else {
                if (res.status === 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        },


        async deleteModal(discount, i) {
            const deletingObj = {
                url: "discount/delete",
                data: discount,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(dis) {
            this.dataEdit = dis;
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        }
    }
};
</script>
<style scoped>
table,
table * {
    font-size: 10px;
}

.modal-cell {
    padding: 0 !important;
    position: relative;
}

.modal-cell .modal-btn {
    height: 100%;
    transition: 0.5s transform;
}

.modal-cell:hover .modal-btn {
    position: absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}

.header-select {
    width: 35%;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
    opacity: 0;
}

table, tr, th, td, option, select, label, button, a, div, p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
