<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Reschedule Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card shadow-sm border-0 mb-3">
                                                <div class="card-body">
                                                    <div class="row align-items-end">

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">Terminal</label>
                                                            <select class="form-control filter-input"
                                                                v-model="filterCancel.terminal"
                                                                @change="overissueFilter()">
                                                                <option value="0">All</option>
                                                                <option v-for="(terminal, i) in terminals" :key="i"
                                                                    :value="terminal.id">
                                                                    {{ terminal.name }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">Passenger Name</label>
                                                            <input type="text" class="form-control filter-input"
                                                                v-model="filterCancel.passenger_name"
                                                                @keyup="overissueFilter()" placeholder="Search Name">
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">Cell No</label>
                                                            <input type="text" class="form-control filter-input"
                                                                v-model="filterCancel.passenger_contact"
                                                                @keyup="overissueFilter()"
                                                                placeholder="Search Contact">
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">CNIC</label>
                                                            <input type="text" class="form-control filter-input"
                                                                v-model="filterCancel.passenger_cnic"
                                                                @keyup="overissueFilter()"
                                                                placeholder="Enter CNIC without dashes">
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">From Date</label>
                                                            <input type="date" class="form-control filter-input"
                                                                v-model="filterCancel.fromDate"
                                                                @change="overissueFilter()">
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">To Date</label>
                                                            <input type="date" class="form-control filter-input"
                                                                v-model="filterCancel.toDate"
                                                                @change="overissueFilter()">
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <label class="filter-label">Status</label>
                                                            <select class="form-control filter-input"
                                                                v-model="filterCancel.type"
                                                                @change="overissueFilter()">
                                                                <option value="0">All</option>
                                                                <option value="reschedule">Reschedule</option>
                                                                <option value="booked">Booked</option>
                                                                <option value="advance booking">Advance Booking</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-3 mb-3">
                                                            <button class="btn btn-primary btn-block filter-btn"
                                                                @click="getPdfPrint()">
                                                                <i class="fa fa-print mr-1"></i> Print Report
                                                            </button>
                                                        </div>

                                                        <form
                                                            :action="$store.state.api_url + 'api/web/v1/print/pdf/reschedule/report'"
                                                            method="POST" ref="refReschedule" target="_blank">
                                                            <input type="hidden" name="token"
                                                                :value="$store.state.token">
                                                            <input type="hidden" name="terminal"
                                                                :value="filterCancel.terminal">
                                                            <input type="hidden" name="fromDate"
                                                                :value="filterCancel.fromDate">
                                                            <input type="hidden" name="toDate"
                                                                :value="filterCancel.toDate">
                                                            <input type="hidden" name="type" :value="filterCancel.type">
                                                            <input type="hidden" name="passenger_name"
                                                                :value="filterCancel.passenger_name">
                                                            <input type="hidden" name="passenger_contact"
                                                                :value="filterCancel.passenger_contact">
                                                            <input type="hidden" name="passenger_cnic"
                                                                :value="filterCancel.passenger_cnic">
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <div v-if="tableLoading">
                                                            <img class="loading-spinner"
                                                                :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                        </div>
                                                        <table class="table text-center" v-else>
                                                            <thead>
                                                                <tr>
                                                                    <th>Terminal Name</th>
                                                                    <th>Passenger Name</th>
                                                                    <th>Cell NO</th>
                                                                    <th>Cnic NO</th>
                                                                    <th>Status</th>
                                                                    <th>Current Status</th>
                                                                    <th>From Bus Time</th>
                                                                    <th>To Bus Time</th>
                                                                    <th>Reschedule From</th>
                                                                    <th>Reschedule To</th>
                                                                    <th>From Seat</th>
                                                                    <th>To Seat</th>
                                                                    <th>Old Fare</th>
                                                                    <th>New Fare</th>
                                                                    <th>Remarks</th>
                                                                    <th>Over Issue By</th>
                                                                    <th>Over Issue Time</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <tr v-for="(filter, i) in filters" :key="i"
                                                                    :class="filter.badge">
                                                                    <td>{{ filter.terminal_name ? filter.terminal_name :
                                                                        'Not Fetched' }}</td>
                                                                    <td>{{ filter.passenger_name }}</td>
                                                                    <td>{{ filter.passenger_contact }}</td>
                                                                    <td>{{ filter.passenger_cnic }}</td>
                                                                    <td>{{ filter.type }}</td>
                                                                    <td>{{ filter.new_type }}</td>
                                                                    <td>{{ filter.old_bus_time }}</td>
                                                                    <td>{{ filter.new_bus_time }}</td>
                                                                    <td>{{
                                                                        filter.old_departure+'-'+filter.old_destination
                                                                        }}</td>
                                                                    <td>{{
                                                                        filter.new_departure+'-'+filter.new_destination
                                                                        }}</td>
                                                                    <td>{{ filter.old_seat }}</td>
                                                                    <td>{{ filter.new_seat }}</td>
                                                                    <td>{{ filter.old_fare }}</td>
                                                                    <td>{{ filter.new_fare }}</td>
                                                                    <td>{{ filter.reason }}</td>
                                                                    <td>{{ filter.reschedule_by }}</td>
                                                                    <td>{{ filter.reschedule_time }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
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
export default {
    name: "ReschedulePage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            filters: [],
            refundFilters: [],
            tableLoading: true,
            filterCancel: {
                terminal: 0,
            fromDate:  new Date().toISOString().split('T')[0],
            toDate:  new Date().toISOString().split('T')[0],
            type: 0,
            passenger_name: '',
            passenger_contact: '',
            passenger_cnic: '',
            },
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchFilters();
        this.overissueFilter();
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
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'reschedule/getTerminals');
            if (resTerminals.status == 200) {
                this.terminals = resTerminals.data;
            }

        },
        async overissueFilter() {
            this.tableLoading = true;
            const resFetchData = await this.callApi("post", 'reschedule/fetchFilterData', this.filterCancel);
            console.log(resFetchData);
            if (resFetchData.status == 200) {
                this.filters = resFetchData.data;
                this.tableLoading = false;
            }

        },
        getPdfPrint: function () {
            this.$refs.refReschedule.submit();
        }
    },

}
</script>
<style scoped>
table,
th,
td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}

.red {
    background-color: #ec3030;
}

.yellow {
    background-color: #bdbd02;
}

.white {
    background-color: #FFFFFF;
}

.green {
    background-color: #03b203;
}

.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
}
</style>
