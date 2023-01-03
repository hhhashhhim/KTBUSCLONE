<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Users</h4>
                            <div class="card-header-action">
                                <a
                                    href="#add-modal"
                                    data-toggle="modal"
                                    :data-target="'#'+formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
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
                                                    id="users_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Contact</th>
                                                        <th>Role</th>
                                                        <!--                                                        <th>Action</th>-->
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(user, i) in users" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ user.name }}</td>
                                                        <td>{{ user.email }}</td>
                                                        <td>{{ user.contact }}</td>
                                                        <th>{{ user.role ? user.role.name : "Not Found" }}</th>
                                                        <!--                                                        <td>-->
                                                        <!--                                                            <a-->
                                                        <!--                                                                href="#edit-modal"-->
                                                        <!--                                                                data-toggle="modal"-->
                                                        <!--                                                                @click="edit(user)"-->
                                                        <!--                                                                class="btn btn-primary mx-1"-->
                                                        <!--                                                            >-->
                                                        <!--                                                                <i class="far fa-edit"></i>-->
                                                        <!--                                                            </a>-->
                                                        <!--                                                            &lt;!&ndash;                                <a&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                  href="#delete-modal"&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                  data-toggle="modal"&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                  @click="deleteModal(user, i)"&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                  class="btn btn-danger"&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                >&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                  <i class="far fa-trash-alt"></i>&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                </a>&ndash;&gt;-->
                                                        <!--                                                        </td>-->
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
                heading="New User"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"

            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Name <span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Name"
                            id="name"
                            autocomplete="off"
                            v-model="data.name"
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
                            v-model="data.email"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                        <vue-mask id="phone"
                                  class="form-control"
                                  v-model="data.contact"
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
                            v-model="data.password"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="terminals">Terminal <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" id="terminals"
                                v-model="data.terminal_id">
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
                        <label for="role">Role <span class="text-danger ml-1">*</span></label>
                        <div class="float-right badge badge-primary mx-0 mb-1" style="cursor: pointer"
                             data-toggle="modal" data-target="#addRoleModal"> Add New Role
                        </div>
                        <select
                            type="text"
                            class="form-control"
                            id="role"
                            v-model="data.role"
                        >
                            <option value="0" selected>Select Role</option>
                            <option v-for="(role, i) in roles" :value="role.id" :key="i">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="this.loading" @click="add">
                        {{ this.loading ? 'Loading...' : 'Add User' }}
                    </button>
                </template>
            </Add>
            <!-- Add Modal -->
            <!--
                        addRoles New-->
            <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Role</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger ml-1">*</span></label>
                                    <input type="text" id="name" class="form-control" v-model="roleName"/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="addNewRole()"
                                    :disabled="loadingRole">
                                {{ loadingRole ? 'Loading...' : 'Add Role' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!--End MOdal-->
            <Edit
                heading="Edit User"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Name</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Name"
                            id="name"
                            v-model="dataEdit.name"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Email"
                            id="email"
                            v-model="dataEdit.email"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact">Contact</label>
                        <vue-mask id="phone"
                                  class="form-control"
                                  v-model="dataEdit.contact"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsContact"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            class="form-control"
                            placeholder="Enter Password"
                            id="password"
                            v-model="dataEdit.password"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="terminals">Terminal <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" id="terminals"
                                v-model="dataEdit.terminal_id">
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
                        <label for="role">Role</label>
                        <select
                            type="text"
                            class="form-control"
                            id="role"
                            v-model="dataEdit.role"
                        >
                            <option value="">Select Role</option>
                            <option v-for="(role, i) in roles" :value="role.id" :key="i">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-success"
                            @click="update"
                        >
                            Update User
                        </button>
                    </div>
                </div>
            </Edit>

            <!-- Add Modal -->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This "USER" ???'
            />
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";


export default {
    name: "Role",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },
            roles: [],
            users: [],
            formID: 'newUser',
            roleName: '',
            data: {
                name: "",
                email: "",
                contact: "",
                password: "",
                role: 0,
                company_id: "",
                terminal_id: 0,
            },
            dataEdit: {
                terminal_id: 0,
            },
            terminals: [],
            success: false,
            loading: false,
            loadingEdit: false,
            loadingRole: false,
        };
    },
    async created() {
        await this.fetchUsers();
    },
    methods: {
        clearForm: function () {
            this.data.name = "";
            this.data.email = "";
            this.data.contact = "";
            this.data.password = "";
            this.data.role = 0;
            this.roleName = '';
        },

        async fetchUsers() {
            const userRes = await this.callApi("post", "user");
            if (userRes.status == 200) {
                this.users = userRes.data;
            } else {
                console.log(userRes)
            }
            const roleRes = await this.callApi("post", "company/roles", {id: this.data.company_id});
            if (roleRes.status == 200) {
                this.roles = roleRes.data;
            } else {
                console.log(roleRes)
            }

            const resDepart = await this.callApi("post", 'terminals/all');
            if (resDepart.status == 200) {
                this.terminals = resDepart.data
            } else {
                console.log(resDepart);
            }
            setTimeout(() => {
                $("#users_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = [];

            if (this.data.name == "" || typeof this.data.name == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.email == "" || typeof this.data.email == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Email is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.password == "" || typeof this.data.password == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Password is Required",
                    icon: "error",
                    timer: 4000
                });
            if (this.data.terminal_id == 0)
                return swal({
                    title: "Required!!",
                    text: "Please Select Terminal",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.role == 0)
                return swal({
                    title: "Required!!",
                    text: "Please Select Role",
                    icon: "error",
                    timer: 2000
                });


            $("#users_table").DataTable().destroy();
            this.loading = true;
            const res = await this.callApi("post", "user/store", this.data);
            if (res.status == 200) {
                this.loading = false;

                swal({
                    title: "Success!!",
                    text: "User Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.users = res.data
                this.data.name = this.data.email = this.data.contact = this.data.password = this.data.role = this.data.company_id = "";
                await this.fetchUsers();
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
                            timer: 4000
                        });
                    }
                }
            }
        },
        async addNewRole() {
            if (this.roleName == '' || typeof this.roleName == 'undefined') {
                return swal({
                    title: "Required!!!",
                    text: "Role Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingRole = true;
            const resAddRole = await this.callApi("post", "role/store", {name: this.roleName});
            if (resAddRole.status == 200) {
                this.loadingRole = false;
                this.roles.push(resAddRole.data);
                this.roleName = '';
                swal({
                    title: "Success!!",
                    text: "Role added Successfully ",
                    icon: "success",
                    timer: 2000
                });
            }
            if (resAddRole.status == 422) {
                this.loadingRole = false;
                let errorContent = "";
                let count = 0;
                for (const key in resAddRole.data.errors) {
                    resAddRole.data.errors[key].forEach((element) => {
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

        },
        async edit(user) {
            this.dataEdit = user;
            this.dataEdit.role = user.role_id;
            const roleRes = await this.callApi("post", "company/roles", {id: user.company_id});
            this.roles = roleRes.data;
        },
        async update() {
            this.validationErrors = [];
            if (this.dataEdit.name == "")
                return this.errorsArray("User Name is Required", "Name");
            $("#users_table").DataTable().destroy();
            const res = await this.callApi("post", "user/update", this.dataEdit);
            if (res.status == 201) {
                this.success = "User Updated Successfully";
                this.dataEdit = "";
                const userRes = await this.callApi("post", "user", {});
                this.users = userRes.data;
                setTimeout(() => {
                    this.success = "";
                    $("#edit-modal").modal("hide");
                }, 3000);
                setTimeout(() => {
                    $("#users_table").DataTable();
                }, 3000);
            } else {
                if (res.status == 422) {
                    console.log();
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        async deleteModal(user, i) {
            const deletingObj = {
                url: "user/delete",
                data: user,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
        async fetchCompanyRoles() {
        }
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            console.log(obj);
            if (obj.isDeleted) {
                this.users.splice(obj.index, 1);
            }
        },
    },
};
</script>
