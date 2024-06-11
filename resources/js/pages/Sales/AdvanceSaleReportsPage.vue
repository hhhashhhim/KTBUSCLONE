<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>ADVANCE SALES REPORT</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-2" v-if="checkForSubmenuButtons('terminal-filter')">
                                                    <label for="terminalFilter">Terminals</label>
                                                    <select id="terminalFilter" class="form-control"
                                                            v-model="filterSales.terminal"
                                                        >
                                                        <option value="0">Select Terminals</option>
                                                        <option v-for="(terminal, i) in terminals" :key="i"
                                                                :value="terminal.id">
                                                            {{ terminal.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="usernameFilter">Users</label>
                                                    <select id="usernameFilter" class="form-control"
                                                            v-model="filterSales.user"
                                                        >
                                                        <option value="0">Select Users</option>
                                                        <option v-for="(user, i) in users" :key="i"
                                                                :value="user.id">
                                                            {{ user.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="routeIds">Routes</label>
                                                    <select id="routeIds" class="form-control" multiple
                                                            v-model="filterSales.route"
                                                        >
                                                        <option value="0">Select Route</option>
                                                        <option v-for="(route, i) in routes" :key="i"
                                                                :value="route.id">
                                                            {{ route.name }}  ({{ route.via??'n/a' }})
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="fromDate">From Date Time</label>
                                                    <input id="fromDate" type="datetime-local" class="form-control"
                                                           v-model="filterSales.fromDateTime">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="toDate">To Date Time</label>
                                                    <input id="toDate" type="datetime-local" class="form-control"
                                                           v-model="filterSales.toDateTime">
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-primary mt-4" type="button" @click="salesFilter()"
                                                            :disabled="loadingTable">
                                                        {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- <div class="d-flex justify-content-end" v-if="filters.record != null">
                                                <button class="btn btn-dark mt-4" type="button" @click="salesPrint()"
                                                        :disabled="loadingTable">
                                                    {{ loadingTable ? 'Loading...' : 'Print Record' }}
                                                </button>
                                            </div> -->
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <input type="checkbox" v-model="filterSales.counterSale">
                                                    <lable class="mx-1">Check for count sale</lable>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <div v-if="tableLoading">
                                                            <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                        </div>
                                                        <div v-else>
                                                            <div class="d-flex justify-content-end mb-2">
                                                                <form :action="$store.state.api_url + 'api/web/v1/advance/sales/pdf'" method="POST" ref="salePrint"
                                                                    target="_blank">
                                                                    <input type="hidden" name="token" :value="this.$store.state.token">
                                                                    <input type="hidden" name="terminal" :value="filterSales.terminal">
                                                                    <input type="hidden" name="user" :value="filterSales.user">
                                                                    <input type="hidden" name="route" :value="filterSales.route">
                                                                    <input type="hidden" name="fromDateTime" :value="filterSales.fromDateTime">
                                                                    <input type="hidden" name="toDateTime" :value="filterSales.toDateTime">
                                                                    <input type="hidden" name="counterSale" :value="filterSales.counterSale">
                                                                    <input type="submit" value="Print" class="btn btn-dark">
                                                                </form>
                                                            </div>
                                                            <table class="table table-striped table-hover text-center"
                                                                id="saleReportTable">
                                                                <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Bus No</th>
                                                                    <th>Bus Class</th>
                                                                    <th>No of Seat</th>
                                                                    <th>Terminal Name</th>
                                                                    <th>User Name</th>
                                                                    <th>Sale Amount</th>
                                                                    <th>ELT Amount</th>
                                                                </tr>
                                                                </thead>

                                                                <tbody>
                                                                <tr v-for="(data,i) in filters.record" :key="i">
                                                                    <td>{{ data.date }}<br>{{ data.time }}</td>
                                                                    <td>{{ data.bus_number }}</td>
                                                                    <td>{{ data.bus_class }}</td>
                                                                    <td>{{ data.seats }}</td>
                                                                    <td>{{ data.terminal }}</td>
                                                                    <td>{{ data.user }}</td>
                                                                    <td>{{ data.sales }}</td>
                                                                    <td>{{ data.elt }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th colspan="3"></th>
                                                                    <th>{{ totalSeats() ?? 0 }}</th>
                                                                    <th colspan="2"></th>
                                                                    <th>{{ totalSeatFare() ?? 0 }}</th>
                                                                    <th>{{ totalEltFare() ?? 0 }}</th>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--                                                Refund Ticket -->
                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">TICKET REFUND</h3>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <thead>
                                                            <tr>
                                                                <th>SR NO</th>
                                                                <th>TICKET ID</th>
                                                                <th>TERMINAL</th>
                                                                <th>BUS NO</th>
                                                                <th>SEAT NO</th>
                                                                <th>REFUND AMOUNT</th>
                                                                <th>CANCELATION CHARGES</th>
                                                                <th>BUS TIMING</th>
                                                                <th>REFUND BY</th>
                                                                <th>CANCELATION DATE</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(dataRefund,i) in filters.refund" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ dataRefund.id }}</td>
                                                                <td>{{ dataRefund.terminal_name }}</td>
                                                                <td>{{ dataRefund.bus_NO }}</td>
                                                                <td>{{ dataRefund.seat_no }}</td>
                                                                <td>{{ dataRefund.amount_refund }}</td>
                                                                <td>{{ dataRefund.cancelation_charges }}</td>
                                                                <td>{{ dataRefund.bus_time }}</td>
                                                                <td>{{ dataRefund.refund_by }}</td>
                                                                <td>{{ dataRefund.cancel_date }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="5"></th>
                                                                <th>{{ refundTotal() ?? 0 }}</th>
                                                                <th>{{ refundTotalCharges() ?? 0 }}</th>
                                                                <th colspan="3"></th>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->

                                                <!--                                                Refund -->
                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">Counter Expenses</h3>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Terminal Name</th>
                                                                <th>Amount</th>
                                                                <th>Narration</th>
                                                                <th> Added By</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(single,i) in filters.counterExpenses" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ single.terminal.name }}</td>
                                                                <td>{{ single.amount }}</td>
                                                                <td>{{ single.narration }}</td>
                                                                <td>{{ single.added_by.name }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="2"></th>
                                                                <th>{{ totalCounterAmount() ?? 0 }}</th>
                                                                <th colspan="2"></th>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->


                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">Cash Details</h3>
                                                    </div>
                                                    <div class="table-responsive w-100">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <tbody>
                                                            <tr>
                                                                <th style="width: 75% !important;">CASH ON COUNTER</th>
                                                                <td style="width: 25% !important;">
                                                                    {{ totalSeatFare() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL ELT</th>
                                                                <td style="width: 25% !important;">
                                                                    {{ totalEltFare() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL REFUND</th>
                                                                <td style="width: 25% !important;">{{
                                                                        refundTotal() ?? 0
                                                                    }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL CANCELLATION
                                                                    CHARGES
                                                                </th>
                                                                <td style="width: 25% !important;">
                                                                    {{ refundTotalCharges() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">Total Counter Expenses
                                                                </th>
                                                                <td style="width: 25% !important;">{{ totalCounterAmount() ?? 0 }}                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->
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
    name: "AdvanceSaleReportsPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            loadingTable: false,
            tableLoading: true,
            users: [],
            permissions: [],
            routes: [],
            filters: [],
            refundFilters: [],
            filterSales: {
                terminal: 0,
                user: 0,
                route: [],
                fromDateTime: '',
                toDateTime: '',
                counterSale: false,
            },
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchFilters();
        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        setTimeout(() => {
            $("#routeIds").select2({
                closeOnSelect: false
            });
        }, 300);
    },
    mounted() {
        const self = this;
        // route
        const routeIds = $('#routeIds');
        routeIds.on('change', function() {
            const selectedValues = $(this).val();
            self.filterSales.route = selectedValues;
        });
       
    },
    methods: {
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'advance/sales/getTerminals');
            const resUserNames = await this.callApi("post", 'advance/sales/getUserNames');
            const resRoutes = await this.callApi("post", 'advance/sales/getRoutes');
            if (resTerminals.status == 200 && resUserNames.status == 200 && resRoutes.status == 200) {
                this.tableLoading = false;
                this.terminals = resTerminals.data;
                this.users = resUserNames.data;
                this.routes = resRoutes.data;
            }

        },
        async salesFilter() {
            this.tableLoading = true;
            if (!this.filterSales.fromDateTime)
                return swal({
                    title: "Required",
                    text: "From date is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.filterSales.toDateTime)
                return swal({
                    title: "Required",
                    text: "To date is required",
                    icon: "error",
                    timer: 2000
                });
            const resFetchData = await this.callApi("post", 'advance/sales/fetchFilterData', this.filterSales);
            if (resFetchData.status == 200) {
                this.tableLoading = false;
                this.filters.record = resFetchData.data.record;
                this.filters.refund = resFetchData.data.refund;
                this.filters.counterExpenses = resFetchData.data.counterExpenses;
                this.loadingTable = false;
            }

        },
        // sales Table
        totalSeats: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.seats;
                }, 0)
            }
        },
        // sales print
        salesPrint: function () {
            if (!this.filterSales.fromDateTime)
                return swal({
                    title: "Required",
                    text: "From date is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.filterSales.toDateTime)
                return swal({
                    title: "Required",
                    text: "To date is required",
                    icon: "error",
                    timer: 2000
                });
            this.$refs.salePrint.submit();
        },
        totalSeatFare: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.sales;
                }, 0)
            }
        },
        totalEltFare: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.elt;
                }, 0)
            }
        },
        // refund Table
        refundTotalCharges: function () {
            // if (this.filters.refund) {
            //     return this.filters.refund.reduce((sum, single) => {
            //         return sum += single.cancelation_charges;
            //     }, 0)
            // }
            if (this.filters.refund) {
                let totalCharges = 0;
                for (const key in this.filters.refund) {
                    if (this.filters.refund.hasOwnProperty(key)) {
                        totalCharges += this.filters.refund[key].cancelation_charges;
                    }
                }
                return totalCharges;
            }
            return 0; // Return 0 if this.filters.refund is falsy
        },
        // Counter amount
        totalCounterAmount: function () {
            if (this.filters.counterExpenses) {
                return this.filters.counterExpenses.reduce((sum, single) => {
                    return sum += single.amount;
                }, 0)
            }
        },
        refundTotal: function () {
            if (this.filters.refund) {
            let totalRefund = 0;
            for (const key in this.filters.refund) {
                if (this.filters.refund.hasOwnProperty(key)) {
                    totalRefund += this.filters.refund[key].amount_refund;
                }
            }
            return totalRefund;
        }
        return 0; // Return 0 if this.filters.refund is falsy
        },
            refundTotalSeats: function () {
                if (this.filters.refund) {
                    return this.filters.refund.length;
                }
            }

    },

}
</script>
<style scoped>
table, th, td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>
