<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Schedule Closing Detail</h4>
                            <!-- <div class="card-header-action">
                                <a href="#" :data-target="'#' + formID" data-toggle="modal" class="btn btn-primary"
                                   @click="clearForm()" v-if="checkForSubmenuButtons('add-close-booking')">
                                    Close Booking
                                </a>
                            </div> -->
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
                                                    style="border-collapse: separate;
                                                    border-spacing: 0 10px;"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Bus Number</th>
                                                        <th>Schedule</th>
                                                        <th>Route Name</th>
                                                        <th>Schedule Date</th>
                                                        <th>Schedule Time</th>
                                                        <th v-if="checkForSubmenuButtons('edit-close-booking')">Action
                                                        </th>
                                                        <!-- <th>Expense</th> -->
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <template v-for="(data, i) in closings" :key="i">
                                                        <tr v-for="(close, j) in data" :key="j">
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-left border-bottom border-success' : 'border-left border-top border-success' : 'border-left border-bottom border-top border-danger'">
                                                                {{ close.bus.bus_number }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.route.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_date }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_time }}
                                                            </td>
                                                            <td v-if="checkForSubmenuButtons('edit-close-booking')"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-right border-success' : 'border-right border-top border-success' : 'border-bottom border-right border-top border-danger'">
                                                                <button
                                                                    v-if="checkForSubmenuButtons('edit-close-booking')"
                                                                    :data-target="'#' + editFormID"
                                                                    data-toggle="modal"
                                                                    @click="editSchedule(close)"
                                                                    class="btn btn-primary mx-1">
                                                                    <i class="far fa-edit" title="Edit Closing"></i>
                                                                </button>
                                                                <button
                                                                    v-if="checkForSubmenuButtons('edit-close-booking')"
                                                                    @click="releaseSchedule(close.id)"
                                                                    class="btn btn-danger mx-1">
                                                                    Release
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </template>
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
                        <label for="city_id">Bus <span class="text-danger ml-1">*</span></label>
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
                        <label for="name">Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            class="form-control"
                            placeholder="Enter Bus Name"
                            @change="getSchedule"
                            v-model="addData.date"
                        />
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="city_id">Schedule <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addData.schedule">
                            <option value="">Select Schedule</option>
                            <option
                                v-for="(schedule, i) in schedules"
                                :key="i"
                                :value="schedule.id"
                            >
                                {{
                                    schedule.name + (schedule.schedule_detail.length == 0 ? '' : ' (' + schedule.schedule_detail[0].departure_time + ')')
                                }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Driver <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" id="assignDriver" v-model="addData.drivers" multiple>
                            <option
                                v-for="(driver, i) in drivers"
                                :key="i"
                                :value="driver.id"
                            >
                                {{ driver.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Host <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" id="assignHost" v-model="addData.hosts" multiple>
                            <option
                                v-for="(host, i) in hosts"
                                :key="i"
                                :value="host.user_id"
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
                        {{ loading ? 'Loading...' : 'Close Booking' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal -->
            <!--Seat Class-->

            <!--End Seat Class-->

            <!--Edit Modal-->
            <Edit
                heading="Edit Close Schedule"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="city_id">Bus <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editData.bus" disabled>
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
                        <label for="name">Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            class="form-control"
                            placeholder="Enter Bus Name"
                            @change="getSchedule"
                            v-model="editData.date"
                            disabled
                        />
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="city_id">Schedule <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editData.schedule" disabled>
                            <option value="">Select Schedule</option>
                            <option v-for="(schedule, i) in editSchedules"
                                :key="i"
                                :value="schedule.id"
                            >
                                {{
                                    schedule.name + (schedule.schedule_detail.length == 0 ? '' : ' (' + schedule.schedule_detail[0].departure_time + ')')
                                }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Driver <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" id="updateAssignDriver" v-model="editData.drivers" multiple>
                            <option
                                v-for="(driver, i) in drivers"
                                :key="i"
                                :value="driver.id"
                            >
                                {{ driver.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Bus Host <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" id="updateAssignHost" v-model="editData.hosts" multiple>
                            <option
                                v-for="(host, i) in hosts"
                                :key="i"
                                :value="host.user_id"
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
                            v-model="editData.description"
                            cols="30"
                            rows="10"
                        ></textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="updateSchedule" :disabled="loading"
                    >
                        {{ loading ? 'Loading...' : 'Update Schedule' }}
                    </button>
                </template>
            </Edit>
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
import Multiselect from '@vueform/multiselect'
// import Delete from "../../components/Delete.vue";

import {mapGetters} from "vuex";

export default {
    name: "buses",
    components: {
        Add,
        Edit,
        Multiselect
        // Delete,
    },
    data() {
        return {
            loading: false,
            buses: [],
            closings: [],
            schedules: [],
            editSchedules: [],
            permissions: [],
            drivers: [],
            hosts: [],
            validationErrors: "",
            formID: "schedule_closing_form",
            editFormID: "edit_schedule_closing_form",
            seatNo: 0,
            addData: {
                bus: "",
                date: "",
                schedule: "",
                drivers: [],
                hosts: [],
                description: "",
            },
            editData: {
                closingId: "",
                mergeId: "",
                bus: "",
                date: "",
                schedule: "",
                drivers: [],
                hosts: [],
                description: "",
            },
            success: false,
            errors: false,
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

        this.fetchData();
        this.permissions = this.$store.state.permissions;
    },
    mounted() {
        setTimeout(() => {
            const self = this;
            const assignDriver = $('#assignDriver');
            const assignHost = $('#assignHost');
            const updateAssignDriver = $('#updateAssignDriver');
            const updateAssignHost = $('#updateAssignHost');
            
            // Initialize Select2
            assignDriver.select2();
            assignHost.select2();

            assignDriver.on('change', function() {
                const selectedValues = $(this).val();
                self.addData.drivers = selectedValues;
            });
            assignHost.on('change', function() {
                const selectedValues = $(this).val();
                self.addData.hosts = selectedValues;
            });
            updateAssignDriver.on('change', function() {
                const selectedValues = $(this).val();
                self.editData.drivers = selectedValues;
            });
            updateAssignHost.on('change', function() {
                const selectedValues = $(this).val();
                self.editData.hosts = selectedValues;
            });
        }, 1000);
    },
    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            const res = await this.callApi("post", "booking/close/schedule/closing");
            if (res.status == 200) {
                this.closings = res.data.closings;
                this.buses = res.data.buses;
                this.hosts = res.data.hosts;
                this.drivers = res.data.drivers;
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $('#closing_table').DataTable({
                    'order': []
                });
            }, 300);
            $(".select2").select2();
        },
        async getSchedule() {
            const data = {
                date: this.addData.date
            }
            const res = await this.callApi("post", "booking/close/schedule/fetch", data);

            if (res.status == 200) {
                this.schedules = res.data;
            } else {
                console.log(res);
            }
        },
        async getScheduleForEdit(date) {
            const data = {
                date: date
            }
            const res = await this.callApi("post", "booking/close/schedule/fetch", data);

            if (res.status == 200) {
                this.editSchedules = res.data;
            } else {
                console.log(res);
            }
        },
        async getMembers(closingId) {
            const data = {
                closingId: closingId
            }
            const res = await this.callApi("post", "booking/close/schedule/closing/members", data);

            if (res.status == 200) {
                this.editData.drivers = res.data.drivers;
                this.editData.hosts = res.data.hosts;
            } 
        },

        async closeSchedule() {
            this.validationErrors = [];
            if (!this.addData.bus)
                return swal({
                    title: "Required",
                    text: "Bus is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.addData.date)
                return swal({
                    title: "Required",
                    text: "Date is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.addData.schedule)
                return swal({
                    title: "Required",
                    text: "Schedule is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.addData.drivers.length == 0)
                return swal({
                    title: "Required",
                    text: "Driver is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.addData.hosts.length == 0)
                return swal({
                    title: "Required",
                    text: "Host is required",
                    icon: 'error',
                    timer: 2000
                });
            this.loadig = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/store", this.addData);
            if (res.status == 201) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Schedule Closed Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#closing_table').DataTable().destroy();
                this.addData.bus = "";
                this.addData.date = "";
                this.addData.schedule = "";
                this.addData.drivers = [];
                this.addData.hosts = [];
                this.addData.description = "";
                // this.fetchData();
                this.$router.go(0);

            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
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
        editSchedule(schedule) {
            this.editData.closingId = schedule.id;
            this.editData.mergeId = schedule.ticket_merge_id;
            this.editData.bus = schedule.bus_id;
            this.editData.date = schedule.schedule_date;
            this.getScheduleForEdit(schedule.schedule_date);
            this.editData.schedule = schedule.schedule_id;
            this.editData.description = schedule.description;
            this.getMembers(schedule.id);
            
            setTimeout(() => {
                $("#updateAssignDriver").select2();
                $("#updateAssignHost").select2();
            }, 1000);
        },
        // viewBus(view) {
        //     this.dataView = view;
        //     console.log(this.dataViews);
        // },
        async updateSchedule() {
            this.validationErrors = [];
            if (!this.editData.bus)
                return swal({
                    title: "Required",
                    text: "Bus is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.editData.date)
                return swal({
                    title: "Required",
                    text: "Date is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.editData.schedule)
                return swal({
                    title: "Required",
                    text: "Schedule is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.editData.drivers.length == 0)
                return swal({
                    title: "Required",
                    text: "Driver is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.editData.hosts.length == 0)
                return swal({
                    title: "Required",
                    text: "Host is required",
                    icon: 'error',
                    timer: 2000
                });
            this.loading = true;

            const res = await this.callApi("post", "booking/close/schedule/closing/update", this.editData);
            if (res.status === 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Schedule Closing Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#closing_table').DataTable().destroy();
                this.loading = false;
                // this.fetchData();
                this.$router.go(0);
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
        async releaseSchedule(id) {
            this.validationErrors = [];
            this.loading = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/release", {closingId:id});
            if (res.status === 200) {
                swal({
                    title: "Success",
                    text: "Schedule Released Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#closing_table').DataTable().destroy();
                this.loading = false;
                this.fetchData();
                this.$router.go(0);
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
<style src="@vueform/multiselect/themes/default.css"></style>

