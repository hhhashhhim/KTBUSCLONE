<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Account Categories</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Category
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
                                                       id="designation_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>City Name</th>
                                                        <th>Terminal Name</th>
                                                        <th>Department Name</th>
                                                        <th>No. Of Designations</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(designation, i) in designations" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ designation.terminal.city.name}}</td>
                                                        <td>{{ designation.terminal.name}}</td>
                                                        <td>{{ designation.name }}</td>
                                                        <td>{{ designation.designation_count }}</td>
                                                        <td>{{ designation.added_by.name }}</td>
                                                        <td>
                                                            <button
                                                                data-target="#detail-modal"
                                                                data-toggle="modal"
                                                                @click="designationDetail(designation.id)"
                                                                class="btn btn-info mx-2"
                                                            >
                                                                <i class="far fa-eye"></i>
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
                :heading="'Add Account Category'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="terminals">Tier 1 <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" @change="getSecondLevel(addForm.firstLevel)" v-model="addForm.firstLevel">
                            <option value="" selected>Select Tier 1</option>
                            <option value="1">Assets</option>
                            <option value="2">Liabilities</option>
                            <option value="3">Equity</option>
                            <option value="4">Revenue</option>
                            <option value="5">Expenses</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="departmentName">Tier 2<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addForm.secondLevel">
                            <option value="" selected>Select Tier 2</option>
                            <option
                                v-for="(second, i) in secondLevels"
                                :key="i"
                                :value="second.id"
                            >
                                {{ second.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="addForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addCategory"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Add Category' }}
                    </button>
                </template>
            </Add>

            
            <!-- Add Modal End -->
            <!--            Edit Model-->
            <!-- <Edit
                heading="Edit Designation"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="terminals">Terminals <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" id="terminals"
                                v-model="dataEdit.terminal_id"  @change="getEditDepartment(dataEdit.terminal_id)">
                            <option value="0">Select Terminal</option>
                            <option
                                v-for="(terminal, i) in terminals"
                                :value="terminal.id"
                                :key="i"
                            >{{ terminal.name }} ({{ terminal.city.name }})
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="departmentName">Department<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="dataEdit.department_id">
                            <option value="0" selected>Select Department</option>
                            <option
                                v-for="(department, i) in editDepartments"
                                :key="i"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDesignation"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Designation' }}
                    </button>
                </template>
            </Edit> -->
            <!--            Edit modal End-->

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "AccountCategoryPage",
    components: {
        Add,
        Edit,
        vueMask,
    },
    data() {
        return {
            addForm: {
                firstLevel: "",
                secondLevel: "",
                name: "",
            },
            designations: [],
            departmentsDetails: [],
            secondLevels: [],
            editDepartments: [],
            loading: false,
            formID: "category_form",
            editFormID: "edit_designation_form",
            deleteFormID: "delete_designation_form",
            validationErrors: [],
            terminals: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
        };
    },
    async created() {
        await this.fetchDesignations();
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
    },
    methods: {

        async fetchDesignations() {
            const resAllTerminals = await this.callApi("post", 'settings/tickets/terminals');
            if (resAllTerminals.status == 200) {
                this.terminals = resAllTerminals.data
            } else {
                console.log(resAllTerminals);
            }
            const resDesig = await this.callApi("post", 'hrm/designation');
            console.log(resDesig.data);
            if (resDesig.status == 200) {
                this.designations = resDesig.data
            } else {
                console.log(resDesig);
            }
            const resDepart = await this.callApi("post", 'hrm/department');
            console.log(resDepart);
            if (resDepart.status == 200) {
                this.editDepartments = resDepart.data
            } else {
                console.log(resDepart);
            }
            setTimeout(function () {
                $("#designation_table").DataTable();
                $("#show_designation").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm = {
                firstLevel: "",
                secondLevel: "",
                name: "",
            };
        },
        async getEditDepartment(id){
            // this.dataEdit = []
            const resDepartment = await this.callApi("post", 'hrm/designation/getTerminal', {'id': id});
            console.log(resDepartment);
            if (resDepartment.status == 200 && resDepartment.data.length > 0) {
                this.editDepartments = resDepartment.data;
            }else{
                this.dataEdit.department_id = 0;
            }
            if (resDepartment.status == 422) {

                let errorContent = "";
                let count = 0;
                for (const key in resDepartment.data.errors) {
                    resDepartment.data.errors[key].forEach((element) => {
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
        },
        async getSecondLevel(id) {
            const resSecondLevel = await this.callApi("post", 'accounts/coa/getSecondLevel', {'id': id});
           
            if (resSecondLevel.status == 200 && resSecondLevel.data.length > 0) {
                this.secondLevels = resSecondLevel.data;
            }else{
                this.addForm.secondLevel = "";
            }
            if (resSecondLevel.status == 422) {

                let errorContent = "";
                let count = 0;
                for (const key in resSecondLevel.data.errors) {
                    resSecondLevel.data.errors[key].forEach((element) => {
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
        },
        async addCategory() {
            this.validationErrors = [];
            if (this.addForm.firstLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 1",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.secondLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 2",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.name)
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            
            const resCategory = await this.callApi("post", "accounts/coa/category/store", this.addForm);
            if (resCategory.status == 201) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Category Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#designation_table").DataTable().destroy();
                $("#show_designation").DataTable().destroy();
                await this.fetchDesignations();
            } else {
                if (resCategory.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCategory.data.errors) {
                        resCategory.data.errors[key].forEach((element) => {
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

        // async updateDesignation() {
        //     this.validationErrors = [];
        //     if (this.dataEdit.department_id == "0")
        //         return swal({
        //             title: "Required!",
        //             text: "Please Select Department",
        //             icon: "error",
        //             timer: 2000
        //         });
        //     if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined')
        //         return swal({
        //             title: "Required!",
        //             text: "Name is Required",
        //             icon: "error",
        //             timer: 2000
        //         });
        //     this.loading = true;
        //     const resDepartmentEdit = await this.callApi("post", 'hrm/designation/update', this.dataEdit);
        //     if (resDepartmentEdit.status == 200) {
        //         this.loading = false;
        //         swal({
        //             title: "Success!",
        //             text: "Designation Updated Successfully",
        //             icon: "success",
        //             timer: 2000
        //         });
        //         $("#designation_table").DataTable().destroy();
        //         $("#show_designation").DataTable().destroy();
        //         await this.fetchDesignations();
        //     } else {
        //         if (resDepartmentEdit.status == 422) {
        //             this.loading = false;
        //             let errorContent = "";
        //             let count = 0;
        //             for (const key in resDepartmentEdit.data.errors) {
        //                 resDepartmentEdit.data.errors[key].forEach((element) => {
        //                     errorContent += (
        //                         (++count) + " - " + //creating serial no.
        //                         element + // main error
        //                         "\n" // creating new line
        //                     );
        //                 });
        //                 swal({
        //                     title: "Error",
        //                     text: errorContent,
        //                     icon: "error",
        //             timer: 2000
        //                 });

        //             }
        //         }
        //     }
        // },

        // editDesignation(designation) {
        //     this.dataEdit = designation
        // },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.departmentsDetails.splice(obj.index, 1)
                $("#designation_table").DataTable().destroy();
                $("#show_designation").DataTable().destroy();
                this.fetchDesignations();
            }
        }
    }
};
</script>
<style scoped>
</style>
