<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Online Terminal Cancellation Requests</h4>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end mb-3">
                                <div class="col-md-3 mb-2">
                                    <label>Request ID</label>
                                    <input type="text" class="form-control" v-model="filters.request_id">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Invoice ID</label>
                                    <input type="text" class="form-control" v-model="filters.invoice_id">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Booking Reference</label>
                                    <input type="text" class="form-control" v-model="filters.booking_reference">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Status</label>
                                    <select class="form-control" v-model="filters.status">
                                        <option value="">All</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="failed">Failed</option>
                                        <option value="unauthorized">Unauthorized</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button class="btn btn-primary mr-2" @click="fetchRequests()">Search</button>
                                    <button class="btn btn-secondary" @click="resetFilters()">Reset</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <div v-if="loading" class="text-center py-4">
                                    <img class="loading-spinner"
                                         :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                </div>
                                <table class="table table-bordered table-striped text-center" id="online_terminal_cancellation_table" v-else>
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
                                            <th>Booking Time</th>
                                            <th>Request Date</th>
                                            <th>Time Difference</th>
                                            <th>Source</th>
                                            <th>Status</th>
                                            <th>Decision By</th>
                                            <th>Cancelled At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="requests.length === 0">
                                            <td colspan="19" class="text-muted py-4">No online terminal cancellation requests found.</td>
                                        </tr>
                                        <tr v-for="request in requests" :key="request.id">
                                            <td>{{ request.invoice_id }}</td>
                                            <td>{{ request.booking_reference || 'N/A' }}</td>
                                            <td>{{ request.route || 'N/A' }}</td>
                                            <td>{{ formatSeats(request.seat_numbers) }}</td>
                                            <td>{{ request.cancellation_reason || 'N/A' }}</td>
                                            <td>{{ request.bus_date || 'N/A' }}</td>
                                            <td>{{ request.bus_time || 'N/A' }}</td>
                                            <td>{{ request.passenger_name || 'N/A' }}</td>
                                            <td>{{ request.cnic || 'N/A' }}</td>
                                            <td>{{ request.contact || 'N/A' }}</td>
                                            <td>{{ formatFare(request.fare) }}</td>
                                            <td>{{ request.booking_time || 'N/A' }}</td>
                                            <td>{{ request.request_date || 'N/A' }}</td>
                                            <td>{{ request.time_difference || timeDifference(request) }}</td>
                                            <td>{{ request.source || 'Online Terminal' }}</td>
                                            <td>
                                                <span :class="statusClass(request.status)">
                                                    {{ statusLabel(request.status) }}
                                                </span>
                                                <div class="small text-muted" v-if="request.cancellation_status">
                                                    {{ statusLabel(request.cancellation_status) }}
                                                </div>
                                            </td>
                                            <td>{{ request.decision_by || 'N/A' }}</td>
                                            <td>{{ request.cancelled_at || 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group" v-if="request.status === 'pending'">
                                                    <button class="btn btn-success btn-sm"
                                                            v-if="canUseButton('approve-request')"
                                                            :disabled="actionLoadingId === request.id"
                                                            @click="approveRequest(request)">
                                                        Approve
                                                    </button>
                                                    <button class="btn btn-danger btn-sm"
                                                            v-if="canUseButton('reject-request')"
                                                            :disabled="actionLoadingId === request.id"
                                                            @click="openRejectModal(request)">
                                                        Reject
                                                    </button>
                                                </div>
                                                <span class="text-muted" v-else>No action</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="onlineTerminalRejectModal" tabindex="-1" role="dialog"
                 aria-labelledby="onlineTerminalRejectModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="onlineTerminalRejectModalLabel">Reject Online Terminal Request</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label>Rejection Reason</label>
                            <textarea class="form-control" rows="4" v-model="rejectionReason"
                                      placeholder="Enter rejection reason"></textarea>
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
export default {
    name: "OnlineTerminalCancellationRequestsPage",
    data() {
        return {
            permissions: [],
            requests: [],
            loading: false,
            actionLoadingId: null,
            rejectLoading: false,
            selectedRequest: null,
            rejectionReason: '',
            filters: {
                request_id: '',
                invoice_id: '',
                booking_reference: '',
                status: '',
            },
        };
    },
    created() {
        this.permissions = this.$store.state.permissions;
        this.fetchRequests();
    },
    methods: {
        async fetchRequests() {
            this.destroyDataTable();
            this.loading = true;
            const res = await this.callApi('post', 'online-terminals/cancellations/requests', this.filters);
            if (res.status === 200 && res.data.status === true) {
                this.requests = res.data.data.requests;
            }
            this.loading = false;
            setTimeout(() => {
                $("#online_terminal_cancellation_table").DataTable();
            }, 300);
        },
        resetFilters() {
            this.filters = {
                request_id: '',
                invoice_id: '',
                booking_reference: '',
                status: '',
            };
            this.fetchRequests();
        },
        async approveRequest(request) {
            const confirmed = await swal({
                title: "Approve cancellation?",
                text: "This will cancel the requested seat(s).",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            });

            if (!confirmed) {
                return;
            }

            this.actionLoadingId = request.id;
            const res = await this.callApi('post', 'online-terminals/cancellations/approve', { id: request.id });
            this.actionLoadingId = null;

            if (res.status === 200 && this.isApiSuccess(res.data)) {
                swal("Approved", res.data.message, "success");
                this.fetchRequests();
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
            } else {
                swal("Error", res.data.message || "Rejection failed.", "error");
            }
        },
        destroyDataTable() {
            if ($.fn.DataTable.isDataTable("#online_terminal_cancellation_table")) {
                $("#online_terminal_cancellation_table").DataTable().destroy();
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
        timeDifference(request) {
            if (!request.bus_date || !request.bus_time || !request.request_date) {
                return 'N/A';
            }

            const busDateTime = new Date(`${request.bus_date} ${request.bus_time}`);
            const requestDateTime = new Date(request.request_date);
            if (Number.isNaN(busDateTime.getTime()) || Number.isNaN(requestDateTime.getTime())) {
                return 'N/A';
            }

            let seconds = Math.floor((busDateTime.getTime() - requestDateTime.getTime()) / 1000);
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
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
}
</style>
