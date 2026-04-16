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
                                                            v-model="filterForm.nameFilter" @keyup="filterFunction()" />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Invoice</label>
                                                        <input id="name" type="text" class="form-control"
                                                            v-model="filterForm.invoiceFilter"
                                                            @keyup="filterFunction()" />
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
                                                                {{ route.name }} ({{ route.via ?? "n/a" }})
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
                                                <div class="col-md-4 d-none">
                                                    <div class="form-group">
                                                        <label for="statusFilter">Status</label>
                                                        <select id="statusFilter" class="form-control"
                                                            v-model="filterForm.statusFilter"
                                                            @change="filterFunction()">
                                                            <option value="">---Select Status---</option>
                                                            <option value="booked">
                                                                Booked / Confirm Booked
                                                            </option>
                                                            <option value="advance booking">
                                                                Advance Booked / Reserved
                                                            </option>
                                                            <option value="over-issue">Over Issue</option>
                                                            <option value="canceled">Cancelled</option>
                                                            <!--                                                            <option value="reschedule">Reschedule Ticket</option>-->
                                                            <!--                                                            <option value="over-issue">Over Issue Ticket</option>-->
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date From</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                            v-model="filterForm.fromDateFilter"
                                                            @change="filterFunction()" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dateFilter">Departure Date To</label>
                                                        <input type="date" class="form-control" id="dateFilter"
                                                            v-model="filterForm.toDateFilter"
                                                            @change="filterFunction()" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="table-responsive">
                                                    <div v-if="tableLoading">
                                                        <img class="loading-spinner" :src="$store.state.main_url +
                                                            'assets/img/loading-spinner.gif'
                                                            " />
                                                    </div>
                                                    <table v-else style="
                              width: 100%;
                              margin: 0;
                              overflow: auto;
                              font-size: 12px;
                            " class="table table-striped table-hover" id="filterTable">
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
                                                                <td v-if="record.bus">
                                                                    {{ record.bus.bus_number }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.schedule_date">
                                                                    {{ record.schedule_date }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.schedule_time">
                                                                    {{ record.schedule_time }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td v-if="record.terminal">
                                                                    {{ record.terminal.name }}
                                                                </td>
                                                                <td v-else>N/A</td>
                                                                <td>{{ record.added_by.name }}</td>
                                                                <td>{{ record.invoice_id }}</td>
                                                                <td>{{ record.seat_no }}</td>
                                                                <td>{{ record.name }}</td>
                                                                <td>{{ record.cnic }}</td>
                                                                <td>{{ record.contact }}</td>
                                                                <td>
                                                                    {{
                                                                        parseFloat(record.seat_fare) -
                                                                    parseFloat(record.discount ?? 0)
                                                                    }}
                                                                </td>
                                                                <td>{{ formatDate(record.created_at) }}</td>
                                                                <td>
                                                                    {{
                                                                        record.type == "canceled"
                                                                            ? record.cancel_ticket.added_by_name
                                                                                ? record.cancel_ticket.added_by_name
                                                                                    .name
                                                                    : "Auto"
                                                                    : "N/A"
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        record.type == "canceled"
                                                                            ? formatDate(
                                                                                record.cancel_ticket.created_at
                                                                    )
                                                                    : "N/A"
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        record.type == "over-issue"
                                                                            ? record.over_issue_seats.overissue_by
                                                                    .name
                                                                    : "N/A"
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        record.type == "over-issue"
                                                                            ? formatDate(
                                                                                record.over_issue_seats.created_at
                                                                    )
                                                                    : "N/A"
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        record.refund_amount
                                                                            ? "Refunded"
                                                                    : record.type
                                                                    }}
                                                                </td>

                                                                <td>
                                                                    <span v-if="record.refund_amount">
                                                                        {{ record.refund_amount }} → ({{ 100 -
                                                                        (record.refund_percentage || 0) }}%)
                                                                    </span>
                                                                    <span v-else>-</span>
                                                                </td>

                                                                <td>
                                                                    <div class="d-flex gap-1">
                                                                        <button v-if="record.refund_amount === null"
                                                                            class="btn btn-sm btn-warning"
                                                                            @click="openRefundModal(record)">
                                                                            Refund
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
                        <h5 class="modal-title fw-semibold" id="refundModalLabel">
                            Refund Booking
                        </h5>
                        <button type="button" class="close" @click="closeRefundModal()" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <p class="mb-1">
                                <strong>Passenger Name:</strong> {{ selectedRecord?.name }}
                            </p>
                            <p class="mb-1">
                                <strong>Invoice ID:</strong> {{ selectedRecord?.invoice_id }}
                            </p>
                            <p class="mb-1">
                                <strong>Seat No:</strong> {{ selectedRecord?.seat_no }}
                            </p>
                            <p class="mb-1">
                                <strong>Fare:</strong> {{ totalFare }}
                            </p>
                        </div>

                        <!-- Manual Refund Amount -->
                        <div class="mb-3">
                            <label for="refundAmount" class="form-label fw-semibold">Refund Amount</label><br>
                            <small class="text-muted">Entered amount will be refunded to customer, remaining amount will
                                stay with company</small>
                            <input v-model="refundAmount" id="refundAmount" type="text" min="0" step="0.01"
                                class="form-control" placeholder="Enter refund amount"
                                @keypress="$numberValidate($event, { dot: true })" />
                            <small class="text-muted">Maximum refundable amount: {{ totalFare }}</small>
                        </div>

                        <div v-if="selectedRecord && refundAmount !== ''" class="alert alert-info py-2 mb-3">
                            <div><strong>Customer Refund Amount:</strong> {{ parseFloat(refundAmount || 0).toFixed(2) }}
                            </div>
                            <div><strong>Company Keep Amount:</strong> {{ (parseFloat(totalFare || 0) -
                                parseFloat(refundAmount || 0)).toFixed(2) }}</div>
                            <div>
                                <strong>Company Keep Percentage:</strong>
                                {{
                                    parseFloat(totalFare || 0) > 0
                                        ? (((parseFloat(totalFare || 0) - parseFloat(refundAmount || 0)) / parseFloat(totalFare
                                || 1)) * 100).toFixed(2)
                                : 0
                                }}%
                            </div>
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
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content shadow border-0 rounded-3">

                    <!-- Header -->
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-semibold" id="refundDetailsModalLabel">
                            Refund Details
                        </h5>
                        <button type="button" class="close text-white" @click="closeRefundViewModal()">
                            <span>&times;</span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">

                        <!-- Summary Cards -->
                       <div class="row g-3 mb-4 text-center">

  <div class="col-6">
    <div class="p-3 border rounded bg-light h-100">
      <small class="text-muted d-block mb-1">Total Paid</small>
      <div class="fw-bold fs-6">
        {{
          (
            parseFloat(selectedRefund?.seat_fare || 0)
            - parseFloat(selectedRefund?.discount || 0)
          ).toFixed(2)
        }} PKR
      </div>
    </div>
  </div>

  <div class="col-6">
    <div class="p-3 border rounded bg-light h-100">
      <small class="text-muted d-block mb-1">Refund</small>
      <div class="fw-bold text-success fs-6">
        {{ parseFloat(selectedRefund?.refund_amount || 0).toFixed(2) }} PKR
      </div>
    </div>
  </div>

  <div class="col-6">
    <div class="p-3 border rounded bg-light h-100">
      <small class="text-muted d-block mb-1">Company Keep</small>
      <div class="fw-bold text-danger fs-6">
        {{
          Math.max(
            0,
            (
              parseFloat(selectedRefund?.seat_fare || 0)
              - parseFloat(selectedRefund?.discount || 0)
              - parseFloat(selectedRefund?.refund_amount || 0)
            )
          ).toFixed(2)
        }} PKR
      </div>
    </div>
  </div>

  <div class="col-6">
    <div class="p-3 border rounded bg-light h-100">
      <small class="text-muted d-block mb-1">Refund %</small>
      <div class="fw-bold fs-6">
        {{ parseFloat(selectedRefund?.refund_percentage || 0).toFixed(2) }}%
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="p-3 border rounded bg-light">
      <small class="text-muted d-block mb-1">Company Keep %</small>
      <div class="fw-bold fs-6">
        {{
          (
            100 - parseFloat(selectedRefund?.refund_percentage || 0)
          ).toFixed(2)
        }}%
      </div>
    </div>
  </div>

</div>

                        <!-- Reason -->
                     <div class="mt-3">
  <label class="fw-semibold mb-1">Refund Reason</label>
  <div class="border rounded p-3 bg-light" style="min-height: 60px;">
    {{
      selectedRefund?.refund_reason &&
      selectedRefund?.refund_reason.trim() !== ''
        ? selectedRefund.refund_reason
        : 'No reason provided'
    }}
  </div>
</div>

                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-sm px-3" @click="closeRefundViewModal()">
                            Close
                        </button>
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
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsContact: {
                placeholder: "03xx-xxxxxxx",
            },
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
                fromDateFilter: "",
                toDateFilter: "",
                nameFilter: "",
                invoiceFilter: "",
                phoneFilter: "",
                terminalFilter: "",
                routeFilter: "",
                busFilter: "",
                statusFilter: "canceled",
            },
            selectedRecord: null,
            refundReason: "",
            refundAmount: "",   // ✅ new
            selectedRefund: {},
        };
    },
    async created() {
        $(".modal").remove();
        this.fetchRoutes();
        this.fetchTerminals();
        this.fetchBus();
        this.filterForm.fromDateFilter = new Date().toISOString().substr(0, 10);
        this.filterForm.toDateFilter = new Date().toISOString().substr(0, 10);
        const currentRouteName = this.$route.name;
        if (currentRouteName == "booking-page") {
            window.addEventListener("keydown", this.enterKey);
            window.addEventListener("keydown", this.altM);
        } else {
            window.removeEventListener("keydown", this.enterKey);
            window.removeEventListener("keydown", this.altM);
        }
    },

    methods: {
        async fetchRoutes() {
            const resRoute = await this.callApi("post", "allBooking/routes");
            if (resRoute.status == 200) {
                this.routes = resRoute.data;
            }
        },
        async fetchTerminals() {
            const resTerminal = await this.callApi("post", "allBooking/terminals");
            if (resTerminal.status === 200) {
                // Filter only the terminal with ID 14
                this.terminals = resTerminal.data.filter(
                    (terminal) => terminal.id === 14
                );
            }
        },
        async fetchBus() {
            const resBuses = await this.callApi("post", "allBooking/buses");
            if (resBuses.status == 200) {
                this.buses = resBuses.data;
            }
        },

        async filterFunction() {
            this.tableLoading = true;
            const resFilter = await this.callApi(
                "post",
                "allBooking/jazzcashfilter",
                this.filterForm
            );
            if (resFilter.status === 200) {
                this.allRecords = resFilter.data.data;
                this.totalFare = resFilter.data.total_fare;
            }
            this.tableLoading = false;
        },
        formatDate(timestamp) {
            const date = new Date(timestamp);
            const hours = date.getHours() % 12 || 12; // Get hours in 12-hour format
            const minutes = ("0" + date.getMinutes()).slice(-2); // Ensure minutes are always two digits
            const ampm = date.getHours() < 12 ? "AM" : "PM"; // Get AM/PM

            // Format date as DD-MM-YYYY
            const formattedDate =
                ("0" + date.getDate()).slice(-2) +
                "-" +
                ("0" + (date.getMonth() + 1)).slice(-2) +
                "-" +
                date.getFullYear();

            // Combine time and date
            return `${hours}:${minutes} ${ampm} | ${formattedDate}`;
        },
        openRefundModal(record) {
            this.selectedRecord = record;
            this.refundReason = "";
            this.refundPercentage = "";
            this.calculatedRefundAmount = ""; // optional reset

            const modal = new bootstrap.Modal(document.getElementById("refundModal"));
            modal.show();
        },

        async confirmRefund() {
            const totalFare = parseFloat(this.totalFare);
            const enteredRefundAmount = parseFloat(this.refundAmount);

            if (this.refundAmount === null || this.refundAmount === '') {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Information",
                    text: "Please enter refund amount.",
                });
                return;
            }

            if (isNaN(enteredRefundAmount) || enteredRefundAmount < 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Invalid Amount",
                    text: "Please enter a valid refund amount.",
                });
                return;
            }

            if (enteredRefundAmount > totalFare) {
                Swal.fire({
                    icon: "warning",
                    title: "Invalid Amount",
                    text: `Refund amount cannot be greater than paid fare.`,
                });
                return;
            }

            if (!this.refundReason.trim()) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Information",
                    text: "Please enter a refund reason.",
                });
                return;
            }

            const companyKeepAmount = totalFare - enteredRefundAmount;

            const payload = {
                ticket_id: this.selectedRecord.id,
                refund_reason: this.refundReason,
                refund_amount: enteredRefundAmount,
            };

            console.log("Payload:", payload);
            console.log("Company Keep Amount:", companyKeepAmount);

            Swal.fire({
                title: "Processing Refund...",
                text: "Please wait while we process your request.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            try {
                const res = await this.callApi("post", "allBooking/refund", payload);

                const data = res?.data;
                const ppMessage = data?.response?.pp_ResponseMessage || "";
                const genericMsg = data?.message || "";
                const combinedMsg = ppMessage || genericMsg || "Refund response received.";

                if (data?.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Refund Successful",
                        text: combinedMsg,
                        timer: 2500,
                        showConfirmButton: true,
                    });

                    this.selectedRecord.refunded = true;
                    this.closeRefundModal();
                    this.filterFunction();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Refund Failed",
                        text: combinedMsg,
                    });
                }
            } catch (err) {
                console.error("Refund Error:", err);
                Swal.fire({
                    icon: "error",
                    title: "Server or Network Error",
                    text: "Refund failed due to a network or server issue.",
                });
            }
        },

        closeRefundModal() {
            this.refundAmount = '';
            this.refundReason = '';
            this.selectedRecord = null;
            $("#refundModal").click();
        },
        closeRefundViewModal() {
            $("#refundDetailsModal").click();
        },
        viewRefundDetails(record) {
            this.selectedRefund = {
                refund_amount: record.refund_amount,
                refund_percentage: record.refund_percentage,
                seat_fare: record.seat_fare,
                discount: record.discount,
            };
            const modal = new bootstrap.Modal(
                document.getElementById("refundDetailsModal")
            );
            modal.show();
        },
    },
    computed: {
        totalFare() {
            if (!this.selectedRecord) return 0;

            const fare = parseFloat(this.selectedRecord.seat_fare || 0);
            const discount = parseFloat(this.selectedRecord.discount || 0);

            return fare - discount; // ✅ ALWAYS use this
        },

        calculatedRefundAmount() {
            if (!this.selectedRecord || this.refundAmount === "") return 0;

            const enteredAmount = parseFloat(this.refundAmount);
            const totalFare = parseFloat(this.totalFare);

            if (isNaN(enteredAmount) || enteredAmount < 0) return 0;

            return Math.min(enteredAmount, totalFare).toFixed(2); // ✅ safe cap
        }
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1);
            }
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
