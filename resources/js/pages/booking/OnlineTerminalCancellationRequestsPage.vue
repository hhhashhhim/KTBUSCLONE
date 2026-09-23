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
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cnicFilter">CNIC</label>
                                        <vue-mask id="cnicFilter" class="form-control"
                                                  v-model="filters.cnicFilter" mask="00000-0000000-0"
                                                  @keyup="fetchRequests()" :raw="false"
                                                  :options="options">
                                        </vue-mask>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="phoneFilter">Cell #</label>
                                        <vue-mask id="phoneFilter" class="form-control"
                                                  v-model="filters.phoneFilter" mask="0000-0000000"
                                                  :raw="false" @keyup="fetchRequests()"
                                                  :options="optionsContact">
                                        </vue-mask>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="nameFilter">Name</label>
                                        <input id="nameFilter" type="text" class="form-control"
                                               v-model="filters.nameFilter"
                                               @keyup="fetchRequests()">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="invoiceFilter">Invoice</label>
                                        <input id="invoiceFilter" type="text" class="form-control"
                                               v-model="filters.invoiceFilter"
                                               @keyup="fetchRequests()">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="routeFilter">Route</label>
                                        <select id="routeFilter" class="form-control"
                                                v-model="filters.routeFilter"
                                                @change="fetchRequests()">
                                            <option value="">---Select Route---</option>
                                            <option v-for="(route, i) in routes" :key="i" :value="route.id">
                                                {{ route.name }}  ({{ route.via ?? 'n/a' }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="terminalFilter">Terminals</label>
                                        <select id="terminalFilter" class="form-control"
                                                v-model="filters.terminalFilter"
                                                @change="fetchRequests()">
                                            <option value="">---Select Terminal---</option>
                                            <option v-for="(terminal, i) in terminals" :key="i" :value="terminal.id">
                                                {{ terminal.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="busFilter">Bus #</label>
                                        <select id="busFilter" class="form-control"
                                                v-model="filters.busFilter"
                                                @change="fetchRequests()">
                                            <option value="">---Select Bus #---</option>
                                            <option v-for="(bus, i) in buses" :key="i" :value="bus.id">
                                                {{ bus.bus_number }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="statusFilter">Status</label>
                                        <select id="statusFilter" class="form-control"
                                                v-model="filters.statusFilter"
                                                @change="fetchRequests()" :disabled="activeTab === 'pending'">
                                            <option value="">{{ activeTab === 'pending' ? 'Pending' : 'All completed statuses' }}</option>
                                            <option v-if="activeTab === 'completed'" value="cancelled">Cancelled</option>
                                            <option v-if="activeTab === 'completed'" value="approved">Approved</option>
                                            <option v-if="activeTab === 'completed'" value="rejected">Rejected</option>
                                            <option v-if="activeTab === 'completed'" value="failed">Failed</option>
                                            <option v-if="activeTab === 'completed'" value="unauthorized">Unauthorized</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fromDateFilter">Departure Date From</label>
                                        <input type="date" class="form-control" id="fromDateFilter"
                                               v-model="filters.fromDateFilter"
                                               @change="fetchRequests()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="toDateFilter">Departure Date To</label>
                                        <input type="date" class="form-control" id="toDateFilter"
                                               v-model="filters.toDateFilter"
                                               @change="fetchRequests()">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="transactionFilter">Transaction #</label>
                                        <input id="transactionFilter" type="text" class="form-control"
                                               v-model="filters.transactionFilter"
                                               @keyup="fetchRequests()">
                                    </div>
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
                            <div class="d-flex align-items-center justify-content-between mt-3" v-if="pagination.last_page > 1">
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
            routes: [],
            terminals: [],
            buses: [],
            loading: false,
            requestSequence: 0,
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
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();
        this.fetchRequests();
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
        selectTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.filters.statusFilter = '';
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
            }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "online-terminals/cancellations/terminals");
            if (resTerminal.status === 200) {
                this.terminals = resTerminal.data;
            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "online-terminals/cancellations/buses");
            if (resBuses.status === 200) {
                this.buses = resBuses.data;
            }
        },
        async fetchRequests(page = 1) {
            const sequence = ++this.requestSequence;
            this.destroyDataTable();
            this.loading = true;
            const res = await this.callApi('post', 'online-terminals/cancellations/requests', {
                ...this.filters, tab: this.activeTab, page,
            });
            if (sequence !== this.requestSequence) {
                return;
            }
            if (res.status === 200 && res.data.status === true) {
                this.requests = res.data.data.requests;
                this.counts = res.data.data.counts || { pending: 0, completed: 0 };
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
