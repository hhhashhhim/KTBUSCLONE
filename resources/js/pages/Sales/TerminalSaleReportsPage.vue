<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Terminal Sales Report</h4>
                            <button type="button" class="btn btn-outline-primary ml-auto" @click="$refs.ticketActivity.open(filterSales.invoice_id)"><i class="fas fa-ticket-alt mr-1"></i> Ticket Details</button>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="sales-filter-heading">
                                                <div><h5>Report filters</h5><p>Choose a departure period and narrow results by route, terminal or passenger.</p></div>
                                            </div>
                                            <div class="route-group-bar" v-if="canUseReportFilter('terminal-sale-route-filter')">
                                                <div class="route-group-picker">
                                                    <label for="routeGroupFilter">Saved route group</label>
                                                    <select id="routeGroupFilter" class="form-control" v-model="filterSales.route_group_id" :disabled="groupsLoading">
                                                        <option value="">Custom route selection</option>
                                                        <option v-for="group in routeGroups" :key="group.id" :value="group.id">{{ group.name }} ({{ group.route_ids.length }} routes)</option>
                                                    </select>
                                                    <small>Reuse a saved set, such as Karachi closing, for this report and its printout.</small>
                                                </div>
                                                <button type="button" class="btn btn-outline-primary" @click="openRouteGroupModal" :disabled="groupsLoading || !groupsReady">
                                                    <i class="fas fa-layer-group mr-1"></i> Manage route groups
                                                </button>
                                            </div>
                                            <div v-if="groupLoadError" class="alert alert-warning">{{ groupLoadError }}</div>
                                            <div class="row sales-filter-grid">
                                                <div class="col-md-6 my-2">
                                                    <label for="ticketStatusFilter">Ticket status</label>
                                                    <select id="ticketStatusFilter" class="form-control" multiple v-model="filterSales.ticket_status">
                                                        <option value="booked">Booked</option>
                                                        <option value="over-issue">Over-issued</option>
                                                        <option value="canceled">Cancelled</option>
                                                        <option value="refunded">Refund recorded (cancelled tickets)</option>
                                                        <option value="unpaid">Expired / cancelled unpaid reservations</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 my-2">
                                                    <label for="cancellationScopeFilter">Cancellation entries</label>
                                                    <select id="cancellationScopeFilter" class="form-control" multiple v-model="filterSales.cancellation_scope">
                                                        <option value="financial">Sales / settlement entries (refund or cancellation charge)</option>
                                                        <option value="charges">Cancellations with charges only</option>
                                                        <option value="refund">Cancellations with a recorded refund only</option>
                                                    </select>
                                                </div>
                                                <div class="col-12"><p class="text-muted small mb-2">Select multiple options to include any matching entry. Clear a filter to include all its options. Cancellation entries applies only to cancelled rows. Settlement entries excludes unpaid reservations and zero-amount cancellations; explicitly selecting unpaid reservations includes them for audit. Refund recorded means a saved amount, not payment-provider confirmation.</p></div>
                                                <div class="col-12 filter-section-label">Passenger &amp; booking</div>
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
                                                        <option value="0">All permitted terminals</option>
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
                                                        <option value="0">All permitted users</option>
                                                        <option v-for="(user, i) in users" :key="i" :value="user.id">
                                                            {{ user.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label for="fromDate">Departure from <span class="text-danger">*</span></label>
                                                    <input id="fromDate" type="datetime-local" class="form-control"
                                                        v-model="filterSales.fromDateTime">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label for="toDate">Departure to <span class="text-danger">*</span></label>
                                                    <input id="toDate" type="datetime-local" class="form-control"
                                                        v-model="filterSales.toDateTime">
                                                </div>
                                                <div class="my-2 col-md-3">
                                                    <label for="terminalSalesColumns">Report columns</label>
                                                    <select id="terminalSalesColumns" class="form-control" multiple v-model="visibleColumns">
                                                        <option v-for="column in columnOptions" :key="column.key" :value="column.key">{{ column.label }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="report-tools-bar mt-2">
                                                <span class="filter-selection-summary">{{ filterSales.route.length ? filterSales.route.length + ' routes selected' : 'All permitted routes' }} · {{ filters.record.length }} records loaded</span>
                                                <button class="btn btn-light" type="button" @click="resetReportFilters" :disabled="loadingTable">Reset filters</button>
                                                <button class="btn btn-primary" type="button" @click="salesFilter()" :disabled="loadingTable">
                                                    <i class="fas fa-filter mr-1"></i>{{ loadingTable ? 'Loading...' : 'Apply filters' }}
                                                </button>
                                                <div v-if="filters.record.length">
                                                    <button class="btn btn-dark" type="button"
                                                        @click="salesPrint()" :disabled="loadingTable">
                                                        <i class="fas fa-print mr-1"></i> Print report
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
                                                <input type="hidden" name="route_group_id" :value="filterSales.route_group_id">
                                                <input type="hidden" name="ticket_status" :value="filterSales.ticket_status.join(',')">
                                                <input type="hidden" name="cancellation_scope" :value="filterSales.cancellation_scope.join(',')">
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
                                                                        data.invoice_id ?? 'N/A' }}
                                                                        <div v-if="data.type === 'canceled'"><span :class="isUnpaidCancellation(data) ? 'reservation-tag' : 'cancelled-tag'">{{ cancellationLabel(data) }}</span></div>
                                                                    </td>
                                                                    <td v-if="isColumnVisible('transaction_id')">{{
                                                                        data.transaction_id ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('terminal_name')">{{
                                                                        data.terminal?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('status')">
                                                                        <span v-if="data.type === 'canceled'" :class="isUnpaidCancellation(data) ? 'reservation-tag' : 'cancelled-tag'">{{ cancellationLabel(data) }}</span>
                                                                        <span v-else>{{ data.type ?? 'N/A' }}</span>
                                                                    </td>
                                                                    <td v-if="isColumnVisible('action_by')">{{
                                                                        data.updated_name?.name ?? 'N/A' }}</td>
                                                                    <td v-if="isColumnVisible('sale')">{{
                                                                        $insertComma(data.type != 'canceled' ?
                                                                        (data.seat_fare -
                                                                        data.discount) : 0) }}</td>
                                                                    <td v-if="isColumnVisible('refund')">{{
                                                                        $insertComma(data.type == 'canceled' && !isUnpaidCancellation(data) ?
                                                                        data.refund : 0) }}
                                                                    </td>
                                                                    <td v-if="isColumnVisible('cancellation_charges')">
                                                                        {{ $insertComma(isUnpaidCancellation(data) ? 0 : data.cancellation_charges ?? 0) }}
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
                                                                        {{ $insertComma(isUnpaidCancellation(data) ? 0 : data.net_cash ?? 0) }}
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
        <TicketActivityModal ref="ticketActivity" />
        <div class="modal fade" id="terminalSalesRouteGroupModal" tabindex="-1" role="dialog" aria-labelledby="routeGroupModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="routeGroupModalTitle">Manage route groups</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="savingGroup"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Save routes together once, then select the group whenever you need the report. Groups are shared within your company.</p>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="editingRouteGroup">Add or edit</label>
                                <select id="editingRouteGroup" class="form-control" v-model="groupForm.id" :disabled="savingGroup">
                                    <option value="">+ Add a new group</option>
                                    <option v-for="group in routeGroups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="routeGroupName">Group name <span class="text-danger">*</span></label>
                                <input id="routeGroupName" class="form-control" maxlength="120" placeholder="e.g. Karachi closing" v-model="groupForm.name" :disabled="savingGroup">
                            </div>
                        </div>
                        <label for="groupRouteSearch">Routes <span class="text-danger">*</span> <span class="badge badge-primary ml-2">{{ groupForm.route_ids.length }} selected</span></label>
                        <input id="groupRouteSearch" class="form-control mb-2" placeholder="Search routes or via cities..." v-model="groupRouteSearch">
                        <div class="group-route-list">
                            <label v-for="route in searchableGroupRoutes" :key="route.id" class="group-route-option">
                                <input type="checkbox" :value="Number(route.id)" v-model="groupForm.route_ids" :disabled="savingGroup">
                                <span>{{ route.name }} <small class="text-muted">{{ route.via ? '(' + route.via + ')' : '' }}</small></span>
                            </label>
                            <p v-if="!searchableGroupRoutes.length" class="text-muted p-3 mb-0">No matching routes.</p>
                        </div>
                        <div class="alert alert-danger mt-3 mb-0" v-if="groupSaveError">{{ groupSaveError }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="savingGroup">Close</button>
                        <button type="button" class="btn btn-primary" @click="saveRouteGroup" :disabled="savingGroup">{{ savingGroup ? 'Saving...' : (groupForm.id ? 'Save changes' : 'Create group') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
import TicketActivityModal from './TicketActivityModal.vue';
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
    components: { TicketActivityModal },
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            routes: [],
            routeGroups: [],
            groupsLoading: false,
            groupsReady: false,
            groupLoadError: '',
            savingGroup: false,
            groupSaveError: '',
            groupRouteSearch: '',
            groupForm: { id: '', name: '', route_ids: [] },
            loadingTable: false,
            appliedSalesFilters: null,
            users: [],
            permissions: [],
            columnOptions: COLUMN_OPTIONS.map((column) => ({ ...column })),
            visibleColumns: COLUMN_OPTIONS.filter((column) => column.checked).map((column) => column.key),
            filters: {
                record: []
            },
            refundFilters: [],
            filterSales: {
                ticket_status: [],
                cancellation_scope: ['financial'],
                terminal: 0,
                user: 0,
                route: [],
                route_group_id: '',
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
        searchableGroupRoutes() {
            const search = this.groupRouteSearch.trim().toLowerCase();
            const routes = [...this.routes];
            this.groupForm.route_ids.forEach(id => {
                if (!routes.some(route => Number(route.id) === Number(id))) routes.push({ id, name: `Unavailable route #${id} — uncheck to remove` });
            });
            return routes.filter(route => `${route.name} ${route.via || ''}`.toLowerCase().includes(search));
        },
    },
    async created() {
        $('.modal').remove();
        this.permissions = this.$store.state.permissions;
        this.fetchFilters();
        if (this.canUseReportFilter('terminal-sale-route-filter')) this.fetchRouteGroups();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },
    mounted() {
        this.initSelect2Filters();

    },
    beforeUnmount() {
        ['#routeIds', '#terminalFilter', '#usernameFilter', '#routeGroupFilter', '#terminalSalesColumns', '#editingRouteGroup', '#ticketStatusFilter', '#cancellationScopeFilter'].forEach(id => {
            $(id).off('.terminalSales');
            if ($(id).hasClass('select2-hidden-accessible')) $(id).select2('destroy');
        });
        $('#terminalSalesRouteGroupModal').modal('hide');
    },
    methods: {
        setupSelect2(id, value, onChange, options = {}) {
            const select = $(id);
            if (!select.length) return;
            if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
            select.select2({ width: '100%', minimumResultsForSearch: 0, ...options });
            select.off('change.terminalSales').on('change.terminalSales', () => onChange(select.val()));
            select.val(Array.isArray(value) ? value.map(String) : String(value ?? '')).trigger('change.select2');
        },
        initSelect2Filters() {
            this.$nextTick(() => {
                this.setupSelect2('#ticketStatusFilter', this.filterSales.ticket_status, value => { this.filterSales.ticket_status = value || []; }, { closeOnSelect: false, placeholder: 'All report statuses' });
                this.setupSelect2('#cancellationScopeFilter', this.filterSales.cancellation_scope, value => { this.filterSales.cancellation_scope = value || []; }, { closeOnSelect: false, placeholder: 'All cancellations (including unpaid reservations)' });
                this.setupSelect2('#terminalFilter', this.filterSales.terminal, value => { this.filterSales.terminal = value || 0; });
                this.setupSelect2('#usernameFilter', this.filterSales.user, value => { this.filterSales.user = value || 0; });
                this.setupSelect2('#routeGroupFilter', this.filterSales.route_group_id, value => {
                    this.filterSales.route_group_id = value || '';
                    this.applyRouteGroup();
                });
                this.setupSelect2('#routeIds', this.filterSales.route, value => {
                    this.filterSales.route_group_id = '';
                    this.filterSales.route = (value || []).map(Number);
                    $('#routeGroupFilter').val('').trigger('change.select2');
                }, { closeOnSelect: false, placeholder: 'All permitted routes' });
                this.setupSelect2('#terminalSalesColumns', this.visibleColumns, value => { this.visibleColumns = value || []; }, { closeOnSelect: false, placeholder: 'Choose report columns' });
                this.setupSelect2('#editingRouteGroup', this.groupForm.id, value => {
                    this.groupForm.id = value || '';
                    this.editRouteGroup();
                }, { dropdownParent: $('#terminalSalesRouteGroupModal') });
            });
        },
        async fetchRouteGroups() {
            this.groupsLoading = true;
            try {
                const res = await this.callApi('post', 'terminals/sales/route-groups');
                if (res.status === 200) {
                    this.routeGroups = res.data;
                    this.groupsReady = true;
                    this.groupLoadError = '';
                    this.initSelect2Filters();
                } else {
                    this.groupLoadError = 'Saved route groups could not be loaded. Please check that the new database migration has been run. Custom route filters still work.';
                }
            } finally { this.groupsLoading = false; }
        },
        setSelectedRoutes(ids) {
            this.filterSales.route = (ids || []).map(Number);
            this.$nextTick(() => $('#routeIds').val(this.filterSales.route.map(String)).trigger('change.select2'));
        },
        applyRouteGroup() {
            const group = this.routeGroups.find(group => Number(group.id) === Number(this.filterSales.route_group_id));
            this.setSelectedRoutes(group ? group.route_ids : []);
        },
        openRouteGroupModal() {
            this.groupForm.id = this.filterSales.route_group_id || '';
            this.editRouteGroup();
            if (!this.groupForm.id) this.groupForm.route_ids = [...this.filterSales.route].map(Number);
            $('#terminalSalesRouteGroupModal').modal('show');
            this.initSelect2Filters();
        },
        editRouteGroup() {
            const group = this.routeGroups.find(group => Number(group.id) === Number(this.groupForm.id));
            this.groupForm = group ? { id: group.id, name: group.name, route_ids: group.route_ids.map(Number) } : { id: '', name: '', route_ids: [] };
            this.groupSaveError = '';
            this.groupRouteSearch = '';
        },
        async saveRouteGroup() {
            if (this.savingGroup) return;
            if (!this.groupForm.name.trim() || !this.groupForm.route_ids.length) {
                this.groupSaveError = 'Enter a group name and select at least one route.';
                return;
            }
            this.savingGroup = true;
            this.groupSaveError = '';
            try {
                const res = await this.callApi('post', 'terminals/sales/route-groups/save', { ...this.groupForm, id: this.groupForm.id || null });
                if (res.status === 200) {
                    const group = res.data.group;
                    this.routeGroups = [...this.routeGroups.filter(item => Number(item.id) !== Number(group.id)), group].sort((a, b) => a.name.localeCompare(b.name));
                    this.filterSales.route_group_id = group.id;
                    this.applyRouteGroup();
                    this.initSelect2Filters();
                    $('#terminalSalesRouteGroupModal').modal('hide');
                    swal('Saved', 'Route group saved and selected. Click Apply filters to load the report.', 'success');
                } else {
                    this.groupSaveError = Object.values(res.data?.errors || {}).flat().join(' ') || res.data?.message || 'Unable to save the route group.';
                }
            } finally { this.savingGroup = false; }
        },
        resetReportFilters() {
            this.filterSales = { terminal: 0, user: 0, route: [], route_group_id: '', invoice_id: '', transaction_id: '', passenger_name: '', passenger_contact: '', passenger_cnic: '', fromDateTime: '', toDateTime: '', ticket_status: [], cancellation_scope: ['financial'] };
            this.appliedSalesFilters = null;
            this.setSelectedRoutes([]);
            this.initSelect2Filters();
            this.filters.record = [];
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
                this.initSelect2Filters();
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

            const submittedFilters = JSON.parse(JSON.stringify(this.filterSales));
            const resFetchData = await this.callApi("post", 'terminals/sales/fetchFilterData', submittedFilters);
            if (resFetchData.status == 200) {
                this.filters.record = Object.values(resFetchData.data.record);
                this.appliedSalesFilters = submittedFilters;
            } else {
                swal('Unable to load report', Object.values(resFetchData.data?.errors || {}).flat().join(' ') || resFetchData.data?.message || 'Please try again.', 'error');
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
            if (!this.appliedSalesFilters || JSON.stringify(this.appliedSalesFilters) !== JSON.stringify(this.filterSales)) {
                return swal('Apply filters first', 'Apply your current filters before printing so the submitted report matches the displayed results.', 'info');
            }
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
                    if (data.type === 'canceled' && !this.isUnpaidCancellation(data)) {
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
                    return sum + (this.isUnpaidCancellation(data) ? 0 : Number(data.cancellation_charges) || 0);
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
                    return sum + (this.isUnpaidCancellation(data) ? 0 : Number(data.net_cash) || 0);
                }, 0);
            }
            return 0;
        },
        isUnpaidCancellation(data) {
            return data.type === 'canceled' && (data.is_unpaid_cancellation === true || data.cancel_ticket?.type === 'advance booking');
        },
        cancellationLabel(data) {
            if (data.report_status) return data.report_status;
            if (!this.isUnpaidCancellation(data)) return 'Cancelled';
            return String(data.cancel_ticket?.reason || '').trim().toLowerCase() === 'auto cancel'
                ? 'Expired reservation' : 'Cancelled reservation';
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
.cancelled-tag { display: inline-block; margin-top: 4px; padding: 3px 7px; border-radius: 5px; border: 1px solid #f4b9c0; background: #fff0f1; color: #b42338; font-size: 10px; font-weight: 700; line-height: 1.3; }
.reservation-tag { display: inline-block; margin-top: 4px; padding: 3px 7px; border-radius: 5px; border: 1px solid #e5c98b; background: #fff8e8; color: #805b10; font-size: 10px; font-weight: 700; line-height: 1.3; }
.sales-filter-grid :deep(.select2-selection--multiple) { max-height: 85px; overflow-y: auto; }
.sales-filter-heading h5 { margin: 0 0 6px; color: #263455; font-size: 17px; }
.sales-filter-heading p { color: #788399; margin-bottom: 18px; font-size: 13px; }
.route-group-bar { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; padding: 18px 20px; margin-bottom: 20px; background: #f1f4ff; border: 1px solid #e0e6fc; border-radius: 10px; }
.route-group-picker { flex: 1; min-width: 220px; }
.route-group-picker small { display: block; color: #73809a; margin-top: 7px; }
.sales-filter-grid { padding: 12px; border: 1px solid #e7ecf3; background: #fafbfd; border-radius: 10px; margin: 0; }
.sales-filter-grid label, .route-group-picker label { font-size: 12px; font-weight: 600; color: #526079; margin-bottom: 7px; }
.filter-section-label { text-transform: uppercase; letter-spacing: .08em; color: #8994a8; font-size: 11px; font-weight: 700; padding-top: 4px; }
.filter-selection-summary { margin-right: auto; color: #7a8598; font-size: 12px; }
.group-route-list { max-height: 320px; overflow-y: auto; border: 1px solid #e3e8ef; border-radius: 8px; }
.group-route-option { display: flex; gap: 12px; align-items: center; padding: 12px 15px; margin: 0; border-bottom: 1px solid #eef1f6; cursor: pointer; }
.group-route-option:hover { background: #f4f6ff; }
.group-route-option input { width: 17px; height: 17px; flex-shrink: 0; }
.sales-filter-grid .column-dropdown-wrapper { position: relative; min-width: 0; max-width: none; }
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
