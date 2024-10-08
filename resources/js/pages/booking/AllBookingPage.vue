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
                                                        <vue-mask id="CNIC" class="form-control"
                                                                  v-model="filterForm.cnicFilter" mask="00000-0000000-0"
                                                                  @keyup="filterFunction()" :raw="false"
                                                                  :options="options">
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="phone">Cell #</label>
                                                        <vue-mask id="phone" class="form-control"
                                                                  v-model="filterForm.phoneFilter" mask="0000-0000000"
                                                                  :raw="false" @keyup="filterFunction()"
                                                                  :options="optionsContact">
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
                                                        <label for="name">Invoice</label>
                                                        <input id="name" type="text" class="form-control"
                                                               v-model="filterForm.invoiceFilter"
                                                               @keyup="filterFunction()">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="routeFilter">Route</label>
                                                        <select id="routeFilter" class="form-control"
                                                                v-model="filterForm.routeFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Route---</option>
                                                            <option v-for="(route, i) in routes" :key="i"
                                                                    :value="route.id">
                                                                {{ route.name }}  ({{ route.via??'n/a' }})
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
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
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="busFilter">Bus #</label>
                                                        <select id="busFilter" class="form-control"
                                                                v-model="filterForm.busFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Bus #---</option>
                                                            <option v-for="(bus, i) in buses" :key="i" :value="bus.id">
                                                                {{ bus.bus_number }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="statusFilter">Status</label>
                                                        <select id="statusFilter" class="form-control"
                                                                v-model="filterForm.statusFilter"
                                                                @change="filterFunction()">
                                                            <option value="">---Select Status---</option>
                                                            <option value="booked">Booked / Confirm Booked</option>
                                                            <option value="advance booking">Advance Booked / Reserved</option>
                                                            <option value="over-issue">Over Issue</option>
                                                            <option value="canceled">Cancelled</option>
        <!--                                                            <option value="reschedule">Reschedule Ticket</option>-->
        <!--                                                            <option value="over-issue">Over Issue Ticket</option>-->
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date From</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                               v-model="filterForm.fromDateFilter"
                                                               @change="filterFunction()">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date To</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                               v-model="filterForm.toDateFilter"
                                                               @change="filterFunction()">
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="table-responsive">
                                                    <div v-if="tableLoading">
                                                        <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                    </div>
                                                    <table v-else style=" width:100%; margin:0; overflow:auto; font-size: 12px"
                                                           class="table table-striped table-hover" id="filterTable">
                                                        <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Route</th>
                                                            <th>Bus No</th>
                                                            <th>Bus Date</th>
                                                            <th>Bus Time</th>
                                                            <th>Terminal name</th>
                                                            <th>Booked By</th>
                                                            <th>Invoice</th>
                                                            <th>Seat No</th>
                                                            <th>Passenger Name</th>
                                                            <th>CNIC</th>
                                                            <th>Contact</th>
                                                            <th>Fare</th>
                                                            <th>Booking Time</th>
                                                            <th>Canceled By</th>
                                                            <th>Canceled Date</th>
                                                            <th>Over issue By</th>
                                                            <th>Over Issue Date</th>
                                                            <th>Status</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <tr v-for="(record, i) in allRecords" :key="i">
                                                            <td>{{ ++i }}</td>
                                                            <td>{{ record.schedule.route.name }}</td>
                                                            <td v-if="record.bus">{{ record.bus.bus_number }}</td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="record.schedule_date">{{
                                                                    record.schedule_date
                                                                }}
                                                            </td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="record.schedule_time">{{
                                                                    record.schedule_time
                                                                }}
                                                            </td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="record.terminal">{{ record.terminal.name }}</td>
                                                            <td v-else>N/A</td>
                                                            <td>{{ record.added_by.name }}</td>
                                                            <td>{{ record.invoice_id }}</td>
                                                            <td>{{ record.seat_no }}</td>
                                                            <td>{{ record.name }}</td>
                                                            <td>{{ record.cnic }}</td>
                                                            <td>{{ record.contact }}</td>
                                                            <td>{{ parseFloat(record.seat_fare) - parseFloat(record.discount ?? 0) }}
                                                            </td>
                                                            <td>{{ formatDate(record.created_at) }}</td>
                                                            <td>{{ record.type == "canceled" ? record.cancel_ticket.added_by_name ? record.cancel_ticket.added_by_name.name : 'Auto' : 'N/A' }}
                                                            </td>
                                                            <td>{{ record.type == "canceled" ? formatDate(record.cancel_ticket.created_at) : 'N/A' }}
                                                            </td>
                                                            <td>{{ record.type == "over-issue" ? record.over_issue_seats.overissue_by.name : 'N/A' }}
                                                            </td>
                                                            <td>{{ record.type == "over-issue" ? formatDate(record.over_issue_seats.created_at) : 'N/A' }}
                                                            </td>
                                                            <td>{{ record.type }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th>{{totalFare}}</th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
                                                            <th></th>
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
            tableLoading: true,
            routes: [],
            terminals: [],
            buses: [],
            validationErrors: [],
            allRecords: [],
            totalFare: "",
            filterForm: {
                cnicFilter: "",
                fromDateFilter: "",
                toDateFilter: "",
                nameFilter: "",
                invoiceFilter: "",
                phoneFilter: "",
                terminalFilter: "",
                routeFilter: "",
                busFilter: "",
                statusFilter: "",
            }
        };
    },
    async created() {
        $('.modal').remove();
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();
        this.filterForm.fromDateFilter = new Date().toISOString().substr(0, 10);
        this.filterForm.toDateFilter = new Date().toISOString().substr(0, 10);
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
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "allBooking/routes");
            if (resRoute.status == 200) {
                this.routes = resRoute.data;
            }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "allBooking/terminals");
            if (resTerminal.status == 200) {
                this.terminals = resTerminal.data;

            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "allBooking/buses");
            if (resBuses.status == 200) {
                this.buses = resBuses.data;
            }

        },

        async filterFunction() {
            this.tableLoading = true;
            const resFilter = await this.callApi("post", "allBooking/filter", this.filterForm);
            if (resFilter.status == 200) {
                this.allRecords = resFilter.data.data;
                this.totalFare = resFilter.data.total_fare;
                this.tableLoading = false;
            }
        },
        formatDate(timestamp) {
            const date = new Date(timestamp);
            const hours = date.getHours() % 12 || 12; // Get hours in 12-hour format
            const minutes = ('0' + date.getMinutes()).slice(-2); // Ensure minutes are always two digits
            const ampm = date.getHours() < 12 ? 'AM' : 'PM'; // Get AM/PM

            // Format date as DD-MM-YYYY
            const formattedDate = ('0' + date.getDate()).slice(-2) + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + date.getFullYear();

            // Combine time and date
            return `${hours}:${minutes} ${ampm} | ${formattedDate}`;
        }
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
            }
        }
    }
};
</script>
<style scoped>
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>
