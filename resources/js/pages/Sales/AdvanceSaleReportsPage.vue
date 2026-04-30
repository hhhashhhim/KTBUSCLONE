<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>ADVANCE SALES REPORT</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="advance-filter-panel">
                                                <div class="filter-panel-head">
                                                    <div>
                                                        <h5 class="filter-panel-title">Refine Report</h5>
                                                        <p class="filter-panel-subtitle">
                                                            Filter by terminal, route, passenger, payment, and time range.
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3"
                                                         v-if="checkForSubmenuButtons('terminal-filter')">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="terminalFilter">Terminals</label>
                                                            <select id="terminalFilter" class="form-control filter-control"
                                                                    v-model="filterSales.terminal">
                                                                <option value="0">Select Terminals</option>
                                                                <option v-for="(terminal, i) in terminals" :key="i"
                                                                        :value="terminal.id">
                                                                    {{ terminal.name }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="usernameFilter">Users</label>
                                                            <select id="usernameFilter" class="form-control filter-control"
                                                                    v-model="filterSales.user">
                                                                <option value="0">Select Users</option>
                                                                <option v-for="(user, i) in users" :key="i"
                                                                        :value="user.id">
                                                                    {{ user.name }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="routeIds">Routes</label>
                                                            <select id="routeIds" class="form-control filter-control filter-select2" multiple
                                                                    v-model="filterSales.route">
                                                                <option value="0">Select Route</option>
                                                                <option v-for="(route, i) in routes" :key="i"
                                                                        :value="route.id">
                                                                    {{ route.name }} ({{ route.via??'n/a' }})
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="invoiceId">Invoice ID</label>
                                                            <input id="invoiceId" type="text" class="form-control filter-control"
                                                                   v-model="filterSales.invoice_id"
                                                                   placeholder="Enter invoice id">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="transactionId">Transaction ID</label>
                                                            <input id="transactionId" type="text" class="form-control filter-control"
                                                                   v-model="filterSales.transaction_id"
                                                                   placeholder="Enter transaction id">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="passengerName">Passenger Name</label>
                                                            <input id="passengerName" type="text" class="form-control filter-control"
                                                                   v-model="filterSales.passenger_name"
                                                                   placeholder="Enter passenger name">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="passengerContact">Passenger Contact</label>
                                                            <input id="passengerContact" type="text" class="form-control filter-control"
                                                                   v-model="filterSales.passenger_contact"
                                                                   placeholder="Enter passenger contact">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="passengerCnic">Passenger CNIC</label>
                                                            <input id="passengerCnic" type="text" class="form-control filter-control"
                                                                   v-model="filterSales.passenger_cnic"
                                                                   placeholder="Enter passenger CNIC">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="fromDate">From Date Time</label>
                                                            <input id="fromDate" type="datetime-local" class="form-control filter-control"
                                                                   v-model="filterSales.fromDateTime">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                                        <div class="filter-field">
                                                            <label class="filter-label" for="toDate">To Date Time</label>
                                                            <input id="toDate" type="datetime-local" class="form-control filter-control"
                                                                   v-model="filterSales.toDateTime">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-4 col-lg-6 col-md-8 mb-3">
                                                        <div class="filter-action-wrap">
                                                            <label class="counter-sale-toggle">
                                                                <input type="checkbox" v-model="filterSales.counterSale">
                                                                <span>Check for count sale</span>
                                                            </label>
                                                            <button class="btn btn-primary filter-submit-btn" type="button" @click="salesFilter()"
                                                                    :disabled="loadingTable">
                                                                {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- <div class="d-flex justify-content-end" v-if="filters.record != null">
                                                <button class="btn btn-dark mt-4" type="button" @click="salesPrint()"
                                                        :disabled="loadingTable">
                                                    {{ loadingTable ? 'Loading...' : 'Print Record' }}
                                                </button>
                                            </div> -->
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <div v-if="tableLoading">
                                                            <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                        </div>
                                                        <div v-else>
                                                            <div class="d-flex justify-content-end mb-2">
                                                                <form :action="$store.state.api_url + 'api/web/v1/advance/sales/pdf'" method="POST" ref="salePrint"
                                                                    target="_blank">
                                                                    <input type="hidden" name="token" :value="this.$store.state.token">
                                                                    <input type="hidden" name="terminal" :value="filterSales.terminal">
                                                                    <input type="hidden" name="user" :value="filterSales.user">
                                                                    <input type="hidden" name="route" :value="filterSales.route">
                                                                    <input type="hidden" name="invoice_id" :value="filterSales.invoice_id">
                                                                    <input type="hidden" name="transaction_id" :value="filterSales.transaction_id">
                                                                    <input type="hidden" name="passenger_name" :value="filterSales.passenger_name">
                                                                    <input type="hidden" name="passenger_contact" :value="filterSales.passenger_contact">
                                                                    <input type="hidden" name="passenger_cnic" :value="filterSales.passenger_cnic">
                                                                    <input type="hidden" name="fromDateTime" :value="filterSales.fromDateTime">
                                                                    <input type="hidden" name="toDateTime" :value="filterSales.toDateTime">
                                                                    <input type="hidden" name="counterSale" :value="filterSales.counterSale">
                                                                    <input type="submit" value="Print" class="btn btn-dark">
                                                                </form>
                                                            </div>
                                                            <table class="table table-striped table-hover text-center"
                                                                id="saleReportTable">
                                                                <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Bus No</th>
                                                                    <th>Bus Class</th>
                                                                    <th>No of Seat</th>
                                                                    <th>Terminal Name</th>
                                                                    <th>User Name</th>
                                                                    <th>Invoice</th>
                                                                    <th>Transaction #</th>
                                                                    <th>Passenger Name</th>
                                                                    <th>Cell No</th>
                                                                    <th>CNIC No</th>
                                                                    <th>Sale Amount</th>
                                                                    <th>ELT Amount</th>
                                                                </tr>
                                                                </thead>

                                                                <tbody>
                                                                <tr v-for="(data,i) in filters.record" :key="i">
                                                                    <td>{{ data.date }}<br>{{ data.time }}</td>
                                                                    <td>{{ data.bus_number }}</td>
                                                                    <td>{{ data.bus_class }}</td>
                                                                    <td>{{ data.seats }}</td>
                                                                    <td>{{ data.terminal }}</td>
                                                                    <td>{{ data.user }}</td>
                                                                    <td>
                                                                        <div v-if="data.invoice_id && data.invoice_id.length">
                                                                            <div v-for="(invoiceId, invoiceIndex) in data.invoice_id" :key="`invoice-${i}-${invoiceIndex}`">
                                                                                {{ invoiceId }}
                                                                            </div>
                                                                        </div>
                                                                        <span v-else>N/A</span>
                                                                    </td>
                                                                    <td>
                                                                        <div v-if="data.transaction_id && data.transaction_id.length">
                                                                            <div v-for="(transactionId, transactionIndex) in data.transaction_id" :key="`transaction-${i}-${transactionIndex}`">
                                                                                {{ transactionId }}
                                                                            </div>
                                                                        </div>
                                                                        <span v-else>N/A</span>
                                                                    </td>
                                                                    <td>
                                                                        <div v-if="data.passenger_name && data.passenger_name.length">
                                                                            <div v-for="(passengerName, passengerIndex) in data.passenger_name" :key="`passenger-name-${i}-${passengerIndex}`">
                                                                                {{ passengerName }}
                                                                            </div>
                                                                        </div>
                                                                        <span v-else>N/A</span>
                                                                    </td>
                                                                    <td>
                                                                        <div v-if="data.passenger_contact && data.passenger_contact.length">
                                                                            <div v-for="(passengerContact, contactIndex) in data.passenger_contact" :key="`passenger-contact-${i}-${contactIndex}`">
                                                                                {{ passengerContact }}
                                                                            </div>
                                                                        </div>
                                                                        <span v-else>N/A</span>
                                                                    </td>
                                                                    <td>
                                                                        <div v-if="data.passenger_cnic && data.passenger_cnic.length">
                                                                            <div v-for="(passengerCnic, cnicIndex) in data.passenger_cnic" :key="`passenger-cnic-${i}-${cnicIndex}`">
                                                                                {{ passengerCnic }}
                                                                            </div>
                                                                        </div>
                                                                        <span v-else>N/A</span>
                                                                    </td>
                                                                    <td>{{ data.sales }}</td>
                                                                    <td>{{ data.elt }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th colspan="3"></th>
                                                                    <th>{{ totalSeats() ?? 0 }}</th>
                                                                    <th colspan="7"></th>
                                                                    <th>{{ totalSeatFare() ?? 0 }}</th>
                                                                    <th>{{ totalEltFare() ?? 0 }}</th>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--                                                Refund Ticket -->
                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">TICKET REFUND</h3>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <thead>
                                                            <tr>
                                                                <th>SR NO</th>
                                                                <th>TICKET ID</th>
                                                                <th>TERMINAL</th>
                                                                <th>BUS NO</th>
                                                                <th>SEAT NO</th>
                                                                <th>REFUND AMOUNT</th>
                                                                <th>CANCELATION CHARGES</th>
                                                                <th>BUS TIMING</th>
                                                                <th>REFUND BY</th>
                                                                <th>CANCELATION DATE</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(dataRefund,i) in filters.refund" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ dataRefund.id }}</td>
                                                                <td>{{ dataRefund.terminal_name }}</td>
                                                                <td>{{ dataRefund.bus_NO }}</td>
                                                                <td>{{ dataRefund.seat_no }}</td>
                                                                <td>{{ dataRefund.amount_refund }}</td>
                                                                <td>{{ dataRefund.cancelation_charges }}</td>
                                                                <td>{{ dataRefund.bus_time }}</td>
                                                                <td>{{ dataRefund.refund_by }}</td>
                                                                <td>{{ dataRefund.cancel_date }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="5"></th>
                                                                <th>{{ refundTotal() ?? 0 }}</th>
                                                                <th>{{ refundTotalCharges() ?? 0 }}</th>
                                                                <th colspan="3"></th>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->

                                                <!--                                                Refund -->
                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">Counter Expenses</h3>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Terminal Name</th>
                                                                <th>Amount</th>
                                                                <th>Narration</th>
                                                                <th> Added By</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(single,i) in filters.counterExpenses" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ single.terminal.name }}</td>
                                                                <td>{{ single.amount }}</td>
                                                                <td>{{ single.narration }}</td>
                                                                <td>{{ single.added_by.name }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="2"></th>
                                                                <th>{{ totalCounterAmount() ?? 0 }}</th>
                                                                <th colspan="2"></th>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->


                                                <!-- <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">Cash Details</h3>
                                                    </div>
                                                    <div class="table-responsive w-100">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <tbody>
                                                            <tr>
                                                                <th style="width: 75% !important;">CASH ON COUNTER</th>
                                                                <td style="width: 25% !important;">
                                                                    {{ totalSeatFare() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL ELT</th>
                                                                <td style="width: 25% !important;">
                                                                    {{ totalEltFare() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL REFUND</th>
                                                                <td style="width: 25% !important;">{{
                                                                        refundTotal() ?? 0
                                                                    }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL CANCELLATION
                                                                    CHARGES
                                                                </th>
                                                                <td style="width: 25% !important;">
                                                                    {{ refundTotalCharges() ?? 0 }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">Total Counter Expenses
                                                                </th>
                                                                <td style="width: 25% !important;">{{ totalCounterAmount() ?? 0 }}                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> -->
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
export default {
    name: "AdvanceSaleReportsPage",
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            loadingTable: false,
            tableLoading: true,
            users: [],
            permissions: [],
            routes: [],
            terminals: [],
            filters: {
                record: [],
                refund: [],
                counterExpenses: [],
            },
            refundFilters: [],
            filterSales: {
                terminal: 0,
                user: 0,
                route: [],
                invoice_id: '',
                transaction_id: '',
                passenger_name: '',
                passenger_contact: '',
                passenger_cnic: '',
                fromDateTime: '',
                toDateTime: '',
                counterSale: false,
            },
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchFilters();

        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        setTimeout(() => {
            $("#routeIds").select2({
                closeOnSelect: false,
                width: '100%'
            });
        }, 300);
    },
    mounted() {
        const self = this;
        // route
        const routeIds = $('#routeIds');
        routeIds.on('change', function() {
            const selectedValues = $(this).val();
            self.filterSales.route = selectedValues;
        });

    },
    methods: {
        async fetchFilters() {
            const resUserNames = await this.callApi("post", 'advance/sales/getUserNames');
            const resRoutes = await this.callApi("post", 'advance/sales/getRoutes');
            const resterminals = await this.callApi("post", 'advance/sales/getTerminals');
            if (resUserNames.status == 200 && resRoutes.status == 200 && resterminals.status == 200) {
                this.tableLoading = false;
                this.users = resUserNames.data;
                this.routes = resRoutes.data;
                this.terminals = resterminals.data;
            }

        },
        async salesFilter() {
            if (!this.filterSales.fromDateTime)
                return swal({
                    title: "Required",
                    text: "From date is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.filterSales.toDateTime)
                return swal({
                    title: "Required",
                    text: "To date is required",
                    icon: "error",
                    timer: 2000
                });
            this.loadingTable = true;
            this.tableLoading = true;
            const resFetchData = await this.callApi("post", 'advance/sales/fetchFilterData', this.filterSales);
            if (resFetchData.status == 200) {
                this.filters.record = resFetchData.data.record;
                this.filters.refund = resFetchData.data.refund;
                this.filters.counterExpenses = resFetchData.data.counterExpenses;
            }
            this.tableLoading = false;
            this.loadingTable = false;

        },
        // sales Table
        totalSeats: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.seats;
                }, 0)
            }
        },
        // sales print
        salesPrint: function () {
            if (!this.filterSales.fromDateTime)
                return swal({
                    title: "Required",
                    text: "From date is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.filterSales.toDateTime)
                return swal({
                    title: "Required",
                    text: "To date is required",
                    icon: "error",
                    timer: 2000
                });
            this.$refs.salePrint.submit();
        },
        totalSeatFare: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.sales;
                }, 0)
            }
        },
        totalEltFare: function () {
            if (this.filters.record) {
                return this.filters.record.reduce((sum, single) => {
                    return sum += single.elt;
                }, 0)
            }
        },
        // refund Table
        refundTotalCharges: function () {
            // if (this.filters.refund) {
            //     return this.filters.refund.reduce((sum, single) => {
            //         return sum += single.cancelation_charges;
            //     }, 0)
            // }
            if (this.filters.refund) {
                let totalCharges = 0;
                for (const key in this.filters.refund) {
                    if (this.filters.refund.hasOwnProperty(key)) {
                        totalCharges += this.filters.refund[key].cancelation_charges;
                    }
                }
                return totalCharges;
            }
            return 0; // Return 0 if this.filters.refund is falsy
        },
        // Counter amount
        totalCounterAmount: function () {
            if (this.filters.counterExpenses) {
                return this.filters.counterExpenses.reduce((sum, single) => {
                    return sum += single.amount;
                }, 0)
            }
        },
        refundTotal: function () {
            if (this.filters.refund) {
            let totalRefund = 0;
            for (const key in this.filters.refund) {
                if (this.filters.refund.hasOwnProperty(key)) {
                    totalRefund += this.filters.refund[key].amount_refund;
                }
            }
            return totalRefund;
        }
        return 0; // Return 0 if this.filters.refund is falsy
        },
            refundTotalSeats: function () {
                if (this.filters.refund) {
                    return this.filters.refund.length;
                }
            }

    },

}
</script>
<style scoped>
table, th, td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}

.advance-filter-panel {
    margin-bottom: 1.5rem;
    padding: 1.5rem 1.4rem 1.2rem;
    border: 1px solid #e4e9f7;
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, rgba(88, 102, 241, 0.08), transparent 32%),
        linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
    box-shadow: 0 12px 28px rgba(31, 45, 61, 0.08);
}

.filter-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.1rem;
}

.filter-panel-title {
    margin: 0;
    color: #1d2a57;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.filter-panel-subtitle {
    margin: 0.3rem 0 0;
    color: #6a7695;
    font-size: 0.9rem;
}

.filter-field {
    height: 100%;
}

.filter-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #43506f;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.filter-control {
    min-height: 44px;
    border: 1px solid #d7def3;
    border-radius: 12px;
    background: #fff;
    color: #25304f;
    box-shadow: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.filter-control:focus {
    border-color: #6a78f0;
    box-shadow: 0 0 0 0.2rem rgba(106, 120, 240, 0.14);
}

.filter-action-wrap {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    min-height: 100%;
    padding: 0.35rem 0 0;
}

.counter-sale-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0;
    padding: 0.8rem 1rem;
    border: 1px solid #dfe5f5;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.88);
    color: #50607f;
    font-weight: 600;
    cursor: pointer;
}

.counter-sale-toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #5a68ee;
}

.filter-submit-btn {
    min-width: 170px;
    min-height: 46px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #5a68ee 0%, #7484ff 100%);
    box-shadow: 0 10px 20px rgba(90, 104, 238, 0.24);
    font-weight: 700;
    letter-spacing: 0.01em;
}

.filter-submit-btn:hover:not(:disabled),
.filter-submit-btn:focus:not(:disabled) {
    background: linear-gradient(135deg, #4c5ae4 0%, #6b7cff 100%);
    box-shadow: 0 12px 24px rgba(90, 104, 238, 0.28);
}

.filter-submit-btn:disabled {
    opacity: 0.8;
    box-shadow: none;
}

:deep(.filter-select2 + .select2-container),
:deep(#routeIds + .select2-container) {
    width: 100% !important;
}

:deep(#routeIds + .select2-container .select2-selection--multiple) {
    min-height: 44px;
    border: 1px solid #d7def3;
    border-radius: 12px;
    background: #fff;
    padding: 0.35rem 0.45rem;
}

:deep(#routeIds + .select2-container.select2-container--focus .select2-selection--multiple) {
    border-color: #6a78f0;
    box-shadow: 0 0 0 0.2rem rgba(106, 120, 240, 0.14);
}

:deep(#routeIds + .select2-container .select2-search__field) {
    margin-top: 0 !important;
}

.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
}

@media (max-width: 991.98px) {
    .filter-action-wrap {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-submit-btn {
        width: 100%;
    }
}
</style>
