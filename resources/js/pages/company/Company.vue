<template>
    <div>
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Companies</h4>
                                <div class="card-header-action">
                                    <a href="#" data-toggle="modal" :data-target="'#' + formID" class="btn btn-primary">
                                        Add New Company
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
                                                    <table class="table table-striped table-hover" id="company_table">
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
                                                        <tr v-for="(company, i) in companies" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ company.name }}</td>
                                                            <td>{{ phoneFormat(company.contact) }}</td>
                                                            <td>{{ company.location }}</td>
                                                            <td v-if="company.logo != null">
                                                                <a :href="$store.state.app_url + 'uploads/company/logo/' + (company.logo)"
                                                                   target="_blank">
                                                                    <img
                                                                        :src="$store.state.app_url + 'uploads/company/logo/' + (company.logo)"
                                                                        style="width:90px;height:100px;" alt="">
                                                                </a>
                                                            </td>
                                                            <td v-else>
                                                                <a :href="$store.state.app_url + 'uploads/no-user.png'"
                                                                   target="_blank">
                                                                    <img
                                                                        :src="$store.state.app_url + 'uploads/no-user.png'"
                                                                        style="width:90px;height:100px;" alt="">
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <button :data-target="'#' + editFormID"
                                                                        data-toggle="modal" @click="edit(company.id, i)"
                                                                        class="btn btn-primary mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <!--                                                                <button class="btn btn-danger text-light mx-1">-->
                                                                <!--                                                                    <i class="far fa-trash-alt"></i>-->
                                                                <!--                                                                </button>-->
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
                <Add heading="Company" :errors="this.validationErrors" :success="success" :formID="formID">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="name">Company Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Name" id="name"
                                   v-model="data.name"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                            <vue-mask class="form-control" v-model="data.contact" mask="0000-0000000" :raw="false"
                                      :options="options">
                            </vue-mask>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="Logo">Logo</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="Logo" accept=".jpg,.jpeg,.png"
                                       @change="uploadLogo($event, 'add')">
                                <label class="custom-file-label" for="logo">{{
                                        addLogoName != '' ? addLogoName :
                                            'Choose.jpg, .png, .jpeg Image'
                                    }}</label>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="userName">Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Name" id="userName"
                                   v-model="data.userName"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="email">Email <span class="text-danger ml-1">*</span></label>
                            <input type="email" class="form-control" placeholder="email@address.com" id="email"
                                   v-model="data.email" autocomplete="off"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password <span class="text-danger ml-1">*</span></label>
                            <input type="password" class="form-control" placeholder="Enter Password" id="password"
                                   v-model="data.password" autocomplete="off"/>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location</label>
                            <textarea class="form-control" placeholder="Enter Location" id="location"
                                      v-model="data.location" cols="30" rows="10"></textarea>
                        </div>
                        <div class="form-group col-md-12 text-center mt-4">
                            <h3>Modular Permissions</h3>
                        </div>
                    </div>
                    <div v-for="(moduleName, i) in data.modules" :key="i">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info" style="background-color: #d9edf7 !important">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <input :checked="mod" type="checkbox" :value="true"
                                                   v-model="moduleName.allow"
                                                   :id="moduleName.name"/>
                                            <label class="text-capitalize text-bold ml-1" :for="moduleName.name"
                                                   style="color: black"> {{
                                                    moduleName.name
                                                }}</label>
                                        </div>
                                        <div class="col-md-10" v-if="moduleName.allow">
                                            <div class="alert alert-success"
                                                 style="background-color: #dff0d8 !important"
                                                 v-for="(menus, j) in moduleName.childs"
                                                 :key="j">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <input :checked="menus" type="checkbox" :value="true"
                                                               v-model="menus.allow" :id="menus.name"/>
                                                        <label class="text-capitalize text-bold ml-1" :for="menus.name"
                                                               style="color: black"> {{
                                                                menus.name
                                                            }}</label>
                                                    </div>
                                                    <div class="col-md-10" v-if="menus.allow">
                                                        <div class="alert alert-danger"
                                                             style="background-color: #f2dede !important">
                                                            <div v-if="menus.buttons">
                                                                <span v-for="(button, k) in menus.buttons" :key="j">
                                                                    <input :checked="button.allow" type="checkbox"
                                                                           :value="true"
                                                                           v-model="button.allow" :id="button.name"/>
                                                                    <label class="text-capitalize text-bold ml-1 mr-3"
                                                                           :for="button.name"
                                                                           style="color: black"> {{
                                                                            button.name
                                                                        }}</label>
                                                                </span>
                                                            </div>
                                                            <div v-else><span style="color: black">NO PAGE ACTION</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="add()">
                            {{ loading ? "Loading...." : "Add company" }}
                        </button>
                    </template>
                </Add>

                <!-- Add Modal -->
                <Edit heading="Edit Company" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="name">Company Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Name" id="name"
                                   v-model="dataEdit.name"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                            <vue-mask class="form-control" v-model="dataEdit.contact" mask="0000-0000000" :raw="false"
                                      :options="options">
                            </vue-mask>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="Logo">Logo <small>(Empty field will save logo same)</small></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="editLogo" accept=".jpg,.jpeg,.png"
                                       @change="uploadLogo($event, 'edit')">
                                <label class="custom-file-label" for="editLogo">{{
                                        editLogoName != '' ? editLogoName :
                                            'Choose.jpg, .png, .jpeg Image'
                                    }}</label>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="userName">User Name</label>
                            <input type="text" class="form-control" placeholder="Enter User Name" id="userName"
                                   v-model="dataEdit.user_name"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" placeholder="email@address.com" id="email"
                                   v-model="dataEdit.email"/>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password <small>(Empty field will save password same)</small></label>
                            <input type="password" class="form-control" placeholder="Enter Password" id="password"
                                   v-model="dataEdit.password"/>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="auth">Whatsapp Auth Key</label>
                            <input type="text" class="form-control" placeholder="" id="auth"
                                   v-model="dataEdit.whatsapp_auth_key"/>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location</label>
                            <textarea class="form-control" placeholder="Enter Location" id="location"
                                      v-model="dataEdit.location" cols="30" rows="10"></textarea>
                        </div>
                        <div class="form-group col-md-12 text-center mt-4">
                            <h3>Modular Permissions</h3>
                        </div>
                    </div>
                    <div v-for="(moduleName, i) in dataEdit.modules" :key="i">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info" style="background-color: #d9edf7 !important">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <input :checked="mod" type="checkbox" :value="true"
                                                   v-model="moduleName.allow"
                                                   :id="moduleName.name"/>
                                            <label class="text-capitalize text-bold ml-1" :for="moduleName.name"
                                                   style="color: black"> {{
                                                    moduleName.name
                                                }}</label>
                                        </div>
                                        <div class="col-md-10" v-if="moduleName.allow">
                                            <div class="alert alert-success"
                                                 style="background-color: #dff0d8 !important"
                                                 v-for="(menus, j) in moduleName.childs"
                                                 :key="j">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <input :checked="menus.allow" type="checkbox" :value="true"
                                                               v-model="menus.allow" :id="menus.name"/>
                                                        <label class="text-capitalize text-bold ml-1" :for="menus.name"
                                                               style="color: black"> {{
                                                                menus.name
                                                            }}</label>
                                                    </div>
                                                    <div class="col-md-10" v-if="menus.allow">
                                                        <div class="alert alert-danger"
                                                             style="background-color: #f2dede !important">
                                                            <div v-if="menus.buttons">
                                                            <span v-for="(button, k) in menus.buttons" :key="k">
                                                                <input :checked="button.allow" type="checkbox"
                                                                       :value="true"
                                                                       v-model="button.allow" :id="button.name"/>
                                                                <label class="text-capitalize text-bold mx-2"
                                                                       :for="button.name"
                                                                       style="color: black"> {{ button.name }}</label>
                                                            </span>
                                                            </div>
                                                            <div v-else><span style="color: black">NO PAGE ACTION</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                            {{ loading ? 'Loading...' : 'Update company' }}
                        </button>
                    </template>
                </Edit>

                <!-- Delete Modals -->
                <ConfirmationModal :formID="confirmModalID" v-on:confirmDeleteModal="confirmDeleteModal(event)"/>
            </div>
            <Delete confirmationMessage='Are You Sure You want To Delete This "Company" ???'
                    :confirmModalID="confirmModalID"/>
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
            },
            roles: [],
            formID: "newCompany",
            confirmModalID: "confirmModal",
            editFormID: 'edit_company_form',
            loading: false,
            data: {
                name: "",
                contact: "",
                logo: "",
                location: "",
                modules: [],
            },
            defaultModules: [
                // Admin Panel
                {
                    name: "admin",
                    allow: true,
                    childs: [
                        {
                            name: "dashboard",
                            allow: true,
                            buttons: [
                                {name: "super-data", allow: true}
                            ],
                        },
                        {
                            name: "cities",
                            allow: true,
                            buttons: [
                                {name: "add-city", allow: true},
                                {name: "edit-city", allow: true},
                                {name: "delete-city", allow: true}
                            ],
                        },
                        {
                            name: "terminals",
                            allow: true,
                            buttons: [
                                {name: "add-terminal", allow: true},
                                {name: "view-terminal", allow: true},
                                {name: "edit-terminal", allow: true},
                                {name: "delete-terminal", allow: true},
                                {name: "commission", allow: true},
                                {name: "discount", allow: true}
                            ],
                        },
                        {
                            name: "difference",
                            allow: true,
                        },
                        {
                            name: "fare-class",
                            allow: true,
                            buttons: [
                                {name: "add-class", allow: true},
                                {name: "edit-class", allow: true},
                                {name: "delete-class", allow: true}
                            ],
                        },
                        {
                            name: "fare-table",
                            allow: true,
                        },
                        {
                            name: "sub-routes",
                            allow: true,
                            buttons: [
                                {name: "add-sub-route", allow: true},
                                {name: "edit-sub-route", allow: true},
                                {name: "delete-sub-route", allow: true}
                            ],
                        },
                        {
                            name: "routes",
                            allow: true,
                            buttons: [
                                {name: "add-routes", allow: true},
                                {name: "edit-routes", allow: true},
                                {name: "details-routes", allow: true},
                                {name: "delete-routes", allow: true}
                            ],
                        },
                        {
                            name: "discounts",
                            allow: true,
                            buttons: [
                                {name: "add-discount", allow: true},
                                {name: "edit-discount", allow: true},
                                {name: "delete-discount", allow: true}
                            ],
                        },
                        {
                            name: "surcharge",
                            allow: true,
                            buttons: [
                                {name: "add-surcharge", allow: true},
                                {name: "edit-surcharge", allow: true},
                                {name: "delete-surcharge", allow: true}
                            ],
                        }
                    ],
                },
                // Reports Panel
                {
                    name: "reports",
                    allow: true,
                    childs: [
                        {
                            name: "report-header",
                            allow: true,
                        },
                        {
                            name: "confirm-cancel",
                            allow: true,
                        },
                        {
                            name: "sales",
                            allow: true,
                            buttons: [
                                {name: "terminal-filter", allow: true},
                            ],
                        },
                        {
                            name: "terminal-sale",
                            allow: true,
                            buttons: [
                                {name: "terminal-sale-terminal-filter", allow: true},
                                {name: "terminal-sale-user-filter", allow: true},
                                {name: "terminal-sale-route-filter", allow: true},
                            ],
                        },
                        {
                            name: "schedule-drop",
                            allow: true,
                        },
                        {
                            name: "close-trip",
                            allow: true,
                        },
                        {
                            name: "commission",
                            allow: true,
                        },
                        {
                            name: "complaints",
                            allow: true,
                        },
                        {
                            name: "over-issue",
                            allow: true,
                        },
                        {
                            name: "expenses",
                            allow: true,
                        },
                    ],
                },
                // buses Panel
                {
                    name: "buses",
                    allow: true,
                    childs: [
                        {
                            name: "bus-class",
                            allow: true,
                            buttons: [
                                {name: "add-bus-class", allow: true},
                                {name: "edit-bus-Class", allow: true},
                                {name: "duplicate-bus-Class", allow: true},
                                {name: "delete-bus-class", allow: true}
                            ],
                        },
                        {
                            name: "buses",
                            allow: true,
                            buttons: [
                                {name: "add-buses", allow: true},
                                {name: "edit-buses", allow: true},
                                {name: "delete-buses", allow: true}
                            ],
                        },
                        {
                            name: "schedules",
                            allow: true,
                            buttons: [
                                {name: "update-time", allow: true},
                                {name: "add-schedule", allow: true},
                                {name: "edit-schedule", allow: true},
                                {name: "extend-schedule", allow: true},
                                {name: "delete-schedule", allow: true}
                            ],
                        },
                        {
                            name: "merges",
                            allow: true,
                            buttons: [
                                {name: "add-expense", allow: true}
                            ],
                        }
                    ],
                },
                // Users panel
                {
                    name: "users",
                    allow: true,
                    childs: [
                        {
                            name: "users",
                            allow: true,
                            buttons: [
                                {name: "add-users", allow: true},
                                {name: "edit-users", allow: true},
                                {name: "assign-terminal-admin", allow: true},
                                {name: "delete-user", allow: true}
                            ],
                        },
                        {
                            name: "roles",
                            allow: true,
                            buttons: [
                                {name: "add-role", allow: true},
                                {name: "edit-role", allow: true},
                                {name: "assign-role", allow: true},
                                {name: "delete-role", allow: true}
                            ],
                        }
                    ],
                },

                // Tickting Panel
                {
                    name: "ticketing",
                    allow: true,
                    childs: [
                        {
                            name: "bookings",
                            allow: true,
                            buttons: [
                                {name: "previous-date", allow: true},
                                {name: "assign-bus", allow: true},
                                {name: "terminal-invoice", allow: true},
                                {name: "terminal-id", allow: true},
                                {name: "discount-field", allow: true},
                                {name: "bus-invoice", allow: true},
                                {name: "pax-list", allow: true},
                                {name: "seat-details", allow: true},
                                {name: "bus-class", allow: true},
                                {name: "drop-schedule", allow: true},
                                {name: "seat-details-shortcut", allow: true},
                                {name: "advance-booking", allow: true},
                                {name: "duplicate-ticket", allow: true},
                                {name: "resend-sms", allow: true},
                                {name: "add-elt", allow: true},
                                {name: "reschedule-seats", allow: true},
                                {name: "overissue-seat", allow: true},
                                {name: "cancel-ticket", allow: true},
                                {name: "reserved-cancel", allow: true},
                                {name: "check-assign-bus", allow: true},
                            ]
                        },
                        {
                            name: "closing",
                            allow: true,
                            buttons: [
                                {name: "add-close-booking", allow: true},
                                {name: "edit-close-booking", allow: true},
                            ]
                        },
                        {name: "all-booking", allow: true},
                        {
                            name: "counter-expenses",
                            allow: true,
                            buttons: [
                                {name: "add-counter-expenses", allow: true},
                                {name: "edit-counter-expenses", allow: true},
                            ]
                        },
                    ],
                },
                //Loyalty Card Panel
                {
                    name: "loyaltyCard",
                    allow: true,
                    childs: [
                        {
                            name: "loyaltyCardCategory",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-card-category",
                                    allow: true,
                                },
                                {
                                    name: "edit-card-category",
                                    allow: true,
                                }
                            ]
                        },
                        {
                            name: "loyaltyCardAssign",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-assign-card",
                                    allow: true,
                                },
                                {
                                    name: "edit-assign-card",
                                    allow: true,
                                }
                            ]
                        }
                    ]
                },

                // Expenses Panel
                {
                    name: "expenses",
                    allow: true,
                    childs: [
                        {
                            name: "categories",
                            allow: true,
                            buttons: [
                                {name: "add-category", allow: true},
                                {name: "edit-category", allow: true},
                                {name: "delete-category", allow: true},
                            ]
                        }
                    ],
                },
                // HRm Panel
                {
                    name: "hrm",
                    allow: true,
                    childs: [
                        {
                            name: "employees",
                            allow: true,
                            buttons: [
                                {name: "add-employee", allow: true},
                                {name: "edit-employee", allow: true},
                                {name: "delete-employee", allow: true},
                            ]
                        },
                        {
                            name: "leaves",
                            allow: true,
                            buttons: [
                                {name: "apply-leave", allow: true},
                                {name: "edit-leave", allow: true},
                                {name: "delete-leave", allow: true},
                            ]
                        },
                        {
                            name: "departments",
                            allow: true,
                            buttons: [
                                {name: "add-department", allow: true},
                                {name: "edit-department", allow: true},
                                {name: "delete-department", allow: true},
                            ]
                        },
                        {
                            name: "designations",
                            allow: true,
                            buttons: [
                                {name: "add-designation", allow: true},
                                {name: "view-designation", allow: true},
                            ]
                        }
                    ],
                },
                // Fleet Maintenance Panel
                {
                    name: "fleet-maintenance",
                    allow: true,
                    childs: [
                        {
                            name: "part",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-part",
                                    allow: true
                                },
                                {
                                    name: "edit-part",
                                    allow: true
                                },
                                {
                                    name: "delete-part",
                                    allow: true
                                }
                            ]
                        },
                        {
                            name: "linking",
                            allow: true,
                            buttons: [
                                {
                                    name: "link-maintenance",
                                    allow: true,
                                },
                                {
                                    name: "edit-link-maintenance",
                                    allow: true,
                                },
                                {
                                    name: "view-link-maintenance",
                                    allow: true
                                },
                            ]
                        },
                        {
                            name: "dues",
                            allow: true,
                            buttons: [
                                {
                                    name: "update-meter-reading",
                                    allow: true,
                                },
                                {
                                    name: "add-irregular-maintenance",
                                    allow: true,
                                },
                                {
                                    name: "add-maintenance",
                                    allow: true
                                },
                            ]
                        },
                        {
                            name: "records",
                            allow: true,
                            buttons: [
                                {
                                    name: "edit-maintenance",
                                    allow: true
                                },
                            ]
                        }
                    ],
                },
                // Refreshment Panel
                {
                    name: "refreshment",
                    allow: true,
                    childs: [
                        {
                            name: "hotels",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-hotel",
                                    allow: true,
                                },
                                {
                                    name: "edit-hotel",
                                    allow: true,
                                },
                                {
                                    name: "food",
                                    allow: true,
                                },
                                {
                                    name: "food-add-food",
                                    allow: true,
                                },
                                {
                                    name: "food-edit-food",
                                    allow: true,
                                },
                                {
                                    name: "deal",
                                    allow: true,
                                },
                                {
                                    name: "deal-add-deal",
                                    allow: true,
                                },
                                {
                                    name: "deal-edit-deal",
                                    allow: true,
                                }
                            ]
                        },
                        {
                            name: "order",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-order",
                                    allow: true,
                                },
                                {
                                    name: "received-order",
                                    "allow": true
                                },
                                {
                                    name: "ready-order",
                                    allow: true
                                },
                                {
                                    name: "delivered-order",
                                    allow: true,
                                }
                            ]
                        }
                    ],
                },
                // Acounts Panel
                {
                    name: "accounts",
                    allow: true,
                    childs: [
                        {
                            name: "chart-of-accounts",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-category",
                                    allow: true,
                                }
                            ]
                        }
                    ],
                },
                // Settings Panel
                {
                    name: "settings",
                    allow: true,
                    childs: [
                        {
                            name: "tickets",
                            allow: true,
                            buttons: [
                                {
                                    name: "add-template",
                                    allow: true,
                                },
                                {
                                    name: "edit-template",
                                    allow: true,
                                }
                            ]
                        }
                    ],
                },
            ],
            addLogoName: '',
            editLogoName: '',
            dataEdit: {
                i: "",
                name: "",
                contact: "",
                logo: "",
                location: "",
                modules: [],
            },
            success: false,
            companies: [],
        };
    },
    async created() {
        $('.modal').remove();
        this.fetchCompany();

        $("input[type='search']").attr("autocomplete", "off");
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },

    methods: {
        close() {
            $(`#${this.formID}`).click();
        },
        editClose() {
            $(`#${this.editForm}`).click();
        },
        async fetchCompany() {
            this.data.modules = this.dataEdit.modules = this.defaultModules;
            const companyRes = await this.callApi("post", "company");
            if (companyRes.status == 200) {
                this.companies = companyRes.data;
                setTimeout(() => {
                    $("#company_table").DataTable();
                }, 300);
            }
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        async add(e) {
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            formData.append('logo', this.data.logo);

            this.validationErrors = [];
            if (this.data.name == "")
                return swal({
                    title: "Required",
                    text: "Company Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.contact == "")
                return swal({
                    title: "Required",
                    text: "CompanyContact is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.userName == "")
                return swal({
                    title: "Required",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.email == "")
                return swal({
                    title: "Required",
                    text: "Company Email is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.password == "")
                return swal({
                    title: "Required",
                    text: "Company Password is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;

            let logo = "";
            if (this.data.logo) {
                const logoRes = await this.callApi("post", "company/logo-upload", formData, config);
                logo = logoRes ? logoRes.data.name : ""
            }
            const res = await this.callApi("post", "company/store", {...this.data, logo});
            if (res.status == 201) {
                this.loading = false
                $("#company_table").DataTable().destroy();
                this.close();
                swal({
                    title: "Success",
                    text: "Company Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchCompany();
                this.data.name = "";
                this.data.logo = "";
                this.data.name = "";
                this.data.contact = "";
                this.data.userName = "";
                this.data.email = "";
                this.data.password = "";
                this.data.location = "";
                this.data.modules = this.defaultModules;
                window.scrollTo(0, 0);
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                }, 2000);
            } else {
                if (res.status == 422) {
                this.loading = false;
                let errorContent = "";
                let count = 0;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
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

            this.dataEdit = {
                ...company,
                modules,
                i,
            };
        },
        async update() {
            const config = {
                headers: {'content-type': 'multipart/form-data'}
            }
            let formData = new FormData();
            formData.append('logo', this.dataEdit.logo);
            this.validationErrors = [];
            if (this.dataEdit.name == "") {
                return swal({
                    title: "Required",
                    text: "Company Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.contact == "") {

                return swal({
                    title: "Required",
                    text: "Company Contact is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            let logo = "";
            if (this.dataEdit.logo) {
                const logoRes = await this.callApi("post", "company/logo-upload", formData, config);
                logo = logoRes ? logoRes.data.name : ""
            }

            const res = await this.callApi("post", "company/update", {...this.dataEdit, logo});

            if (res.status == 200) {
                this.loading = false;
                $("#company_table").DataTable().destroy();
                this.editClose();
                swal({
                    title: "Success",
                    text: "Company Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchCompany();
                $("#company_table").DataTable();
                setTimeout(() => {
                    this.success = "";
                    $("#edit-modal").modal("hide");
                }, 3000);
            } else {
                if (res.status == 422) {
                this.loading = false;
                let errorContent = "";
                let count = 0;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
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
        async deleteModal(company, i) {
            const deletingObj = {
                url: "company/delete",
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
            const imageFile = e.target.files[0];
            if (imageFile.name.match(/\.(jpg|jpeg|png)$/i)) {
                if (name == "add") {
                    const addLogo = e.target.files[0];
                    this.addLogoName = addLogo.name;
                    this.data.logo = imageFile;
                }
                if (name == "edit") {
                    const editLogo = e.target.files[0];
                    this.editLogoName = editLogo.name;
                    this.dataEdit.logo = imageFile;
                }
            } else {
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png",
                    icon: "error",
                    timer: 2000
                });
                e.target.value = '';
            }


        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.companies.splice(obj.index, 1);
            }
        },
    },

};
</script>
<style scoped>
div.dataTables_length select {
    width: 90px !important;
    display: inline-block;
}
</style>
