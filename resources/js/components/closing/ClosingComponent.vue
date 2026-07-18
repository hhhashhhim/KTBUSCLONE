<template>
    <div>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="text-center" id="busModalLabel">
                            Merge Schedule Details
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="close()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="isLoading" class="d-flex flex-column align-items-center justify-content-center my-3">
                            <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'"
                                alt="Loading..." style="width: 20px; height: 20px" />
                            <small class="text-muted mt-1">Loading bus data...</small>
                        </div>

                        <div v-else>
                            <h5 class="text-center" id="busModalLabel">
                                Bus No: {{ data?.singleData?.bus_number || "" }}
                            </h5>
                            <div class="row">

                                <!-- Rawalpindi Table -->
                                <div class="col-md-12 mb-3">
                                    <h5 class="text-center">
                                        {{ data?.singleData?.city_one || "" }}
                                    </h5>
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Terminal Name</th>
                                                <th>Passenger Count</th>
                                                <th>KT Commission</th>
                                                <th>ELT</th>
                                                <th>Cancellation Amount</th>
                                                <th>Total Receivable</th>
                                                <th>Other Commission</th>
                                                <!-- <th>Receivable Cash</th>
                        <th>Receivable Bank</th> -->
                                                <th>Total Received in Cash</th>
                                                <th>Select Bank</th>
                                                <th>Total Received in Bank</th>
                                                <th>Shortage</th>
                                                <th>Received</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(tickets, terminalId) in data.schedule_start"
                                                :key="'start-' + terminalId">
                                                <td>{{ tickets[0].terminal.name }}</td>
                                                <td>{{tickets.filter(t => t.type != 'canceled').length}}</td>
                                                <td>{{ $insertComma(totalCommission(tickets)) }}</td>
                                                <td>{{ $insertComma(totalELT(tickets)) }}</td>
                                                <td>{{ $insertComma(totalCancelAmount(tickets)) }}</td>
                                                <td>{{ $insertComma(totalFare(tickets)) }}</td>
                                                <td>{{ $insertComma(totalOtherCommission(tickets)) }}</td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        v-model="cashBankStart[terminalId].cash"
                                                        @input="updateReceived('start', terminalId, tickets, 'cash')"
                                                        :disabled="totalFare(tickets) == 0"
                                                        @keypress="$numberValidate($event, { dot: true })" />
                                                </td>
                                                <td>
                                                    <select class="form-control rounded-0"
                                                        v-model="cashBankStart[terminalId].selectedBankId"
                                                        :disabled="totalFare(tickets) == 0">
                                                        <option value="" selected>
                                                            Select Bank
                                                        </option>
                                                        <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                                            {{ bank.text }}
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" min="0" class="form-control"
                                                        v-model.number="cashBankStart[terminalId].bank"
                                                        @input="updateBank('start', terminalId, tickets)"
                                                        :disabled="totalFare(tickets) == 0"
                                                        @keypress="$numberValidate($event, { dot: true })" />
                                                </td>

                                                <td>
                                                    <input type="number" class="form-control"
                                                        v-model.number="cashBankStart[terminalId].shortage" readonly />
                                                </td>

                                                <td>{{ netAmountStart(terminalId) }}</td>
                                            </tr>
                                        </tbody>

                                        <tfoot class="table-light">
                                            <tr>
                                                <th>Total</th>

                                                <th>
                                                    {{ totalPassengers(data.schedule_start) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(totalCommissions(data.schedule_start)) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(grandTotalELT(data.schedule_start)) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(grandTotalCancel(data.schedule_start)) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumReceivable()) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(sumOtherCommissions(data.schedule_start)) }}
                                                </th>
                                                <!-- <th></th>
