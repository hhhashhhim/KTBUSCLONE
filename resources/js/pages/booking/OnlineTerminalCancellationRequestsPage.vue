<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Terminal Cancellation Requests</h4>
                        </div>
                        <div class="card-body">
                            <div class="cancellation-tabs mb-4" role="tablist" aria-label="Cancellation request status">
                                <button v-for="tab in ['pending', 'completed']" :key="tab" type="button"
                                        role="tab" :aria-selected="activeTab === tab"
                                        :class="['cancellation-tab', { active: activeTab === tab }]"
                                        @click="selectTab(tab)">
                                    {{ tab === 'pending' ? 'Pending' : 'Completed' }}
                                    <span class="cancellation-count">{{ counts[tab] }}</span>
                                </button>
                            </div>
                            <form class="cancellation-filters" @submit.prevent="applyFilters()">
                                <div class="cancellation-filter-header">
                                    <div class="cancellation-filter-heading">
                                        <span class="cancellation-filter-icon"><i class="fas fa-filter" aria-hidden="true"></i></span>
                                        <div><h5>Find cancellation requests</h5><p>Search by passenger, booking reference or journey.</p></div>
                                    </div>
                                    <span class="cancellation-filter-hint">All filters are optional</span>
                                </div>
                                <div class="cancellation-filter-grid">
                                    <div class="cancellation-filter-field">
                                        <label for="nameFilter">Passenger name</label>
                                        <input id="nameFilter" type="text" class="form-control" v-model="filters.nameFilter" placeholder="Enter passenger name">
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="cnicFilter">CNIC</label>
                                        <vue-mask id="cnicFilter" class="form-control" v-model="filters.cnicFilter" mask="00000-0000000-0" :raw="false" :options="options"></vue-mask>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="phoneFilter">Mobile number</label>
                                        <vue-mask id="phoneFilter" class="form-control" v-model="filters.phoneFilter" mask="0000-0000000" :raw="false" :options="optionsContact"></vue-mask>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="invoiceFilter">Invoice number</label>
                                        <input id="invoiceFilter" type="text" class="form-control" v-model="filters.invoiceFilter" placeholder="Enter invoice number">
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="routeFilter">Route</label>
                                        <select id="routeFilter" class="form-control" data-cancellation-filter="routeFilter" v-model="filters.routeFilter">
                                            <option value="">All routes</option>
                                            <option v-for="route in routes" :key="route.id" :value="route.id">{{ route.name }}{{ route.via ? ' (' + route.via + ')' : '' }}</option>
                                        </select>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="terminalFilter">Terminal</label>
                                        <select id="terminalFilter" class="form-control" data-cancellation-filter="terminalFilter" v-model="filters.terminalFilter">
                                            <option value="">All terminals</option>
                                            <option v-for="terminal in terminals" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option>
                                        </select>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="busFilter">Bus number</label>
                                        <select id="busFilter" class="form-control" data-cancellation-filter="busFilter" v-model="filters.busFilter">
                                            <option value="">All buses</option>
                                            <option v-for="bus in buses" :key="bus.id" :value="bus.id">{{ bus.bus_number }}</option>
                                        </select>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="statusFilter">Request status</label>
                                        <select id="statusFilter" class="form-control" data-cancellation-filter="statusFilter" v-model="filters.statusFilter" :disabled="activeTab === 'pending'">
                                            <option value="">{{ activeTab === 'pending' ? 'Pending requests' : 'All completed statuses' }}</option>
                                            <option v-if="activeTab === 'completed'" value="cancelled">Cancelled</option>
                                            <option v-if="activeTab === 'completed'" value="approved">Approved</option>
                                            <option v-if="activeTab === 'completed'" value="rejected">Rejected</option>
                                            <option v-if="activeTab === 'completed'" value="failed">Failed</option>
                                            <option v-if="activeTab === 'completed'" value="unauthorized">Unauthorized</option>
                                        </select>
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="fromDateFilter">Departure date · From</label>
                                        <input type="date" class="form-control" id="fromDateFilter" v-model="filters.fromDateFilter" :max="filters.toDateFilter || undefined">
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="toDateFilter">Departure date · To</label>
                                        <input type="date" class="form-control" id="toDateFilter" v-model="filters.toDateFilter" :min="filters.fromDateFilter || undefined">
                                    </div>
                                    <div class="cancellation-filter-field">
                                        <label for="transactionFilter">Transaction reference</label>
                                        <input id="transactionFilter" type="text" class="form-control" v-model="filters.transactionFilter" placeholder="Enter transaction reference">
                                    </div>
                                    <div class="cancellation-filter-actions">
                                        <button type="submit" class="btn btn-primary" :disabled="loading"><i class="fas fa-search" aria-hidden="true"></i> Apply filters</button>
                                        <button type="button" class="btn btn-outline-secondary" @click="resetFilters()" :disabled="loading">Reset</button>
                                    </div>
                                </div>
                            </form>

                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                <div class="text-muted small mr-3">
                                    <template v-if="loading">Loading {{ activeTab }} requests…</template>
                                    <template v-else-if="activeTab === 'pending'">Pending requests load automatically, with no date limit.</template>
                                    <template v-else-if="resultScope.recent_completed_only">Completed in the last 10 days ({{ resultScope.from }} to {{ resultScope.to }}). Use filters above to search older records.</template>
                                    <template v-else>Searching completed history using your filters.</template>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <div v-if="loading" class="text-center py-4">
                                    <img class="loading-spinner"
                                         :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                </div>
                                <table class="table table-bordered table-striped text-center" id="online_terminal_cancellation_table" v-show="!loading">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Booking Reference</th>
                                            <th>Route</th>
                                            <th>Seats</th>
                                            <th>Reason</th>
                                            <th>Bus Date</th>
                                            <th>Bus Time</th>
                                            <th>Passenger Name</th>
                                            <th>CNIC</th>
                                            <th>Contact</th>
                                            <th>Fare</th>
                                            <th>Deduction %</th>
                                            <th>Deduction Amount</th>
                                            <th>Refund %</th>
                                            <th>Refund Amount</th>
                                            <th>Booking Time</th>
                                            <th>Request Date</th>
                                            <th>Requested By</th>
                                            <th>Time Difference</th>
                                            <th>Source</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Decision By</th>
                                            <th>Decision Remarks</th>
                                            <th>Cancelled At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="request in requests" :key="request.row_key || request.id"
                                            :class="timeDifferenceRowClass(request)">
                                            <td>{{ request.invoice_id }}</td>
                                            <td>{{ request.booking_reference || 'N/A' }}</td>
                                            <td>{{ request.route || 'N/A' }}</td>
                                            <td>
                                                {{ formatSeats(request.seat_numbers) }}
                                                <div class="small text-muted" v-if="request.request_seat_count > 1">
                                                    Grouped request: {{ request.request_seat_count }} seats
                                                </div>
                                            </td>
                                            <td>{{ request.cancellation_reason || 'N/A' }}</td>
                                            <td>{{ request.bus_date || 'N/A' }}</td>
                                            <td>{{ request.bus_time || 'N/A' }}</td>
                                            <td>{{ request.passenger_name || 'N/A' }}</td>
                                            <td>{{ request.cnic || 'N/A' }}</td>
                                            <td>{{ request.contact || 'N/A' }}</td>
                                            <td>{{ formatFare(request.fare) }}</td>
                                            <td>{{ formatPercentage(request.deduction_percentage) }}</td>
                                            <td>{{ formatFare(request.deduction_amount) }}</td>
                                            <td>{{ formatPercentage(request.refund_percentage) }}</td>
                                            <td>{{ formatFare(request.refund_amount) }}</td>
                                            <td>{{ request.booking_time || 'N/A' }}</td>
                                            <td>{{ request.request_date || 'N/A' }}</td>
                                            <td>{{ request.requested_by || 'Not recorded' }}</td>
                                            <td>{{ request.time_difference || timeDifference(request) }}</td>
                                            <td>{{ request.source || 'Online Terminal' }}</td>
                                            <td>
                                                <span :class="request.request_type === 'offline' ? 'badge badge-secondary' : 'badge badge-info'">
                                                    {{ request.request_type === 'offline' ? 'Offline' : 'Online' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span :class="statusClass(request.status)">
                                                    {{ statusLabel(request.status) }}
                                                </span>
                                                <div class="small text-muted" v-if="request.cancellation_status">
                                                    {{ statusLabel(request.cancellation_status) }}
                                                </div>
                                            </td>
                                            <td>{{ request.decision_by || 'N/A' }}</td>
                                            <td>{{ request.decision_remarks || 'N/A' }}</td>
                                            <td>{{ request.cancelled_at || 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group" v-if="request.status === 'pending'">
                                                    <button class="btn btn-success btn-sm"
                                                            v-if="canUseButton('approve-request')"
                                                            :disabled="actionLoadingId === request.id"
                                                            @click="openApproveModal(request)">
                                                        {{ request.request_seat_count > 1 ? 'Approve Group' : 'Approve' }}
                                                    </button>
                                                    <button class="btn btn-danger btn-sm"
                                                            v-if="canUseButton('reject-request')"
                                                            :disabled="actionLoadingId === request.id"
                                                            @click="openRejectModal(request)">
                                                        {{ request.request_seat_count > 1 ? 'Reject Group' : 'Reject' }}
                                                    </button>
                                                </div>
                                                <span class="text-muted" v-else>No action</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center justify-content-between flex-wrap mt-3">
                                <div class="cancellation-page-size mr-2">
                                    <label class="mb-0" for="cancellationPageSize">Requests per page</label>
                                    <select id="cancellationPageSize" class="form-control" v-model.number="perPage" :disabled="loading">
                                        <option :value="25">25</option><option :value="50">50</option><option :value="100">100</option>
                                    </select>
                                </div>
                                <span class="text-muted">Page {{ pagination.current_page }} of {{ pagination.last_page }} · {{ pagination.total }} requests</span>
                                <div>
                                    <button class="btn btn-outline-primary btn-sm mr-2" :disabled="loading || pagination.current_page <= 1"
                                            @click="fetchRequests(pagination.current_page - 1)">Previous</button>
                                    <button class="btn btn-outline-primary btn-sm" :disabled="loading || pagination.current_page >= pagination.last_page"
                                            @click="fetchRequests(pagination.current_page + 1)">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="onlineTerminalApproveModal" tabindex="-1" role="dialog"
                 aria-labelledby="onlineTerminalApproveModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="onlineTerminalApproveModalLabel">Approve Terminal Request</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info" v-if="selectedRequest">
                                Seats: {{ formatSeats(selectedRequest.request_seat_numbers || selectedRequest.seat_numbers) }}
                                <div v-if="selectedRequest.request_seat_count > 1">This existing grouped request covers all listed seats. Approval applies to all of them.</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Requested Seats Fare</label>
                                    <input type="text" class="form-control" :value="formatFare(approvePreview.fare)" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Deduction Percentage</label>
                                    <input type="number" class="form-control" min="0" max="100" step="0.01"
                                           v-model.number="approveForm.deduction_percentage">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label>Deduction Amount</label>
                                    <input type="text" class="form-control" :value="formatFare(approvePreview.deduction_amount)" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Refund Percentage</label>
                                    <input type="text" class="form-control" :value="formatPercentage(approvePreview.refund_percentage)" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label>Refund Amount</label>
                                    <input type="text" class="form-control" :value="formatFare(approvePreview.refund_amount)" disabled>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>Approval Remarks</label>
                                <textarea class="form-control" rows="4" v-model="approveForm.remarks"
                                          placeholder="Enter approval remarks"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" @click="approveRequest()"
                                    :disabled="approveLoading">
                                Approve Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="onlineTerminalRejectModal" tabindex="-1" role="dialog"
                 aria-labelledby="onlineTerminalRejectModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="onlineTerminalRejectModalLabel">Reject Terminal Request</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info" v-if="selectedRequest">
                                Seats: {{ formatSeats(selectedRequest.request_seat_numbers || selectedRequest.seat_numbers) }}
                                <div v-if="selectedRequest.request_seat_count > 1">Rejection applies to all seats in this existing grouped request.</div>
                            </div>
                            <label>Rejection Remarks</label>
                            <textarea class="form-control" rows="4" v-model="rejectionReason"
                                      placeholder="Enter rejection remarks"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" @click="rejectRequest()"
                                    :disabled="rejectLoading">
                                Reject Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import vueMask from "vue-jquery-mask";

export default {
    name: "OnlineTerminalCancellationRequestsPage",
    components: {
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
            permissions: [],
            requests: [],
            activeTab: 'pending',
            counts: { pending: 0, completed: 0 },
            pagination: { current_page: 1, last_page: 1, total: 0 },
            perPage: 25,
            resultScope: { recent_completed_only: false, from: '', to: '' },
            routes: [],
            terminals: [],
            buses: [],
            loading: false,
            requestSequence: 0,
            filtersDisposed: false,
            appliedFilters: {},
            actionLoadingId: null,
            approveLoading: false,
            rejectLoading: false,
            selectedRequest: null,
            approveForm: {
                deduction_percentage: 0,
                remarks: '',
            },
            rejectionReason: '',
            filters: {
                cnicFilter: '',
                phoneFilter: '',
                nameFilter: '',
                invoiceFilter: '',
                routeFilter: '',
                terminalFilter: '',
                busFilter: '',
                statusFilter: '',
                fromDateFilter: '',
                toDateFilter: '',
                transactionFilter: '',
            },
        };  
    },
    created() {
        this.permissions = this.$store.state.permissions;
        this.appliedFilters = this.defaultFilters();
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();
        this.fetchRequests();
    },
    mounted() {
        this.initFilterSelect2();
    },
    beforeUnmount() {
        this.filtersDisposed = true;
        this.requestSequence++;
        this.destroyFilterSelect2();
        this.destroyDataTable();
    },
    computed: {
        approvePreview() {
            const fare = Number(this.selectedRequest?.request_fare ?? this.selectedRequest?.fare) || 0;
            const deductionPercentage = Math.min(Math.max(Number(this.approveForm.deduction_percentage) || 0, 0), 100);
            const refundPercentage = 100 - deductionPercentage;
            const deductionAmount = this.calculatePercentAmount(fare, deductionPercentage);
            const refundAmount = this.calculatePercentAmount(fare, refundPercentage);

            return {
                fare,
                deduction_percentage: deductionPercentage,
                deduction_amount: deductionAmount,
                refund_percentage: refundPercentage,
                refund_amount: refundAmount,
            };
        },
    },
    methods: {
        initFilterSelect2() {
            this.$nextTick(() => {
                if (this.filtersDisposed || !this.$el || typeof $ === 'undefined' || !$.fn.select2) return;
                this.$el.querySelectorAll('[data-cancellation-filter], #cancellationPageSize').forEach(element => {
                    const select = $(element);
                    const field = element.dataset.cancellationFilter;
                    select.off('.terminalCancellationFilters');
                    if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                    select.select2({ width: '100%', minimumResultsForSearch: 0, dropdownParent: select.parent() });
                    select.val(String(field ? this.filters[field] || '' : this.perPage)).trigger('change.select2');
                    select.on('change.terminalCancellationFilters', () => {
                        if (field) {
                            this.filters[field] = select.val() || '';
                        } else {
                            this.perPage = Number(select.val()) || 25;
                            this.fetchRequests();
                        }
                    });
                });
            });
        },
        destroyFilterSelect2() {
            if (!this.$el || typeof $ === 'undefined' || !$.fn.select2) return;
            this.$el.querySelectorAll('[data-cancellation-filter], #cancellationPageSize').forEach(element => {
                const select = $(element);
                select.off('.terminalCancellationFilters');
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
            });
        },
        applyFilters() {
            this.appliedFilters = { ...this.filters };
            this.fetchRequests();
        },
        selectTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            // Do not carry historical Completed filters into the Pending inbox.
            this.filters = this.defaultFilters();
            this.appliedFilters = { ...this.filters };
            this.initFilterSelect2();
            this.fetchRequests();
        },
        defaultFilters() {
            return {
                cnicFilter: '',
                phoneFilter: '',
                nameFilter: '',
                invoiceFilter: '',
                routeFilter: '',
                terminalFilter: '',
                busFilter: '',
                statusFilter: '',
                fromDateFilter: '',
                toDateFilter: '',
                transactionFilter: '',
            };
        },
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "online-terminals/cancellations/routes");
            if (resRoute.status === 200) {
                this.routes = resRoute.data;
                this.initFilterSelect2();
            }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "online-terminals/cancellations/terminals");
            if (resTerminal.status === 200) {
                this.terminals = resTerminal.data;
                this.initFilterSelect2();
            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "online-terminals/cancellations/buses");
            if (resBuses.status === 200) {
                this.buses = resBuses.data;
                this.initFilterSelect2();
            }
        },
        async fetchRequests(page = 1) {
            const sequence = ++this.requestSequence;
            this.destroyDataTable();
            this.loading = true;
            const res = await this.callApi('post', 'online-terminals/cancellations/requests', {
                ...this.appliedFilters, tab: this.activeTab, page, per_page: this.perPage,
            });
            if (sequence !== this.requestSequence) {
                return;
            }
            if (res.status === 200 && res.data.status === true) {
                this.requests = res.data.data.requests;
                this.counts = res.data.data.counts || { pending: 0, completed: 0 };
                this.resultScope = res.data.data.scope || { recent_completed_only: false, from: '', to: '' };
                this.pagination = res.data.data.pagination || { current_page: 1, last_page: 1, total: this.requests.length };
            } else {
                this.requests = [];
            }
            this.loading = false;
            this.$nextTick(() => {
                this.initDataTable();
            });
        },
        resetFilters() {
            this.filters = this.defaultFilters();
            this.appliedFilters = { ...this.filters };
            this.initFilterSelect2();
            this.fetchRequests();
        },
        openApproveModal(request) {
            this.selectedRequest = request;
            this.approveForm = {
                deduction_percentage: Number(request.deduction_percentage) || 0,
                remarks: request.decision_remarks || '',
            };
            $('#onlineTerminalApproveModal').modal('show');
        },
        async approveRequest() {
            if (!this.selectedRequest) {
                return;
            }

            if (this.approveForm.deduction_percentage < 0 || this.approveForm.deduction_percentage > 100) {
                swal("Error", "Deduction percentage must be between 0 and 100.", "error");
                return;
            }

            this.approveLoading = true;
            this.actionLoadingId = this.selectedRequest.id;
            const res = await this.callApi('post', 'online-terminals/cancellations/approve', {
                id: this.selectedRequest.id,
                deduction_percentage: this.approveForm.deduction_percentage,
                remarks: this.approveForm.remarks,
            });
            this.approveLoading = false;
            this.actionLoadingId = null;

            if (res.status === 200 && this.isApiSuccess(res.data)) {
                $('#onlineTerminalApproveModal').modal('hide');
                swal("Approved", res.data.message, "success");
                this.fetchRequests();
                window.dispatchEvent(new CustomEvent('terminal-cancellation-count-refresh'));
            } else {
                swal("Error", res.data.message || "Approval failed.", "error");
            }
        },
        openRejectModal(request) {
            this.selectedRequest = request;
            this.rejectionReason = '';
            $('#onlineTerminalRejectModal').modal('show');
        },
        async rejectRequest() {
            if (!this.rejectionReason.trim()) {
                swal("Error", "Rejection reason is required.", "error");
                return;
            }

            this.rejectLoading = true;
            const res = await this.callApi('post', 'online-terminals/cancellations/reject', {
                id: this.selectedRequest.id,
                rejection_reason: this.rejectionReason,
            });
            this.rejectLoading = false;

            if (res.status === 200 && this.isApiSuccess(res.data)) {
                $('#onlineTerminalRejectModal').modal('hide');
                swal("Rejected", res.data.message, "success");
                this.fetchRequests();
                window.dispatchEvent(new CustomEvent('terminal-cancellation-count-refresh'));
            } else {
                swal("Error", res.data.message || "Rejection failed.", "error");
            }
        },
        destroyDataTable() {
            const table = document.getElementById('online_terminal_cancellation_table');
            if (!table || !window.$ || !$.fn.DataTable) {
                return;
            }

            if (!table.parentNode) {
                this.clearDataTableSettings(table);
                return;
            }

            if ($.fn.DataTable.isDataTable(table)) {
                try {
                    $(table).DataTable().destroy(false);
                } catch (error) {
                    this.clearDataTableSettings(table);
                    $(table).removeClass('dataTable no-footer');
                }
            }
        },
        initDataTable() {
            const table = document.getElementById('online_terminal_cancellation_table');
            if (table && table.parentNode && $.fn.DataTable && !$.fn.DataTable.isDataTable(table)) {
                $(table).DataTable({
                    // Pagination and filtering belong to the API, not just the
                    // subset of rows currently loaded in the browser.
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: false,
                    language: {
                        emptyTable: 'No terminal cancellation requests found in this tab.',
                    },
                });
            }
        },
        clearDataTableSettings(table) {
            const settings = $.fn.dataTableSettings || [];
            for (let i = settings.length - 1; i >= 0; i--) {
                if (settings[i].nTable === table || settings[i].sTableId === table.id) {
                    settings.splice(i, 1);
                }
            }
        },
        isApiSuccess(data) {
            return data && (data.status === true || data.status === 'success');
        },
        formatSeats(seats) {
            return Array.isArray(seats) && seats.length ? seats.join(', ') : 'N/A';
        },
        formatFare(fare) {
            if (fare === null || fare === undefined || fare === 'N/A') {
                return 'N/A';
            }

            return Number(fare);
        },
        formatPercentage(percentage) {
            if (percentage === null || percentage === undefined || percentage === 'N/A') {
                return 'N/A';
            }

            return `${Number(percentage)}%`;
        },
        calculatePercentAmount(amount, percentage) {
            return Math.round(((Number(amount) || 0) * (Number(percentage) || 0) / 100) * 100) / 100;
        },
        timeDifference(request) {
            if (!request.bus_date || !request.bus_time || !request.request_date) {
                return 'N/A';
            }

            const differenceMinutes = this.timeDifferenceMinutes(request);
            if (differenceMinutes === null) {
                return 'N/A';
            }

            let seconds = differenceMinutes * 60;
            const prefix = seconds < 0 ? '-' : '';
            seconds = Math.abs(seconds);
            const days = Math.floor(seconds / 86400);
            seconds %= 86400;
            const hours = Math.floor(seconds / 3600);
            seconds %= 3600;
            const minutes = Math.floor(seconds / 60);
            const parts = [];

            if (days) {
                parts.push(`${days} day${days > 1 ? 's' : ''}`);
            }
            if (hours) {
                parts.push(`${hours}h`);
            }
            if (minutes || !parts.length) {
                parts.push(`${minutes}m`);
            }

            return prefix + parts.join(' ');
        },
        timeDifferenceMinutes(request) {
            if (!request.bus_date || !request.bus_time || !request.request_date) {
                return null;
            }

            const busDateTime = new Date(`${request.bus_date} ${request.bus_time}`);
            const requestDateTime = new Date(request.request_date);
            if (Number.isNaN(busDateTime.getTime()) || Number.isNaN(requestDateTime.getTime())) {
                return null;
            }

            return Math.floor((busDateTime.getTime() - requestDateTime.getTime()) / 60000);
        },
        timeDifferenceRowClass(request) {
            const minutes = this.timeDifferenceMinutes(request);
            if (minutes === null) {
                return '';
            }

            if (minutes < 180) {
                return 'time-difference-red';
            }

            if (minutes < 360) {
                return 'time-difference-yellow';
            }

            if (minutes < 1440) {
                return 'time-difference-green';
            }

            return 'time-difference-white';
        },
        statusLabel(status) {
            if (!status) {
                return 'Pending';
            }

            return String(status)
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase());
        },
        statusClass(status) {
            switch (status) {
                case 'pending':
                    return 'badge badge-warning';
                case 'approved':
                    return 'badge badge-info';
                case 'cancelled':
                    return 'badge badge-success';
                case 'rejected':
                    return 'badge badge-danger';
                case 'failed':
                    return 'badge badge-dark';
                default:
                    return 'badge badge-secondary';
            }
        },
        canUseButton(buttonName) {
            for (let i = 0; i < this.permissions.length; i++) {
                if (!Array.isArray(this.permissions[i].childs)) {
                    continue;
                }

                const submenu = this.permissions[i].childs.find((item) => item.name === 'online-terminal-cancellation');
                if (!submenu || !Array.isArray(submenu.buttons)) {
                    continue;
                }

                const button = submenu.buttons.find((item) => item.name === buttonName);
                return button ? button.allow : false;
            }

            return false;
        },
    },
};
</script>

