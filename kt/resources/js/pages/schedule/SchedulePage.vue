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
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="DiscountName">From Date</label>
                        <input type="date" class="form-control" v-model="DepartureDate"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">To Date</label>
                        <input type="date" class="form-control" v-model="DestinationDate">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="DiscountName">Dept Time</label>
                        <input type="time" class="form-control" v-model="DepartureTime"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">Trip Duration</label>
                        <input type="time" class="form-control" v-model="TripDuration">
                    </div>
                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-success"
                            @click="addSchedule"
                        >
                            Save Schedule
                        </button>
                    </div>
                </div>
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
                        <input type="date" class="form-control" v-model="dataEdit.DepartureDate"/>
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
                confirmationMessage='Are You Sure You want To Delete This Discount ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import showRouteDetails from "../route/popup/showRouteDetail";

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
            success: false,
            error: false,
            DepartureDate: '',
            DestinationDate: '',
            DepartureTime: '',
            TripDuration: '',
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
            console.log(res.data);
            this.schedules = res.data
        } else {
            console.log(res);
        }
    },
    methods: {
        async addSchedule() {
            this.validationErrors = [];
            if (this.DepartureDate === "")
                return this.errorsArray("From Date is Required", "DepartureDate");
            if (this.DestinationDate === "")
                return this.errorsArray("To Date is Required", "DestinationDate");
            if (this.DepartureTime === "")
                return this.errorsArray("Departure Time is Required", "DepartureTime");
            if (this.TripDuration === "")
                return this.errorsArray("Trip Duration is Required", "TripDuration");

            const data = {
                dept_date: this.DepartureDate,
                dept_time: this.DepartureTime,
                dest_date: this.DestinationDate,
                trip_duration: this.TripDuration,
            }

            const res = await this.callApi("post", "/schedule/store", data);
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
        edit(sched) {
            this.dataEdit = sched;
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
table,
table * {
    font-size: 10px;
}

.modal-cell {
    padding: 0 !important;
    position: relative;
}

.modal-cell .modal-btn {
    height: 100%;
    transition: 0.5s transform;
}

.modal-cell:hover .modal-btn {
    position: absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}

.header-select {
    width: 35%;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
    opacity: 0;
}

table, tr, th, td, option, select, label, button, a, div, p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