<th></th> -->

                                                <th>
                                                    {{ $insertComma(sumCash()) }}
                                                </th>

                                                <th></th>
                                                <!-- Select Bank column -->

                                                <th>
                                                    {{ $insertComma(sumBank()) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumShortage()) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumReceived()) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Faisalabad Table -->
                                <div class="col-md-12 mb-3">
                                    <h5 class="text-center">
                                        {{ data?.singleData?.city_two || "" }}
                                    </h5>
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Terminal Name</th>
                                                <th>Passenger Count</th>
                                                <th>KT Commission</th>
                                                <th>ELT</th>
                                                <th>Cancellation Amount</th>
                                                <th>Total Receivable</th>
                                                <th>Other Commission</th>
                                                <th>Total Received in Cash</th>
                                                <th>Select Bank</th>
                                                <th>Total Received in Bank</th>
                                                <th>Shortage</th>
                                                <th>Received</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(tickets, terminalId) in data.schedule_return"
                                                :key="'return-' + terminalId">
                                                <td>{{ tickets[0].terminal.name }}</td>
                                                <td>{{tickets.filter(t => t.type != 'canceled').length}}</td>
                                                <td>{{ $insertComma(totalCommission(tickets)) }}</td>
                                                <td>{{ $insertComma(totalELT(tickets)) }}</td>
                                                <td>{{ $insertComma(totalCancelAmount(tickets)) }}</td>
                                                <td>{{ $insertComma(totalFare(tickets)) }}</td>
                                                <td>{{ $insertComma(totalOtherCommission(tickets)) }}</td>

                                                <td>
                                                    <input type="text" min="0" class="form-control"
                                                        v-model.number="cashBankReturn[terminalId].cash"
                                                        @input="updateReceived('return', terminalId, tickets, 'cash')"
                                                        :disabled="totalFare(tickets) == 0"
                                                        @keypress="$numberValidate($event, { dot: true })" />
                                                </td>
                                                <td>
                                                    <select class="form-control rounded-0"
                                                        v-model="cashBankReturn[terminalId].selectedBankId"
                                                        :disabled="totalFare(tickets) == 0">
                                                        <option value="" selected>
                                                            Select Bank
                                                        </option>
                                                        <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                                            {{ bank.text }}
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" min="0" class="form-control"
                                                        v-model.number="cashBankReturn[terminalId].bank"
                                                        @input="updateBank('return', terminalId, tickets)"
                                                        :disabled="totalFare(tickets) == 0"
                                                        @keypress="$numberValidate($event, { dot: true })" />
                                                </td>

                                                <td>
                                                    <input type="number" class="form-control"
                                                        v-model.number="cashBankReturn[terminalId].shortage" readonly />
                                                </td>

                                                <td>{{ netAmountReturn(terminalId) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>Total</th>

                                                <th>
                                                    {{ totalPassengers(data.schedule_return) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(totalCommissions(data.schedule_return)) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(grandTotalELT(data.schedule_return)) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(grandTotalCancel(data.schedule_return)) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumReceivableReturn()) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(sumOtherCommissions(data.schedule_return)) }}
                                                </th>
                                                <th>
                                                    {{ $insertComma(sumCashReturn()) }}
                                                </th>

                                                <th></th>
                                                <!-- Select Bank -->

                                                <th>
                                                    {{ $insertComma(sumBankReturn()) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumShortageReturn()) }}
                                                </th>

                                                <th>
                                                    {{ $insertComma(sumReceivedReturn()) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                             <div class="col-12 col-md-12">
    <h5 class="text-center">Expenses</h5>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Category</th>
                <th>Description</th>
                <th>Total Expense Amount</th>
                <th>Total Paid</th>
                <th>Credit Balance</th>
                <th>Action</th>
                <th>Expense Header</th>
            </tr>
        </thead>

        <tbody>
            <tr v-for="(row, index) in loop" :key="'expense-row-' + index">
                <td>
                    <select
    class="form-control rounded-0"
    v-model="postData.category[index]"
    :disabled="editAble"
    @change="handleCategoryChange(index)"
>
    <option value="">Select Category</option>
    <option
        v-for="(category, i) in categories"
        :key="'cat-' + i"
        :value="category.id"
    >
        {{ category.name }}
    </option>
</select>
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control"
                        v-model="postData.description[index]"
                        :disabled="editAble"
                    />
                </td>

                <td>
                    <input
                        type="text"
                        min="0"
                        class="form-control rounded-0"
                        v-model.number="postData.amount[index]"
                        @input="syncPaid(index)"
                        @keypress="$numberValidate($event, { dot: true })"
                    />
                </td>

                <td>
                    <input
                        type="text"
                        min="0"
                        class="form-control rounded-0"
                        v-model.number="postData.paid[index]"
                        @keypress="$numberValidate($event, { dot: true })"
                    />
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control rounded-0"
                        :value="balances[index] || 0"
                        readonly
                    />
                </td>

                <td class="add-btn" v-if="!editAble">
                    <button
                        type="button"
                        class="btn btn-outline-primary mx-2"
                        @click="addRow"
                    >
                        <i class="fas fa-plus"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="removeRow(index)"
                        v-if="loop > 1"
                    >
                        <i class="fas fa-trash"></i>
                    </button>

                    <input
    type="checkbox"
    class="ml-2"
    v-model="postData.showExtra[index]"
    @change="handleExtraChange(index)"
/>
                </td>

                <td v-else></td>

               <td class="header-td">
    <select
        v-if="postData.showExtra[index] === true"
        class="form-control mt-1"
        v-model="postData.extraCategory[index]"
    >
        <option value="">Select Option</option>
        <option
            v-for="(header, i) in safeReportsHeaders"
            :key="'header-' + i"
            :value="header.id"
        >
            {{ header.name }}
        </option>
    </select>
</td>
            </tr>
        </tbody>
    </table>
</div>
                                <div class="col-6 col-md-6">
                                    <!-- Total Receivable Table -->
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">
                                                    Total Receivable
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total Receivable in Cash</th>
                                                <td>
                                                    {{ $insertComma(sumStartCash() + sumReturnCash()) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Receivable in Bank</th>
                                                <td>
                                                    {{ $insertComma(sumStartBank() + sumReturnBank()) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Total Received Table -->
                                    <table class="table table-sm table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">Total Received</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total Received in Cash</th>
                                                <td>{{ $insertComma(sumCash() + sumCashReturn()) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Total Received in Bank</th>
                                                <td>{{ $insertComma(sumBank() + sumBankReturn()) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <table class="table table-sm table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">Grand Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total Receivable</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumReceivable() + sumReceivableReturn()
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Other Commission</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumOtherCommissions(data.schedule_return) +
                                                            sumOtherCommissions(data.schedule_start)
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Shortage</th>
                                                <td>
                                                    {{
                                                        $insertComma(sumShortage() + sumShortageReturn())
                                                    }}
                                                </td>
                                            </tr>

                                            <tr class="border-r">
                                                <th>Total Received</th>
                                                <td>{{ $insertComma(grandTotal) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-6 col-md-6">
                                    <!-- Expenses Table -->
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">Expenses</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total KT Commission</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            totalCommissions(data.schedule_return) +
                                                            totalCommissions(data.schedule_start)
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Other Commission</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumOtherCommissions(data.schedule_return) +
                                                            sumOtherCommissions(data.schedule_start)
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Paid Expenses</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            Math.round(totalAmount - totalExpenses)
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Credit Expenses</th>
                                                <td>{{ $insertComma(totalExpenses) }}</td>
                                            </tr>
                                            <tr class="border-r">
                                                <th>Total Expense</th>
                                                <td>{{ $insertComma(grandTotalexpense) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Profit and Loss Table -->
                                    <table class="table table-sm table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">Profit and Loss</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total Receivable</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumReceivable() + sumReceivableReturn()
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Expenses</th>
                                                <td>{{ $insertComma(grandTotalexpense) }}</td>
                                            </tr>
                                            <tr class="border-r">
                                                <th>Total</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumReceivable() +
                                                            sumReceivableReturn() -
                                                            grandTotalexpense
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Cash in Hand Summary Table -->
                                    <table class="table table-sm table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">
                                                    Cash in Hand Summary
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>Total Received in Cash</th>
                                                <td>{{ $insertComma(sumCash() + sumCashReturn()) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Total Paid Expenses</th>
                                                <td>{{ $insertComma(totalPaid) }}</td>
                                            </tr>
                                            <tr class="border-r">
                                                <th>Total Cash in Hand</th>
                                                <td>
                                                    {{
                                                        $insertComma(
                                                            sumCash() + sumCashReturn() - totalPaid
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="close()">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary d-flex align-items-center"
                            @click="saveTicketClosingShortage" :disabled="loading">
                            <!-- Loader -->
                            <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status"
                                aria-hidden="true"></span>

                            <!-- Text -->
                            <span>
                                {{ loading ? "Saving..." : "Save changes" }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["data", "banks", "busIds", "mergeIds", "routes", "addData"],
    data() {
        return {
            categories: [],
            postData: {
                ticket_merge_id: "",
                category: [],
                description: [],
                amount: [],
                paid: [],
                ledger: [],
                invoice: [],
                showExtra: [false],
                extraCategory: [''],
            },
            loop: 1,
            loading: false,
            cashBank: {},
            editingField: null, // 'cash' | 'bank'
            cashBankStart: {},
            cashBankReturn: {},
            mergedData: {},
            reportsHeaders: [],
        };
    },
    watch: {
        "data.schedule_start": {
            immediate: true,
            handler(val) {
                this.cashBankStart = this.buildCashBankRows(val);
            },
        },
        "data.schedule_return": {
            immediate: true,
            handler(val) {
                this.cashBankReturn = this.buildCashBankRows(val);
            },
        },
    },
    async created() {
        this.fetchData();
        this.fetchReportsHeaders();
    },
    computed: {

        totalCash() {
            return (
                this.sumObject(this.cashBankStart, "cash") +
                this.sumObject(this.cashBankReturn, "cash")
            );
        },
 safeReportsHeaders() {
            return Array.isArray(this.reportsHeaders)
                ? this.reportsHeaders.filter(item => item && item.id != null)
                : [];
        },
        totalBank() {
            return (
                this.sumObject(this.cashBankStart, "bank") +
                this.sumObject(this.cashBankReturn, "bank")
            );
        },
        totalBankExpenses() {
            if (!this.postData.paid) return 0;
            return this.postData.paid.reduce((sum, val) => sum + Number(val || 0), 0);
        },
        isLoading() {
            // Show loader if data is null, undefined, or empty object
            return !this.data || Object.keys(this.data).length === 0;
        },
        grandTotal() {
            return (
                this.sumCash() +
                this.sumCashReturn() +
                this.sumBank() +
                this.sumBankReturn()
            );
        },
        total() {
            return (
                this.sumShortage() +
                this.sumShortageReturn() +
                this.grandTotal +
                this.sumReceivable() +
                this.sumReceivableReturn()
            );
        },
        grandTotalexpense() {
            return (
                (Number(this.totalAmount) || 0) +
                this.totalCommissions(this.data.schedule_return || {}) +
                this.totalCommissions(this.data.schedule_start || {}) +
                this.sumOtherCommissions(this.data.schedule_return || {}) +
                this.sumOtherCommissions(this.data.schedule_start || {})
            );
        },

        totalAmount() {
            return this.postData.amount.reduce(
                (sum, val) => sum + (Number(val) || 0),
                0
            );
        },

        totalPaid() {
            const paid = this.postData.paid.reduce(
                (sum, val) => sum + (Number(val) || 0),
                0
            );
            return paid;
        },
        totalExpenses() {
            return this.postData.amount.reduce((sum, val, index) => {
                const paid = Number(this.postData.paid[index]) || 0;
                return sum + (Number(val) - paid);
            }, 0);
        },
        balances() {
            return this.postData.amount.map((amt, index) => {
                let paid = this.postData.paid[index] || 0;

                // Clamp paid so it never exceeds amount
                if (paid > amt) {
                    paid = amt;
                    this.postData.paid[index] = paid;
                }

                return amt - paid;
            });
        },
    },
    methods: {
        ensureRowIndexes(index) {
            if (!Array.isArray(this.postData.category)) this.postData.category = [];
            if (!Array.isArray(this.postData.description)) this.postData.description = [];
            if (!Array.isArray(this.postData.amount)) this.postData.amount = [];
            if (!Array.isArray(this.postData.paid)) this.postData.paid = [];
            if (!Array.isArray(this.postData.invoice)) this.postData.invoice = [];
            if (!Array.isArray(this.postData.showExtra)) this.postData.showExtra = [];
            if (!Array.isArray(this.postData.extraCategory)) this.postData.extraCategory = [];

            if (typeof this.postData.category[index] === "undefined") this.postData.category[index] = '';
            if (typeof this.postData.description[index] === "undefined") this.postData.description[index] = '';
            if (typeof this.postData.amount[index] === "undefined") this.postData.amount[index] = "";
            if (typeof this.postData.paid[index] === "undefined") this.postData.paid[index] = "";
            if (typeof this.postData.invoice[index] === "undefined") this.postData.invoice[index] = '';
            if (typeof this.postData.showExtra[index] === "undefined") this.postData.showExtra[index] = false;
            if (typeof this.postData.extraCategory[index] === "undefined") this.postData.extraCategory[index] = '';
        },

         handleExtraChange(index) {
    // if unchecked manually, clear selected header
    if (!this.postData.showExtra[index]) {
        this.postData.extraCategory.splice(index, 1, "");
        return;
    }

    // if checked manually and category has linked header, auto assign it
    const selectedCategoryId = this.postData.category[index];

    const selectedCategory = this.categories.find(
        category => Number(category.id) === Number(selectedCategoryId)
    );

    if (selectedCategory && selectedCategory.report_header_id) {
        this.postData.extraCategory.splice(index, 1, selectedCategory.report_header_id);
    }
},
        toggleHeader(index) {
            if (!this.postData.showExtra[index]) {
                this.postData.extraCategory[index] = "";
            }
        },
        buildCashBankRows(schedule) {
            return Object.entries(schedule || {}).reduce((rows, [terminalId, tickets]) => {
                if (!Array.isArray(tickets) || !tickets.length) return rows;

                const commission = this.totalOtherCommission(tickets);
                const total = Math.round(this.totalFare(tickets) - commission);
                const method = tickets[0]?.terminal?.recovery_method;

                rows[terminalId] = {
                    total,
                    commission,
                    cash: method === "cash" ? total : 0,
                    bank: method === "bank" ? total : 0,
                    shortage: 0,
                    selectedBankId: "",
                };

                return rows;
            }, {});
        },
        updateReceived(direction, terminalId, tickets, editedField) {
            const rows = direction === "start" ? this.cashBankStart : this.cashBankReturn;
            const row = rows[terminalId];
            if (!row) return;

            const total = Math.round(this.totalFare(tickets) - this.totalOtherCommission(tickets));
            let cash = Math.max(0, Number(row.cash) || 0);
            let bank = Math.max(0, Number(row.bank) || 0);

            if (cash + bank > total) {
                if (editedField === "cash") {
                    cash = Math.max(0, total - bank);
                } else {
                    bank = Math.max(0, total - cash);
                }
            }

            row.total = total;
            row.cash = cash;
            row.bank = bank;
            row.shortage = Math.max(0, total - cash - bank);
        },
        updateBank(direction, terminalId, tickets) {
            this.updateReceived(direction, terminalId, tickets, "bank");
        },
        syncPaid(index) {
            // Auto-fill Paid when Amount changes
            this.postData.paid[index] = this.postData.amount[index];
        },
        netAmountStart(id) {
            const row = this.cashBankStart[id];
            return row ? row.cash + row.bank : 0;
        },

        netAmountReturn(id) {
            const row = this.cashBankReturn[id];
            return row ? row.cash + row.bank : 0;
        },

        sumObject(obj, key) {
            return Object.values(obj).reduce(
                (sum, row) => sum + (Number(row[key]) || 0),
                0
            );
        },

        totalFare(tickets) {
            return tickets.reduce((sum, t) => {
                if (t.type != 'canceled') {
                    const fare = parseFloat(t.seat_fare) || 0;
                    const discount = parseFloat(t.discount) || 0;
                    const ticketELT = parseFloat(t?.elt?.elt_price) || 0;
                    // console.log("test", t.discount);
                    return sum + ((fare + ticketELT) - discount);
                } else {
                    const percentage = parseFloat(t?.cancel_ticket?.percentage) || 0;

                    const seatFare = parseFloat(t?.seat_fare) || 0;

                    const cancelAmount = (seatFare * percentage) / 100;
                    return sum + cancelAmount;
                }
            }, 0);

        },
        totalELT(tickets) {
            return tickets.reduce((sum, t) => {
                // Skip canceled tickets
                if (t.type == 'canceled') return sum;

                const ticketELT = parseFloat(t?.elt?.elt_price) || 0;
                return sum + ticketELT;
            }, 0);
        },

        totalCancelAmount(tickets) {
            return tickets.reduce((sum, t) => {
                const percentage = parseFloat(t?.cancel_ticket?.percentage) || 0;
                const seatFare = parseFloat(t?.seat_fare) || 0;

                const cancelAmount = (seatFare * percentage) / 100;

                return sum + cancelAmount;
            }, 0);
        },



        sumByRecovery(schedule, method) {
            if (!schedule) return 0;

            return Object.values(schedule).reduce((sum, tickets) => {
                const filtered = tickets.filter(
                    (t) => t.terminal?.recovery_method === method
                );

                return sum + parseFloat(this.totalFare(filtered));
            }, 0);
        },
        sumStartCash() {
            return this.sumByRecovery(this.data?.schedule_start, "cash");
        },

        sumStartBank() {
            return this.sumByRecovery(this.data?.schedule_start, "bank");
        },

        sumReturnCash() {
            return this.sumByRecovery(this.data?.schedule_return, "cash");
        },

        sumReturnBank() {
            return this.sumByRecovery(this.data?.schedule_return, "bank");
        },
        sumReceivable() {
            return Object.values(this.cashBankStart).reduce((sum, row) => {
                if (row.type == 'canceled') return sum; // skip canceled rows
                return sum + (Number(row.total) + Number(row.commission) || 0);
            }, 0);
        },

        sumCash() {
            return Object.values(this.cashBankStart).reduce((sum, row) => {
                if (row.type == 'canceled') return sum;
                return sum + (Number(row.cash) || 0);
            }, 0);
        },

        sumBank() {
            return Object.values(this.cashBankStart).reduce((sum, row) => {
                if (row.type == 'canceled') return sum;
                return sum + (Number(row.bank) || 0);
            }, 0);
        },

        sumShortage() {
            return Object.values(this.cashBankStart).reduce((sum, row) => {
                if (row.type == 'canceled') return sum;
                return sum + (Number(row.shortage) || 0);
            }, 0);
        },

        close() {
            $("#exampleModal").modal("hide");
        },
        sumReceived() {
            return Object.values(this.cashBankStart).reduce((sum, row) => {
                if (row.type == 'canceled') return sum; // skip canceled rows
                return sum + (Number(row.cash) || 0) + (Number(row.bank) || 0);
            }, 0);
        },

        netAmount(id) {
            const row = this.cashBank[id];
            if (!row) return 0;

            const shortage = Number(row.shortage || 0);
            const received = row.total - shortage;

            // hard guards
            if (received < 0) return 0;
            if (received > row.total) return row.total;

            return received;
        },
        sumReceivableReturn() {
            return Object.values(this.cashBankReturn).reduce(
                (sum, row) => sum + (Number(row.total) + Number(row.commission) || 0),
                0
            );
        },

        sumCashReturn() {
            return Object.values(this.cashBankReturn).reduce(
                (sum, row) => sum + (Number(row.cash) || 0),
                0
            );
        },

        sumBankReturn() {
            return Object.values(this.cashBankReturn).reduce(
                (sum, row) => sum + (Number(row.bank) || 0),
                0
            );
        },

        sumShortageReturn() {
            return Object.values(this.cashBankReturn).reduce(
                (sum, row) => sum + (Number(row.shortage) || 0),
                0
            );
        },

        sumReceivedReturn() {
            return Object.values(this.cashBankReturn).reduce(
                (sum, row) => sum + (Number(row.cash) || 0) + (Number(row.bank) || 0),
                0
            );
        },

        totalPassengers(data) {
            const groups = data || {};
            return Object.values(groups).reduce((sum, tickets) => {
                // Count only tickets that are not canceled
                const validTickets = tickets.filter(t => t.type != 'canceled');
                return sum + validTickets.length;
            }, 0);
        },

        totalCommissions(data) {
            const groups = data || {};
            return Object.values(groups).reduce((sum, tickets) => {
                // Only include tickets that are not canceled
                const validTickets = tickets.filter(t => t.type != 'canceled');
                return sum + this.totalCommission(validTickets);
            }, 0);
        },

        grandTotalELT(data) {
            const groups = data || {};
            return Object.values(groups).reduce((sum, tickets) => {
                // Only include tickets that are not canceled
                const validTickets = tickets.filter(t => t.type != 'canceled');
                return sum + this.totalELT(validTickets);
            }, 0);
        },

        grandTotalCancel(data) {
            const groups = data || {};
            return Object.values(groups).reduce(
                (sum, tickets) => sum + this.totalCancelAmount(tickets),
                0
            );
        },
        sumOtherCommissions(data) {
            const groups = data || {};
            return Object.values(groups).reduce((sum, tickets) => {
                // Only include tickets that are not canceled
                const validTickets = tickets.filter(t => t.type != 'canceled');
                return sum + this.totalOtherCommission(validTickets);
            }, 0);
        },
        totalAmounts(data) {
            const groups = data || {};
            return Object.values(groups).reduce(
                (sum, tickets) => sum + this.totalFare(tickets),
                0
            );
        },
        async fetchData() {
            const res = await this.callApi("post", "expenses/categories/getCategory");
            if (res.status == 200) {
                this.categories = res.data;
            }
        },
        async fetchReportsHeaders() {
            const res = await this.callApi("post", "expenses/categories/reportsHeaderExpense");

            if (res.status == 200) {
                this.reportsHeaders = Array.isArray(res.data)
                    ? res.data
                    : (res.data?.headers || []);
            }
        },
 handleCategoryChange(index) {
    const selectedCategoryId = this.postData.category[index];

    const selectedCategory = this.categories.find(
        category => Number(category.id) === Number(selectedCategoryId)
    );

    if (selectedCategory && selectedCategory.report_header_id) {
        this.postData.showExtra.splice(index, 1, true);
        this.postData.extraCategory.splice(index, 1, selectedCategory.report_header_id);
    } else {
        this.postData.showExtra.splice(index, 1, false);
        this.postData.extraCategory.splice(index, 1, "");
    }
},
        totalCommission(list) {
            return list.reduce((sum, t) => {
                // 1. Check if the ticket is canceled.
                // If it is, skip the calculation and return the current sum.
                if (t.type == 'canceled') {
                    return sum;
                }

                // 2. Otherwise, proceed with the calculation
                const adjPercent = Number(t.commission?.adjustment_commission || 0);

                // Adjustment % always on seat fare (after discount)
                const adjustment = (adjPercent / 100) * Number(t.seat_fare - t.discount);

                return sum + adjustment;
            }, 0);
        },
        receivable(terminalId, tickets) {
            return this.totalFare(tickets) - this.totalOtherCommission(tickets);
        },
        totalOtherCommission(list) {
            let fixCommission = 0;

            const value = list.reduce((sum, t) => {
                // 1. Skip if ticket is canceled
                if (t.type == 'canceled') {
                    return sum;
                }

                const fare = parseFloat(t.seat_fare || 0);
                const discount = parseFloat(t.discount) || 0;
                const afterDiscount = fare - discount;

                // Update fixCommission (only from active tickets)
                fixCommission = parseFloat(t.commission?.fix_commission || 0);

                const flat = parseFloat(t.commission?.flat_commission || 0);
                const percent = parseFloat(t.commission?.percentage_commission || 0);

                const flatOrPercentage = flat > 0 ? flat : (percent / 100) * afterDiscount;

                return sum + flatOrPercentage;
            }, 0);

            // Round the final result
            return Math.round(value + fixCommission);
        },
        saveRow(event, fieldName, index) {
            if (fieldName == "first") {
                this.postData.category[index] = event.target.value;

                const selectedCategory = this.categories.find(
                    (cat) => Number(cat.id) === Number(event.target.value)
                );

                if (selectedCategory && selectedCategory.report_header_id) {
                    this.postData.showExtra[index] = true;
                    this.postData.extraCategory[index] = selectedCategory.report_header_id;
                } else {
                    this.postData.showExtra[index] = false;
                    this.postData.extraCategory[index] = "";
                }
            }

            if (fieldName == "second") {
                this.postData.description[index] = event.target.value;
            }

            if (fieldName == "third") {
                this.postData.amount[index] = event.target.value;
            }

            if (fieldName == "fourth") {
                this.postData.invoice[index] = event.target.value;
            }

            if (fieldName == "five") {
                this.postData.paid[index] = event.target.value;
            }

            if (fieldName == "six") {
                this.postData.ledger[index] = event.target.checked;
            }

            // total amount sum only for show
            this.totalAmount = this.postData.amount.reduce(
                (a, b) => parseFloat(a || 0) + parseFloat(b || 0),
                0
            );

            this.netProfit = this.totalSale - this.totalAmount;
        },
         addRow() {
            this.loop++;

            this.postData.category.push('');
            this.postData.description.push('');
            this.postData.amount.push('');
            this.postData.paid.push('');
            this.postData.invoice.push('');
            this.postData.showExtra.push(false);
            this.postData.extraCategory.push('');

            this.totalAmount = (this.postData.amount || []).reduce((a, b) => {
                return parseFloat(a || 0) + parseFloat(b || 0);
            }, 0);

            this.netProfit = parseFloat(this.totalSale || 0) - parseFloat(this.totalAmount || 0);
        },

        removeRow(index) {
            if (this.loop <= 1) return;

            this.postData.category.splice(index, 1);
            this.postData.description.splice(index, 1);
            this.postData.amount.splice(index, 1);
            this.postData.paid.splice(index, 1);
            this.postData.invoice.splice(index, 1);
            this.postData.showExtra.splice(index, 1);
            this.postData.extraCategory.splice(index, 1);

            this.loop--;

            this.totalAmount = (this.postData.amount || []).reduce((a, b) => {
                return parseFloat(a || 0) + parseFloat(b || 0);
            }, 0);

            this.netProfit = parseFloat(this.totalSale || 0) - parseFloat(this.totalAmount || 0);
        },

        async saveHeaderLinks(ticketMergeId) {
            let headIds = [];
            let values = [];

            (this.postData.showExtra || []).forEach((checked, index) => {
                const selectedHead = this.postData.extraCategory?.[index];
                const amount = this.postData.amount?.[index];

                if (
                    checked === true &&
                    selectedHead !== null &&
                    selectedHead !== undefined &&
                    selectedHead !== ''
                ) {
                    headIds.push(selectedHead);
                    values.push(parseFloat(amount || 0));
                }
            });

            if (!headIds.length) return;

            const res = await this.callApi("post", "expenses/categories/expenseHeaderLink", {
                ticket_merge_id: ticketMergeId,
                headIds,
                values
            });

            this.ensureSuccessfulResponse(res, "Unable to save expense header links");
        },
        getApiError(response, fallback) {
            const data = response?.data || response || {};
            const errors = data.errors || data.Error;

            if (Array.isArray(errors)) return errors.join("\n");

            if (errors && typeof errors === "object") {
                const messages = Object.values(errors)
                    .flatMap((value) => Array.isArray(value) ? value : [value])
                    .filter(Boolean);

                if (messages.length) return messages.join("\n");
            }

            return data.message || fallback;
        },
        ensureSuccessfulResponse(response, fallback) {
            if (!response || ![200, 201].includes(response.status)) {
                throw new Error(this.getApiError(response, fallback));
            }
        },
        //  // ===== Merge Schedule API =====
        async mergeScheduleApi(addData = {}) {
            const payload = {
                ...addData,
                routes: this.routes,
                expenses: this.postData,
                busIds: addData.busIds?.length ? addData.busIds : this.busIds || [],
                mergeIds: addData.mergeIds?.length ? addData.mergeIds : this.mergeIds || [],
            };


            if (!payload.busIds.length || !payload.mergeIds.length) {
                Swal.fire({
                    icon: "error",
                    title: "Cannot merge",
                    text: "Bus IDs or Merge IDs are empty. Fetch unclosing data first.",
                });
                return null;
            }

            try {
                const res = await this.callApi(
                    "post",
                    "booking/close/schedule/closing/merge",
                    payload
                );

                if (res.status === 200 || res.status === 201) {
                    const mergedData = res.data || {};
                    this.mergedData = mergedData;

                    return mergedData;
                } else {
                    throw new Error(`Merge failed with status ${res.status}`);
                }
            } catch (error) {
                const errMsg =
                    error?.response?.data?.Error?.join("\n") || error.message || "Merge failed";
                Swal.fire({ icon: "error", title: "Merge Error", text: errMsg });
                throw new Error(errMsg);
            }
        },

        // ===== Save Ticket Closing Shortage =====
      async saveTicketClosingShortage() {
    this.loading = true;

    try {
        // Iterate over the currently displayed schedules so stale cache keys can
        // never add, remove, or replace Start/Return rows in the API payload.
        const mapRows = (schedule, cashBank) =>
            Object.entries(schedule || {}).reduce((rows, [terminalId, tickets]) => {
                if (!Array.isArray(tickets) || !tickets.length) return rows;

                const fallbackRow = this.buildCashBankRows({ [terminalId]: tickets })[terminalId];
                const row = cashBank[terminalId] || fallbackRow;
                const commission = this.totalOtherCommission(tickets);
                const total = Math.round(this.totalFare(tickets) - commission);
                const cash = Math.max(0, Number(row.cash) || 0);
                const bank = Math.max(0, Number(row.bank) || 0);
                const received = cash + bank;

                rows.push({
                    terminal_id: Number(terminalId),
                    passenger_count: tickets.filter(ticket => ticket.type !== "canceled").length,
                    kt_commission: this.totalCommission(tickets),
                    elt: this.totalELT(tickets),
                    cancellation_amount: this.totalCancelAmount(tickets),
                    total_receivable: total + commission,
                    other_commission: commission,
                    total_received_cash: cash,
                    bank_id: row.selectedBankId || null,
                    total_received_bank: bank,
                    shortage: Math.max(0, total - received),
                    received,
                });

                return rows;
            }, []);

        const startRows = mapRows(this.data?.schedule_start, this.cashBankStart);
        const returnRows = mapRows(this.data?.schedule_return, this.cashBankReturn);

        if (!startRows.length && !returnRows.length) {
            throw new Error("No current start or return schedule rows are available to save");
        }
        // ✅ STEP 1: Merge
        const mergedResult = await this.mergeScheduleApi(this.addData);

        if (!mergedResult || !mergedResult.id) {
            throw new Error("Merge failed: Invalid response");
        }

        this.closingData = mergedResult;

        // ✅ STEP 2: Header Links
        await this.saveHeaderLinks(mergedResult.id);
        // if this fails → it will automatically jump to catch

        // ✅ STEP 3: Prepare Data
        const payload = {
            ticket_closing_id: mergedResult.id,
            records: []
        };

        if (startRows.length) {
            payload.records.push({
                type: "start",
                route: this.routes.start,
                bus_id: this.busIds[0] || 0,
                rows: startRows,
            });
        }

        if (returnRows.length) {
            payload.records.push({
                type: "return",
                route: this.routes.return,
                bus_id: this.busIds[0] || 0,
                rows: returnRows,
            });
        }

        // ❗ EXTRA SAFETY (don’t hit API with empty data)
        if (!payload.records.length) {
            throw new Error("No records to save");
        }

        // ✅ STEP 4: Final API
        const saveResponse = await this.callApi(
            "post",
            "booking/close/schedule/closing/ticket-closing-shortage",
            payload
        );
        this.ensureSuccessfulResponse(saveResponse, "Unable to save ticket closing entries");

        // ✅ SUCCESS
        Swal.fire({
            icon: "success",
            title: "Saved!",
            text: "Ticket closing saved successfully",
            timer: 1500,
            showConfirmButton: false,
        });

        this.$emit('closingSaved');
        this.$emit('fetchData');
        this.closeexampleModal();

    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "Failed to save ticket closing"
        });

        // ❗ IMPORTANT: stop everything
        return;
    } finally {
        this.loading = false;
    }
},

        // async saveTicketClosingShortage() {
        //   this.loading = true;

        //   try {
        //     // Step 1 & 2: SKIP MERGE API
        //     // We create a dummy ID so the code doesn't break
        //     const dummyId = 999;

        //     // Step 3: Calculation Logic
        //     const mapRows = (cashBank, schedule) =>
        //       Object.entries(cashBank).map(([terminalId, row]) => {
        //         // Filter tickets to exclude canceled ones for accuracy
        //         const allTickets = schedule[terminalId] || [];
        //         const activeTickets = allTickets.filter(t => t.type !== 'canceled');

        //         return {
        //           terminal_id: Number(terminalId),
        //           passenger_count: activeTickets.length,
        //           kt_commission: this.totalCommission(allTickets),
        //           elt: this.totalELT(allTickets),
        //           cancellation_amount: this.totalCancelAmount(allTickets),
        //           // Calculation check
        //           total_receivable: row.total + row.commission,
        //           other_commission: row.commission || 0,
        //           total_received_cash: row.cash,
        //           bank_id: row.selectedBankId || null,
        //           total_received_bank: row.bank,
        //           shortage: row.shortage,
        //           received: row.cash + row.bank,
        //           mergeId: dummyId // Using dummy ID instead of mergedResult.id
        //         };
        //       });

        //     // Step 4: Prepare Payloads
        //     const startPayload = mapRows(this.cashBankStart, this.data.schedule_start);
        //     const returnPayload = mapRows(this.cashBankReturn, this.data.schedule_return);

        //     // LOG TO CONSOLE AS A TABLE (Easier to read than a list)
        //     console.log("--- START DATA CHECK ---");
        //     console.table(startPayload);

        //     console.log("--- RETURN DATA CHECK ---");
        //     console.table(returnPayload);

        //     // Show a message to confirm it's just a check
        //     Swal.fire({
        //       icon: "info",
        //       title: "Check Mode",
        //       text: "Check the browser console (F12) to see the calculated values.",
        //     });

        //   } catch (error) {
        //     console.error("Error during calculation check:", error);
        //   } finally {
        //     this.loading = false;
        //   }
        // },
        closeexampleModal() {
            $("#exampleModal").modal("hide");
        },
    },
     mounted() {
        this.ensureRowIndexes(0);
        console.log("reportsHeaders:", this.reportsHeaders);

    },
};
</script>
<style scoped>
.add-btn {
    width: 138px;
}

.header-td {
    width: 200px;
}

.totals tr td {
    font-size: 18px;
    font-weight: 700;
}

.totals tr th {
    font-size: 18px;
    font-weight: 700;
}

.border-r {
    border-top: 1px solid gray;
    border-bottom: 1px solid gray;
    font-weight: bold;
}

/* Chrome, Safari, Edge, Opera */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type="number"] {
    -moz-appearance: textfield;
}
</style>
