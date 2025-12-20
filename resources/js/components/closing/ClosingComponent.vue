<template>
  <div>
    <div
      class="modal fade"
      id="exampleModal"
      tabindex="-1"
      role="dialog"
      aria-labelledby="exampleModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="text-center" id="busModalLabel">
              Merge Schedule Details
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <div
              v-if="isLoading"
              class="d-flex flex-column align-items-center justify-content-center my-3"
            >
              <img
                class="loading-spinner"
                :src="$store.state.main_url + 'assets/img/loading-spinner.gif'"
                alt="Loading..."
                style="width: 20px; height: 20px"
              />
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
                        <th>Total Receivable</th>
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
                      <tr
                        v-for="(tickets, terminalId) in data.schedule_start"
                        :key="'start-' + terminalId"
                      >
                        <td>{{ tickets[0].terminal.name }}</td>
                        <td>{{ tickets.length }}</td>
                        <td>{{ totalCommission(tickets) }}</td>
                        <td>{{ totalFare(tickets) }}</td>
<!-- <td>
  <span v-if="tickets[0].terminal.recovery_method === 'cash'">
    {{ totalFare(tickets) }}
  </span>
  <span v-else>0</span>
</td>
<td>
  <span v-if="tickets[0].terminal.recovery_method === 'bank'">
    {{ totalFare(tickets) }}
  </span>
  <span v-else>0</span>
</td> -->


                        <td>
                          <input
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="cashBankStart[terminalId].cash"
                            @input="editingField = 'cash'"
                          />
                        </td>
                        <td>
                          <select
                            class="form-control rounded-0"
                            v-model="selectedBank"
                          >
                            <option value="" selected disabled>
                              Select Bank
                            </option>
                            <option
                              v-for="bank in banks"
                              :key="bank.id"
                              :value="bank.id"
                            >
                              {{ bank.text }}
                            </option>
                          </select>
                        </td>
                        <td>
                          <input
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="cashBankStart[terminalId].bank"
                            @input="editingField = 'bank'"
                          />
                        </td>

                        <td>
                          <input
                            type="number"
                            class="form-control"
                            v-model.number="cashBankStart[terminalId].shortage"
                            readonly
                          />
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
                          {{ totalCommissions(data.schedule_start) }}
                        </th>
                        <th>
                          {{ sumReceivable() }}
                        </th>
<!-- <th></th>
<th></th> -->

                        <th>
                          {{ sumCash() }}
                        </th>

                        <th></th>
                        <!-- Select Bank column -->

                        <th>
                          {{ sumBank() }}
                        </th>

                        <th>
                          {{ sumShortage() }}
                        </th>

                        <th>
                          {{ sumReceived() }}
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
                        <th>Total Receivable</th>
                        <th>Total Received in Cash</th>
                        <th>Select Bank</th>
                        <th>Total Received in Bank</th>
                        <th>Shortage</th>
                        <th>Received</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(tickets, terminalId) in data.schedule_return"
                        :key="'return-' + terminalId"
                      >
                        <td>{{ tickets[0].terminal.name }}</td>
                        <td>{{ tickets.length }}</td>
                        <td>{{ totalCommission(tickets) }}</td>
                        <td>{{ totalFare(tickets) }}</td>

                        <td>
                          <input
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="cashBankReturn[terminalId].cash"
                            @input="editingField = 'cash'"
                          />
                        </td>
