<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a href="#addBus" data-toggle="modal" class="btn btn-primary" @click="clearForm()"
                                   v-if="checkForSubmenuButtons('add-buses')">
                                    Add New Bus
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
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="buses_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Bus Class</th>
                                                        <th>Bus Number</th>
                                                        <th>Chassis Number</th>
                                                        <th>Insurance Number</th>
                                                        <th>Route Permit</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-buses') || checkForSubmenuButtons('delete-buses')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(bus, i) in buses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td v-if="bus.bus_class">{{ bus.bus_class.name }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ bus.bus_number }}</td>
                                                        <td>{{ bus.chassis_number ? bus.chassis_number : "N/A" }}</td>
                                                        <td>{{
                                                                bus.insurance_number ? bus.insurance_number : "N/A"
                                                            }}
                                                        </td>
                                                        <td>{{
                                                                bus.route_permit_number ? bus.route_permit_number : "N/A"
                                                            }}
                                                        </td>
                                                        <td v-if="bus.added_by">{{ bus.added_by.name }}</td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="checkForSubmenuButtons('edit-buses') || checkForSubmenuButtons('delete-buses')">
                                                            <button title="Edit Bus"
                                                                    v-if="checkForSubmenuButtons('edit-buses')"
                                                                    :data-target="'#' + editFormID"
                                                                    data-toggle="modal"
                                                                    @click="editBus(bus)"
                                                                    class="btn btn-primary mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" v-if="checkForSubmenuButtons('delete-buses')"
                                                                    class="btn btn-danger"
                                                            >
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#' + deleteFormID"-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteBus(bus, i)"-->
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
                heading="New Bus"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
            </Add>

            <div class="modal fade" id="addBus" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h4 class="modal-title">Add Bus</h4>
                            <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                                aria-label="Close"
                                @click="closeModal()"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <div
                                class="alert alert-danger alert-dismissible fade show"
                                role="alert"
                                v-if="this.validationErrors.length"
                            >
                                <button
                                    type="button"
                                    class="close"
                                    data-dismiss="alert"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Close</span>
                                </button>
                                <!-- {{ this.validationErrors.length }} -->
                                <ul>
                                    <li v-for="(error, i) in this.validationErrors" :key="i">
                                        {{ error.desc }}
                                    </li>
                                </ul>
                            </div>
                            <div
                                class="alert alert-success alert-dismissible fade show"
                                role="alert"
                                v-if="success"
                            >
                                <button
                                    type="button"
                                    class="close"
                                    data-dismiss="alert"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Close</span>
                                </button>
                                {{ success }}
                            </div>
                            <slot></slot>
                            <div class="row">
                                <div class=" form-group col-md-12">
                                    <label for="city_id">Bus Class <span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="data.fare_class">
                                        <option value="0">Select Bus Class</option>
                                        <option
                                            v-for="(fareClass, i) in fareClasses"
                                            :key="i"
                                            :value="fareClass.id"
                                        >
                                            {{ fareClass.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Bus Number <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Bus Name"
                                        v-model="data.busNumber"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Chassis Number</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Chasis Number"
                                        v-model="data.chassisNumber"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Insurance Number</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Insurance Number"
                                        v-model="data.insuranceNumber"
                                    />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Route Permit Number</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Route Permit Number"
                                        v-model="data.routePermit"
                                    />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" @click="addBuses" :disabled="loading">
                                    {{ loading ? 'Loading...' : 'Add Bus' }}
                                </button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add Modal -->
            <!--Seat Class-->

            <!--End Seat Class-->

            <!--Edit Modal-->
            <Edit
                heading="Edit Bus"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="city_id">Bus Class <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="dataEdit.fare_class_id">
                            <option value="0">Select Bus Class</option>
                            <option
                                v-for="(fareClass, i) in fareClasses"
                                :key="i"
                                :value="fareClass.id"
                            >
                                {{ fareClass.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Number <span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Bus Name"
                            v-model="dataEdit.bus_number"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Chassis Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Chasis Number"
                            v-model="dataEdit.chassis_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Insurance Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Insurance Number"
                            v-model="dataEdit.insurance_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Route Permit Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Route Permit Number"
                            v-model="dataEdit.route_permit_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="updateBus" :disabled="loading"
                    >
                        {{ loading ? 'Loading...' : 'Update Bus' }}
                    </button>
                </template>
            </Edit>
            <!-- Add Modal -->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage="Are You Sure You want To Delete This Bus Record ???"
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
    name: "buses",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            loading: false,
            buses: [],
            seatClass: "0",
            seatType: "0",
            fareClasses: [],
            updateSeatValue: [],
            permissions: [],
            validationErrors: "",
            records: "",
            columns: "",
            details: "",
            dataView: {},
            formID: "bus_form",
            editFormID: "edit_bus_form",
            deleteFormID: "delete_bus_form",
            seatNo: 0,
            data: {
                noOfSeats: "",
                busNumber: "",
                fare_class: 0,
                chassisNumber: "",
                insuranceNumber: "",
                routePermit: "",
            },
            dataEdit: {
                busNumber: "",
                fare_class: "",
                chassisNumber: "",
                insuranceNumber: "",
                noOfSeats: "",
                routePermit: "",
                noOfRows: "",
                no_of_cols: "",
                seatMap: [],
            },
            delId: "",
            success: false,
            errors: false,
        };
    },
    async created() {
        $('.modal').remove();
        await this.fetchBuses()

        this.permissions = this.$store.state.permissions;
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
        closeModal(){
            $(".modal").click();
        },
        clearForm: function () {
            this.data = {};
            this.data.fare_class = 0;
        },
        async fetchBuses() {
            const res = await this.callApi("post", "buses");
            if (res.status == 200) {
                this.buses = res.data;
            } else {
                console.log(res);
            }

            const resFareClass = await this.callApi("post", "buses/bus_classes");
            if (resFareClass.status == 200) {
                this.fareClasses = resFareClass.data;
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $('#buses_table').DataTable();
            }, 300);
        },
        isNumber: function (evt) {
            evt = evt ? evt : window.event;
            var charCode = evt.which ? evt.which : evt.keyCode;
            if (
                charCode > 31 &&
                (charCode < 48 || charCode > 57) &&
                charCode !== 46
            ) {
                evt.preventDefault();
            } else {
                return true;
            }
        },

        async addBuses() {
            this.validationErrors = [];
            if (this.data.busNumber === "")
                return swal({
                    title: "Required",
                    text: "Bus Number is required",
                    type: 'error',
                    timer: 2000
                });
            if (this.data.fare_class === "")
                return swal({
                    title: "Required",
                    text: "Bus Class is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loadig = true;
            const res = await this.callApi("post", "buses/store", this.data);
            if (res.status === 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Bus Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#buses_table').DataTable().destroy();
                this.loading = false;
                window.scrollTo(0, 0);
                this.data = {};
                this.data.fare_class = 0;
                await this.fetchBuses();
                setTimeout(() => {
                    // window.location.reload();
                    this.isShowDiv = false;
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
        editBus(val) {
            this.dataEdit = val;
        },
        viewBus(view) {
            this.dataView = view;
            console.log(this.dataViews);
        },
        async updateBus() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return this.errorsArray("Bus Name is Required", "Name");
            this.loading = true;

            const res = await this.callApi("post", "buses/update", this.dataEdit);
            if (res.status === 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Bus Record updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#buses_table').DataTable().destroy();
                this.loading = false;
                await this.fetchBuses();
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
        async deleteBus(busVal, i) {
            const deletingObj = {
                url: "buses/delete",
                data: busVal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
            setTimeout(() => {
                // window.location.reload();
            }, 3000);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#buses_table').DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>

