<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Discount Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3" v-if="checkForSubmenuButtons('terminal-sale-terminal-filter')">
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
                                                <!-- <div class="col-md-2" v-if="checkForSubmenuButtons('terminal-sale-user-filter')">
                                                    <label for="usernameFilter">Schedule</label>
                                                    <select id="usernameFilter" class="form-control"
                                                            v-model="filterSales.schedule"
                                                        >
                                                        <option value="0">Select Schedule</option>
                                                        <option v-for="(schedule, i) in schedules" :key="i"
                                                                :value="schedule.id">
                                                            {{ schedule.name }}
                                                        </option>
                                                    </select>
                                                </div> -->
                                                <div class="col-md-3" v-if="checkForSubmenuButtons('terminal-sale-route-filter')">
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
                                                    <button class="btn btn-primary mt-4" type="button" @click="discountFilter()"
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
                                            </div>
                                            <form :action="$store.state.api_url + 'api/web/v1/advance/sales/pdf'" method="POST" ref="salePrint"
                                                target="_blank">
                                                <input type="hidden" name="token" :value="this.$store.state.token">
                                                <input type="hidden" name="terminal" :value="filterSales.terminal">
                                                <input type="hidden" name="user" :value="filterSales.user">
                                                <input type="hidden" name="route" :value="filterSales.route">
                                                <input type="hidden" name="fromDateTime" :value="filterSales.fromDateTime">
                                                <input type="hidden" name="toDateTime" :value="filterSales.toDateTime">
                                            </form> -->
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover text-center"
                                                               id="saleReportTable">
                                                            <thead>
                                                            <tr>
                                                                <th width="150px">Bus Time</th>
                                                                <th>Bus No</th>
                                                                <th>Route</th>
                                                                <th>Name</th>
                                                                <th>Cnic</th>
                                                                <th>Seat No</th>
                                                                <th>Invoice</th>
                                                                <th>Terminal Name</th>
                                                                <th>Status</th>
                                                                <th>Remarks</th>
                                                                <th>Discount</th>
                                                                <th>Schedule Discount</th>
                                                                <th>Terminal Discount</th>
                                                            </tr>
                                                            </thead>

                                                            <tbody>
                                                            <tr v-for="(data,i) in filters.record" :key="i">
                                                                <td>{{ data.schedule_date }}<br>{{ data.schedule_time }}</td>
                                                                <td>{{ data.bus ? data.bus.bus_number : 'N/A' }}</td>
                                                                <td>{{ data.route.name }}  ({{ data.route.via??'n/a' }})</td>
                                                                <td>{{ data.customer.name }}</td>
                                                                <td>{{ data.customer.contact }}</td>
                                                                <td>{{ data.seat_no }}</td>
                                                                <td>{{ data.invoice_id }}</td>
                                                                <td>{{ data.terminal.name }}</td>
                                                                <td>{{ data.type }}</td>
                                                                <td>{{ data.remarks }}</td>
                                                                <td>{{ data.discount }}</td>
                                                                <td>{{ data.schedule_discount }}</td>
                                                                <td>{{ data.terminal_discount }}</td>
                                                            </tr>
                                                            <tr v-if="filters.record.length > 0">
                                                                <th colspan="5"></th>
                                                                <th>{{ filters.record.length }}</th>
                                                                <th colspan="3"></th>
                                                                <th>{{ totalDiscount() }}</th>
                                                                <th>{{ totalScheduleDiscount() }}</th>
                                                                <th>{{ totalTerminalDiscount() }}</th>
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
    name: "TerminalSaleReportsPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            loadingTable: false,
            schedules: [],
            permissions: [],
            filters: {
                record: []
            },
            refundFilters: [],
            filterSales: {
                terminal: 0,
                schedule: 0,
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
            const resTerminals = await this.callApi("post", 'advance/sales/getTerminals');
            const resSchedules = await this.callApi("post", 'advance/sales/getSchedules');
            const resRoutes = await this.callApi("post", 'advance/sales/getRoutes');
            if (resTerminals.status == 200 && resSchedules.status == 200 && resRoutes.status == 200) {
                this.terminals = resTerminals.data;
                this.schedules = resSchedules.data;
                this.routes = resRoutes.data;
            }

        },
        async discountFilter() {
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
           
            const resFetchData = await this.callApi("post", 'terminals/discount/fetchFilterData', this.filterSales);
            if (resFetchData.status == 200) {
                this.filters.record = Object.values(resFetchData.data.record);
            }
            this.loadingTable = false;

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
        totalDiscount: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                        const seatDiscount = Number(data.discount) || 0;
                        return sum + (seatDiscount);
                }, 0);
            } else {
                return 0;
            }
        },
        totalScheduleDiscount: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                        const seatScheduleDiscount = Number(data.schedule_discount) || 0;
                        return sum + (seatScheduleDiscount);
                }, 0);
            } else {
                return 0;
            }
        },
        totalTerminalDiscount: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                        const seatTerminalDiscount = Number(data.terminal_discount) || 0;
                        return sum + (seatTerminalDiscount);
                }, 0);
            } else {
                return 0;
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
