<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Schedule Details</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                    @click="getData()"
                                >
                                    Add Schedule
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
                                        <div class="card-header">
                                            <h4></h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="edit_schedule"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Departure Date</th>
                                                        <th>Departure Time</th>
                                                        <th>Destination Date</th>
                                                        <th>Destination Time</th>
                                                        <th>Created By</th>
                                                        <th>Modified By</th>
                                                        <th>Created Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(schedule, i) in schedules" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ schedule.departure_date }}</td>
                                                        <td>{{ schedule.departure_time }}</td>
                                                        <td>{{ schedule.destination_date }}</td>
                                                        <td>{{ schedule.destination_time }}</td>
                                                        <td v-if="schedule.added_by">{{ schedule.added_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="schedule.updated_by">{{ schedule.updated_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ schedule.created_at }}</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(schedule)" class="btn btn-warning mx-1">
                                                                <i class="far fa-edit"></i>
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
                :heading="'ADD NEW SCHEDULE'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID">

                <div class="row mb-3">
                    <div class="col-md-3 text-center" :class="activeSection!=0?'':'border p-3  text-light bg-primary'">
                        Step 1
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step1'?'':'border p-3  text-light bg-info'">Step 2
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step2'?'':'border p-3  text-light bg-success'">Step 3
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step3'?'':'border p-3  text-light bg-warning'">Step 4
                    </div>
                </div>
                <section class="section1" :class="activeSection!=0?'d-none':''">
                    <div class="row">
                        <div class="col-md-4 class form-group">
                            <label for="DiscountName">Departure Date Time</label>
                            <input type="datetime-local" class="form-control" v-model="data.DepartureDateTime"/>
                        </div>
                        <div class="col-md-4 class form-group">
                            <label for="DiscountName">Destination Date Time</label>
                            <input type="datetime-local" class="form-control" v-model="data.DestinationDateTime"/>
                        </div>
                        <div class="col-md-4">
                            <label for="DiscountName">Bus Type</label>
                            <select  class="form-control" id="class" v-model="data.class"
                            >
                                <option value="" selected>Select Type</option>
                                <option v-for="(type, i) in classes" :value="type.id" :key="i">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <button class="btn btn-success step1 float-right" @click="nextSection('step1')">Next<i
                                class="fas fa-arrow-right pr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section2" :class="activeSection!='step1'?'d-none':''">
                    <div class="row">
                        <div class="col-md-4 class form-group">
                            <label for="DiscountName">Routes</label>
                            <select  class="form-control" id="route"
                                    @change="getSelectiveData($event , 'route')" v-model="data.route"
                            >
                                <option value="" selected>Select Route</option>
                                <option v-for="(route, i) in routes" :value="route.id" :key="i">
                                    {{ route.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4 class form-group">
                            <label for="DiscountName">City</label>
                            <select  class="form-control" @change="getSelectiveData($event , 'city')"
                                    id="city" v-model="data.city"
                            >
                                <option value="" selected>Select City</option>
                                <option v-for="(city, i) in cities" :value="city.id" :key="i">
                                    {{ city.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4 class form-group">
                            <label for="DiscountName">Terminal</label>
                            <select  class="form-control" id="terminal" v-model="data.terminal">
                                <option value="" selected>Select Terminal</option>
                                <option v-for="(terminal, i) in terminals" :value="terminal.id" :key="i">
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back1 float-left" @click="previousSection(0)"><i
                                class="fas fa-arrow-left mr-1"></i>Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success step2 float-right" @click="nextSection('step2')">Next<i
                                class="fas fa-arrow-right mr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section3" :class="activeSection!='step2'?'d-none':''">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="DiscountName">Bus</label>
                            <select  class="form-control" id="terminal" v-model="data.bus">
                                <option value="" selected>Select bus</option>
                                <option v-for="(terminal, i) in buses" :value="terminal.id" :key="i">
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back2 float-left" @click="previousSection('step1')"><i
                                class="fas fa-arrow-left mr-1"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success step2 float-right" @click="nextSection('step3')">Next<i
                                class="fas fa-arrow-right mr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section4" :class="activeSection!='step3'?'d-none':''">
                    <div class="row my-3 py-2">
                        <div class="col-md-12 text-center">
                            <span class="h3 font-weight-bold text-muted"> Review </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back2 float-left" @click="previousSection('step2')"><i
                                class="fas fa-arrow-left mr-1"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="submitFormButton" class=" btn btn-success float-right" @click="addSchedule">Save
                                Schedule
                            </button>
                        </div>
                    </div>
                </section>
            </Add>

            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Schedule"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="DiscountName">From Date</label>
                        <input type="date" class="form-control" v-model="dataEdit.departure_date"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">To Date</label>
                        <input type="date" class="form-control" v-model="dataEdit.DestinationDate">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="DiscountName">Dept Time</label>
                        <input type="time" class="form-control" v-model="dataEdit.DepartureTime"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">Trip Duration</label>
                        <input type="time" class="form-control" v-model="dataEdit.TripDuration">
                    </div>
                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-success"
                            @click="updateSchedule"
                        >
                            Update Schedule
                        </button>
                    </div>
                </div>
            </Edit>
            <!--            Edit MOdel End-->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This Surcharge ???'
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
    name: "SchedulePage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            schedules: [],
            formID: "addNewSchedule",
            validationErrors: [],
            value:[],
            success: false,
            error: false,
            routes: '',
            cities: '',
            terminals: '',
            classes: '',
            TripDuration: '',
            activeSection: 0,
            data: {
                DepartureDateTime: '',
                DestinationDateTime: '',
                route: '',
                city: '',
                terminal: '',
                class: '',

            },
            dataEdit: {
                DepartureDate: '',
                DestinationDate: '',
                DepartureTime: '',
                TripDuration: '',
            },
        };
    },
    async created() {


        const res = await this.callApi("post", '/schedule');
        if (res.status === 200) {
            this.schedules = res.data
        } else {
            console.log(res);
        }
    },


    methods: {
        async getSelectiveData(event, name) {
            this.value = event.target.value;
            if (name == 'route') {
                const resRoute = await this.callApi("post", '/schedule/getCity', {id: this.data.route});
                this.cities = resRoute.data;
            }
            if (name == 'city') {
                const resCity = await this.callApi("post", '/schedule/getTerminal', {id: this.data.city});
                // this.terminals = resCity.data;
                console.log(resCity.data);
            }
        },
        async getData() {
            const resGetAllRoutes = await this.callApi("post", "/schedule/getRoute");
            this.routes = resGetAllRoutes.data;

            const resGetAllClasses = await this.callApi("post", "/fare-class");
            this.classes = resGetAllClasses.data;
        },
        nextSection(nextBtn) {
            this.activeSection = nextBtn
        },
        previousSection(prvBtn) {
            this.activeSection = prvBtn;
        },

        async addSchedule() {
            this.validationErrors = [];
            if (this.DepartureDateTime === "")
                return this.errorsArray("Departure Date and Time is Required", "DepartureDateTime");
            if (this.TripDuration === "")
                return this.errorsArray("Trip Duration is Required", "TripDuration");

            const data = {
                dept_date_time: this.DepartureDateTime,
                trip_duration: this.TripDuration,
            }

            const res = await this.callApi("post", "/schedule/store", this.data);
            if (res.status === 201 && res.statusText === "Created") {
                this.success = "Schedule Created Successfully";
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                        res.data.errors.name.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateSchedule() {
            this.validationErrors = [];
            if (this.DepartureDate === "")
                return this.errorsArray("From Date is Required", "DepartureDate");
            if (this.DestinationDate === "")
                return this.errorsArray("To Date is Required", "DestinationDate");
            if (this.DepartureTime === "")
                return this.errorsArray("Departure Time is Required", "DepartureTime");
            if (this.TripDuration === "")
                return this.errorsArray("Trip Duration is Required", "TripDuration");

            const res = await this.callApi("post", '/schedule/update', this.dataEdit);
            if (res.status === 200 && res.statusText === "OK") {
                this.success = "Discount Updated Successfully";
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        edit(schema) {
            this.dataEdit = schema;
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        }
    }
};

</script>
<style scoped>

</style>