<td>
                          <select
                            class="form-control rounded-0"
                            v-model="selectedBank"
                          >
                            <option value="" selected disabled>
                              Select Bank
                            </option>
                            <option
                              v-for="bank in banks"
                              :key="bank.id"
                              :value="bank.id"
                            >
                              {{ bank.text }}
                            </option>
                          </select>
                        </td>
                        <td>
                          <input
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="cashBankReturn[terminalId].bank"
                            @input="editingField = 'bank'"
                          />
                        </td>

                        <td>
                          <input
                            type="number"
                            class="form-control"
                            v-model.number="cashBankReturn[terminalId].shortage"
                            readonly
                          />
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
                          {{ totalCommissions(data.schedule_return) }}
                        </th>

                        <th>
                          {{ sumReceivableReturn() }}
                        </th>

                        <th>
                          {{ sumCashReturn() }}
                        </th>

                        <th></th>
                        <!-- Select Bank -->

                        <th>
                          {{ sumBankReturn() }}
                        </th>

                        <th>
                          {{ sumShortageReturn() }}
                        </th>

                        <th>
                          {{ sumReceivedReturn() }}
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
                        <!-- <th>Invoice number</th> -->
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(i, index) in loop" :key="index">
                        <td>
                          <!-- {{ items[0] ? items[0].price : '' }} -->
                          <select
                            class="form-control rounded-0"
                            @change="saveRow($event, 'first', index)"
                            :value="postData.category[index]"
                            :disabled="editAble"
                          >
                            <option value="" selected>Select Category</option>
                            <option
                              v-for="(category, i) in categories"
                              :value="category.id"
                              :key="i"
                            >
                              {{ category.name }}
                            </option>
                          </select>
                        </td>
                        <td>
                          <input
                            type="text"
                            class="form-control"
                            @keyup="saveRow($event, 'second', index)"
                            :value="postData.description[index]"
                            :disabled="editAble"
                          />
                        </td>
                        <td>
  <!-- Total Expense -->
  <input
    type="number"
    min="0"
    class="form-control rounded-0"
    v-model.number="postData.amount[index]"
    @input="syncPaid(index)"
  />
</td>

<td>
  <!-- Total Expense Paid -->
  <input
    type="number"
    min="0"
    class="form-control rounded-0"
    v-model.number="postData.paid[index]"
  />
</td>

<td>
  <!-- Balance -->
  <input
    type="number"
    class="form-control rounded-0"
    :value="balances[index]"
    readonly
  />
