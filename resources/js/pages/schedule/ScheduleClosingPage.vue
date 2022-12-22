<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a href="#" :data-target="'#' + formID" data-toggle="modal" class="btn btn-primary" @click="clearForm()">
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
                                                    id="closing_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Bus Category</th>
                                                        <th>Bus Number</th>
                                                        <th>Chassis Number</th>
                                                        <th>Insurance Number</th>
                                                        <th>Route Permit</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(bus, i) in buses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td v-if="bus.bus_class">{{ bus.bus_class.name }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ bus.bus_number }}</td>
                                                        <td>{{ bus.chassis_number ? bus.chassis_number : "N/A" }}</td>
                                                        <td>{{ bus.insurance_number ? bus.insurance_number : "N/A" }}</td>
                                                        <td>{{ bus.route_permit_number ? bus.route_permit_number : "N/A" }}</td>
                                                        <td v-if="bus.added_by">{{ bus.added_by.name }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>
                                                            <button
                                                                :data-target="'#' + editFormID"
                                                                data-toggle="modal"
                                                                @click="editBus(bus)"
                                                                class="btn btn-primary mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </button>
<!--                                                            <button-->
<!--                                                                :data-target="'#' + deleteFormID"-->
<!--                                                                data-toggle="modal"-->
<!--                                                                @click="deleteBus(bus, i)"-->
<!--                                                                class="btn btn-danger"-->
<!--                                                            >-->
<!--                                                                <i class="far fa-trash-alt"></i>-->
<!--                                                            </button>-->
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
                heading="Close Schedule"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="city_id">Bus <span class="text-danger">*</span></label>
                        <select class="form-control" v-model="addData.bus">
                            <option value="">Select Bus Class</option>
                            <option
                                v-for="(bus, i) in buses"
                                :key="i"
                                :value="bus.id"
                            >
                                {{ bus.bus_number }}
                            </option>
                        </select>
                    </div>
                    <div class=" form-group col-md-6">
                        
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Date <span class="text-danger">*</span></label>
                        <input
                        type="date"
                        class="form-control"
                        placeholder="Enter Bus Name"
                        @change="getSchedule"
                        v-model="addData.date"
                        />
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="city_id">Schedule <span class="text-danger">*</span></label>
                        <select class="form-control" v-model="addData.schedule">
                            <option value="">Select Schedule</option>
                            <option
                                v-for="(schedule, i) in schedules"
                                :key="i"
                                :value="schedule.id"
                            >
                                {{ schedule.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Driver <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0 select2" v-model="addData.drivers" multiple="multiple">
                            <option
                                v-for="(driver, i) in drivers"
                                :key="i"
                                :value="driver.user_id"
                            >
                                {{ driver.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Host <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0 select2" v-model="addData.hosts" multiple="multiple">
                            <option
                                v-for="(host, i) in hosts"
                                :key="i"
                                :value="host.id"
                            >
                                {{ host.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="location">Description</label>
                        <textarea
                            class="form-control"
                            placeholder="Enter Description"
                            id="location"
                            v-model="addData.description"
                            cols="30"
                            rows="10"
                        ></textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="closeSchedule" :disabled="loading"
                    >
                        {{ loading ? 'Loading...' : 'Add Bus' }}
                    </button>
                </template>
            </Add>
           

            <!-- Add Modal -->
            <!--Seat Class-->

            <!--End Seat Class-->

            <!--Edit Modal-->
            <!-- <Edit
                heading="Edit Bus"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="city_id">Bus Class <span class="text-danger">*</span></label>
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
                        <label for="name">Bus Number <span class="text-danger">*</span></label>
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
            </Edit> -->
            <!-- Add Modal -->
            <!-- <Delete :deleteForm="deleteFormID"
                confirmationMessage="Are You Sure You want To Delete This Bus Record ???"
            /> -->
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
// import Delete from "../../components/Delete.vue";

import {mapGetters} from "vuex";

export default {
    name: "buses",
    components: {
        Add,
        Edit,
        // Delete,
    },
    data() {
        return {
            loading : false,
            buses: [],
            // seatClass: "0",
            // seatType: "0",
            schedules: [],
            drivers: [],
            hosts: [],
            // updateSeatValue: [],
            validationErrors: "",
            // records: "",
            // columns: "",
            // details: "",
            // dataView: {},
            formID: "schedule_closing_form",
            editFormID: "edit_schedule_closing_form",
            // deleteFormID: "delete_bus_form",
            seatNo: 0,
            addData: {
                bus: "",
                date: "",
                schedule: "",
                drivers: [],
                hosts: [],
                description: "",
            },
            // dataEdit: {
            //     busNumber: "",
            //     fare_class: "",
            //     chassisNumber: "",
            //     insuranceNumber: "",
            //     noOfSeats: "",
            //     routePermit: "",
            //     noOfRows: "",
            //     no_of_cols: "",
            //     seatMap: [],
            // },
            // delId: "",
            success: false,
            errors: false,
        };
    },
    async created() {
        await this.fetchData();
    },

    methods: {
        clearForm:function(){
            this.data = {};
        },
        async fetchData(){
            const res = await this.callApi("post", "booking/schedule/closing");
            if (res.status == 200) {
                this.buses = res.data.buses;
                this.hosts = res.data.hosts;
                this.drivers = res.data.drivers;
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $('#closing_table').DataTable();
                $('.select2').select2();
            }, 300);
        },
        async getSchedule(){
            const data = {
                date: this.addData.date
            }
            const res = await this.callApi("post", "booking/schedule/fetch", data);
            
            if (res.status == 200) {
                this.schedules = res.data;
            } else {
                console.log(res);
            }
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

        async closeSchedule() {
            this.validationErrors = [];
            // if (this.data.busNumber === "")
            //   return swal({
            //         title: "Required",
            //         text: "Bus Number is required",
            //         type: 'error',
            //        timer: 2000
            //     });
            // if (this.data.fare_class === "")
            //     return swal({
            //         title: "Required",
            //         text: "Bus Class is Required",
            //         icon: "error",
            //         timer: 2000
            //     });
            console.log(this.addData);return ;
            this.loadig = true;
            const res = await this.callApi("post", "buses/store", this.data);
            if (res.status === 201) {
              swal({
                    title: "Success",
                    text: "Bus Created Successfully",
                    icon: "success",
                   timer: 2000
                });
                $('#closing_table').DataTable().destroy();
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
        // editBus(val) {
        //     this.dataEdit = val;
        // },
        // viewBus(view) {
        //     this.dataView = view;
        //     console.log(this.dataViews);
        // },
        // async updateBus() {
        //     this.validationErrors = [];
        //     if (this.dataEdit.name === "")
        //         return this.errorsArray("Bus Name is Required", "Name");
        //     this.loading = true;

        //     const res = await this.callApi("post", "buses/update", this.dataEdit);
        //     if (res.status === 200) {
        //        swal({
        //             title: "Success",
        //             text: "Bus Record updated Successfully",
        //             icon: "success",
        //            timer: 2000
        //         });
        //         $('#closing_table').DataTable().destroy();
        //         this.loading = false;
        //         await this.fetchBuses();
        //     } else {
        //         if (res.status == 422) {
        //             this.loading = false;

        //             for (const key in res.data.errors) {
        //                 res.data.errors[key].forEach((element) => {
        //                     this.errorsArray(element, key);
        //                 });
        //             }
        //         }
        //     }
        // },
        // async deleteBus(busVal, i) {
        //     const deletingObj = {
        //         url: "buses/delete",
        //         data: busVal,
        //         index: i,
        //     };
        //     this.$store.commit("setDeleteObj", deletingObj);
        //     setTimeout(() => {
        //         // window.location.reload();
        //     }, 3000);
        // },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#closing_table').DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>

