<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Bookkaru Cancellation Requests</h4>
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
                                <table class="table table-bordered table-striped text-center"  id="bookkaru_table" v-else>
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Invoice</th>
                                            <th>Booking Reference</th>
                                            <th>Seats</th>
                                            <th>Reason</th>
                                            <th>Request Date</th>
                                            <th>Source</th>
                                            <th>Status</th>
                                            <th>Approved By</th>
                                            <th>Rejected By</th>
                                            <th>Cancelled At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="requests.length === 0">
                                            <td colspan="12" class="text-muted py-4">No Bookkaru cancellation requests found.</td>
                                        </tr>
                                        <tr v-for="request in requests" :key="request.id">
                                            <td>{{ request.request_id }}</td>
                                            <td>{{ request.invoice_id }}</td>
                                            <td>{{ request.booking_reference || 'N/A' }}</td>
                                            <td>{{ formatSeats(request.seat_numbers) }}</td>
                                            <td>{{ request.cancellation_reason || 'N/A' }}</td>
                                            <td>{{ request.request_date || 'N/A' }}</td>
                                            <td>{{ request.source || 'Bookkaru' }}</td>
                                            <td>
                                                <span :class="statusClass(request.status)">
                                                    {{ statusLabel(request.status) }}
                                                </span>
                                                <div class="small text-muted" v-if="request.cancellation_status">
                                                    {{ statusLabel(request.cancellation_status) }}
                                                </div>
                                            </td>
                                            <td>{{ request.approved_by || 'N/A' }}</td>
                                            <td>{{ request.rejected_by || 'N/A' }}</td>
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

            <div class="modal fade" id="bookkaruRejectModal" tabindex="-1" role="dialog"
                 aria-labelledby="bookkaruRejectModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bookkaruRejectModalLabel">Reject Bookkaru Request</h5>
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
    name: "BookkaruCancellationRequestsPage",
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
            this.loading = true;
            const res = await this.callApi('post', 'bookkaru/cancellations/requests', this.filters);
            if (res.status === 200 && res.data.status === true) {
                this.requests = res.data.data.requests;
            }
            this.loading = false;
             setTimeout(function () {
                $("#bookkaru_table").DataTable();
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
            const res = await this.callApi('post', 'bookkaru/cancellations/approve', { id: request.id });
            this.actionLoadingId = null;

            if (res.status === 200 && res.data.status === true) {
                swal("Approved", res.data.message, "success");
                $("#bookkaru_table").DataTable().destroy();
                this.fetchRequests();
            } else {
                swal("Error", res.data.message || "Approval failed.", "error");
            }
        },
        openRejectModal(request) {
            this.selectedRequest = request;
            this.rejectionReason = '';
            $('#bookkaruRejectModal').modal('show');
        },
        async rejectRequest() {
            if (!this.rejectionReason.trim()) {
                swal("Error", "Rejection reason is required.", "error");
                return;
            }

            this.rejectLoading = true;
            const res = await this.callApi('post', 'bookkaru/cancellations/reject', {
                id: this.selectedRequest.id,
                rejection_reason: this.rejectionReason,
            });
            this.rejectLoading = false;

            if (res.status === 200 && res.data.status === true) {
                $('#bookkaruRejectModal').modal('hide');
                swal("Rejected", res.data.message, "success");
                 $("#bookkaru_table").DataTable().destroy();
                this.fetchRequests();
            } else {
                swal("Error", res.data.message || "Rejection failed.", "error");
            }
        },
        formatSeats(seats) {
            return Array.isArray(seats) ? seats.join(', ') : 'N/A';
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

                const submenu = this.permissions[i].childs.find((item) => item.name === 'bookkaru-cancellation');
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
