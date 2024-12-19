<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Users</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('assign-terminal-admin')"
                                   data-toggle="modal" @click="getAuthTerminal()"
                                   data-target="#assignTerminalUser"
                                   class="btn text-light mr-2"
                                   :class="$store.state.user.terminal_id == null ? 'btn-danger' :'btn-primary'"
                                >
                                    Assign Terminal To Company Admin (Yourself)
                                </a>
                                <a v-if="checkForSubmenuButtons('add-users')" title="Add User"
                                   data-toggle="modal"
                                   :data-target="'#'+formID"
                                   class="btn btn-primary text-light" @click="clearForm()"
                                >
                                    Add New
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row px-2 mb-4">
                                <div class="col-md-4">
                                    <label for="fromDate">Name</label>
                                    <input type="text" class="form-control" name="name"
                                            v-model="filterData.name" @keyup="fetchUsers()" readonly onfocus="this.removeAttribute('readonly');" />
                                </div>
                                <div class="col-md-4">
                                    <label for="terminalFilter">Select Terminal</label>
                                    <select id="terminalFilter" class="form-control"
                                            v-model="filterData.terminal"
                                            @change="fetchUsers()">
                                        <option value="">Select Terminal</option>
                                        <option v-for="(item, i) in terminals" :key="i"
                                                :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="routeFilter">Select Role</label>
                                    <select id="routeFilter" class="form-control"
                                            v-model="filterData.role"
                                            @change="fetchUsers()">
                                        <option value="">Select Role</option>
                                        <option v-for="(role, i) in roles" :key="i"
                                                :value="role.id">
                                            {{ role.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
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
                                                        <th>Terminal Name</th>
                                                        <th>Role</th>
                                                        <th v-if="checkForSubmenuButtons('edit-users') || checkForSubmenuButtons('delete-user')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(user, i) in users" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ user.name }}</td>
                                                        <td>{{ user.email }}</td>
                                                        <td>{{ phoneFormat(user.contact) }}</td>
                                                        <td v-if="user.terminal_id !=  0 && user.terminal_id != null">
                                                            {{ user.terminal.city.name }} - {{ user.terminal.name }}
                                                        </td>
                                                        <td v-else>N/A</td>
                                                        <th>{{ user.role ? user.role.name : "Not Found" }}</th>
                                                        <td v-if="checkForSubmenuButtons('edit-users') || checkForSubmenuButtons('delete-user')">
                                                            <a v-if="checkForSubmenuButtons('edit-users')"
                                                               :data-target="'#'+editFormID"
                                                               data-toggle="modal"
                                                               @click="edit(user)"
                                                               class="btn btn-primary text-light mx-1"
                                                               title="Edit User"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                               v-if="checkForSubmenuButtons('delete-user')"
                                                               :data-target="'#' + hideFormID" @click="delId = user.id" data-toggle="modal"
                                                               title="Delete User"
                                                               class="btn btn-danger text-light"
                                                            >
                                                                <i class="far fas fa-trash"></i>
                                                            </a>
                                                            <!--                                                            href="#delete-modal"-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(user, i)"-->
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
                        <label for="departure">Departure City <span class="text-danger ml-1">*</span></label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllDepartures">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllDepartures">Deselect All</button>
                        <select class="form-control" id="departure" multiple
                                v-model="data.departure">
                            <option
                                v-for="(singleDeparture, i) in departureCities"
                                :value="singleDeparture.id"
                                :key="i"
                            >{{ singleDeparture.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="destinations">Destination City <span class="text-danger ml-1">*</span></label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllDestination">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllDestination">Deselect All</button>
                        <select class="form-control" id="destinations" multiple
                                v-model="data.destination">
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
                    <div class="form-group col-md-6">
                        <label for="role">User Type <span class="text-danger ml-1">*</span></label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input"
                                    id="femaleCheckBox"
                                    v-bind:checked="data.online_user == 1"
                                    @click="changeUser($event)"
                                    name="">
                            <label class="custom-control-label"
                                    for="femaleCheckBox">Online User</label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="this.loading" @click="add()">
                        {{ this.loading ? 'Loading...' : 'Add User' }}
                    </button>
                </template>
            </Add>
            <!-- Add Modal -->
            <!--Add Roles New-->
            <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Role</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                                
                            </button>
                        </div>
                    <div class="modal-content">
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

            <!--Update Terminal id To your self-->
            <div class="modal fade" id="assignTerminalUser" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Terminal </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="closeModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-3">
                                <div class="form-group col-md-12">
                                    <label for="terminal">Terminal<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" id="terminals"
                                            v-model="updateTerminal">
                                        <option value="0" selected>Select Terminal</option>
                                        <option
                                            v-for="(terminal, i) in terminals"
                                            :value="terminal.id"
                                            :key="i"
                                        >{{ terminal.city.name }} - {{ terminal.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-primary" @click="updateTerminalUser()"
                                    :disabled="loadingTerminal">
                                {{ loadingTerminal ? 'Loading...' : 'Update Terminal' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!--End MOdal-->
            <Edit
                heading="Edit User"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Name"
                            id="name"
                            v-model="dataEdit.name"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email<span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Email"
                            id="email"
                            v-model="dataEdit.email"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact">Contact<span class="text-danger ml-1">*</span></label>
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
                        <label for="password">Password<span class="text-danger ml-1">*</span></label>
                        <i class="ml-2 far fa-eye" :title="userPass"></i>
                        <input
                            type="password"
                            class="form-control"
                            placeholder="Enter Password"
                            id="password"
                            v-model="dataEdit.password"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="departure">Departure City <span class="text-danger ml-1">*</span></label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllEditDepartures">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllEditDepartures">Deselect All</button>
                        <select class="form-control" id="editDeparture" multiple
                                v-model="dataEdit.departure_city_ids">
                            <option
                                v-for="(singleDeparture, i) in departureCities"
                                :value="singleDeparture.id"
                                :key="i"
                            >{{ singleDeparture.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="destinations">Destination City <span class="text-danger ml-1">*</span></label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllEditDestination">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllEditDestination">Deselect All</button>
                        <select class="form-control" id="editDestinations" multiple
                                v-model="dataEdit.destination_city_ids">
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
                        <label for="role">Role<span class="text-danger ml-1">*</span></label>
                        <select
                            type="text"
                            class="form-control"
                            id="role"
                            v-model="dataEdit.role_id"
                        >
                            <option value="0">Select Role</option>
                            <option v-for="(role, i) in roles" :value="role.id" :key="i">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="role">Allowed Seats Check</label>
                        <select
                            type="text"
                            class="form-control"
                            id="role"
                            v-model="dataEdit.check_allowed_seats"
                        >
                            <option value="0">Not Checked</option>
                            <option value="1">Checked</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="role">User Type</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input"
                                    id="femaleCheckBoxEdit"
                                    v-bind:checked="dataEdit.online_user == 1"
                                    @click="changeEditUser($event)"
                                    name="">
                            <label class="custom-control-label"
                                    for="femaleCheckBoxEdit">Online User</label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="role">Apply Time Validation</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input"
                                    id="bookinMinute"
                                    v-bind:checked="dataEdit.check_booking_minutes == 1"
                                    @click="changeEditMinute($event)"
                                    name="">
                            <label class="custom-control-label"
                                    for="bookinMinute">Yes</label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="update()"
                        :disabled="this.loadingUpdate"
                    >
                        {{ this.loadingUpdate ? "Loading..." : "Update User" }}
                    </button>
                </template>
            </Edit>

            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This City ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideUser"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Delete' }}
                    </button>
                </template>
            </Hide>

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Hide from "../../components/Hide.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";
import script from "@vueform/multiselect";


export default {
    name: "Role",
    components: {
        Add,
        Edit,
        Hide,
        vueMask,
    },
    data() {
        return {
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },
            roles: [],
            userPass: "N/A",
            users: [],
            permissions: [],
            departureCities: [],
            destinationCities: [],
            formID: 'user_form',
            editFormID: 'edit_user_form',
            hideFormID: 'hide_user_form',
            roleName: '',
            updateTerminal: 0,
            filterData: {
                name : "",
                terminal : "",
                role : "",
            },
            data: {
                name: "",
                email: "",
                contact: "",
                password: "",
                role: 0,
                company_id: "",
                terminal_id: 0,
                online_user: 0,
                destination: [],
                departure: [],
            },
            dataEdit: {
                terminal_id: 0,
                departure_city_ids: [],
                destination_city_ids: [],
                role_id: 0,
            },
            terminals: [],
            delId: "",
            success: false,
            loading: false,
            loadingEdit: false,
            loadingRole: false,
            loadingTerminal: false,
            loadingUpdate: false,
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

        await this.fetchUsers();
        this.permissions = this.$store.state.permissions;
    },
    mounted() {
        setTimeout(() => {
            const departure = $('#departure');
            const destinations = $('#destinations');
            const editDeparture = $('#editDeparture');
            const editDestinations = $('#editDestinations');

            // Initialize Select2
            departure.select2({
                closeOnSelect: false
            });
            destinations.select2({
                closeOnSelect: false
            });

            // Handle Select2 change event
            const self = this;

            departure.on('change', function() {
                const selectedValues = $(this).val();
                self.data.departure = selectedValues;
            });
            destinations.on('change', function() {
                const selectedValues = $(this).val();
                self.data.destination = selectedValues;
            });
            editDeparture.on('change', function() {
                const selectedValues = $(this).val();
                self.dataEdit.departure_city_ids = selectedValues;
            });
            editDestinations.on('change', function() {
                const selectedValues = $(this).val();
                self.dataEdit.destination_city_ids = selectedValues;
            });
        }, 1000);
    },
    methods: {
        closeModal() {
            $(".modal").click();
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },

        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },

        showButton: function () {
            return this.authCheck == 0;
        },
        async getAuthTerminal() {
            const resDepart = await this.callApi("post", 'terminals/all');
            if (resDepart.status == 200) {
                this.updateTerminal = resDepart.data.authTerminalId == null ? 0 : resDepart.data.authTerminalId;
            } else {
                console.log(resDepart);
            }
        },
        clearForm: function () {
            this.data.name = "";
            this.data.email = "";
            this.data.contact = "";
            this.data.password = "";
            this.data.role = 0;
            this.data.departure = 0;
            this.data.destination = 0;
            this.roleName = '';
        },

        selectAllDepartures() {
            $("#departure > option").prop("selected", true);
            $("#departure").trigger("change"); 
        },

        deselectAllDepartures() {
            $("#departure > option").prop("selected", false);
            $("#departure").trigger("change");
        },
        
        selectAllDestination() {
            $("#destinations > option").prop("selected", true);
            $("#destinations").trigger("change"); 
        },

        deselectAllDestination() {
            $("#destinations > option").prop("selected", false);
            $("#destinations").trigger("change");
        },
        
        selectAllEditDepartures() {
            $("#editDeparture > option").prop("selected", true);
            $("#editDeparture").trigger("change"); 
        },

        deselectAllEditDepartures() {
            $("#editDeparture > option").prop("selected", false);
            $("#editDeparture").trigger("change");
        },
        
        selectAllEditDestination() {
            $("#editDestinations > option").prop("selected", true);
            $("#editDestinations").trigger("change"); 
        },

        deselectAllEditDestination() {
            $("#editDestinations > option").prop("selected", false);
            $("#editDestinations").trigger("change");
        },

        async fetchUsers() {
            const userRes = await this.callApi("post", "user",this.filterData);
            const resCities = await this.callApi("post", "user/cities");
            if (userRes.status == 200 && resCities.status == 200) {
                this.users = userRes.data.users;
                this.authCheck = userRes.data.authCheck;
                this.destinationCities = resCities.data;
                this.departureCities = resCities.data;
            } else {
                console.log(userRes);
                console.log(resCities);
            }
            const roleRes = await this.callApi("post", "company/roles", {id: this.data.company_id});
            if (roleRes.status == 200) {
                this.roles = roleRes.data;
            } else {
                console.log(roleRes)
            }

            const resDepart = await this.callApi("post", 'terminals/all');
            if (resDepart.status == 200) {
                this.terminals = resDepart.data.terminals;
                this.updateTerminal = resDepart.data.authTerminalId == null ? 0 : resDepart.data.authTerminalId;
            } else {
                console.log(resDepart);
            }
            // setTimeout(() => {
            //     $("#users_table").DataTable();
            // }, 300);
        },

        changeUser: function (e) {
            if (e.target.checked) {
                this.data.online_user = 1;
            } else {
                this.data.online_user = 0;
            }
        },
        
        changeEditUser: function (e) {
            if (e.target.checked) {
                this.dataEdit.online_user = 1;
            } else {
                this.dataEdit.online_user = 0;
            }
        },
        
        changeEditMinute: function (e) {
            if (e.target.checked) {
                this.dataEdit.check_booking_minutes = 1;
            } else {
                this.dataEdit.check_booking_minutes = 0;
            }
        },

        async add() {
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
            if (this.data.contact == "" || typeof this.data.contact == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Contact is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.password == "" || typeof this.data.password == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Password is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.departure == "" || typeof this.data.departure == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Departure City is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.destination == "" || typeof this.data.destination == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Destination City is Required",
                    icon: "error",
                    timer: 2000
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
                $("#users_table").DataTable().destroy();
                await this.fetchUsers();
                setTimeout(() => {
                    $("#users_table").DataTable();
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

        async updateTerminalUser() {
            if (this.updateTerminal == 0) {
                return swal({
                    title: "Required!!!",
                    text: "Please Select Terminal",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingTerminal = true;
            const resTerminalUpdate = await this.callApi("post", "user/update/terminal", {terminal_id: this.updateTerminal});
            if (resTerminalUpdate.status == 200) {
                this.loadingTerminal = false;
                this.updateTerminal = resTerminalUpdate.data.terminal_id;
                this.fetchUsers();
                $('#assignTerminalUser').modal('hide');
                swal({
                    title: "Success!!",
                    text: "Terminal Id Successfully Updated",
                    icon: "success",
                    timer: 2000
                });
            }
            if (resTerminalUpdate.status == 422) {
                this.loadingTerminal = false;
                let errorContent = "";
                let count = 0;
                for (const key in resTerminalUpdate.data.errors) {
                    resTerminalUpdate.data.errors[key].forEach((element) => {
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
                        timer: 2000
                    });

                }
            }

        },

        async edit(user) {
            const resEditUser = await this.callApi("post", "user/edit", {'id': user.id});
            if (resEditUser.status == 200) {
                this.dataEdit = resEditUser.data;
                this.userPass = resEditUser.data.userpass ? resEditUser.data.userpass.user_password : 'N/A';
                setTimeout(() => {
                    $("#editDeparture").select2({
                        closeOnSelect: false
                    });
                    $("#editDestinations").select2({
                        closeOnSelect: false
                    });
                }, 200);
            } else {
                console.log(resEditUser);
            }
        },

        async update() {
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined') {
                return swal({
                    title: "Required!!!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.email == "" || typeof this.dataEdit.email == 'undefined') {
                return swal({
                    title: "Required!!!",
                    text: "Email is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.contact == "" || typeof this.dataEdit.contact == 'undefined') {
                return swal({
                    title: "Required!!!",
                    text: "Contact Number is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.departure_city_ids == "" || typeof this.dataEdit.departure_city_ids == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Departure City is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.destination_city_ids == "" || typeof this.dataEdit.destination_city_ids == 'undefined')
                return swal({
                    title: "Required!!",
                    text: "Destination City is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.terminal_id == "0") {
                return swal({
                    title: "Required!!!",
                    text: "Please Select Terminal",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.role_id == "0") {
                return swal({
                    title: "Required!!!",
                    text: "please select Role",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingUpdate = true;
            const resUpdateUser = await this.callApi("post", "user/update", this.dataEdit);
            if (resUpdateUser.status == 201) {
                this.loadingUpdate = false;
                swal({
                    title: "Successfull!!",
                    text: "User Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#users_table").DataTable().destroy();
                await this.fetchUsers();
                setTimeout(() => {
                    $("#users_table").DataTable();
                }, 300);
            }
            if (resUpdateUser.status == 422) {
                this.loadingUpdate = false;
                this.error = resUpdateUser.data;
                let errorContent = "";
                let count = 0;
                for (const key in resUpdateUser.data.errors) {
                    resUpdateUser.data.errors[key].forEach((element) => {
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
        async hideUser() {
            this.loading = true;
            const resHide = await this.callApi("post", 'user/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "User Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#users_table").DataTable().destroy();
                await this.fetchUsers();
                setTimeout(() => {
                    $("#users_table").DataTable();
                }, 300);
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
