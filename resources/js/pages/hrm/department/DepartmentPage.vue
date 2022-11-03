<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Departments</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Department
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
                                                       id="department_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(department, i) in departments" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ department.name }}</td>
                                                        <td>{{ department.added_by.name }}</td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editdepartment(department)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button :data-target="'#' + deleteFormID"
                                                                    data-toggle="modal"
                                                                    @click="deleteModal(department,i)"
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
                :heading="'Add Department'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="addForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addDepartment"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Add Department' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Department"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="editName">Name <span class="text-danger">*</span></label>
                        <input type="text" id="editName" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDepartment"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Department' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Department ???'
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
    name: "DepartmentPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            addForm: {},
            departments: [],
            loading: false,
            formID: "department_form",
            editFormID: "edit_department_form",
            deleteFormID: "delete_department_form",
            validationErrors: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
        };
    },
    async created() {
        await this.fetchDepartments();
    },
    methods: {

        async fetchDepartments() {
            const resDepart = await this.callApi("post", 'hrm/department');
            console.log(resDepart);
            if (resDepart.status == 200) {
                this.departments = resDepart.data
            } else {
                console.log(resDepart);
            }
            setTimeout(function () {
                $("#department_table").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm = {};
        },

        async addDepartment() {
            this.validationErrors = [];
            if (this.addForm.name == "" || typeof this.addForm.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resDepartAdd = await this.callApi("post", "hrm/department/store", this.addForm);
            if (resDepartAdd.status == 201) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Department Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#department_table").DataTable().destroy();
                await this.fetchDepartments();
            } else {
                if (resDepartAdd.status == 422) {
                    this.loading = false;
                    for (const key in resDepartAdd.data.errors) {
                        resDepartAdd.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateDepartment() {
            this.validationErrors = [];
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resDepartmentEdit = await this.callApi("post", 'hrm/department/update', this.dataEdit);
            if (resDepartmentEdit.status == 200) {
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Department Name Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#department_table").DataTable().destroy();
                await this.fetchDepartments();
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

        async deleteModal(depart, i) {
            const deletingObj = {
                url: "hrm/department/delete",
                data: depart,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        editdepartment(departEdit) {
            this.dataEdit = departEdit
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.departments.splice(obj.index, 1)
                $("#department_table").DataTable().destroy();
                this.fetchDepartments();
            }
        }
    }
};
</script>
<style scoped>
</style>
