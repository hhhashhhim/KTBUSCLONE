<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>All Bookings</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="CNIC">CNIC</label>
                                                        <vue-mask id="CNIC"
                                                                  class="form-control"
                                                                  v-model="filterForm.cnicFilter"
                                                                  mask="00000-0000000-0"
                                                                  @keyup="filterFunction()"
                                                                  :raw="false"
                                                                  :options="options"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="phone">Cell #</label>
                                                        <vue-mask id="phone"
                                                                  class="form-control"
                                                                  v-model="filterForm.phoneFilter"
                                                                  mask="0000-0000000"
                                                                  :raw="false"
                                                                  @keyup="filterFunction()"
                                                                  :options="optionsContact"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input id="name" type="text" class="form-control"
                                                               v-model="filterForm.nameFilter"
                                                               @keyup="filterFunction()">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Route</label>
                                                        <select id="routeFilter" class="form-control"
                                                                v-model="filterForm.routeFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Route---</option>
                                                            <option v-for="(route, i) in routes" :key="i"
                                                                    :value="route.id">
                                                                {{ route.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="terminalsFilter">Terminals</label>
                                                        <select id="terminalsFilter" class="form-control"
                                                                v-model="filterForm.terminalFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Terminal---</option>
                                                            <option v-for="(terminal, i) in terminals" :key="i"
                                                                    :value="terminal.id">
                                                                {{ terminal.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="busFilter">Bus #</label>
                                                        <select id="busFilter" class="form-control"
                                                                v-model="filterForm.busFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Bus #---</option>
                                                            <option v-for="(bus, i) in buses" :key="i"
                                                                    :value="bus.id">
                                                                {{ bus.bus_number }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Date</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                               v-model="filterForm.dateFilter"
                                                               @change="filterFunction()">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="statusFilter">Status</label>
                                                        <select id="statusFilter" class="form-control"
                                                                v-model="filterForm.statusFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Status---</option>
                                                            <option value="booked">Booked</option>
                                                            <option value="advance booking">Advance Booked</option>
                                                            <option value="canceled">Cancelled</option>
                                                            <option value="reschedule">Reschedule Ticket</option>
                                                            <option value="over-issue">Over Issue Ticket</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="table-responsive">
                                                    <table style=" width:100%; margin:0; overflow:auto; font-size: 12px"
                                                           class="table table-striped table-hover" id="filterTable">
                                                        <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Route</th>
                                                            <th>Bus No</th>
                                                            <th>Bus Time</th>
                                                            <th>Terminal name</th>
                                                            <th>Booked By</th>
                                                            <th>Seat No</th>
                                                            <th>Passenger Name</th>
                                                            <th>CNIC</th>
                                                            <th>Contact</th>
                                                            <th>Fare</th>
                                                            <th>Booking Date</th>
                                                            <th>Canceled By</th>
                                                            <th>Canceled Date</th>
                                                            <th>Status</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr v-for="(record, i) in allRecords" :key="i">
                                                            <td>{{ ++i }}</td>
                                                            <td>{{ record.schedule.route.name }}</td>
                                                            <td>{{ record.bus ? record.bus.bus_number : 'N/A' }}</td>
                                                            <td>{{ record.schedule_detail ? record.schedule_detail.departure_time : 'N/A' }}</td>
                                                            <td>{{ record.terminal.name }}</td>
                                                            <td>{{ record.added_by.name }}</td>
                                                            <td>{{ record.seat_no }}</td>
                                                            <td>{{ record.name}}</td>
                                                            <td>{{ record.cnic}}</td>
                                                            <td>{{ record.contact}}</td>
                                                            <td>{{ parseFloat(record.seat_fare) - parseFloat(record.discount??0)}}</td>
                                                            <td>{{ record.created_at}}</td>
                                                            <td>{{ record.cancel ? record.cancel.added_by.name : 'N/A' }}</td>
                                                            <td>{{ record.cancel ? record.cancel.created_at : 'N/A' }}</td>
                                                            <td>{{ record.type }}</td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
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
    name: "AllBookingPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },
            loading: false,
            showAllBooking: false,
            routes: [],
            terminals: [],
            buses: [],
            validationErrors: [],
            allRecords: [],
            filterForm: {
                cnicFilter: "",
                dateFilter: "",
                nameFilter: "",
                phoneFilter: "",
                terminalFilter: "",
                routeFilter: "",
                busFilter: "",
                statusFilter: "",
            }
        };
    },
    async created() {
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();
<<<<<<< HEAD
        this.filterFunction();
        setTimeout(() => {
            $("#filterTable").DataTable();
        }, 300);
=======
        // setTimeout(() => {
        //     $("#filterTable").DataTable();
        // }, 300);
>>>>>>> bc0b64180a01a4972bebca60f3c3c13c85a8e9c3
    },
    methods: {
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "allBooking/routes");
            if (resRoute.status == 200) {
                this.routes = resRoute.data;

            }
            // else {
            //     this.filterForm.routeFilter = 0;
            // }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "allBooking/terminals");
            if (resTerminal.status == 200) {
                this.terminals = resTerminal.data;
            }
            // else {
            //     this.filterForm.terminalFilter = 0;
            // }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "allBooking/buses");
            if (resBuses.status == 200) {
                this.buses = resBuses.data;
            }
            // else {
            //     this.filterForm.busFilter = 0;
            // }

        },

        async filterFunction() {
            const resFilter = await this.callApi("post", "allBooking/filter", this.filterForm);
            if (resFilter.status == 200) {
                // $("#booking_table").DataTable().destroy();
                this.allRecords = resFilter.data;
                // setTimeout(() => {
                //     $("#booking_table").DataTable();
                // }, 900);
            }
            // else {
            //     this.filterForm.busFilter = 0;
            // }

        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                $('#discount_table').DataTable().destroy();
            }
        }
    }
};
</script>
<style scoped>
</style>
