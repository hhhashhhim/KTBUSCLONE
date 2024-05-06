<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Terminal Commission Report</h4>
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
                                            <form :action="$store.state.api_url + 'api/web/v1/advance/sales/pdf'" method="POST" ref="salePrint"
                                                target="_blank">
                                                <input type="hidden" name="token" :value="this.$store.state.token">
                                                <input type="hidden" name="terminal" :value="filterSales.terminal">
                                                <input type="hidden" name="user" :value="filterSales.user">
                                                <input type="hidden" name="route" :value="filterSales.route">
                                                <input type="hidden" name="fromDateTime" :value="filterSales.fromDateTime">
                                                <input type="hidden" name="toDateTime" :value="filterSales.toDateTime">
                                            </form>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover text-center"
                                                               id="saleReportTable">
                                                            <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Bus No</th>
                                                                <th>Bus Class</th>
                                                                <th>No of Seat</th>
                                                                <th>Terminal Name</th>
                                                                <th>Sale Amount</th>
                                                                <th>ELT Amount</th>
                                                                <th>Terminal Commission</th>
                                                                <th>Fixed Commission</th>
                                                                <th>Total Commission</th>
                                                            </tr>
                                                            </thead>

                                                            <tbody>
                                                            <tr v-for="(data,i) in filters.record" :key="i">
                                                                <td>{{ data.date }}<br>{{ data.time }}</td>
                                                                <td>{{ data.bus_number }}</td>
                                                                <td>{{ data.bus_class }}</td>
                                                                <td>{{ data.seats }}</td>
                                                                <td>{{ data.terminal }}</td>
                                                                <td>{{ data.sales }}</td>
                                                                <td>{{ data.elt }}</td>
                                                                <td>{{ data.terminal_commission }}</td>
                                                                <td>{{ data.fix_commission }}</td>
                                                                <td>{{ parseInt(data.terminal_commission) + parseInt(data.fix_commission) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="3"></th>
                                                                <th>{{ totalSeats() ?? 0 }}</th>
                                                                <th></th>
                                                                <th>{{ totalSeatFare() ?? 0 }}</th>
                                                                <th>{{ totalEltFare() ?? 0 }}</th>
                                                                <th>{{ totalTerminalCommission() ?? 0 }}</th>
                                                                <th>{{ totalFixedCommission() ?? 0 }}</th>
                                                                <th>{{ totalTerminalCommission() + totalFixedCommission() }}</th>
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
    name: "TerminalCommissionReportsPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            loadingTable: false,
            users: [],
            permissions: [],
            filters: [],
            filterSales: {
                terminal: 0,
                user: 0,
                route: [],
                fromDateTime: '',
                toDateTime: '',
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
            const resTerminals = await this.callApi("post", 'terminal/commissions/getTerminals');
            const resUserNames = await this.callApi("post", 'terminal/commissions/getUserNames');
            const resRoutes = await this.callApi("post", 'terminal/commissions/getRoutes');
            if (resTerminals.status == 200 && resUserNames.status == 200 && resRoutes.status == 200) {
                this.terminals = resTerminals.data;
                this.users = resUserNames.data;
                this.routes = resRoutes.data;
            }

        },
        async salesFilter() {
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
            this.loadingTable = true;
            const resFetchData = await this.callApi("post", 'terminal/commissions/fetchFilterData', this.filterSales);
            if (resFetchData.status == 200) {
                this.filters.record = resFetchData.data.record;
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
        totalTerminalCommission: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.terminal_commission;
                }, 0)
            }
        },
        totalFixedCommission: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.fix_commission;
                }, 0)
            }
        },

    },

}
</script>
<style scoped>
table, th, td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}
</style>
