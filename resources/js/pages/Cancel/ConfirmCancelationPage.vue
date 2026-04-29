<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Confirm Cancellation Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="card shadow-sm border-0 mb-3">
                                                    <div class="card-body">
                                                        <div class="row align-items-end">
                                                            <!-- CNIC -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">CNIC</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_cnic"
                                                                    @keyup="CancelFilter()"
                                                                    placeholder="Enter CNIC (without dashes)">
                                                            </div>
                                                            <!-- Contact -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Contact</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_contact"
                                                                    @keyup="CancelFilter()" placeholder="03XXXXXXXXX">
                                                            </div>
                                                            <!-- Passenger Name -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Passenger</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_name"
                                                                    @keyup="CancelFilter()" placeholder="Name">
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Invoice ID</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.invoice_id"
                                                                    @keyup="CancelFilter()" placeholder="Invoice ID">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label for="routeFilter">Route</label>
                                                                    <select id="routeFilter" class="form-control"
                                                                        v-model="filterCancel.route"
                                                                        @change="CancelFilter()">
                                                                        <option value="0">---Select Route---</option>
                                                                        <option v-for="(route, i) in routes" :key="i"
                                                                            :value="route.id">
                                                                            {{ route.name }} ({{ route.via ?? 'n/a' }})
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <!-- Terminal -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">Terminal</label>
                                                                <select class="form-control filter-input"
                                                                    v-model="filterCancel.terminal"
                                                                    @change="CancelFilter()">
                                                                    <option value="0">All</option>
                                                                    <option v-for="(terminal, i) in terminals" :key="i"
                                                                        :value="terminal.id">
                                                                        {{ terminal.name }}
                                                                    </option>
                                                                </select>
                                                            </div>


                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">Transaction ID</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.transaction_id"
                                                                    @keyup="CancelFilter()"
                                                                    placeholder="Transaction ID">
                                                            </div>






                                                            <!-- From Date -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">From</label>
                                                                <input type="date" class="form-control filter-input"
                                                                    v-model="filterCancel.fromDate"
                                                                    @change="CancelFilter()">
                                                            </div>

                                                            <!-- To Date -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">To</label>
                                                                <input type="date" class="form-control filter-input"
                                                                    v-model="filterCancel.toDate"
                                                                    @change="CancelFilter()">
                                                            </div>

                                                            <!-- Button -->
                                                            <div class="col-md-4 mb-3">
                                                                <button class="btn btn-primary btn-block filter-btn"
                                                                    @click="getPdfPrint()">
                                                                    <i class="fa fa-print mr-1"></i> Print
                                                                </button>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Print Confirmation Cancel report -->
                                                <form
                                                    :action="$store.state.api_url + 'api/web/v1/print/pdf/confirm/cancellation/report'"
                                                    method="POST" ref="refConfirmCancle" target="_blank">

                                                    <input type="hidden" name="token" :value="$store.state.token">

                                                    <!-- Existing -->
                                                    <input type="hidden" name="terminal" :value="filterCancel.terminal">
                                                    <input type="hidden" name="bus_id" :value="filterCancel.bus_id">
                                                    <input type="hidden" name="fromDate" :value="filterCancel.fromDate">
                                                    <input type="hidden" name="toDate" :value="filterCancel.toDate">
                                                    <input type="hidden" name="type" :value="filterCancel.type">

                                                    <!-- NEW -->
                                                    <input type="hidden" name="route" :value="filterCancel.routeFilter">
                                                    <input type="hidden" name="invoice_id"
                                                        :value="filterCancel.invoice_id">
                                                    <input type="hidden" name="transaction_id"
                                                        :value="filterCancel.transaction_id">

                                                    <input type="hidden" name="passenger_name"
                                                        :value="filterCancel.passenger_name">
                                                    <input type="hidden" name="passenger_contact"
                                                        :value="filterCancel.passenger_contact">
                                                    <input type="hidden" name="passenger_cnic"
                                                        :value="filterCancel.passenger_cnic">

                                                </form>
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
                                                                    <th width="200px">Bus Time</th>
                                                                    <th>Terminal Name</th>
                                                                    <th>Route</th>
                                                                    <th>Transaction #</th>
                                                                    <th>Invoice</th>
                                                                    <th>Cancel By</th>
                                                                    <th>Seat No</th>
                                                                    <th>Type</th>
                                                                    <th>Passenger Name</th>
                                                                    <th>Cell NO</th>
                                                                    <th>Cnic NO</th>
                                                                    <th>Total Fare</th>
                                                                    <th>Cancellation Percentage</th>
                                                                    <th>Amount Refund</th>
                                                                    <th>Cancellation Charges</th>
                                                                    <th>Remarks</th>
                                                                    <th width="200px">Cancellation Date</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <tr v-for="(filter, i) in filters" :key="i"
                                                                    :class="filter.badge">
                                                                    <td>{{ filter.bus_time }}</td>
                                                                    <td>{{ filter?.terminal?.name }}</td>
                                                                    <td>{{ filter?.route?.name }}</td>
                                                                    <td>{{ filter.transaction_id }}</td>
                                                                    <td>{{ filter.invoice_id }}</td>
                                                                    <td>{{ filter.cancel_by }}</td>
                                                                    <td>{{ filter.seat_no }}</td>
                                                                    <td>{{ filter.type }}</td>
                                                                    <td>{{ filter.passenger_name }}</td>
                                                                    <td>{{ filter.passenger_contact }}</td>
                                                                    <td>{{ filter.passenger_cnic }}</td>
                                                                    <td>{{ filter.total_fare }}</td>
                                                                    <td>{{ filter.cancel_percentage }} %</td>
                                                                    <td>{{ filter.amount_refund }}</td>
                                                                    <td>{{ filter.cancelation_charges }}</td>
                                                                    <td>{{ filter.cancel_reason }}</td>
                                                                    <td>{{ filter.cancel_date }}</td>
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
    name: "ConfirmCancelationPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            buses: [],
            routes: [],
            filters: [],
            refundFilters: [],
            tableLoading: true,
            filterCancel: {
                terminal: 0,
                route: 0,
                invoice_id: '',
                transaction_id: '',
                fromDate: new Date().toISOString().split('T')[0],
                toDate: new Date().toISOString().split('T')[0],
                type: 0,
                passenger_name: '',
                passenger_contact: '',
                passenger_cnic: ''
            },
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchFilters();
        this.fetchFilterBuses();
        this.fetchRoutes();
        this.CancelFilter();
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
            const resRoute = await this.callApi("post", "confirm/cancellation/routes");
            if (resRoute.status == 200) {
                this.routes = resRoute.data;
            }
        },
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'confirm/cancellation/getTerminals');
            if (resTerminals.status == 200) {
                this.terminals = resTerminals.data;
            }

        },
        async fetchFilterBuses() {
            const resBuses = await this.callApi("post", 'confirm/cancellation/getBuses');
            if (resBuses.status == 200) {
                this.buses = resBuses.data;
            }

        },
        async CancelFilter() {
            this.tableLoading = true;
            const resFetchData = await this.callApi("post", 'confirm/cancellation/fetchFilterData', this.filterCancel);
            console.log(resFetchData);
            if (resFetchData.status == 200) {
                this.filters = resFetchData.data;
                this.tableLoading = false;
            }

        },
        getPdfPrint: function () {
            this.$refs.refConfirmCancle.submit();
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
