<template>
    <section class="section">
        <div class="section-body">
            <div class="card card-primary">
                <div class="card-header">
                    <h4>Fault Claims</h4>
                    <div class="card-header-action">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#faultModal"
                            @click="clearForm" v-if="checkForSubmenuButtons('initiate-request')">
                            Initiate Request
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="fault_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bus</th>
                                <th>Driver</th>
                                <th>Status</th>
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
                                <td>
                                    <button class="btn btn-info btn-sm" @click="viewDetails(item)" v-if="checkForSubmenuButtons('view-claim')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button v-if="item.status === 'dock time' && checkForSubmenuButtons('add-result')" class="btn btn-success btn-sm ml-2"
                                        @click="openDockModal(item)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Fault Modal -->
            <div class="modal fade" id="faultModal" tabindex="-1" role="dialog" aria-labelledby="faultModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="faultModalLabel">Add Fault Claim</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click=closeModal()>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Bus <span class="text-danger">*</span></label>
                                    <select class="form-control" ref="busSelect" v-model="data.bus_id">
                                        <option value="" disabled>Select Bus</option>
                                        <option v-for="bus in buses" :key="bus.id" :value="bus.id">{{ bus.bus_number }}
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label>Driver <span class="text-danger">*</span></label>
                                    <select class="form-control" ref="driverSelect" v-model="data.driver_id">
                                        <option value="" disabled>Select Driver</option>
                                        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{
                                            driver.name }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Dock Time (Duration) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" min="0" v-model="data.dock_hours"
                                            placeholder="Hours">
                                        <span class="input-group-text">h</span>
                                        <input type="number" class="form-control" min="0" max="59"
                                            v-model="data.dock_minutes" placeholder="Minutes">
                                        <span class="input-group-text">m</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Priority <span class="text-danger">*</span></label>
                                    <select class="form-control" v-model="data.periority">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-12">
                                    <label>Description <span class="text-danger">*</span></label>
                                    <textarea v-model="data.description" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                                {{ loading ? 'Saving...' : 'Save' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                @click="closeModal()">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Add Modal -->

            <div class="modal fade" id="viewDockModal" tabindex="-1" role="dialog" aria-labelledby="viewDockModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark p-3 text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-tools mr-2"></i> Fault Claim Details
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                                @click="selectedFault = null">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">
                                        <span class="text-dark">Bus:</span> <strong>{{ selectedFault?.bus?.bus_number ||
                                            'N/A' }}</strong> |
                                        <span class="text-dark">Driver:</span> <strong>{{ selectedFault?.driver?.name ||
                                            'N/A' }}</strong>
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
                                                            <h6>Dock Start Time: {{ dock.dock_start_time ?
                                                                formatDateTime(dock.dock_start_time) : 'Not Assigned' }}
                                                            </h6>
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
                                                                    dock.approved_by || 'N/A' }}
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
                                                                <strong class="text-dark">Approval
                                                                    Comments:</strong><br>
                                                                {{ dock.comments || 'N/A' }}
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

            <!-- Add Dock Modal -->
            <div class="modal fade" id="addDockModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary p-3 text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-clipboard-check mr-2"></i> Inspection Result
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                                @click="resetResultForm()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="row">
                                <!-- Bus and Driver Info -->
                                <div class="col-md-6 mb-3">
                                    <strong>Bus Number:</strong> {{ result.bus_number || 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Driver Name:</strong> {{ result.driver_name || 'N/A' }}
                                </div>

                                <!-- Main Status Selection -->
                                <div class="col-md-12 mb-3">
                                    <label><strong>Status <span class="text-danger">*</span></strong></label>
                                    <div>
                                        <label><input type="radio" v-model="result.status" value="no_fault" /> No
                                            Fault</label>
                                        <label class="ml-3"><input type="radio" v-model="result.status"
                                                value="resolved" /> Resolved</label>
                                        <label class="ml-3"><input type="radio" v-model="result.status"
                                                value="dock_required" /> Dock Required</label>
                                    </div>
                                </div>

                                <!-- No Fault Fields -->
                                <div class="col-md-6" v-if="result.status === 'no_fault'">
                                    <div class="form-group">
                                        <label>Mechanic Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="result.mechanic_name" />
                                    </div>
                                </div>

                                <!-- Resolved Repair Type Selection -->
                                <div class="col-md-12 mb-3" v-if="result.status === 'resolved'">
                                    <label><strong>Repair Type <span class="text-danger">*</span></strong></label>
                                    <div>
                                        <label><input type="radio" v-model="result.repair_type" value="in_house" /> In
                                            House</label>
                                        <label class="ml-3"><input type="radio" v-model="result.repair_type"
                                                value="outsource" /> Outsource</label>
                                        <label class="ml-3"><input type="radio" v-model="result.repair_type"
                                                value="hybrid" /> Hybrid</label>
                                    </div>
                                </div>

                                <!-- Shared Fields (Resolved: all types) -->
                                <div class="col-md-6" v-if="result.status === 'resolved' && result.repair_type">
                                    <div class="form-group">
                                        <label>Parts</label>
                                        <select class="form-control" multiple v-model="result.parts" ref="partsSelect">
                                            <option v-for="part in parts" :key="part.id" :value="part.id">{{ part.name
                                                }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6" v-if="result.status === 'resolved' && result.repair_type">
                                    <div class="form-group">
                                        <label>Current Reading <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" v-model="result.reading" />
                                    </div>
                                </div>

                                <div class="col-md-6" v-if="result.status === 'resolved' && result.repair_type">
                                    <div class="form-group">
                                        <label>Maintenance Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" v-model="result.maintenance_date" />
                                    </div>
                                </div>

                                <!-- Mechanic Name (In House + Hybrid) -->
                                <div class="col-md-6"
                                    v-if="result.status === 'resolved' && ['in_house', 'hybrid'].includes(result.repair_type)">
                                    <div class="form-group">
                                        <label>Mechanic Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="result.mechanic_name" />
                                    </div>
                                </div>

                                <!-- Vendor/Bill (Outsource + Hybrid) -->
                                <div class="col-md-6"
                                    v-if="result.status === 'resolved' && ['outsource', 'hybrid'].includes(result.repair_type)">
                                    <div class="form-group">
                                        <label>Vendor <span class="text-danger">*</span></label>
                                        <select class="form-control" v-model="result.vendor_id" ref="vendorSelect">
                                            <option value="">Select Vendor</option>
                                            <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{
                                                vendor.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6"
                                    v-if="result.status === 'resolved' && ['outsource', 'hybrid'].includes(result.repair_type)">
                                    <div class="form-group">
                                        <label>Bill Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" v-model="result.bill_amount" />
                                    </div>
                                </div>

                                <!-- Dock Required Fields -->
                                <div class="col-md-6" v-if="result.status === 'dock_required'">
                                    <div class="form-group">
                                        <label>Dock Time <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" min="0"
                                                v-model="result.dock_hours" placeholder="Hours">
                                            <span class="input-group-text">h</span>
                                            <input type="number" class="form-control" min="0" max="59"
                                                v-model="result.dock_minutes" placeholder="Minutes">
                                            <span class="input-group-text">m</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6" v-if="result.status === 'dock_required'">
                                    <div class="form-group">
                                        <label>Priority <span class="text-danger">*</span></label>
                                        <select class="form-control" v-model="result.periority">
                                            <option value="">Select</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12" v-if="result.status === 'dock_required'">
                                    <div class="form-group">
                                        <label>Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" v-model="result.dock_description"></textarea>
                                    </div>
                                </div>

                                <!-- Comments Field Always At End (for all) -->
                                <div class="col-md-12" v-if="
                                    (result.status === 'no_fault') ||
                                    (result.status === 'resolved' && result.repair_type)
                                ">
                                    <div class="form-group">
                                        <label>Comments <span class="text-danger">*</span></label>
                                        <textarea class="form-control" v-model="result.comments" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="submitResult" :disabled="loading">
                                <i class="fas fa-paper-plane"></i> {{ loading ? 'Submitting...' : 'Submit' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </section>
</template>

<script>
import moment from 'moment';
export default {
    name: "fault-claims",
    data() {
        return {
            faults: [],
            buses: [],
            drivers: [],
            inspection: [],
            permissions: [],
            data: {
                bus_id: '',
                driver_id: '',
                description: '',
                dock_hours: '',
                dock_minutes: '',
                periority: 'low'
            },
            loading: false,
            selectedFault: null,
            result: {
                claim_id: '',
                status: '',              // no_fault / resolved / dock_required
                repair_type: '',         // in_house / outsource / hybrid

                parts: [],
                reading: '',
                maintenance_date: '',
                comments: '',

                mechanic_name: '',
                vendor_id: '',
                bill_amount: '',

                dock_hours: '',
                dock_minutes: '',
                dock_hours: '',
                dock_minutes: '',
                periority: '',
                dock_description: '',
            },
            resultDataReset: {},
            parts: [],
            vendors: []
        };
    },
    async created() {
        await this.fetchData();
        this.addDataReset = JSON.parse(JSON.stringify(this.data));
        this.resultDataReset = JSON.parse(JSON.stringify(this.result));
        this.permissions = this.$store.state.permissions;
    },
    watch: {
        'data.bus_id'(newVal) {
            this.$nextTick(() => {
                this.initSelect2();
            });
        },
        'data.driver_id'(newVal) {
            this.$nextTick(() => {
                this.initSelect2();
            });
        },
        'result.parts'(newVal) {
            this.$nextTick(() => {
                this.initInspectionSelect2();
            });
        },
        'result.vendor_id'(newVal) {
            this.$nextTick(() => {
                this.initInspectionSelect2();
            });
        },
        'result.status'(newVal) {
            this.$nextTick(() => {
                // Re-init when dock_required is selected
                if (newVal === 'dock_required') {
                    this.initInspectionSelect2();
                }
            });
            console.log(this.result);
        },
        'result.repair_type'(newVal) {
            this.$nextTick(() => {
                if (this.result.status === 'resolved') {
                    this.initInspectionSelect2();
                }
            });
            console.log(this.result);
        }
    },
    methods: {
        closeModal() {
            $(".modal").click();
        },
        async fetchData() {
            if ($.fn.DataTable.isDataTable("#fault_table")) {
                $("#fault_table").DataTable().destroy();
            }
            const res = await this.callApi('post', 'fleet/fault-claims');
            if (res.status === 200) {
                this.faults = res.data.faults;
                this.drivers = res.data.drivers;
                this.buses = res.data.buses;

                setTimeout(function () {
                    $("#fault_table").DataTable();
                }, 300);
                $('#faultModal').on('shown.bs.modal', () => {
                    this.$nextTick(() => {
                        this.initSelect2();
                    });
                });
            }
        },
        openDockModal(claim) {
            this.result = JSON.parse(JSON.stringify(this.resultDataReset));
            this.result.claim_id = claim.id;
            this.result.bus_number = claim.bus?.bus_number || 'N/A';
            this.result.driver_name = claim.driver?.name || 'N/A';
            this.callApi('post', 'fleet/inspection-result/data', { claim_id: claim.id }).then(res => {
                if (res.status === 200) {
                    this.parts = res.data.parts || [];
                    this.vendors = res.data.vendors || [];
                } else {
                    swal("Error", "Failed to fetch parts/vendors", "error");
                }
            });

            this.$nextTick(() => {
                $('#addDockModal').modal('show');
                this.initInspectionSelect2();
            });
        },
        async viewDetails(item) {
            this.selectedFault = null;

            // Make API call to get full fault with dock requests
            const res = await this.callApi('post', `fleet/fault-claims/show`, { id: item.id });

            if (res.status === 200 && res.data) {
                this.selectedFault = res.data.fault;
                this.inspection = res.data.inspection;
                this.$nextTick(() => {
                    $('#viewDockModal').modal('show');
                });
            } else {
                swal({
                    title: "Error",
                    text: "Failed to load fault details.",
                    icon: "error",
                    timer: 2000
                });
            }
        },
        async add() {
            if (!this.data.bus_id) {
                return swal({
                    title: "Required",
                    text: "Please select a Bus",
                    icon: "error",
                    timer: 2000
                });
            }

            if (!this.data.driver_id) {
                return swal({
                    title: "Required",
                    text: "Please select a Driver",
                    icon: "error",
                    timer: 2000
                });
            }

            if (!this.data.dock_hours && !this.data.dock_minutes) {
                return swal({
                    title: "Required",
                    text: "Please enter Dock Time (hours or minutes)",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.data.dock_minutes < 0 || this.data.dock_minutes > 59) {
                return swal({
                    title: "Invalid",
                    text: "Minutes must be between 0 and 59",
                    icon: "error",
                    timer: 2000
                });
            }

            if (!this.data.periority) {
                return swal({
                    title: "Required",
                    text: "Please select a Priority level",
                    icon: "error",
                    timer: 2000
                });
            }

            if (!this.data.description) {
                return swal({
                    title: "Required",
                    text: "Please enter a Description",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            // Format dock_time from hours and minutes
            const hours = this.data.dock_hours || 0;
            const minutes = this.data.dock_minutes || 0;
            const dock_time = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

            this.data = {
                ...this.data,
                dock_time
            };
            this.loading = true;
            const res = await this.callApi('post', 'fleet/fault-claims/store', this.data);
            if (res.status === 200) {
                swal({
                    title: "Success",
                    text: "Fault claim created successfully!",
                    icon: "success",
                    timer: 2000
                });

                this.$nextTick(() => $('#faultModal').modal('hide'));
                await this.fetchData();
                this.data = JSON.parse(JSON.stringify(this.addDataReset));
            }
            this.loading = false;
        },
        async submitResult() {
            this.loading = true;

            // Helper function for showing error
            const showError = (message) => {
                this.loading = false;
                return swal({
                    title: "Required",
                    text: message,
                    icon: "error",
                    timer: 2000
                });
            };

            // Validate status
            if (!this.result.status) return showError("Please select a Status");

            // No Fault
            if (this.result.status === 'no_fault') {
                if (!this.result.mechanic_name) return showError("Please enter Mechanic Name");
            }

            // Resolved
            if (this.result.status === 'resolved') {
                if (!this.result.repair_type) return showError("Please select a Repair Type");
                if (!this.result.reading) return showError("Please enter Current Reading");
                if (!this.result.maintenance_date) return showError("Please select Maintenance Date");

                if (['in_house', 'hybrid'].includes(this.result.repair_type)) {
                    if (!this.result.mechanic_name) return showError("Please enter Mechanic Name");
                }

                if (['outsource', 'hybrid'].includes(this.result.repair_type)) {
                    if (!this.result.vendor_id) return showError("Please select a Vendor");
                    if (!this.result.bill_amount) return showError("Please enter Bill Amount");
                }
            }

            // Dock Required
            if (this.result.status === 'dock_required') {
                if (!this.result.dock_hours && !this.result.dock_minutes) {
                    return showError("Please enter Dock Time");
                }
                if (!this.result.periority) return showError("Please select Priority");
                if (!this.result.dock_description) return showError("Please enter Description");
            }

            // Comments (for all)
            if (
                this.result.status === 'no_fault' ||
                (this.result.status === 'resolved' && this.result.repair_type)
            ) {
                if (!this.result.comments) return showError("Please enter Comments");
            }

            const hours = this.result.dock_hours || 0;
            const minutes = this.result.dock_minutes || 0;

            const dock_time = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

            // Add formatted dock_time to data payload
            this.result = {
                ...this.result,
                dock_time
            };

            // ✅ Submit data
            const res = await this.callApi('post', 'fleet/inspection-result/submit', this.result);
            if (res.status === 200) {
                swal({
                    title: "Success",
                    text: "Result Submited",
                    icon: "success",
                    timer: 2000
                });

                this.$nextTick(() => $('#addDockModal').modal('hide'));
                await this.fetchData();
                this.result = JSON.parse(JSON.stringify(this.resultDataReset));
            }
            this.loading = false;
        },
        getStatusClass(status) {
            switch (status) {
                case 'pending':
                    return 'badge badge-warning';
                case 'approved':
                    return 'badge badge-success';
                case 'rejected':
                    return 'badge badge-danger';
                default:
                    return 'badge badge-secondary';
            }
        },
        initSelect2() {
            const vm = this;
            setTimeout(() => {
                $(this.$refs.busSelect).select2({ dropdownParent: $('#faultModal') })
                    .off('change').on('change', function () {
                        vm.data.bus_id = $(this).val();
                    });

                $(this.$refs.driverSelect).select2({ dropdownParent: $('#faultModal') })
                    .off('change').on('change', function () {
                        vm.data.driver_id = $(this).val();
                    });
            }, 100);
        },
        initInspectionSelect2() {
            const vm = this;

            // Parts - multiple
            if (this.$refs.partsSelect) {
                const $parts = $(this.$refs.partsSelect);
                $parts.select2({ dropdownParent: $('#addDockModal') })
                    .off('change')
                    .on('change', function () {
                        vm.result.parts = $(this).val(); // update Vue data
                    });
            }

            // Vendor - single
            if (this.$refs.vendorSelect) {
                const $vendor = $(this.$refs.vendorSelect);
                $vendor.select2({ dropdownParent: $('#addDockModal') })
                    .off('change')
                    .on('change', function () {
                        vm.result.vendor_id = $(this).val(); // update Vue data
                    });
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
        },
    }
};
</script>
