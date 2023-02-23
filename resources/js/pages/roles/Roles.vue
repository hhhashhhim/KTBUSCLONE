<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Roles</h4>
                            <div class="card-header-action">
                                <a href="#add-modal" data-toggle="modal" :data-target="'#' + formID" class="btn btn-primary">
                                    Add New Role
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="role_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(role, i) in roles" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ role.name }}</td>
                                                            <td>
                                                                <router-link
                                                                    :to="{ name: 'role.permission', params: { id: role.id } }"
                                                                    class="btn btn-warning">
                                                                    <i class="fas fa-user-shield"></i>
                                                                </router-link>
                                                                <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(role)" class="btn btn-primary mx-2">
                                                                    <i class="far fa-edit"></i>
                                                            </button>
                                                                <!--                                                                <a href="#delete-modal" data-toggle="modal" @click="deleteModal(role,i)" class="btn btn-danger">-->
                                                                <!--                                                                    <i class="far fa-trash-alt"></i>-->
                                                                <!--                                                                </a>-->
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
            <Add heading="New Role" :errors="this.validationErrors" :success="success" :formID="formID">
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Name" id="name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="this.loading" @click="add">
                        {{ this.loading ? "Loading..." : "Add Role" }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit heading="Edit Role" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Name" id="name" v-model="dataEdit.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="this.loadingEdit" @click="update">
                        {{ this.loadingEdit ? "Loading..." : "Update Role" }}
                    </button>
                </template>
            </Edit>

            <!-- Add Modal -->
            <Delete confirmationMessage="Are You Sure You want To Delete This Role ???" />

        </div>
    </section>
</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
import Delete from '../../components/Delete.vue';
import { mapGetters } from 'vuex';

export default {
    name: "Role",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            roles: [],
            formID: 'newRole',
            editFormID: "editRolesButton",
            data: {
                name: "",
                company_id: ""
            },
            dataEdit: {
                id: "",
                name: "",
                company_id: "",
            },
            delId: "",
            success: false,
            loading: false,
            loadingEdit: false,
        }
    },
    async created() {
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
        await this.fetchRoles();
    },
    methods: {
        async fetchRoles() {
            const res = await this.callApi("post", 'role', { name: this.data.name });
            if (res.status == 200) {
                this.roles = res.data
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $("#role_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = []
            if (this.data.name == "" || typeof this.data.name == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            $("#role_table").DataTable().destroy();
            this.loading = true;
            const res = await this.callApi("post", 'role/store', this.data);
            if (res.status == 200) {
                this.loading = false;
                swal({
                    title: "Success!!",
                    text: "Role Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.roles.unshift(res.data);
                this.data.name = this.data.company_id = "";
                await this.fetchRoles();
                setTimeout(() => {
                    this.success = ""
                }, 3000);
                setTimeout(() => {
                    $("#role_table").DataTable();
                }, 300);
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
        edit(role) {
            this.dataEdit = role;
        },
        async update() {
            this.validationErrors = []
            if (this.dataEdit.name == "") return this.errorsArray("Role Name is Required", "Name");
            $("#role_table").DataTable().destroy();
            const res = await this.callApi("post", 'role/update', this.dataEdit);
            if (res.status == 201) {
                this.success = "Role Updated Successfully";
                const res = await this.callApi("post", 'role', { name: this.data.name });
                if (res.status == 200) {
                    this.roles = res.data
                }
                this.dataEdit.name = this.dataEdit.company_id = "";
                setTimeout(() => {
                    this.success = ""
                }, 3000);
                setTimeout(() => {
                    $("#role_table").DataTable();
                }, 300);
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach(element => {
                            this.errorsArray(element, key)
                        });
                    }
                }
            }
        },
        async deleteModal(role, i) {
            const deletingObj = {
                url: "role/delete",
                data: role,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            console.log(obj);
            if (obj.isDeleted) {
                this.roles.splice(obj.index, 1)
                setTimeout(() => {
                    $("#role_table").DataTable();
                }, 300);
            }
        }
    }
}
</script>
