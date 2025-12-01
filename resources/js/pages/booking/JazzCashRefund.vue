<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>All Bookings</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="CNIC">CNIC</label>
                                                        <vue-mask id="CNIC" class="form-control"
                                                            v-model="filterForm.cnicFilter" mask="00000-0000000-0"
                                                            @keyup="filterFunction()" :raw="false" :options="options">
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="phone">Cell #</label>
                                                        <vue-mask id="phone" class="form-control"
                                                            v-model="filterForm.phoneFilter" mask="0000-0000000"
                                                            :raw="false" @keyup="filterFunction()"
                                                            :options="optionsContact">
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input id="name" type="text" class="form-control"
                                                            v-model="filterForm.nameFilter" @keyup="filterFunction()">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Invoice</label>
                                                        <input id="name" type="text" class="form-control"
                                                            v-model="filterForm.invoiceFilter"
                                                            @keyup="filterFunction()">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="routeFilter">Route</label>
                                                        <select id="routeFilter" class="form-control"
                                                            v-model="filterForm.routeFilter" @change="filterFunction()">
                                                            <option value="">---Select Route---</option>
                                                            <option v-for="(route, i) in routes" :key="i"
                                                                :value="route.id">
                                                                {{ route.name }} ({{ route.via ?? 'n/a' }})
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="terminalsFilter">Terminals</label>
                                                        <select id="terminalsFilter" class="form-control"
                                                            v-model="filterForm.terminalFilter"
                                                            @change="filterFunction()">
                                                            <option value="">---Select Terminal---</option>
                                                            <option v-for="(terminal, i) in terminals" :key="i"
                                                                :value="terminal.id">
                                                                {{ terminal.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="busFilter">Bus #</label>
                                                        <select id="busFilter" class="form-control"
                                                            v-model="filterForm.busFilter" @change="filterFunction()">
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
                                                            v-model="filterForm.statusFilter"
                                                            @change="filterFunction()">
                                                            <option value="">---Select Status---</option>
                                                            <option value="booked">Booked / Confirm Booked</option>
                                                            <option value="advance booking">Advance Booked / Reserved
                                                            </option>
                                                            <option value="over-issue">Over Issue</option>
                                                            <option value="canceled">Cancelled</option>
                                                            <option value="reschedule">Reschedule Ticket</option>
                                                            <option value="over-issue">Over Issue Ticket</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date From</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                            v-model="filterForm.fromDateFilter"
                                                            @change="filterFunction()">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date To</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                            v-model="filterForm.toDateFilter"
                                                            @change="filterFunction()">
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="table-responsive">
                                                    <div v-if="tableLoading">
                                                        <img class="loading-spinner"
                                                            :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                    </div>
                                                    <table v-else
                                                        style=" width:100%; margin:0; overflow:auto; font-size: 12px"
                                                        class="table table-striped table-hover" id="filterTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Route</th>
                                                                <th>Bus No</th>
                                                                <th>Bus Date</th>
                                                                <th>Bus Time</th>
                                                                <th>Terminal name</th>
                                                                <th>Booked By</th>
                                                                <th>Invoice</th>
                                                                <th>Seat No</th>
                                                                <th>Passenger Name</th>
                                                                <th>CNIC</th>
                                                                <th>Contact</th>
                                                                <th>Fare</th>
                                                                <th>Booking Time</th>
                                                                <th>Canceled By</th>
                                                                <th>Canceled Date</th>
                                                                <th>Over issue By</th>
                                                                <th>Over Issue Date</th>
                                                                <th>Status</th>
                                                                <th>Refund</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(record, i) in allRecords" :key="i">
                                                                <td>{{ ++i }}</td>
                                                                <td>{{ record.schedule.route.name }}</td>
                                                                <td v-if="record.bus">{{ record.bus.bus_number }}</td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.schedule_date">{{
                                                                    record.schedule_date
                                                                    }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.schedule_time">{{
                                                                    record.schedule_time
                                                                    }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.terminal">{{ record.terminal.name }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td>{{ record.added_by.name }}</td>
                                                                <td>{{ record.invoice_id }}</td>
                                                                <td>{{ record.seat_no }}</td>
                                                                <td>{{ record.name }}</td>
                                                                <td>{{ record.cnic }}</td>
                                                                <td>{{ record.contact }}</td>
                                                                <td>{{ parseFloat(record.seat_fare) -
                                                                    parseFloat(record.discount ?? 0) }}
                                                                </td>
                                                                <td>{{ formatDate(record.created_at) }}</td>
                                                                <td>{{ record.type == "canceled" ?
                                                                    record.cancel_ticket.added_by_name ?
                                                                        record.cancel_ticket.added_by_name.name : 'Auto' :
                                                                    'N/A' }}
                                                                </td>
                                                                <td>{{ record.type == "canceled" ?
                                                                    formatDate(record.cancel_ticket.created_at) : 'N/A'
                                                                    }}
                                                                </td>
                                                                <td>{{ record.type == "over-issue" ?
                                                                    record.over_issue_seats.overissue_by.name : 'N/A' }}
                                                                </td>
                                                                <td>{{ record.type == "over-issue" ?
                                                                    formatDate(record.over_issue_seats.created_at) :
                                                                    'N/A' }}
                                                                </td>
                                                                <td>{{ record.type }}</td>
                                                                <td>
                                                                    <span v-if="record.refund_amount">
                                                                        {{ record.refund_amount }} → ({{
                                                                            record.refund_percentage }}%)
                                                                    </span>
                                                                    <span v-else>-</span>
                                                                </td>

                                                                <td>
                                                                    <div class="d-flex gap-1">
                                                                        <button class="btn btn-sm btn-warning"
                                                                            :disabled="record.refund_amount !== null"
                                                                            @click="openRefundModal(record)">
                                                                            {{ record.refund_amount ? 'Refunded' :
                                                                                'Refund' }}
                                                                        </button>

                                                                        <button v-if="record.refund_amount"
                                                                            class="btn btn-sm btn-info mx-2"
                                                                            @click="viewRefundDetails(record)">
                                                                            View
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th>{{ totalFare }}</th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
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
        <!-- Refund Modal -->
        <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-3">

                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title fw-semibold" id="refundModalLabel">Refund Booking</h5>
                        <button type="button" class="close" @click="closeRefundModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <p class="mb-1"><strong>Passenger Name:</strong> {{ selectedRecord?.name }}</p>
                            <p class="mb-1"><strong>Invoice ID:</strong> {{ selectedRecord?.invoice_id }}</p>
                            <p class="mb-1"><strong>Seat No:</strong> {{ selectedRecord?.seat_no }}</p>
                            <p class="mb-3"><strong>Fare:</strong> {{ selectedRecord?.seat_fare }}</p>
                        </div>

                        <!-- Refund Percentage -->
                        <div class="mb-3">
                            <label for="refundPercentage" class="form-label fw-semibold">Refund Percentage</label>
                            <select v-model="refundPercentage" id="refundPercentage" class="form-control">
                                <option disabled value="">Select percentage</option>
                                <option v-for="p in [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]" :key="p" :value="p">{{ p
                                }}%
                                </option>
                            </select>
                        </div>

                        <!-- Calculated Refund -->
                        <div v-if="selectedRecord && refundPercentage" class="alert alert-info py-2 mb-3">
                            <i class="bi bi-cash-coin me-1"></i>
                            Refund Amount: <strong>{{ calculatedRefundAmount }}</strong>
                        </div>

                        <!-- Refund Reason -->
                        <div class="mb-3">
                            <label for="refundReason" class="form-label fw-semibold">Refund Reason</label>
                            <textarea v-model="refundReason" id="refundReason" class="form-control" rows="3"
                                placeholder="Enter refund reason"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" @click="closeRefundModal()">
                            Close
                        </button>
                        <button type="button" class="btn btn-success btn-sm px-3" @click="confirmRefund">
                            Confirm Refund
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Refund Details Modal -->
        <div class="modal fade" id="refundDetailsModal" tabindex="-1" aria-labelledby="refundDetailsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="refundDetailsModalLabel">Refund Details</h5>
                        <button type="button" class="close" @click="closeRefundViewModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Refund Amount:</strong> {{ selectedRefund.refund_amount }} PKR</p>
                        <p><strong>Refund Percentage:</strong> {{ selectedRefund.refund_percentage }}%</p>
                        <p><strong>Refund Reason:</strong></p>
                        <p class="border p-2 rounded bg-light">{{ selectedRefund.refund_reason }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeRefundViewModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>


    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import { mapGetters } from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "AllBookingPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: { placeholder: "xxxxx-xxxxxxx-x" },
            optionsContact: { placeholder: "03xx-xxxxxxx" },
            loading: false,
            showAllBooking: false,
            tableLoading: true,
            routes: [],
            terminals: [],
            buses: [],
            validationErrors: [],
            allRecords: [],
            totalFare: "",
            filterForm: {
                cnicFilter: "",
                fromDateFilter: new Date().toISOString().substr(0, 10),
                toDateFilter: new Date().toISOString().substr(0, 10),
                nameFilter: "",
                invoiceFilter: "",
                phoneFilter: "",
                terminalFilter: 14, // Force terminal 14
                routeFilter: "",
                busFilter: "",
                statusFilter: "canceled", // Force canceled tickets
            },
            selectedRecord: null,
            refundReason: "",
            refundPercentage: "",
            selectedRefund: {},
        };
    },
    async created() {
        $('.modal').remove();
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();

        const currentRouteName = this.$route.name;
        if (currentRouteName === 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },
    methods: {
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "allBooking/routes");
            if (resRoute.status == 200) this.routes = resRoute.data;
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "allBooking/terminals");
            if (resTerminal.status === 200) {
                // Only terminal 14
                this.terminals = resTerminal.data.filter(t => t.id === 14);
            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "allBooking/buses");
            if (resBuses.status == 200) this.buses = resBuses.data;
        },
        async filterFunction() {
            this.tableLoading = true;
            // Force canceled and terminal 14 in filter payload
            this.filterForm.statusFilter = "canceled";
            this.filterForm.terminalFilter = 14;

            const resFilter = await this.callApi("post", "allBooking/jazzcashfilter", this.filterForm);
            if (resFilter.status === 200) {
                this.allRecords = resFilter.data.data;
                this.totalFare = resFilter.data.total_fare;
            }
            this.tableLoading = false;
        },
        formatDate(timestamp) {
            const date = new Date(timestamp);
            const hours = date.getHours() % 12 || 12;
            const minutes = ('0' + date.getMinutes()).slice(-2);
            const ampm = date.getHours() < 12 ? 'AM' : 'PM';
            const formattedDate = ('0' + date.getDate()).slice(-2) + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + date.getFullYear();
            return `${hours}:${minutes} ${ampm} | ${formattedDate}`;
        },
        openRefundModal(record) {
            this.selectedRecord = record;
            this.refundReason = "";
            this.refundPercentage = "";
            const modal = new bootstrap.Modal(document.getElementById("refundModal"));
            modal.show();
        },
        async confirmRefund() {
            if (!this.refundPercentage || !this.refundReason.trim()) {
                Swal.fire({ icon: "warning", title: "Missing Information", text: "Please fill refund percentage and reason." });
                return;
            }

            const payload = {
                ticket_id: this.selectedRecord.id,
                refund_reason: this.refundReason,
                refund_percentage: this.refundPercentage,
                refund_amount: this.calculatedRefundAmount,
            };

            Swal.fire({ title: "Processing Refund...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const res = await this.callApi("post", "allBooking/refund", payload);
                const data = res?.data;
                const ppMsg = data?.response?.pp_ResponseMessage || "";
                const msg = data?.message || "";
                const combinedMsg = ppMsg || msg || "Refund response received.";
                const isSuccess = ppMsg.toLowerCase().includes("successful") || msg.toLowerCase().includes("successful");

                if (isSuccess) {
                    Swal.fire({ icon: "success", title: "Refund Successful", text: combinedMsg, timer: 2500 });
                    this.selectedRecord.refunded = true;
                    this.closeRefundModal();
                    this.filterFunction();
                } else {
                    Swal.fire({ icon: "error", title: "Refund Failed", text: combinedMsg });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({ icon: "error", title: "Server Error", text: "Refund failed due to network or server issue." });
            }
        },
        closeRefundModal() {
            $("#refundModal").click();
        },
        closeRefundViewModal() {
            $("#refundDetailsModal").click();
        },
        viewRefundDetails(record) {
            this.selectedRefund = {
                refund_amount: record.refund_amount,
                refund_percentage: record.refund_percentage,
                refund_reason: record.refund_reason,
            };
            const modal = new bootstrap.Modal(document.getElementById('refundDetailsModal'));
            modal.show();
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj']),
        calculatedRefundAmount() {
            if (!this.selectedRecord || !this.refundPercentage) return 0;
            return ((parseFloat(this.selectedRecord.seat_fare || 0) * this.refundPercentage) / 100).toFixed(2);
        },
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1);
            }
        }
    }
};
</script>

<style scoped>
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
}
</style>
