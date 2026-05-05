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
                                                                    <i class="fa fa-print mr-1"></i> Print Report
                                                                </button>
                                                            </div>
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
                                                            <input type="hidden" name="visible_columns"
                                                                :value="visibleColumns.join(',')">
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
                                                                    <th v-if="isColumnVisible('terminal_name')">Terminal Name</th>
                                                                    <th v-if="isColumnVisible('passenger_name')">Passenger Name</th>
                                                                    <th v-if="isColumnVisible('passenger_contact')">Cell NO</th>
                                                                    <th v-if="isColumnVisible('passenger_cnic')">Cnic NO</th>
                                                                    <th v-if="isColumnVisible('status')">Status</th>
                                                                    <th v-if="isColumnVisible('current_status')">Current Status</th>
                                                                    <th v-if="isColumnVisible('from_bus_time')">From Bus Time</th>
                                                                    <th v-if="isColumnVisible('to_bus_time')">To Bus Time</th>
                                                                    <th v-if="isColumnVisible('reschedule_from')">Reschedule From</th>
                                                                    <th v-if="isColumnVisible('reschedule_to')">Reschedule To</th>
                                                                    <th v-if="isColumnVisible('from_seat')">From Seat</th>
                                                                    <th v-if="isColumnVisible('to_seat')">To Seat</th>
                                                                    <th v-if="isColumnVisible('old_fare')">Old Fare</th>
                                                                    <th v-if="isColumnVisible('new_fare')">New Fare</th>
                                                                    <th v-if="isColumnVisible('remarks')">Remarks</th>
                                                                    <th v-if="isColumnVisible('reschedule_by')">Over Issue By</th>
                                                                    <th v-if="isColumnVisible('reschedule_time')">Over Issue Time</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <tr v-for="(filter, i) in filters" :key="i"
                                                                    :class="filter.badge">
                                                                    <td v-if="isColumnVisible('terminal_name')">{{ filter.terminal_name ? filter.terminal_name :
                                                                        'Not Fetched' }}</td>
                                                                    <td v-if="isColumnVisible('passenger_name')">{{ filter.passenger_name }}</td>
                                                                    <td v-if="isColumnVisible('passenger_contact')">{{ filter.passenger_contact }}</td>
                                                                    <td v-if="isColumnVisible('passenger_cnic')">{{ filter.passenger_cnic }}</td>
                                                                    <td v-if="isColumnVisible('status')">{{ filter.type }}</td>
                                                                    <td v-if="isColumnVisible('current_status')">{{ filter.new_type }}</td>
                                                                    <td v-if="isColumnVisible('from_bus_time')">{{ filter.old_bus_time }}</td>
                                                                    <td v-if="isColumnVisible('to_bus_time')">{{ filter.new_bus_time }}</td>
                                                                    <td v-if="isColumnVisible('reschedule_from')">{{
                                                                        filter.old_departure+'-'+filter.old_destination
                                                                        }}</td>
                                                                    <td v-if="isColumnVisible('reschedule_to')">{{
                                                                        filter.new_departure+'-'+filter.new_destination
                                                                        }}</td>
                                                                    <td v-if="isColumnVisible('from_seat')">{{ filter.old_seat }}</td>
                                                                    <td v-if="isColumnVisible('to_seat')">{{ filter.new_seat }}</td>
                                                                    <td v-if="isColumnVisible('old_fare')">{{ filter.old_fare }}</td>
                                                                    <td v-if="isColumnVisible('new_fare')">{{ filter.new_fare }}</td>
                                                                    <td v-if="isColumnVisible('remarks')">{{ filter.reason }}</td>
                                                                    <td v-if="isColumnVisible('reschedule_by')">{{ filter.reschedule_by }}</td>
                                                                    <td v-if="isColumnVisible('reschedule_time')">{{ filter.reschedule_time }}</td>
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
    { key: 'terminal_name', label: 'Terminal Name', checked: true },
    { key: 'passenger_name', label: 'Passenger Name', checked: true },
    { key: 'passenger_contact', label: 'Cell NO', checked: true },
    { key: 'passenger_cnic', label: 'Cnic NO', checked: true },
    { key: 'status', label: 'Status', checked: true },
    { key: 'current_status', label: 'Current Status', checked: true },
    { key: 'from_bus_time', label: 'From Bus Time', checked: true },
    { key: 'to_bus_time', label: 'To Bus Time', checked: true },
    { key: 'reschedule_from', label: 'Reschedule From', checked: true },
    { key: 'reschedule_to', label: 'Reschedule To', checked: true },
    { key: 'from_seat', label: 'From Seat', checked: true },
    { key: 'to_seat', label: 'To Seat', checked: true },
    { key: 'old_fare', label: 'Old Fare', checked: true },
    { key: 'new_fare', label: 'New Fare', checked: true },
    { key: 'remarks', label: 'Remarks', checked: true },
    { key: 'reschedule_by', label: 'Reschedule By', checked: true },
    { key: 'reschedule_time', label: 'Reschedule Time', checked: true },
];

export default {
    name: "ReschedulePage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            terminals: [],
            filters: [],
            refundFilters: [],
            columnOptions: COLUMN_OPTIONS.map((column) => ({ ...column })),
            visibleColumns: COLUMN_OPTIONS.filter((column) => column.checked).map((column) => column.key),
            showColumnDropdown: false,
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
