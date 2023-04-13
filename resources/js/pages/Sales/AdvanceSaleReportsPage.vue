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
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label for="terminalFilter">Terminals</label>
                                                    <select id="terminalFilter" class="form-control"
                                                            v-model="filterSales.terminal"
                                                            @change="salesFilter()">
                                                        <option value="0">Select Terminals</option>
                                                        <option v-for="(terminal, i) in terminals" :key="i"
                                                                :value="terminal.id">
                                                            {{ terminal.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="usernameFilter">Users</label>
                                                    <select id="usernameFilter" class="form-control"
                                                            v-model="filterSales.user"
                                                            @change="salesFilter()">
                                                        <option value="0">Select Users</option>
                                                        <option v-for="(user, i) in users" :key="i"
                                                                :value="user.id">
                                                            {{ user.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="fromDate">From Date</label>
                                                    <input id="fromDate" type="date" class="form-control"
                                                           v-model="filterSales.fromDate" @change="salesFilter()">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="fromTime">From Time</label>
                                                    <input id="fromTime" type="time" class="form-control"
                                                           v-model="filterSales.fromTime" @change="salesFilter()">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="toDate">To Date</label>
                                                    <input id="toDate" type="date" class="form-control"
                                                           v-model="filterSales.toDate" @change="salesFilter()">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="toTime">To Time</label>
                                                    <input id="toTime" type="time" class="form-control"
                                                           v-model="filterSales.toTime" @change="salesFilter()">
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover">
                                                            <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Bus No</th>
                                                                <th>No of Seat</th>
                                                                <th>Terminal Name</th>
                                                                <th>User Nam</th>
                                                                <th>Sale Amount</th>
                                                                <th>ELT Amount</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <tr v-for="(data,i) in filters.record" :key="i">
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2"></td>
                                                                <td>Total Seats</td>
                                                                <td colspan="2"></td>
                                                                <td>Total Sale AMount</td>
                                                                <td>ELT PRICE</td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <!--                                                Refund Ticket -->
                                                <div class="col-md-12 text-center">
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
                                                                <td>SR NO</td>
                                                                <td>TICKET ID</td>
                                                                <td>TERMINAL</td>
                                                                <td>BUS NO</td>
                                                                <td>SEAT NO</td>
                                                                <td>REFUND AMOUNT</td>
                                                                <td>CANCELATION CHARGES</td>
                                                                <td>BUS TIMING</td>
                                                                <td>REFUND BY</td>
                                                                <td>CANCELATION DATE</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="4"></td>
                                                                <td>No of seats</td>
                                                                <td>Refund Total</td>
                                                                <td>Total CANCELATION CHARGES</td>
                                                                <td colspan="3"></td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 text-center">
                                                    <div class="my-1">
                                                        <h3 class="text-mute">Cash Details</h3>
                                                    </div>
                                                    <div class="table-responsive w-100">
                                                        <table class="table table-striped table-hover"
                                                               style="  border: 3px solid #b9b9b9">
                                                            <tbody>
                                                            <tr>
                                                                <th style="width: 75% !important;">CASH ON BANK</th>
                                                                <td style="width: 25% !important;">0</td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">CASH ON COUNTER</th>
                                                                <td style="width: 25% !important;">0</td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL ELT</th>
                                                                <td style="width: 25% !important;">0</td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL REFUND</th>
                                                                <td style="width: 25% !important;">0</td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL CANCELATION CHARGES</th>
                                                                <td style="width: 25% !important;">0</td>
                                                            </tr>
                                                            <tr>
                                                                <th style="width: 75% !important;">TOTAL CASH ON COUNTER</th>
                                                                <td style="width: 25% !important;">0</td>
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
export default {
    name: "AdvanceSaleReportsPage",
    data() {
        return {
            terminals: [],
            users: [],
            filters: [],
            refundFilters: [],
            filterSales: {
                terminal: 0,
                user: 0,
                fromDate: '',
                fromTime: '',
                toDate: '',
                toTime: '',
            },
        }
    },
    async created() {
        this.fetchFilters();
    },
    methods: {
        async fetchFilters() {
            const resTerminals = await this.callApi("post", 'advance/sales/getTerminals');
            const resUserNames = await this.callApi("post", 'advance/sales/getUserNames');
            if (resTerminals.status == 200 && resUserNames.status == 200) {
                this.terminals = resTerminals.data;
                this.users = resUserNames.data;
            }

        },
        async salesFilter() {
            const resFetchData = await this.callApi("post", 'advance/sales/fetchFilterData', this.filterSales);
            console.log(resFetchData);

        },
    },
}
</script>
<style scoped>
table, th, td {
    border: 1px solid #b9b9b9;
    border-collapse: collapse;
}
</style>
