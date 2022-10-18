<template>
    <div>
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card card-success">
                            <div class="card-header">
                                <h4>Companies</h4>
                                <div class="card-header-action">
                                    <a
                                        href="#"
                                        data-toggle="modal"
                                        :data-target="'#' + formID"
                                        class="btn btn-success"
                                    >
                                        Add New Company
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
                                                        id="edit_loc"
                                                    >
                                                        <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Contact</th>
                                                            <th>Location</th>
                                                            <th>Logo</th>
                                                            <th>Action</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr v-for="(company, i) in cities" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ company.name }}</td>
                                                            <td>{{ phoneFormat(company.contact) }}</td>
                                                            <td>{{ company.location }}</td>
                                                            <td>
                                                                <img
                                                                    :src="$store.state.app_url +'uploads/company/logo/'+(company.logo)"
                                                                    style="width:200px;" alt="">
                                                            </td>
                                                            <td>
                                                                <a
                                                                    href="#edit-modal"
                                                                    data-toggle="modal"
                                                                    @click="edit(company.id, i)"
                                                                    class="btn btn-primary mx-1"
                                                                >
                                                                    <i class="far fa-edit"></i>
                                                                </a>
                                                                <!--                                  <a-->
                                                                <!--                                    href="#delete-modal"-->
                                                                <!--                                    data-toggle="modal"-->
                                                                <!--                                    @click="deleteModal(company, i)"-->
                                                                <!--                                    class="btn btn-danger"-->
                                                                <!--                                  >-->
                                                                <!--                                    <i class="far fa-trash-alt"></i>-->
                                                                <!--                                  </a>-->
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
                    heading="Company"
                    :errors="this.validationErrors"
                    :success="success"
                    :formID="formID"
                >
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="name">Company Name</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Name"
                                id="name"
                                v-model="data.name"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact">Contact</label>
                            <vue-mask
                                class="form-control"
                                v-model="data.contact"
                                mask="0000-0000000"
                                :raw="false"
                                :options="options">
                            </vue-mask>
