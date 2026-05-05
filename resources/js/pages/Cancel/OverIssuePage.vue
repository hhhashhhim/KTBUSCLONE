<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Over Issue Report</h4>
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
                                                            <!-- CNIC -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">CNIC</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_cnic"
                                                                    @keyup="overissueFilter()"
                                                                    placeholder="Enter CNIC (without dashes)">
                                                            </div>
                                                            <!-- Contact -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Contact</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_contact"
                                                                    @keyup="overissueFilter()" placeholder="03XXXXXXXXX">
                                                            </div>
                                                            <!-- Passenger Name -->
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Passenger</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.passenger_name"
                                                                    @keyup="overissueFilter()" placeholder="Name">
                                                            </div>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="filter-label">Invoice ID</label>
                                                                <input type="text" class="form-control filter-input"
                                                                    v-model="filterCancel.invoice_id"
                                                                    @keyup="overissueFilter()" placeholder="Invoice ID">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label for="routeFilter">Route</label>
                                                                    <select id="routeFilter" class="form-control"
                                                                        v-model="filterCancel.route"
                                                                        @change="overissueFilter()">
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
                                                                    @change="overissueFilter()">
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
                                                                    @keyup="overissueFilter()"
                                                                    placeholder="Transaction ID">
                                                            </div>






                                                            <!-- From Date -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">From</label>
                                                                <input type="date" class="form-control filter-input"
                                                                    v-model="filterCancel.fromDate"
                                                                    @change="overissueFilter()">
                                                            </div>

                                                            <!-- To Date -->
                                                            <div class="col-md-4 mb-3">
                                                                <label class="filter-label">To</label>
                                                                <input type="date" class="form-control filter-input"
                                                                    v-model="filterCancel.toDate"
                                                                    @change="overissueFilter()">
                                                            </div>

                                                            <div class="col-12 mb-3">
                                                                <div class="report-actions">
                                                                    <div class="column-dropdown-wrapper"
                                                                        ref="columnDropdown">
                                                                        <label class="filter-label">Show/Hide Table Headers</label>
                                                                        <button type="button"
                                                                            class="btn btn-outline-secondary column-dropdown-toggle"
                                                                            @click.stop="toggleColumnDropdown()">
                                                                            {{ columnSelectionLabel }}
                                                                        </button>
                                                                        <div v-if="showColumnDropdown"
                                                                            class="column-dropdown-menu"
                                                                            @click.stop>
                                                                            <label v-for="column in columnOptions"
                                                                                :key="column.key"
                                                                                class="column-option">
                                                                                <input type="checkbox"
                                                                                    :value="column.key"
                                                                                    v-model="visibleColumns">
                                                                                <span>{{ column.label }}</span>
                                                                            </label>
                                                                        </div>
                                                                    </div>

                                                                    <button class="btn btn-primary filter-btn print-btn"
                                                                        type="button"
                                                                        @click="getPdfPrint()">
                                                                        <i class="fa fa-print mr-1"></i> Print
                                                                    </button>
                                                                </div>
                                                            </div>

                                                        </div>
                                                </div>
                                            </div>
                                          <form
    :action="$store.state.api_url + 'api/web/v1/print/pdf/over-issue/report'"
    method="POST" ref="refOverissue" target="_blank">

    <input type="hidden" name="token" :value="$store.state.token">
    <input type="hidden" name="terminal" :value="filterCancel.terminal">
    <input type="hidden" name="route" :value="filterCancel.route">
    <input type="hidden" name="invoice_id" :value="filterCancel.invoice_id">
    <input type="hidden" name="transaction_id" :value="filterCancel.transaction_id">
    <input type="hidden" name="fromDate" :value="filterCancel.fromDate">
    <input type="hidden" name="toDate" :value="filterCancel.toDate">
    <input type="hidden" name="type" :value="filterCancel.type">
    <input type="hidden" name="passenger_name" :value="filterCancel.passenger_name">
    <input type="hidden" name="passenger_contact" :value="filterCancel.passenger_contact">
    <input type="hidden" name="passenger_cnic" :value="filterCancel.passenger_cnic">
    <input type="hidden" name="visible_columns" :value="visibleColumns.join(',')">
