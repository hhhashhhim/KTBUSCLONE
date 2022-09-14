<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h4>Terminals</h4>
                            <div class="card-header-action">
                                <a
                                    href="#add-modal"
                                    data-toggle="modal"
                                    :data-target="'#'+formID"
                                    class="btn btn-success"
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
                                                    id="edit_loc"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>Address</th>
                                                        <th>City</th>
                                                        <th>Modified By</th>
                                                        <th>Modified Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(terminal, i) in terminals" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ terminal.name }}</td>
                                                        <td>{{ terminal.contact }}</td>
                                                        <td>{{ terminal.address }}</td>
                                                        <td>{{ terminal.city ? terminal.city.name : "" }}</td>
                                                        <td>{{
                                                                terminal.added_by ? terminal.added_by.name : "Not Found"
                                                            }}
                                                        </td>
                                                        <td>{{ terminal.updated_at }}</td>
                                                        <td>
                                                            <a
                                                                href="#edit-modal"
                                                                data-toggle="modal"
                                                                @click="edit(terminal)"
                                                                class="btn btn-warning mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteModal(terminal, i)"
                                                                class="btn btn-danger"
                                                            >
                                                                <i class="far fa-trash-alt"></i>
                                                            </a>
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
                heading="New Terminal"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="city_id">Terminal City <span class="text-danger">*</span></label>
                        <select class="form-control" v-model="data.city_id">
                            <option value="">Select City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{ city.name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Terminal Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="data.name" @keypress="isAlphabet($event)">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="available_seats">Available Seats</label>
                        <input type="number" class="form-control" v-model="data.available_seats">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="contact">Terminal Contact <span class="text-danger">*</span> </label>
                        <input type="text" class="form-control" maxlength="11" v-model="data.contact"  @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="address">Address</label>
                        <input type="text" class="form-control" v-model="data.address">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="time_difference">Time Difference ( eg 3:40 )</label>
                        <input type="text" class="form-control" v-model="data.time_difference">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="advance_booking">Advance Booking Allowed(Days)</label>
                        <input type="number" class="form-control" v-model="data.advance_booking">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" v-model="data.longitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="Latitude">Latitude</label>
                        <input type="text" class="form-control" v-model="data.latitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="online_terminal_name">Online Terminal Name</label>
                        <input type="text" class="form-control" v-model="data.online_terminal_name">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="order">Terminal Order</label>
                        <input type="text" class="form-control" v-model="data.order">
                    </div>
                    <div class="form-group col-md-4 d-flex align-items-center">
                        <label class="mt-4" for="active">Is Active</label>
                        <label class="colorinput mx-3 mt-3">
            <span>
                  <input
                      type="checkbox"
                      class="colorinput-input"
                      v-model="data.active"
                  />
            <span class="colorinput-color bg-success"></span>
            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-4 d-flex align-items-center">
                        <label class="mt-4" for="sms">SMS</label>
                        <label class="colorinput mx-3 mt-3">
            <span>
                  <input
                      type="checkbox"
                      class="colorinput-input"
                      v-model="data.active_sms"
                  />
            <span class="colorinput-color bg-success"></span>
            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-4 d-flex align-items-center">
                        <label class="mt-4" for="sms">Main Terminal</label>
                        <label class="colorinput mx-3 mt-3">
            <span>
                  <input
                      type="checkbox"
                      class="colorinput-input"
                      v-model="data.is_main"
                  />
            <span class="colorinput-color bg-success"></span>
            </span>
                        </label>
                    </div>

                    <div class="form-group col-md-12">
                        <button type="button" class="btn btn-block btn-success" @click="add">
                            Add Terminal
                        </button>
                    </div>
                </div>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit terminal"
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
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Contact"
                            id="contact"
                            v-model="dataEdit.contact"
                        />
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
                    <div class="form-group col-md-12" v-if="dataEdit.company_id">
                        <label for="company">Company</label>
                        <select
                            type="text"
                            class="form-control"
                            id="company"
                            @change="fetchCompanyRoles"
                            v-model="dataEdit.company_id"
                        >
                            <option value="">Select Company</option>
                            <option v-for="(company, i) in companies" :value="company.id" :key="i">
                                {{ company.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
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
                            Update terminal
                        </button>
                    </div>
                </div>
            </Edit>

            <!-- Add Modal -->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This "terminal" ???'
            />
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";

import {mapGetters} from "vuex";

export default {
    name: "Terminal",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            terminals: [],
            companies: [],
            formID: "newTerminal",
            cities: [],
            data: {
                company_id: "",
                name: "",
                available_seats: "",
                contact: "",
                address: "",
                time_difference: "",
                active_sms: "",
                advance_booking: "",
                longitude: "",
                latitude: "",
                city_id: "",
                online_terminal_name: "",
                active: "",
                inactive: "",
                order: "",
            },
            dataEdit: {},
            success: false,
        };
    },

    async created() {
        const terminalRes = await this.callApi("post", "/terminal");
        const compRes = await this.callApi("post", "/company");
        const cities = await this.callApi("post", "/city");
        this.terminals = terminalRes.data;
        this.companies = compRes.data;
        this.cities = cities.data;
    },
    methods: {
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },
        async add() {
            this.validationErrors = [];

            if (this.data.name == "")
                return this.errorsArray("Terminal Name is Required", "Name");
            if (this.$store.state.user.is_super_admin == 1 && this.data.company_id == "")
                return this.errorsArray("Company is Required", "Password");
            if (this.data.city_id == "")
                return this.errorsArray("Terminal City is Required", "City");
            if (this.data.contact == "")
                return this.errorsArray("Terminal Contact is Required", "Contact");

            const res = await this.callApi("post", "/terminal/store", this.data);
            if (res.status == 200) {
                this.success = "Terminal Created Successfully";
                this.terminals = res.data
                this.data = "";
                setTimeout(() => {
                    this.success = "";
                    // $("#add-modal").modal("hide")
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                if (res.status == 423) {
                    this.errorsArray(res.data.is_main, 'Main Terminal');
                }
            }
        },
        async edit(terminal) {
            this.dataEdit = terminal;
            this.dataEdit.role = terminal.role_id;
            const roleRes = await this.callApi("post", "/company/roles", {id: terminal.company_id});
            this.roles = roleRes.data;
        },
        async update() {
            this.validationErrors = [];
            if (this.dataEdit.name == "")
                return this.errorsArray("terminal Name is Required", "Name");
            const res = await this.callApi("post", "/terminal/update", this.dataEdit);
            if (res.status == 201) {
                this.success = "terminal Updated Successfully";
                this.dataEdit = "";
                const terminalRes = await this.callApi("post", "/terminal", {});
                this.terminals = terminalRes.data;
                setTimeout(() => {
                    this.success = "";
                    $("#edit-modal").modal("hide");
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
        async deleteModal(terminal, i) {
            const deletingObj = {
                url: "/terminal/delete",
                data: terminal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        }
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            console.log(obj);
            if (obj.isDeleted) {
                this.terminals.splice(obj.index, 1);
            }
        },
    },
};
</script>
