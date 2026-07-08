<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Terminal SALES REPORT</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="my-2 col-md-3">
                                                    <label>CNIC No</label>
                                                    <input type="text" class="form-control"
                                                        v-model="filterSales.passenger_cnic">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label>Passenger Name</label>
                                                    <input type="text" class="form-control"
                                                        v-model="filterSales.passenger_name">
                                                </div>

                                                <div class="my-2 col-md-3">
                                                    <label>Cell No</label>
                                                    <input type="text" class="form-control"
                                                        v-model="filterSales.passenger_contact">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label>Invoice ID</label>
                                                    <input type="text" class="form-control"
                                                        v-model="filterSales.invoice_id">
                                                </div>

                                                <div class="my-2 col-md-4">
                                                    <label>Transaction ID</label>
                                                    <input type="text" class="form-control"
                                                        v-model="filterSales.transaction_id">
                                                </div>




                                                <div class="my-2 col-md-4"
                                                    v-if="canUseReportFilter('terminal-sale-terminal-filter')">
                                                    <label for="terminalFilter">Terminals</label>
                                                    <select id="terminalFilter" class="form-control"
                                                        v-model="filterSales.terminal">
                                                        <option value="0">Select Terminals</option>
                                                        <option v-for="(terminal, i) in terminals" :key="i"
                                                            :value="terminal.id">
                                                            {{ terminal.name }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="my-2 col-md-4"
                                                    v-if="canUseReportFilter('terminal-sale-route-filter')">
                                                    <label for="routeIds">Routes</label>
                                                    <select id="routeIds" class="form-control" multiple
                                                        v-model="filterSales.route">
                                                        <option value="0">Select Route</option>
                                                        <option v-for="(route, i) in routes" :key="i" :value="route.id">
                                                            {{ route.name }} ({{ route.via ?? 'n/a' }})
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="my-2 col-md-3"
                                                    v-if="canUseReportFilter('terminal-sale-user-filter')">
                                                    <label for="usernameFilter">Users</label>
                                                    <select id="usernameFilter" class="form-control"
                                                        v-model="filterSales.user">
                                                        <option value="0">Select Users</option>
                                                        <option v-for="(user, i) in users" :key="i" :value="user.id">
                                                            {{ user.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label for="fromDate">From Date Time</label>
                                                    <input id="fromDate" type="datetime-local" class="form-control"
                                                        v-model="filterSales.fromDateTime">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label for="toDate">To Date Time</label>
                                                    <input id="toDate" type="datetime-local" class="form-control"
                                                        v-model="filterSales.toDateTime">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <div class="" ref="columnDropdown">
                                                        <label>Show/Hide Table Headers</label>
                                                        <button type="button"
                                                            class="btn btn-outline-secondary column-dropdown-toggle"
                                                            @click.stop="toggleColumnDropdown()">
                                                            {{ columnSelectionLabel }}
                                                        </button>
                                                        <div v-if="showColumnDropdown" class="column-dropdown-menu"
                                                            @click.stop>
                                                            <label v-for="column in columnOptions" :key="column.key"
                                                                class="column-option">
                                                                <input type="checkbox" :value="column.key"
                                                                    v-model="visibleColumns">
                                                                <span>{{ column.label }}</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <button class="btn btn-primary mt-4" type="button"
                                                        @click="salesFilter()" :disabled="loadingTable">
                                                        {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="report-tools-bar mt-2">

                                                <div v-if="filters.record != null">
                                                    <button class="btn btn-dark mt-4" type="button"
                                                        @click="salesPrint()" :disabled="loadingTable">
                                                        {{ loadingTable ? 'Loading...' : 'Print Record' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <form
                                                :action="$store.state.api_url + 'api/web/v1/print/pdf/terminal/sales/report'"
                                                method="POST" ref="salePrint" target="_blank">
                                                <input type="hidden" name="token" :value="this.$store.state.token">
                                                <input type="hidden" name="terminal" :value="filterSales.terminal">
                                                <input type="hidden" name="user" :value="filterSales.user">
                                                <input type="hidden" name="route" :value="filterSales.route">
                                                <input type="hidden" name="invoice_id" :value="filterSales.invoice_id">
                                                <input type="hidden" name="transaction_id"
                                                    :value="filterSales.transaction_id">
                                                <input type="hidden" name="passenger_name"
                                                    :value="filterSales.passenger_name">
                                                <input type="hidden" name="passenger_contact"
                                                    :value="filterSales.passenger_contact">
                                                <input type="hidden" name="passenger_cnic"
                                                    :value="filterSales.passenger_cnic">
                                                <input type="hidden" name="fromDateTime"
                                                    :value="filterSales.fromDateTime">
                                                <input type="hidden" name="toDateTime" :value="filterSales.toDateTime">
                                                <input type="hidden" name="visible_columns"
                                                    :value="visibleColumns.join(',')">
                                            </form>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover text-center"
                                                            id="saleReportTable">
                                                            <thead>
                                                                <tr>
                                                                    <th v-if="isColumnVisible('bus_time')">Bus Time</th>
                                                                    <th v-if="isColumnVisible('bus_number')">Bus No</th>
                                                                    <th v-if="isColumnVisible('bus_class')">Bus Class
                                                                    </th>
                                                                    <th v-if="isColumnVisible('route')">Route</th>
                                                                    <th v-if="isColumnVisible('passenger_name')">Name
                                                                    </th>
                                                                    <th v-if="isColumnVisible('passenger_cnic')">Cnic
                                                                    </th>
                                                                    <th v-if="isColumnVisible('passenger_contact')">
                                                                        Contact</th>
                                                                    <th v-if="isColumnVisible('seat_no')">Seat No</th>
                                                                    <th v-if="isColumnVisible('invoice_id')">Invoice
                                                                    </th>
                                                                    <th v-if="isColumnVisible('transaction_id')">
                                                                        Transaction id </th>
                                                                    <th v-if="isColumnVisible('terminal_name')">Terminal
                                                                        Name</th>
                                                                    <th v-if="isColumnVisible('status')">Status</th>
                                                                    <th v-if="isColumnVisible('action_by')">Action By
                                                                    </th>
                                                                    <th v-if="isColumnVisible('sale')">Sale</th>
                                                                    <th v-if="isColumnVisible('refund')">Refund</th>
                                                                    <th v-if="isColumnVisible('cancellation_charges')">
                                                                        Cancellation Charges</th>
                                                                    <th v-if="isColumnVisible('terminal_commission')">
                                                                        Terminal Commission</th>
                                                                    <th v-if="isColumnVisible('seat_commission')">
                                                                        Seat Commission
                                                                    </th>
                                                                    <th v-if="isColumnVisible('net_cash')">Net Cash</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <tr v-for="(data, i) in filters.record" :key="i">
                                                                    <td v-if="isColumnVisible('bus_time')">{{
                                                                        data.schedule_date }}<br>{{
                                                                            data.schedule_time }}</td>
                                                                    <td v-if="isColumnVisible('bus_number')">{{ data.bus
                                                                        ? data.bus.bus_number : 'N/A' }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('bus_class')">{{
                                                                        data.bus_class?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('route')">{{
                                                                        data.route?.name ?? 'N/A' }} ({{ data.route?.via
                                                                            ??
                                                                        'n/a'
                                                                        }})</td>
                                                                    <td v-if="isColumnVisible('passenger_name')">{{
                                                                        data.customer?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('passenger_cnic')">{{
                                                                        data.customer?.cnic ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('passenger_contact')">{{
                                                                        data.customer?.contact ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('seat_no')">{{
                                                                        data.seat_no ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('invoice_id')">{{
                                                                        data.invoice_id ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('transaction_id')">{{
                                                                        data.transaction_id ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('terminal_name')">{{
                                                                        data.terminal?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('status')">{{ data.type ??
                                                                        'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('action_by')">{{
                                                                        data.updated_name?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('sale')">{{
                                                                        $insertComma(data.type != 'canceled' ?
                                                                        (data.seat_fare -
                                                                        data.discount) : 0) }}</td>
                                                                    <td v-if="isColumnVisible('refund')">{{
                                                                        $insertComma(data.type == 'canceled' ?
                                                                        data.refund : 0) }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('cancellation_charges')">
                                                                        {{ $insertComma(data.cancellation_charges ?? 0) }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('terminal_commission')">
                                                                        {{
                                                                            data.type != 'canceled'
                                                                                ? (
                                                                        Number(data.comsn) > 0
                                                                        ? $insertComma(data.comsn)
                                                                        : 'N/A'
                                                                        )
                                                                        : 0
                                                                        }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('seat_commission')">
                                                                        {{ $insertComma(data.seat_commission ?? 0) }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('net_cash')">
                                                                        {{ $insertComma(data.net_cash ?? 0) }}
                                                                    </td>
                                                                </tr>
                                                                <tr v-if="filters.record.length > 0">
                                                                    <th v-if="isColumnVisible('bus_time')"></th>
                                                                    <th v-if="isColumnVisible('bus_number')"></th>
                                                                    <th v-if="isColumnVisible('bus_class')"></th>
                                                                    <th v-if="isColumnVisible('route')"></th>
                                                                    <th v-if="isColumnVisible('passenger_name')"></th>
                                                                    <th v-if="isColumnVisible('passenger_cnic')"></th>
                                                                    <th v-if="isColumnVisible('passenger_contact')">
                                                                    </th>
                                                                    <th v-if="isColumnVisible('seat_no')">{{
                                                                        filters.record.length }}</th>
                                                                    <th v-if="isColumnVisible('invoice_id')"></th>
                                                                    <th v-if="isColumnVisible('transaction_id')"></th>
                                                                    <th v-if="isColumnVisible('terminal_name')"></th>
                                                                    <th v-if="isColumnVisible('status')"></th>
                                                                    <th v-if="isColumnVisible('action_by')"></th>
                                                                    <th v-if="isColumnVisible('sale')">{{
                                                                        $insertComma(totalSaleAmount()) }}</th>
                                                                    <th v-if="isColumnVisible('refund')">{{
                                                                        $insertComma(totalRefundAmount()) }}</th>
                                                                    <th v-if="isColumnVisible('cancellation_charges')">
                                                                        {{ $insertComma(totalCancellationCharges()) }}
                                                                    </th>
                                                                    <th v-if="isColumnVisible('terminal_commission')">{{
                                                                        $insertComma(totalCommission()) }}</th>
                                                                    <th v-if="isColumnVisible('seat_commission')">
                                                                        {{ $insertComma(totalSeatCommission()) }}
                                                                    </th>
                                                                    <th v-if="isColumnVisible('net_cash')">{{
                                                                        $insertComma(totalNetCash()) }}</th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div v-if="filters.record.length > 0"
                                                        class="net-cash-rows-card">
                                                        <div>
                                                            <div class="net-cash-rows-title">Net Cash Rows Total</div>
                                                            <div class="net-cash-rows-formula">
                                                                Sum of Net Cash column only. No extra plus or minus.
                                                            </div>
                                                        </div>
                                                        <div class="net-cash-rows-value">
                                                            {{ $insertComma(totalNetCashRowsOnly()) }}
                                                        </div>
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
const COLUMN_OPTIONS = [
    { key: 'bus_time', label: 'Bus Time', checked: true },
    { key: 'bus_number', label: 'Bus No', checked: true },
    { key: 'bus_class', label: 'Bus Class', checked: true },
    { key: 'route', label: 'Route', checked: true },
    { key: 'passenger_name', label: 'Name', checked: true },
    { key: 'passenger_cnic', label: 'Cnic', checked: true },
    { key: 'passenger_contact', label: 'Contact', checked: true },
    { key: 'seat_no', label: 'Seat No', checked: true },
    { key: 'invoice_id', label: 'Invoice', checked: true },
    { key: 'transaction_id', label: 'Transaction id', checked: true },
    { key: 'terminal_name', label: 'Terminal Name', checked: true },
    { key: 'status', label: 'Status', checked: true },
    { key: 'action_by', label: 'Action By', checked: true },
    { key: 'sale', label: 'Sale', checked: true },
    { key: 'refund', label: 'Refund', checked: true },
    { key: 'cancellation_charges', label: 'Cancellation Charges', checked: true },
    { key: 'terminal_commission', label: 'Terminal Commission', checked: true },
    { key: 'seat_commission', label: 'Seat Commission', checked: true },
    { key: 'net_cash', label: 'Net Cash', checked: true },
];

export default {
    name: "TerminalSaleReportsPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            loadingTable: false,
            users: [],
            permissions: [],
            columnOptions: COLUMN_OPTIONS.map((column) => ({ ...column })),
            visibleColumns: COLUMN_OPTIONS.filter((column) => column.checked).map((column) => column.key),
            showColumnDropdown: false,
            filters: {
                record: []
            },
            refundFilters: [],
            filterSales: {
                terminal: 0,
                user: 0,
                route: [],
                invoice_id: '',
                transaction_id: '',
                passenger_name: '',
                passenger_contact: '',
                passenger_cnic: '',
                fromDateTime: '',
                toDateTime: '',
            },
        }
    },
    computed: {
        columnSelectionLabel() {
            if (this.visibleColumns.length === this.columnOptions.length) {
                return 'All Table Headers Selected';
            }

            if (this.visibleColumns.length === 0) {
                return 'No Table Headers Selected';
            }

            return `${this.visibleColumns.length} Table Headers Selected`;
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
        document.addEventListener('click', this.handleDocumentClick);
        const self = this;
        // route
        const routeIds = $('#routeIds');
        routeIds.on('change', function () {
            const selectedValues = $(this).val();
            self.filterSales.route = selectedValues;
        });

    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleDocumentClick);
    },
    methods: {
        toggleColumnDropdown() {
            this.showColumnDropdown = !this.showColumnDropdown;
        },
        handleDocumentClick(event) {
            if (!this.showColumnDropdown || !this.$refs.columnDropdown) {
                return;
            }

            if (!this.$refs.columnDropdown.contains(event.target)) {
                this.showColumnDropdown = false;
            }
        },
        isColumnVisible(columnKey) {
            return this.visibleColumns.includes(columnKey);
        },
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'terminals/sales/getTerminals');
            const resUserNames = await this.callApi("post", 'terminals/sales/getUsers');
            const resRoutes = await this.callApi("post", 'terminals/sales/getRoutes');
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

            const resFetchData = await this.callApi("post", 'terminals/sales/fetchFilterData', this.filterSales);
            if (resFetchData.status == 200) {
                this.filters.record = Object.values(resFetchData.data.record);
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
        totalSaleAmount: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                    if (data.type !== 'canceled') {
                        const seatFare = Number(data.seat_fare) || 0;
                        const discount = Number(data.discount) || 0;
                        return sum + (seatFare - discount);
                    }
                    return sum;
                }, 0);
            }
            return 0;
        },
        totalRefundAmount: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                    if (data.type === 'canceled') {
                        const refundValue = Number(data.refund) || 0;
                        return sum + refundValue;
                    }
                    return sum;
                }, 0);
            }
            return 0;
        },
        totalCancellationCharges: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                    return sum + (Number(data.cancellation_charges) || 0);
                }, 0);
            }
            return 0;
        },
        totalNetCash() {
            return this.totalSaleAmount()
                - this.totalCommission()
                - this.totalSeatCommission()
                + this.totalCancellationCharges();
        },
        totalNetCashRowsOnly() {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {
                    return sum + (Number(data.net_cash) || 0);
                }, 0);
            }
            return 0;
        },
        totalCommission: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {

                const seen = new Set();

                return this.filters.record.reduce((sum, data) => {

                    if (data.type == 'canceled') {
                        return sum;
                    }

                    const scheduleTime = data.schedule_time_exact || data.schedule_time || '';
                    const uniqueKey = [
                        data.terminal_id,
                        data.bus_id,
                        data.schedule_date,
                        scheduleTime,
                    ].join('_');

                    // agar already count ho chuka hai
                    if (seen.has(uniqueKey)) {
                        return sum;
                    }

                    seen.add(uniqueKey);

                    const comsn = Number(data.comsn) || 0;

                    return sum + comsn;

                }, 0);
            }

            return 0;
        },
        totalSeatCommission: function () {
            if (this.filters.record && Array.isArray(this.filters.record)) {
                return this.filters.record.reduce((sum, data) => {

                    const seatCom = Number(data.seat_commission) || 0;

                    if (data.type == 'canceled') {
                        return sum;
                    }

                    return sum + seatCom;

                }, 0);
            }
            return 0;
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

.report-tools-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    justify-content: flex-end;
}

.column-dropdown-wrapper {
    position: relative;
    min-width: 280px;
    max-width: 320px;
}

.column-dropdown-toggle {
    width: 100%;
    text-align: left;
}

.column-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    z-index: 20;
    width: 100%;
    max-height: 260px;
    overflow-y: auto;
    padding: 0.75rem;
    background: #fff;
    border: 1px solid #d7dce3;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
}

.column-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.column-option:last-child {
    margin-bottom: 0;
}

.net-cash-rows-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem 1.25rem;
    border: 1px solid #d7dce3;
    border-radius: 8px;
    background: #f8fafc;
}

.net-cash-rows-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2f3542;
}

.net-cash-rows-formula {
    margin-top: 0.25rem;
    font-size: 0.82rem;
    color: #6b7280;
}

.net-cash-rows-value {
    white-space: nowrap;
    font-size: 1.25rem;
    font-weight: 800;
    color: #1f2a44;
}
</style>