</form>
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
                                                                    <th v-if="isColumnVisible('bus_time')" width="200px">Bus Time</th>
                                                                    <th v-if="isColumnVisible('terminal_name')">Terminal Name</th>
                                                                    <th v-if="isColumnVisible('route')">Route</th>
                                                                    <th v-if="isColumnVisible('transaction_id')">Transaction #</th>
                                                                    <th v-if="isColumnVisible('invoice')">Invoice</th>
                                                                    <th v-if="isColumnVisible('seat_no')">Seat No</th>
                                                                    <th v-if="isColumnVisible('type')">Type</th>
                                                                    <th v-if="isColumnVisible('passenger_name')">Passenger Name</th>
                                                                    <th v-if="isColumnVisible('passenger_contact')">Cell NO</th>
                                                                    <th v-if="isColumnVisible('passenger_cnic')">Cnic NO</th>
                                                                    <th v-if="isColumnVisible('total_fare')">Total Fare</th>
                                                                    <th v-if="isColumnVisible('remarks')">Remarks</th>
                                                                    <th v-if="isColumnVisible('overissue_by')">Over Issue By</th>
                                                                    <th v-if="isColumnVisible('overissue_time')" width="200px">Over Issue Time</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <tr v-for="(filter, i) in filters" :key="i"
                                                                    :class="filter.badge">
                                                                    <td v-if="isColumnVisible('bus_time')">{{ filter.bus_time }}</td>
                                                                    <td v-if="isColumnVisible('terminal_name')">{{ filter.terminal_name }}</td>
                                                                     <td v-if="isColumnVisible('route')">{{ filter?.route?.name }}</td>
                                                                    <td v-if="isColumnVisible('transaction_id')">{{ filter.transaction_id }}</td>
                                                                    <td v-if="isColumnVisible('invoice')">{{ filter.invoice_id }}</td>
                                                                    <td v-if="isColumnVisible('seat_no')">{{ filter.seat_no }}</td>
                                                                    <td v-if="isColumnVisible('type')">{{ filter.type }}</td>
                                                                    <td v-if="isColumnVisible('passenger_name')">{{ filter.passenger_name }}</td>
                                                                    <td v-if="isColumnVisible('passenger_contact')">{{ filter.passenger_contact }}</td>
                                                                    <td v-if="isColumnVisible('passenger_cnic')">{{ filter.passenger_cnic }}</td>
                                                                    <td v-if="isColumnVisible('total_fare')">{{ filter.total_fare }}</td>
                                                                    <td v-if="isColumnVisible('remarks')">{{ filter.overissue_reason }}</td>
                                                                    <td v-if="isColumnVisible('overissue_by')">{{ filter.overissue_by }}</td>
                                                                    <td v-if="isColumnVisible('overissue_time')">{{ filter.overissue_time }}</td>
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
const COLUMN_OPTIONS = [
    { key: 'bus_time', label: 'Bus Time', checked: true },
    { key: 'terminal_name', label: 'Terminal Name', checked: true },
    { key: 'route', label: 'Route', checked: true },
    { key: 'transaction_id', label: 'Transaction #', checked: true },
    { key: 'invoice', label: 'Invoice', checked: true },
    { key: 'seat_no', label: 'Seat No', checked: true },
    { key: 'type', label: 'Type', checked: true },
    { key: 'passenger_name', label: 'Passenger Name', checked: true },
    { key: 'passenger_contact', label: 'Cell NO', checked: true },
    { key: 'passenger_cnic', label: 'Cnic NO', checked: true },
    { key: 'total_fare', label: 'Total Fare', checked: true },
    { key: 'remarks', label: 'Remarks', checked: true },
    { key: 'overissue_by', label: 'Over Issue By', checked: true },
    { key: 'overissue_time', label: 'Over Issue Time', checked: true },
];

export default {
    name: "OverIssuePage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            filters: [],
              routes: [],
            refundFilters: [],
            columnOptions: COLUMN_OPTIONS.map((column) => ({ ...column })),
            visibleColumns: COLUMN_OPTIONS.filter((column) => column.checked).map((column) => column.key),
            showColumnDropdown: false,
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
         this.fetchRoutes();
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
    mounted() {
        document.addEventListener('click', this.handleDocumentClick);
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
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "over-issue/routes");
            if (resRoute.status == 200) {
                this.routes = resRoute.data;
            }
        },
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'over-issue/getTerminals');
            if (resTerminals.status == 200) {
                this.terminals = resTerminals.data;
            }

        },
        async overissueFilter() {
            this.tableLoading = true;
            const resFetchData = await this.callApi("post", 'over-issue/fetchFilterData', this.filterCancel);
            console.log(resFetchData);
            if (resFetchData.status == 200) {
                this.filters = resFetchData.data;
                this.tableLoading = false;
            }

        },
        getPdfPrint: function () {
            this.$refs.refOverissue.submit();
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

.report-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    justify-content: space-between;
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

.print-btn {
    min-width: 160px;
}
</style>
