<template>
    <section class="section">
        <div class="section-body">
            <div class="card card-primary">
                <div class="card-header">
                    <h4>Dock Requests</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="fault_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bus</th>
                                <th>Driver</th>
                                <th>Status</th>
                                <th>Last Dock Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in faults" :key="i">
                                <td>{{ i + 1 }}</td>
                                <td>{{ item.bus?.bus_number || 'N/A' }}</td>
                                <td>{{ item.driver?.name || 'N/A' }}</td>
                                <td>
                                    <span :class="getStatusClass(item.status)">{{ item.status }}</span>
                                </td>
                                <td>{{ item.dock_requests ? item.dock_requests[item.dock_requests.length -1].dock_time : 'N/A' }}</td>
                                <td>
                                    <button class="btn btn-info btn-sm" @click="viewDetails(item)" v-if="checkForSubmenuButtons('view-request')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Dock Modal -->
            <div class="modal fade" id="viewDockModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark p-3 text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-tools mr-2"></i> Fault Claim Details
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                                >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">
                                        <span class="text-dark">Bus:</span>
                                        <strong>{{ selectedFault?.bus?.bus_number || 'N/A' }}</strong> |
                                        <span class="text-dark">Driver:</span>
                                        <strong>{{ selectedFault?.driver?.name || 'N/A' }}</strong>
                                    </h5>

                                    <div v-if="selectedFault?.dock_requests?.length > 0">
                                        <div class="row">
                                            <div class="col-md-12 mb-3" v-for="(dock, i) in selectedFault.dock_requests"
                                                :key="i">
                                                <div class="card border shadow-md">
                                                    <div class="card-body py-2 px-3" style="font-size: 15px;">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-2 rounded mb-2 bg-primary text-white">
                                                            <h6 class="font-weight-bold mb-0">Dock Request #{{ i + 1 }}
                                                            </h6>
                                                            <h6>Dock Start Time: {{dock.dock_start_time ? formatDateTime(dock.dock_start_time) : 'Not Assigned' }}</h6>
                                                            <span :class="getStatusClass(dock.status)">{{ dock.status
                                                                }}</span>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Dock Time:</strong> {{
                                                                dock.dock_time || 'N/A' }}
                                                            </div>
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Priority:</strong> {{
                                                                dock.periority || 'N/A' }}
                                                            </div>
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Approved By:</strong> {{
                                                                dock.approved?.name || 'N/A' }}
                                                            </div>
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Approved At:</strong> {{
                                                                formatDateTime(dock.approved_at) || 'N/A' }}
                                                            </div>
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Description:</strong><br>
                                                                {{ dock.description || 'N/A' }}
                                                            </div>
                                                            <div class="col-md-6 mt-2">
                                                                <strong class="text-dark">Approval Comments:</strong><br>
                                                                {{ dock.comments || 'N/A' }}
                                                            </div>

                                                            <!-- Approve Button -->
                                                            <div class="col-md-12 mt-3 text-right"
                                                                v-if="dock.status === 'pending' && approvingId !== dock.id">
                                                                <button class="btn btn-success btn-sm"
                                                                    @click="startApproval(dock)">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </div>

                                                            <!-- Approval Form -->
                                                            <div class="col-md-12 mt-3" v-if="approvingId === dock.id">
                                                                <div class="p-3 rounded bg-light border">
                                                                    <div class="form-row">
                                                                        

                                                                        <div class="form-group col-md-12">
                                                                            <label><strong>Dock Time <span
                                                                                        class="text-danger">*</span></strong></label>
                                                                            <input type="datetime-local"
                                                                                v-model="dockTime"
                                                                                class="form-control" />
                                                                        </div>
                                                                        <div class="form-group col-md-12">
                                                                            <label><strong>Approval Comment <span
                                                                                        class="text-danger">*</span></strong></label>
                                                                            <textarea v-model="approvalComment"
                                                                                class="form-control" rows="3"
                                                                                placeholder="Enter comment..."></textarea>
                                                                        </div>
                                                                    </div>

                                                                    <div class="d-flex justify-content-end">
                                                                        <button class="btn btn-secondary btn-sm mr-2"
                                                                            @click="resetApproval()">Cancel</button>
                                                                        <button class="btn btn-success btn-sm"
                                                                            @click="approveDock(dock)" v-if="checkForSubmenuButtons('approve-request')">
                                                                            <i class="fas fa-check"></i> Confirm
                                                                            Approval
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                                        <p>No dock requests found for this fault.</p>
                                    </div>

                                    <div class="card shadow border-0 mb-4" v-if="inspection">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-clipboard-check mr-2"></i> Inspection Result
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- Bus and Driver Info at Top -->
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Bus Number:</strong> {{ inspection.bus?.bus_number || 'N/A'
                                                    }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Driver Name:</strong> {{ inspection.driver?.name || 'N/A' }}
                                                </div>
                                            </div>

                                            <!-- Info Box (light background) -->
                                            <div class="bg-light rounded p-3 mb-3">
                                                <!-- Status / Repair / Date -->
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <strong>Status:</strong>
                                                        <span class="badge badge-info text-uppercase">{{
                                                            inspection.status || 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Repair Type:</strong>
                                                        {{ inspection.repair_type ? inspection.repair_type.replace('_',
                                                            ' ') : 'N/A' }}
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Entry Date:</strong>
                                                        {{ formatDateTime(inspection.created_at) || 'N/A' }}
                                                    </div>
                                                </div>

                                                <!-- Mechanic / Vendor / Bill -->
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <strong>Mechanic Name:</strong> {{ inspection.machanic_name ||
                                                            'N/A' }}
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Vendor:</strong> {{ inspection.vendor?.name || 'N/A' }}
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Bill Amount:</strong>
                                                        {{ inspection.amount ?
                                                            parseFloat(inspection.amount).toLocaleString() : 'N/A' }}
                                                    </div>
                                                </div>

                                                <!-- Reading / Maintenance -->
                                                <div class="row mb-0">
                                                    <div class="col-md-4">
                                                        <strong>Current Reading:</strong>
                                                        {{ inspection.current_reading ? inspection.current_reading +
                                                        'KM' : 'N/A' }}
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Maintenance Date:</strong> {{
                                                            inspection.maintenance_date || 'N/A' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Parts -->
                                            <div class="mb-3">
                                                <strong>Parts Used:</strong>
                                                <ul class="list-group list-group-sm mt-2"
                                                    v-if="inspection.parts && inspection.parts.length">
                                                    <li v-for="part in inspection.parts" :key="part.id"
                                                        class="list-group-item py-1 px-3">
                                                        {{ part.part?.name || 'N/A' }}
                                                    </li>
                                                </ul>
                                                <div v-else class="text-muted">No parts used</div>
                                            </div>

                                            <!-- Dock Info -->
                                            <!-- Last Dock Request Header -->
                                            <div
                                                class="px-3 py-2 mb-2 d-flex align-items-center">
                                                <i class="fas fa-tools mr-2"></i>
                                                <strong class="text-dark text-uppercase mb-0">Last Dock Request</strong>
                                            </div>

                                            <!-- Last Dock Request Content -->
                                            <div class="row mb-3" v-if="inspection.dock_request">
                                                <div class="col-md-4">
                                                    <strong>Dock Time:</strong>
                                                    {{ inspection.dock_request.dock_time
                                                        ? inspection.dock_request.dock_time.replace(':', 'h ') + 'm'
                                                    : 'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Priority:</strong> {{ inspection.dock_request.periority ||
                                                    'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Dock Description:</strong> {{
                                                    inspection.dock_request.description || 'N/A' }}
                                                </div>
                                            </div>


                                            <!-- Comments -->
                                            <div class="mt-3">
                                                <strong>Comments:</strong>
                                                <div class="border rounded p-2 bg-light">
                                                    {{ inspection.comments || 'No comments provided.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- End View Dock Modal -->
        </div>
    </section>
</template>

<script>
export default {
    name: "dock-request",
    data() {
        return {
            faults: [],
            inspection: [],
            selectedFault: null,
            permissions: [],
            approvingId: null,
            approvalComment: '',
            dockTime: '', // Renamed from approvalDateTime
        };
    },
    async created() {
        await this.fetchData();
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        async fetchData() {
            const res = await this.callApi('post', 'fleet/dock-requests');
            if (res.status === 200) {
                this.faults = res.data.faults;
                setTimeout(() => {
                    $('#fault_table').DataTable();
                }, 300);
            }
        },
        async viewDetails(item) {
            this.selectedFault = null;
            const res = await this.callApi('post', 'fleet/dock-requests/show', { id: item.id });
            if (res.status === 200 && res.data) {
                this.selectedFault = res.data.fault;
                this.inspection = res.data.inspection;

                // Reset approval comments for this modal
                this.approvalComments = {};
                this.$nextTick(() => $('#viewDockModal').modal('show'));
            } else {
                swal("Error", "Failed to load fault details.", "error");
            }
        },
        getStatusClass(status) {
            switch (status) {
                case 'pending': return 'badge badge-warning';
                case 'resolved': return 'badge badge-success';
                case 'dock time': return 'badge badge-secondary';
                default: return 'badge badge-dark';
            }
        },
        async approveDock(dock) {
            // Basic validation
            if (!this.dockTime) {
                swal("Validation Error", "Please select a valid dock time.", "warning");
                return;
            }

            if (!this.approvalComment || this.approvalComment.trim() === '') {
                swal("Validation Error", "Please enter a comment before approval.", "warning");
                return;
            }


            this.approvingId = dock.id;

            const res = await this.callApi('post', 'fleet/dock-requests/approve', {
                id: dock.id,
                comment: this.approvalComment,
                dock_time: this.dockTime,
            });

            if (res.status === 200) {
                swal("Approved!", "Dock request approved successfully.", "success");

                // Refresh modal
                const detailRes = await this.callApi('post', 'fleet/dock-requests/show', { id: this.selectedFault.id });
                if (detailRes.status === 200 && detailRes.data) {
                    this.selectedFault = detailRes.data.fault;
                    this.inspection = detailRes.data.inspection;
                }

                // Refresh table
                await this.fetchData();
            } else {
                swal("Error", "Failed to approve dock request.", "error");
            }

            this.resetApproval();
        },
        startApproval(dock) {
            this.approvingId = dock.id;
            this.approvalComment = '';
            this.dockTime = ''; // default now
        },

        resetApproval() {
            this.approvingId = null;
            this.approvalComment = '';
            this.dockTime = '';
        },
        formatDateTime(dateTime) {
            if (!dateTime) return 'Not Assigned';

            const date = new Date(dateTime);

            const hours = date.getHours() % 12 || 12;
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = date.getHours() >= 12 ? 'PM' : 'AM';

            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0'); // months are 0-indexed
            const year = date.getFullYear();

            return `${hours}:${minutes} ${ampm} ${day}/${month}/${year}`;
        }
    }
};
</script>