<!--                            <input-->
<!--                                type="text" @keypress="isNumber($event)"-->
<!--                                class="form-control"-->
<!--                                placeholder="Enter contact"-->
<!--                                id="contact"-->
<!--                                v-model="data.contact"-->
<!--                            />-->
                        </div>
                        <div class="form-group col-md-4">
                            <label for="Logo">Logo</label>
                            <input
                                type="file"
                                class="form-control"
                                id="Logo"
                                @change="uploadLogo($event, 'add')"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="userName">Name</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Name"
                                id="userName"
                                v-model="data.userName"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="email">Email</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Email"
                                id="email"
                                v-model="data.email"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                placeholder="Enter Password"
                                id="password"
                                v-model="data.password"
                            />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location</label>
                            <textarea
                                class="form-control"
                                placeholder="Enter Location"
                                id="location"
                                v-model="data.location"
                                cols="30"
                                rows="10"
                            ></textarea>
                        </div>
                        <div class="form-group col-md-12 text-center mt-4">
                            <h3>Modular Permissions</h3>
                        </div>
                    </div>

                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Module Name</th>
                            <th>Permission</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="(moduleName, i) in data.modules" :key="i">
                            <tr>
                                <td>{{ i + 1 }}</td>
                                <td>
                                    <div class="text-capitalize">
                                        {{ moduleName.name }}
                                    </div>
                                </td>
                                <td>
                                    <label class="colorinput mx-3">
                      <span v-if="i != 'name'">
                        <input
                            :checked="mod"
                            type="checkbox"
                            :value="true"
                            class="colorinput-input"
                            v-model="moduleName.allow"
                        />
                        <span class="colorinput-color bg-success"></span>
                      </span>
                                    </label>
                                </td>
                            </tr>
                            <tr v-if="moduleName.allow">
                                <td></td>
                                <td colspan="2" class="py-5">
                                    <label
                                        class="colorinput mx-3"
                                        v-for="(menus, j) in moduleName.childs"
                                        :key="j"
                                    >
                      <span v-if="i != 'name'">
                        <input
                            :checked="menus"
                            type="checkbox"
                            :value="true"
                            class="colorinput-input"
                            v-model="menus.allow"
                        />
                        <span class="colorinput-color bg-success"></span>
                        <span
                            style="position: relative; left: 5px; top: -10px"
                            class="text-capitalize"
                        >
                          {{ menus.name }}
                        </span>
                      </span>
                                    </label>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button
                                    type="button"
                                    class="btn btn-block btn-success mt-4"
                                    :class="loading?'disabled':''"
                                    @click="add"
                                >
                                    {{ loading ? "Loading...." : "Add company" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Add>

                <!-- Add Modal -->
                <Edit
                    heading="Edit Company"
                    :errors="this.validationErrors"
                    :success="success"
                    :formID="formID"
                >
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="name">Company Name</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Name"
                                id="name"
                                v-model="dataEdit.name"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact">Contact</label>
                            <vue-mask
                                class="form-control"
                                v-model="dataEdit.contact"
                                mask="0000-0000000"
                                :raw="false"
                                :options="options">
                            </vue-mask>
<!--                            <input-->
<!--                                type="text" @keypress="isNumber($event)"-->
<!--                                class="form-control"-->
<!--                                placeholder="Enter contact"-->
<!--                                id="contact"-->
<!--                                v-model="dataEdit.contact"-->
<!--                            />-->
                        </div>
                        <div class="form-group col-md-4">
                            <label for="Logo">Logo</label>
                            <input
                                type="file"
                                class="form-control"
                                id="Logo"
                                @change="uploadLogo($event, 'edit')"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="userName">User Name</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter User Name"
                                id="userName"
                                v-model="dataEdit.name"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="email">Email</label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter Email"
                                id="email"
                                v-model="dataEdit.email"
                            />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                placeholder="Enter Password"
                                id="password"
                                v-model="dataEdit.password"
                            />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location</label>
                            <textarea
                                class="form-control"
                                placeholder="Enter Location"
                                id="location"
                                v-model="dataEdit.location"
                                cols="30"
                                rows="10"
                            ></textarea>
                        </div>
                        <div class="form-group col-md-12 text-center mt-4">
                            <h3>Modular Permissions</h3>
                        </div>
                    </div>
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Module Name</th>
                            <th>Permission</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="(moduleName, i) in dataEdit.modules" :key="i">
                            <tr>
                                <td>{{ i + 1 }}</td>
                                <td>
                                    <div class="text-capitalize">
                                        {{ moduleName.name }}
                                    </div>
                                </td>
                                <td>
                                    <label class="colorinput mx-3">
                      <span v-if="i != 'name'">
                        <input
                            :checked="mod"
                            type="checkbox"
                            :value="true"
                            class="colorinput-input"
                            v-model="moduleName.allow"
                        />
                        <span class="colorinput-color bg-success"></span>
                      </span>
                                    </label>
                                </td>
                            </tr>
                            <tr v-if="moduleName.allow">
                                <td></td>
                                <td colspan="2" class="py-5">
                                    <label
                                        class="colorinput mx-3"
                                        v-for="(menus, j) in moduleName.childs"
                                        :key="j"
                                    >
                      <span v-if="i != 'name'">
                        <input
                            :checked="menus"
                            type="checkbox"
                            :value="true"
                            class="colorinput-input"
                            v-model="menus.allow"
                        />
                        <span class="colorinput-color bg-success"></span>
                        <span
                            style="position: relative; left: 5px; top: -10px"
                            class="text-capitalize"
                        >
                          {{ menus.name }}
                        </span>
                      </span>
                                    </label>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button
                                    type="button"
                                    class="btn btn-block btn-success mt-4"
                                    @click="update"
                                >
                                    Update company
                                </button>
                            </div>
                        </div>
                    </div>
                </Edit>

                <!-- Delete Modals -->
                <ConfirmationModal
                    :formID="confirmModalID"
                    v-on:confirmDeleteModal="confirmDeleteModal(event)"
                />
            </div>
            <Delete
                confirmationMessage='Are You Sure You want To Delete This "Company" ???'
                :confirmModalID="confirmModalID"
            />
        </section>
    </div>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import ConfirmationModal from "../../components/ConfirmationModal.vue";
import Modal from "../../components/Modal.vue";
import vueMask from 'vue-jquery-mask';

import {mapGetters} from "vuex";

export default {
    name: "Role",
    components: {
        Add,
        Edit,
        Delete,
        Modal,
        ConfirmationModal,
        vueMask,
    },
    data() {
        return {
            date: null,
            options: {
                placeholder: '0300-0000000',
                // http://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
            },
            roles: [],
            formID: "newCompany",
            confirmModalID: "confirmModal",
            loading: false,
            data: {
                name: "",
                contact: "",
                logo: "",
                location: "",
                modules: [],
            },
            defaultModules: [
                {
                    name: "admin",
                    allow: false,
                    childs: [
                        {name: "dashboard", allow: false},
                        {name: "terminal", allow: false},
                        {name: "fare-table", allow: false},
                        {name: "route", allow: false},
                    ],
                },
                {
                    name: "hrm",
                    allow: false,
                    childs: [
                        {name: "employee", allow: false},
                        {name: "salary", allow: false},
                        {name: "loan", allow: false},
                        {name: "leave managment", allow: false},
                        {name: "attendance", allow: false},
                    ],
                },
                {
                    name: "users",
                    allow: false,
                    childs: [
                        {name: "user", allow: false},
                        {name: "roles", allow: false},
                    ],
                },
            ],
            dataEdit: {
                i: "",
                name: "",
                contact: "",
                logo: "",
                location: "",
                modules: [],
            },
            success: false,
            cities: [],
        };
    },
    async created() {
        this.data.modules = this.dataEdit.modules = this.defaultModules;
        const companyRes = await this.callApi("post", "company");
        if (companyRes.status == 200) {
            this.cities = companyRes.data;
        }
    },
    methods: {
        phoneFormat:function(string){
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        async add(e) {
            // console.log(this.data.modules);
            // return ;

            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            formData.append('logo', this.data.logo);

            this.validationErrors = [];
            if (this.data.name == "")
                return this.errorsArray("Company Name is Required", "Name");
            if (this.data.contact == "")
                return this.errorsArray("Company Contact is Required", "Contact");
            this.loading = true;

            let logo = "";
            if (this.data.logo) {
                const logoRes = await this.callApi("post", "company/logo-upload", formData, config);
                logo:logoRes ? logoRes.data.name : ""
            }
            const res = await this.callApi("post", "company/store", {
                ...this.data,
                logo
            });
            if (res.status == 201) {
                this.loading = false;
                this.success = "Company Created Successfully";
                this.cities.unshift(res.data);
                this.data.name = this.data.contact = this.data.location = "";
                this.data.modules = this.defaultModules;
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                }, 2000);
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
        async edit(id, i) {
            const res = await this.callApi("post", "company/get", {id});
            let company;

            if (res.status == 200) {
                company = res.data;
            } else {
                return alert("Something Went Wrong !!!");
            }

            const modules = company.modules
                .concat(this.dataEdit.modules)
                .filter(function (obj) {
                    return this.has(obj.name) ? false : this.add(obj.name);
                }, new Set());

            // console.log(modules);

            this.dataEdit = {
                ...company,
                modules,
                i,
            };
            // console.log(this.dataEdit);
        },
        async update() {
            this.validationErrors = [];
            if (this.dataEdit.name == "")
                return this.errorsArray("Company Name is Required", "Name");

            const res = await this.callApi("post", "company/update", this.dataEdit);

            if (res.status == 200) {
                this.success = "Company Updated Successfully";
                const companyRes = await this.callApi("post", "company");
                if (companyRes.status == 200) {
                    this.cities = companyRes.data;
                }
                this.dataEdit.name = "";
                this.modules = [
                    {hrm: false},
                    {accounts: false},
                    {booking: false},
                ];
                setTimeout(() => {
                    this.success = "";
                    $("#edit-modal").modal("hide");
                }, 3000);
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        async deleteModal(company, i) {
            const deletingObj = {
                url: "/company/delete",
                data: company,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
        async confirmDeleteModal(data) {
            const res = await this.callApi(
                "post",
                this.getDeletingObj.url,
                this.getDeletingObj.data
            );
            location.reload();
        },
        uploadLogo(e, name) {
            if (name == "add") {
                this.data.logo = e.target.files[0];
            }
            if (name == "edit") {
                this.dataEdit.logo = e.target.files[0];
            }
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1);
            }
        },
    },
};
</script>
