<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Deignations</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Designation
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
                                                        <th>Department Name</th>
                                                        <th>No. Of Designations</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(designation, i) in designations" :key="i">
                                                        <td>{{ i + 1 }}</td>
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
                :heading="'Add Designation'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                            <label for="departmentName">Department<span class="text-danger">*</span></label>
                        <select class="form-control" v-model="addForm.department" >
                            <option value="0" selected>Select Department</option>
                            <option
                                v-for="(department, i) in departments"
                                :key="i"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="addForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addDesignation"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Add Designation' }}
                    </button>
                </template>
            </Add>

            <div class="modal fade" id="detail-modal" tabindex="-1" aria-labelledby="detailModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Designation Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body m-1 p-1">
                            <div class="card-body my-0 py-0">
                                <!-- Table -->
                                <div class="row">
                                    <div class="col-12">
                                        <table class="table table-striped table-hover" id="show_designation">
                                            <thead>
                                            <tr>
                                                <th>Sr No.</th>
                                                <th>Name</th>
                                                <th>Added By</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(single, i) in departmentsDetails" :key="i">
                                                <td>{{ i + 1 }}</td>
                                                <td>{{ single.name }}</td>
                                                <td>{{ single.added_by.name }}</td>
                                                <td>
                                                    <button :data-target="'#' + editFormID" data-toggle="modal"
                                                            @click="editDesignation(single)"
                                                            class="btn btn-primary mx-1">
                                                        <i class="far fa-edit"></i>
                                                    </button>
                                                    <button :data-target="'#' + deleteFormID"
                                                            data-toggle="modal"
                                                            @click="deleteModal(single,i)"
                                                            class="btn btn-danger">
                                                        <i class="far fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- END TABLE -->
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Designation"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="departmentName">Department<span class="text-danger">*</span></label>
                        <select class="form-control" v-model="dataEdit.department_id" disabled>
                            <option value="0" selected>Select Department</option>
                            <option
                                v-for="(department, i) in departments"
                                :key="i"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDesignation"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Designation' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Designation ???'
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
    name: "DesignationPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            addForm: {},
            designations: [],
            departmentsDetails: [],
            departments: [],
            loading: false,
            formID: "designation_form",
            editFormID: "edit_designation_form",
            deleteFormID: "delete_designation_form",
            validationErrors: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
        };
    },
    async created() {
        await this.fetchDesignations();
    },
    methods: {

        async fetchDesignations() {
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
                this.departments = resDepart.data
            } else {
                console.log(resDepart);
            }
            setTimeout(function () {
                $("#designation_table").DataTable({





                });
                $("#show_designation").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm = {
                department: 0,
            };
        },
        async designationDetail(id) {
            const getDepartmentRes = await this.callApi("post", "hrm/designation/edit", {id: id});
            $("#show_designation").DataTable().destroy();
            this.departmentsDetails = getDepartmentRes.data;
            setTimeout(() => {
                $("#show_designation").DataTable();
            }, 300);
        },
        async addDesignation() {
            this.validationErrors = [];
            if (this.addForm.department == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Department",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.name == "" || typeof this.addForm.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resDesignationAdd = await this.callApi("post", "hrm/designation/store", this.addForm);
            if (resDesignationAdd.status == 201) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Designation Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#designation_table").DataTable().destroy();
                $("#show_designation").DataTable().destroy();
                await this.fetchDesignations();
            } else {
                if (resDesignationAdd.status == 422) {
                    this.loading = false;
                    for (const key in resDesignationAdd.data.errors) {
                        resDesignationAdd.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateDesignation() {
            this.validationErrors = [];
            if (this.dataEdit.department_id == "0")
                return swal({
                    title: "Required!",
                    text: "Please Select Department",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resDepartmentEdit = await this.callApi("post", 'hrm/designation/update', this.dataEdit);
            if (resDepartmentEdit.status == 200) {
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Designation Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#designation_table").DataTable().destroy();
                $("#show_designation").DataTable().destroy();
                await this.fetchDesignations();
            } else {
                if (resDepartmentEdit.status == 422) {
                    this.loading = false;
                    for (const key in resDepartmentEdit.data.errors) {
                        resDepartmentEdit.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async deleteModal(designation, i) {
            const deletingObj = {
                url: "hrm/designation/delete",
                data: designation,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        editDesignation(designation) {
            this.dataEdit = designation
        },
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