<style scoped>
.cancellation-filters { margin-bottom: 20px; border: 1px solid #e3e8f0; border-radius: 12px; background: #f8fafc; }
.cancellation-filter-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 20px; border-bottom: 1px solid #e3e8f0; }
.cancellation-filter-heading { display: flex; align-items: center; gap: 12px; }
.cancellation-filter-icon { display: flex; align-items: center; justify-content: center; flex: 0 0 36px; height: 36px; border-radius: 10px; background: #eef2ff; color: #6366f1; }
.cancellation-filter-heading h5 { margin: 0 0 4px; color: #34395e; font-size: 15px; font-weight: 600; }
.cancellation-filter-heading p, .cancellation-filter-hint { margin: 0; color: #64748b; font-size: 12px; line-height: 1.5; }
.cancellation-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px 20px; padding: 20px; }
.cancellation-filter-field { min-width: 0; position: relative; }
.cancellation-filter-field label { display: block; margin-bottom: 7px; color: #475569; font-size: 12px; font-weight: 600; letter-spacing: 0; }
.cancellation-filter-field .form-control { height: 42px; min-width: 0; border: 1px solid #dce2eb; border-radius: 7px; background: #fff; font-size: 13px; padding: 9px 12px; }
.cancellation-filter-field .form-control:focus { border-color: #818cf8; box-shadow: 0 0 0 3px rgba(99, 102, 241, .1); }
.cancellation-filter-field :deep(.select2-container) { width: 100% !important; min-width: 0; }
.cancellation-filter-field :deep(.select2-selection--single) { height: 42px; border: 1px solid #dce2eb; border-radius: 7px; background: #fff; }
.cancellation-filter-field :deep(.select2-selection__rendered) { line-height: 40px !important; padding-left: 12px !important; padding-right: 30px !important; color: #475569; font-size: 13px; }
.cancellation-filter-field :deep(.select2-selection__arrow) { height: 40px !important; right: 8px !important; }
.cancellation-filter-field :deep(.select2-container--focus .select2-selection), .cancellation-filter-field :deep(.select2-container--open .select2-selection) { border-color: #818cf8; box-shadow: 0 0 0 3px rgba(99, 102, 241, .1); }
.cancellation-filter-field :deep(.select2-container--disabled .select2-selection) { background: #edf1f6; }
.cancellation-filter-field :deep(.select2-dropdown), .cancellation-page-size :deep(.select2-dropdown) { border-color: #dce2eb; box-shadow: 0 6px 18px rgba(15, 23, 42, .08); font-size: 13px; }
.cancellation-filter-field :deep(.select2-search__field) { border-radius: 5px; }
.cancellation-filter-actions { display: flex; align-items: flex-end; gap: 8px; }
.cancellation-filter-actions .btn { height: 42px; white-space: nowrap; border-radius: 7px; box-shadow: none; }
.cancellation-filter-actions .btn-primary { flex: 1; }
.cancellation-filter-actions .btn i { margin-right: 5px; }
.cancellation-page-size { display: flex; align-items: center; gap: 10px; font-size: 12px; position: relative; }
.cancellation-page-size :deep(.select2-container) { width: 80px !important; }
.cancellation-page-size :deep(.select2-selection--single) { height: 34px; border-color: #dce2eb; border-radius: 6px; }
.cancellation-page-size :deep(.select2-selection__rendered) { line-height: 32px !important; }
.cancellation-page-size :deep(.select2-selection__arrow) { height: 32px !important; }
@media (max-width: 1199px) { .cancellation-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575px) {
    .cancellation-filter-header { align-items: flex-start; padding: 16px; }
    .cancellation-filter-hint { display: none; }
    .cancellation-filter-grid { grid-template-columns: minmax(0, 1fr); padding: 16px; gap: 14px; }
    .cancellation-tabs { flex-wrap: wrap; }
}
.cancellation-tabs { display: flex; gap: 12px; border-bottom: 1px solid #e9edf5; padding-bottom: 14px; }
.cancellation-tab { display: inline-flex; align-items: center; gap: 12px; border: 1px solid #e3e8f0; border-radius: 8px; padding: 12px 22px; background: #fff; color: #64748b; font-weight: 600; }
.cancellation-tab.active { background: #eef2ff; color: #4f46e5; border-color: #a5b4fc; }
.cancellation-count { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px; padding: 0 7px; border-radius: 50%; background: #34395e; color: white; font-size: 12px; }
.cancellation-tab:focus-visible { outline: 2px solid #6366f1; outline-offset: 3px; }
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
}

/* #online_terminal_cancellation_table tr.time-difference-red > td {
    background-color: #DC3545;
}

#online_terminal_cancellation_table tr.time-difference-yellow > td {
    background-color: #FFC107;
}

#online_terminal_cancellation_table tr.time-difference-green > td {
    background-color: #28A745;
}

#online_terminal_cancellation_table tr.time-difference-white > td {
    background-color:transparent;
} */
</style>