</td>


                        <!-- <td>
                          <input
                            type="text"
                            class="form-control"
                            @keyup="saveRow($event, 'fourth', index)"
                            :value="postData.invoice[index]"
                            disabled
                          />
                        </td> -->
                        <td class="add-btn" v-if="!editAble">
                          <button
                            class="btn btn-outline-primary mx-2"
                            @click="addRow"
                          >
                            Add
                          </button>
                          <button
                            class="btn btn-outline-danger"
                            @click="removeRow($event, index)"
                            v-if="loop != 1"
                          >
                            Remove
                          </button>
                        </td>
                        <td v-else></td>
                      </tr>
                      <!-- <tr class="mt-1">
                        <td></td>
                        <td>
                          <div class="form-group">
                            <label for="totalNums">Total Sale</label>
                            <input
                              id="totalSale"
                              type="text"
                              class="form-control mr-4"
                              disabled
                              :value="totalSale"
                            />
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <label for="totalNums">Total Amount</label>
                            <input
                              id="totalNums"
                              type="text"
                              class="form-control mr-4"
                              disabled
                              :value="totalAmount"
                            />
                          </div>
                        </td>
                        <td>
                          <div class="form-group">
                            <label for="netProfit">Net Profit</label>
                            <input
                              id="netProfit"
                              type="text"
                              class="form-control mr-4"
                              disabled
                              :value="netProfit"
                            />
                          </div>
                        </td>
                        <td></td>
                      </tr> -->
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
                        <td>{{ sumStartCash() + sumReturnCash() }}</td>
                      </tr>
                      <tr>
                        <th>Total Receivable in Bank</th>
                        <td>{{ sumStartBank() + sumReturnBank() }}</td>
                      </tr>
                      <!-- <tr class="border-r">
                        <th>Total Receivable</th>
                        <td>{{ sumReceivable() + sumReceivableReturn() }}</td>
                      </tr> -->
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
                        <td>{{ sumCash() + sumCashReturn() }}</td>
                      </tr>
                      <tr>
                        <th>Total Received in Bank</th>
                        <td>{{ sumBank() + sumBankReturn() }}</td>
                      </tr>
                      <!-- <tr class="border-r">
                        <th>Total</th>
                        <td>{{ grandTotal }}</td>
                      </tr> -->
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
                        <th>Total receivable</th>
                        <td>{{ sumReceivable() + sumReceivableReturn() }}</td>
                      </tr>
                      <tr>
                        <th>Total received</th>
                        <td>{{ grandTotal }}</td>
                      </tr>
                      <tr>
                        <th>Total Shortage</th>
                        <td>{{ sumShortage() + sumShortageReturn() }}</td>
                      </tr>
                      <!-- <tr class=" border-r">
                        <th>Total
                        </th>
                        <td>
                          {{ total }}
                        </td>
                      </tr> -->
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
                            totalCommissions(data.schedule_return) +
                            totalCommissions(data.schedule_start)
                          }}
                        </td>
                      </tr>
                      <tr>
                        <th>Total Paid Expenses</th>
                        <td>{{ totalAmount - totalExpenses }}</td>
                      </tr>
                      <tr>
                        <th>Total Credit Expenses</th>
                        <td>{{ totalExpenses }}</td>
                      </tr>
                      <tr class="border-r">
                        <th>Total Expense</th>
                        <td>{{ grandTotalexpense }}</td>
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
                        <td>{{ sumReceivable() + sumReceivableReturn() }}</td>
                      </tr>
                      <tr>
                        <th>Total Expenses</th>
                        <td>{{ grandTotalexpense }}</td>
                      </tr>
                      <tr class="border-r">
                        <th>Total</th>
                        <td>
                          {{
                            sumReceivable() +
                            sumReceivableReturn() -
                            grandTotalexpense
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
                        <td>{{ sumCash() + sumCashReturn() }}</td>
                      </tr>
                      <tr>
                        <th>Total Paid Expenses</th>
                        <td>{{ totalPaid }}</td>
                      </tr>
                      <tr class="border-r">
                        <th>Total Cash in Hand</th>
                        <td>
                          {{ sumCash() + sumCashReturn() - totalPaid }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Close
            </button>
            <button type="button" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["data", "banks"],
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
      },
      loop: 1,
      loading: false,
      cashBank: {},
      editingField: null, // 'cash' | 'bank'
      cashBankStart: {},
      cashBankReturn: {},
    };
  },
  watch: {
    cashBankStart: {
      deep: true,
      handler(val) {
        this.validateCashBank(val);
      },
    },

    cashBankReturn: {
      deep: true,
      handler(val) {
        this.validateCashBank(val);
      },
    },

    "data.schedule_start": {
      immediate: true,
      deep: true,
      handler(val) {
        if (!val) return;

        Object.entries(val).forEach(([terminalId, tickets]) => {
          if (this.cashBankStart[terminalId]) return;

          const total = this.totalFare(tickets);
          const method = tickets[0].terminal.recovery_method;

          this.cashBankStart[terminalId] = {
            total,
            cash: method === "cash" ? total : 0,
            bank: method === "bank" ? total : 0,
            shortage: 0,
          };
        });
      },
    },
    "data.schedule_return": {
      immediate: true,
      deep: true,
      handler(val) {
        if (!val) return;

        Object.entries(val).forEach(([terminalId, tickets]) => {
          if (this.cashBankReturn[terminalId]) return;

          const total = this.totalFare(tickets);
          const method = tickets[0].terminal.recovery_method;

          this.cashBankReturn[terminalId] = {
            total,
            cash: method === "cash" ? total : 0,
            bank: method === "bank" ? total : 0,
            shortage: 0,
          };
        });
      },
    },
  },
  async created() {
    this.fetchData();
  },
  computed: {
    totalCash() {
      return (
        this.sumObject(this.cashBankStart, "cash") +
        this.sumObject(this.cashBankReturn, "cash")
      );
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
        this.totalCommissions(this.data.schedule_start || {})
      );
    },

    totalAmount() {
      return this.postData.amount.reduce(
        (sum, val) => sum + (Number(val) || 0),
        0
      );
    },

    totalPaid() {
      return this.postData.paid.reduce(
        (sum, val) => sum + (Number(val) || 0),
        0
      );
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
      syncPaid(index) {
      // Auto-fill Paid when Amount changes
      this.postData.paid[index] = this.postData.amount[index];
    },
    validateCashBank(obj) {
      Object.values(obj).forEach((row) => {
        const total = Number(row.total) || 0;
        let cash = Number(row.cash) || 0;
        let bank = Number(row.bank) || 0;

        if (cash + bank > total) {
          if (this.editingField === "cash") {
            cash = total - bank;
          } else if (this.editingField === "bank") {
            bank = total - cash;
          }
        }

        row.cash = Math.max(0, cash);
        row.bank = Math.max(0, bank);
        row.shortage = total - (row.cash + row.bank);
      });
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
      return tickets.reduce((s, t) => s + Number(t.fare || 0), 0);
    },

    totalCommission(tickets) {
      return tickets.reduce((s, t) => s + Number(t.commission || 0), 0);
    },

    sumByRecovery(schedule, method) {
      if (!schedule) return 0;

      return Object.values(schedule).reduce((sum, tickets) => {
        const filtered = tickets.filter(
          (t) => t.terminal?.recovery_method === method
        );
        return sum + this.totalFare(filtered);
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
    return Object.values(this.cashBankStart).reduce(
      (sum, row) => sum + (Number(row.total) || 0),
      0
    );
  },

    sumCash() {
    return Object.values(this.cashBankStart).reduce(
      (sum, row) => sum + (Number(row.cash) || 0),
      0
    );
  },

    sumBank() {
    return Object.values(this.cashBankStart).reduce(
      (sum, row) => sum + (Number(row.bank) || 0),
      0
    );
  },

    sumShortage() {
    return Object.values(this.cashBankStart).reduce(
      (sum, row) => sum + (Number(row.shortage) || 0),
      0
    );
  },

   sumReceived() {
    return Object.values(this.cashBankStart).reduce(
      (sum, row) =>
        sum + (Number(row.cash) || 0) + (Number(row.bank) || 0),
      0
    );
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
      (sum, row) => sum + (Number(row.total) || 0),
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
      (sum, row) =>
        sum + (Number(row.cash) || 0) + (Number(row.bank) || 0),
      0
    );
  },

    totalPassengers(data) {
      const groups = data || {};
      return Object.values(groups).reduce(
        (sum, tickets) => sum + tickets.length,
        0
      );
    },

    totalCommissions(data) {
      const groups = data || {};
      return Object.values(groups).reduce(
        (sum, tickets) => sum + this.totalCommission(tickets),
        0
      );
    },
    totalCommissions(data) {
      const groups = data || {};
      return Object.values(groups).reduce(
        (sum, tickets) => sum + this.totalCommission(tickets),
        0
      );
    },

    totalAmounts(data) {
      const groups = data || {};
      return Object.values(groups).reduce(
        (sum, tickets) => sum + this.totalFare(tickets),
        0
      );
    },
    async fetchData() {
      const res = await this.callApi("post", "expenses/categories");
      if (res.status == 200) {
        this.categories = res.data;
      }
    },
    // ===== TOTALS =====
    totalFare(group) {
      if (!Array.isArray(group)) return 0;

      return group.reduce((sum, item) => sum + Number(item.seat_fare || 0), 0);
    },
    totalCommission(list) {
      return list.reduce((sum, t) => {
        // const fix = Number(t.commission?.fix_commission || 0);
        // const flat = Number(t.commission?.flat_commission || 0);
        // const percent = Number(t.commission?.percentage_commission || 0);
        const adjPercent = Number(t.commission?.adjustment_commission || 0);

        // // If flat > 0 use flat, else percentage
        // const flatOrPercentage = flat > 0
        //     ? flat
        //     : (percent / 100) * Number(t.seat_fare);

        // Adjustment % always on seat fare
        const adjustment =
          (adjPercent / 100) * Number(t.seat_fare - t.discount);

        // return sum + fix + flatOrPercentage + adjustment;
        return sum + adjustment;
      }, 0);
    },
    saveRow(event, fieldName, index) {
      // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
      if (fieldName == "first") {
        this.postData.category[index] = event.target.value;
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
        (a, b) => parseFloat(a) + parseFloat(b),
        0
      );
      this.netProfit = this.totalSale - this.totalAmount;
    },
    addRow() {
      this.loop++;
    },
    removeRow(event, index) {
      this.postData.category.splice(index, 1);
      this.postData.description.splice(index, 1);
      this.postData.amount.splice(index, 1);
      this.postData.paid.splice(index, 1);
      this.postData.invoice.splice(index, 1);
      this.loop--;

      // total amount sum only for show
      this.totalAmount = this.postData.amount.reduce(
        (a, b) => parseFloat(a) + parseFloat(b),
        0
      );
      this.netProfit = this.totalSale - this.totalAmount;
    },
  },
};
</script>
<style scoped>
.add-btn {
  width: 164px;
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