<template>
  <section class="section">
    <div v-if="isLoading" class="d-flex flex-column align-items-center justify-content-center my-3">
      <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'" alt="Loading..."
        style="width: 20px; height: 20px" />
      <small class="text-muted mt-1">Loading bus data...</small>
    </div>
    <div class="section-body" v-else>
      <div class="row text-white">
        <!-- Total Shortage -->
        <div class="col-md-3 mb-3">
          <div class="card stat-card danger">
            <div class="card-body">
              <div class="stat-header">
                <i class="fas fa-arrow-down"></i>
                <span>Total Shortage</span>
              </div>
              <h4 class="stat-value">
                {{ $insertComma(totalShortage) }}
              </h4>
            </div>
          </div>
        </div>

        <!-- Total Cash -->
        <div class="col-md-3 mb-3">
          <div class="card stat-card success">
            <div class="card-body">
              <div class="stat-header">
                <i class="fas fa-money-bill-wave"></i>
                <span>Total Cash</span>
              </div>
              <h4 class="stat-value">
                {{ $insertComma(totalCash) }}
              </h4>
            </div>
          </div>
        </div>

        <!-- Total Bank -->
        <div class="col-md-3 mb-3">
          <div class="card stat-card primary">
            <div class="card-body">
              <div class="stat-header">
                <i class="fas fa-university"></i>
                <span>Total Bank</span>
              </div>
              <h4 class="stat-value">
                {{ $insertComma(totalBank) }}
              </h4>
            </div>
          </div>
        </div>

        <!-- Profit & Loss -->
        <div class="col-md-3 mb-3">
          <div class="card stat-card warning">
            <div class="card-body">
              <div class="stat-header">
                <i class="fas fa-chart-line"></i>
                <span>Profit & Loss</span>
              </div>
              <h4 class="stat-value">
                {{ $insertComma(profitLoss) }}
              </h4>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Closing Date -->
        <div class="col-md-3 mb-3">
          <div class="card stat-card info">
            <div class="card-body">
              <div class="stat-header">
                <i class="far fa-calendar-check"></i>
                <span>Closing Date</span>
              </div>
              <h4 class="stat-value">
                {{ details?.closing_date || "N/A" }}
              </h4>
            </div>
          </div>
        </div>
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
          <div class="card trip-card departure">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div class="trip-header">
                  <i class="fas fa-bus"></i>
                  <span>Departure</span>
                </div>
                <div class="text-right">
                  <button class="btn btn-print departure" @click="printInvoice('departure')">
                    <i class="fas fa-print"></i>
                  </button>
                </div>
              </div>
              <h2 class="bus-number">
                {{ details?.bus?.bus_number || "N/A" }}
              </h2>

              <ul class="trip-info">
                <li>
                  <i class="far fa-calendar-alt text-success"></i>
                  <strong>Date:</strong>
                  {{ details?.schedule_departure_date || "N/A" }}
                </li>

                <li>
                  <i class="fas fa-clock text-warning"></i>
                  <strong>Schedule:</strong>
                  {{ details?.closing?.[0]?.schedule?.name || "N/A" }}
                </li>

                <li>
                  <i class="fas fa-route text-info"></i>
                  <strong>Route:</strong>
                  {{ details?.closing?.[0]?.schedule?.route?.name || "N/A" }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
          <div class="card trip-card return">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div class="trip-header mb-0">
                  <i class="fas fa-undo-alt"></i>
                  <span>Return</span>
                </div>

                <button class="btn btn-print return" @click="printInvoice('return')">
                  <i class="fas fa-print"></i>
                </button>
              </div>

              <h2 class="bus-number">
                {{ details?.bus?.bus_number || "N/A" }}
              </h2>

              <ul class="trip-info">
                <li>
                  <i class="far fa-calendar-alt text-success"></i>
                  <strong>Date:</strong>
                  {{ details?.schedule_return_date || "N/A" }}
                </li>

                <li>
                  <i class="fas fa-clock text-warning"></i>
                  <strong>Schedule:</strong>
                  {{ details?.closing?.[1]?.schedule?.name || "N/A" }}
                </li>

                <li>
                  <i class="fas fa-route text-info"></i>
                  <strong>Route:</strong>
                  {{ details?.closing?.[1]?.schedule?.route?.name || "N/A" }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card py-2">
            <div class="card-header justify-content-between">
              <h5>Terminal Details</h5>
              <button type="button" class="btn btn-print mr-4" @click="print">
                <span> <i class="fas fa-print mr-2"></i>Eng Print </span>
              </button>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-12">
                  <h5 class="text-center">Departure</h5>

                  <table class="table table-bordered table-sm">
                    <thead>
                      <tr>
                        <th>Terminal Name</th>
                        <th>Passenger Count</th>
                        <th>KT Comm</th>
                        <th>ELT</th>
                        <th>Cancellation Amount</th>
                        <th>Receivable</th>
                        <th>Other Comm</th>
                        <th>Net Sale</th>
                        <th>Received in Cash</th>
                        <th>Select Bank</th>
                        <th>Received in Bank</th>
                        <th>Shortage</th>
                        <th>Received</th>
                        <th>Action</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr v-for="(item, index) in startShortages" :key="'start-' + index">
                        <td>{{ item?.terminal?.name }}</td>
                        <td>{{ $insertComma(item.passenger_count) }}</td>
                        <td>{{ $insertComma(item.kt_commission) }}</td>
                        <td>{{ $insertComma(item.elt) }}</td>
                        <td>{{ $insertComma(item.cancellation_amount) }}</td>
                        <td>{{ $insertComma(item.total_receivable) }}</td>
                        <td>{{ $insertComma(item.other_commission) }}</td>
                        <td>{{ $insertComma(parseFloat(item.total_receivable) - (parseFloat(item.kt_commission) +
                          parseFloat(item.other_commission)) )}}</td>
                        <td>
                          <input v-if="isEditing(item)" type="number" class="form-control form-control-sm"
                            v-model.number="item.total_received_cash" />
                          <span v-else>
                            {{ item.total_received_cash }}
                          </span>
                        </td>

                        <td>
                          <select v-if="isEditing(item)" v-model="item.bank_id" class="form-control form-control-sm">
                            <option value="">Select Bank</option>
                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                              {{ bank.name }}
                            </option>
                          </select>

                          <span v-else>
                            {{ item?.bank?.name || "-" }}
                          </span>
                        </td>

                        <td>
                          <input v-if="isEditing(item)" type="number" class="form-control form-control-sm"
                            v-model.number="item.total_received_bank" />
                          <span v-else>
                            {{ item.total_received_bank }}
                          </span>
                        </td>

                        <td :class="{ 'text-danger': Number(item.shortage) > 0 }">
                          {{ item.shortage }}
                        </td>

                        <td>{{ item.received }}</td>
                        <td class="text-nowrap">
                          <button v-if="!isEditing(item)" class="btn btn-sm btn-primary" @click="startEdit(item)">
                            Edit
                          </button>

                          <template v-else>
                            <button class="btn btn-sm btn-success mr-1" @click="saveEdit(item)">
                              Save
                            </button>

                            <button class="btn btn-sm btn-secondary" @click="cancelEdit(item)">
                              Cancel
                            </button>
                          </template>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="font-weight-bold bg-light">
                        <td>Totals</td>
                        <td>
                          {{ $insertComma(startTotals.totalPassengerCount) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalktCommission) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalELT) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalCancellationAmount) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalReceivables) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalOtherCommission) }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalReceivables - (startTotals.totalOtherCommission +
                          startTotals.totalktCommission))
                          }}
                        </td>
                        <td>
                          {{ $insertComma(startTotals.totalReceivedCash) }}
                        </td>
                        <td></td>
                        <!-- bank select column -->
                        <td>
                          {{ $insertComma(startTotals.totalReceivedBank) }}
                        </td>
                        <td>{{ $insertComma(startTotals.totalShortage) }}</td>
                        <td>{{ $insertComma(startTotals.totalReceived) }}</td>
                        <td></td>
                        <!-- Action column -->
                      </tr>
                    </tfoot>
                  </table>

                  <h5 class="text-center">Return</h5>

                  <table class="table table-bordered table-sm">
                    <thead>
                      <tr>
                        <th>Terminal Name</th>
                        <th>Passenger Count</th>
                        <th>KT Comm</th>
                        <th>ELT</th>
                        <th>Cancellation Amount</th>
                        <th>Receivable</th>
                        <th>Other Comm</th>
                        <th>Net sale</th>
                        <th>Received in Cash</th>
                        <th>Select Bank</th>
                        <th>Received in Bank</th>
                        <th>Shortage</th>
                        <th>Received</th>
                        <th>Action</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr v-for="(item, index) in returnShortages" :key="'return-' + index">
                        <td>{{ item?.terminal?.name }}</td>
                        <td>{{ $insertComma(item.passenger_count) }}</td>
                        <td>{{ $insertComma(item.kt_commission) }}</td>
                        <td>{{ $insertComma(item.elt) }}</td>
                        <td>{{ $insertComma(item.cancellation_amount) }}</td>
                        <td>{{ $insertComma(item.total_receivable) }}</td>
                        <td>{{ $insertComma(item.other_commission) }}</td>
                        <td>{{ $insertComma(parseFloat(item.total_receivable) - (parseFloat(item.kt_commission) +
                          parseFloat(item.other_commission)) )}}</td>

                        <td>
                          <input v-if="isEditing(item)" type="number" class="form-control form-control-sm"
                            v-model.number="item.total_received_cash" />
                          <span v-else>
                            {{ item.total_received_cash }}
                          </span>
                        </td>

                        <td>
                          <select v-if="isEditing(item)" v-model="item.bank_id" class="form-control form-control-sm">
                            <option value="">Select Bank</option>
                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                              {{ bank.name }}
                            </option>
                          </select>

                          <span v-else>
                            {{ item?.bank?.name || "-" }}
                          </span>
                        </td>

                        <td>
                          <input v-if="isEditing(item)" type="number" class="form-control form-control-sm"
                            v-model.number="item.total_received_bank" />
                          <span v-else>
                            {{ item.total_received_bank }}
                          </span>
                        </td>

                        <td :class="{ 'text-danger': Number(item.shortage) > 0 }">
                          {{ item.shortage }}
                        </td>

                        <td>{{ item.received }}</td>
                        <td class="text-nowrap">
                          <button v-if="!isEditing(item)" class="btn btn-sm btn-primary" @click="startEdit(item)">
                            Edit
                          </button>

                          <template v-else>
                            <button class="btn btn-sm btn-success mr-1" @click="saveEdit(item)">
                              Save
                            </button>

                            <button class="btn btn-sm btn-secondary" @click="cancelEdit(item)">
                              Cancel
                            </button>
                          </template>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="font-weight-bold bg-light">
                        <td>Totals</td>
                        <td>
                          {{ $insertComma(returnTotals.totalPassengerCount) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalktCommission) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalELT) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalCancellationAmount) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalReceivables) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalOtherCommission) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalReceivables - (returnTotals.totalOtherCommission +
                          returnTotals.totalktCommission)) }}
                        </td>
                        <td>
                          {{ $insertComma(returnTotals.totalReceivedCash) }}
                        </td>
                        <td></td>
                        <!-- bank select column -->
                        <td>
                          {{ $insertComma(returnTotals.totalReceivedBank) }}
                        </td>
                        <td>{{ $insertComma(returnTotals.totalShortage) }}</td>
                        <td>{{ $insertComma(returnTotals.totalReceived) }}</td>
                        <td></td>
                        <!-- Action column -->
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12 col-md-12">
          <div class="card p-3">
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
    </tr>
  </thead>
  <tbody>
    <tr v-for="(i, index) in loop" :key="index">
      <td>
        <select class="form-control rounded-0" @change="saveRow($event, 'first', index)"
          :value="postData.category[index]" :disabled="editAble">
          <option value="" selected>Select Category</option>
          <option v-for="(category, i) in categories" :value="category.id" :key="i">
            {{ category.name }}
          </option>
        </select>
      </td>
      <td>
        <input type="text" class="form-control" @keyup="saveRow($event, 'second', index)"
          :value="postData.description[index]" :disabled="editAble" />
      </td>
      <td>
        <input type="number" min="0" class="form-control rounded-0" v-model.number="postData.amount[index]"
          @input="syncPaid(index)" />
      </td>
      <td>
        <input type="number" min="0" class="form-control rounded-0" v-model.number="postData.paid[index]" />
      </td>
      <td>
        <input type="number" class="form-control rounded-0" :value="balances[index]" readonly />
      </td>
      <td class="add-btn" v-if="!editAble">
        <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
        <button class="btn btn-outline-danger" @click="removeRow($event, index)" v-if="loop != 1">Remove</button>
      </td>
      <td v-else></td>
    </tr>
  </tbody>

  <!-- Footer with sums -->
  <tfoot>
    <tr>
      <th colspan="2">Total</th>
    <th>{{ $insertComma(totalAmount) }}</th>
<th>{{ $insertComma(totalPaid) }}</th>
<th>{{ $insertComma(totalBalance) }}</th>

      <th></th>
    </tr>
  </tfoot>
</table>

            <div class="d-flex justify-content-end">
              <div v-if="totalShortage == 0">
                <button v-if="!checkClosing" type="button" class="text-light btn btn-danger mr-1"
                  data-target="#accountModal" data-toggle="modal" :disabled="loading">
                  Update Account
                </button>
              </div>
              <button type="button" class="btn btn-outline-success mr-4" @click="add" :disabled="loading"
                v-if="!editAble">
                {{ loading ? "Loading..." : "Save" }}
              </button>
              <button type="button" class="btn btn-outline-secondary mr-4" @click="editAble = false" :disabled="loading"
                v-else>
                Edit
              </button>
              <button type="button" class="btn btn-outline-primary mr-4" @click="editAble = true"
                v-if="!editAble && postData.category.length != 0">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!--Daily Summery Report Form-->
      <form :action="$store.state.api_url + 'api/web/v1/print/pdf/daily/summary/report'
        " method="POST" ref="refDailySummaryReport" target="_blank">
        <input type="hidden" name="token" :value="this.$store.state.token" />
        <input type="hidden" name="ticket_merge_id" :value="this.postData.ticket_merge_id" />
      </form>

      <div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-body pt-5">
              <div class="card card-danger">
                <div class="card-header d-flex justify-content-between">
                  <h4 class="modal-title text-center text-danger" style="width: 97%">
                    <i class="fas fa-exclamation-circle fa-2x"></i> Confirmation
                  </h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeModal()">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="card-body text-center">
                  <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="success">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" @click="closeModal()">
                      <span aria-hidden="true">&times;</span>
                      <span class="sr-only">Close</span>
                    </button>
                  </div>
                  <p class="font-weight-bold">
                    You can't edit this once you close the summary. Do you want
                    to procceed ?
                  </p>
                </div>
              </div>
            </div>
            <div class="modal-footer d-block pt-0">
              <button type="button" class="btn btn-danger btn-block" data-dismiss="modal" :disabled="loading"
                @click="updateAccount">
                Yes
              </button>
              <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal" @click="closeModal()">
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form :action="$store.state.api_url + 'api/web/v1/print/pdf/bus/invoice'" method="POST" ref="refBusInvoice"
      target="_blank">
      <input type="hidden" name="token" :value="this.$store.state.token" />
      <input type="hidden" name="destination_city_id" :value="addForm.destinationCity" />
      <input type="hidden" name="departure_city_id" :value="addForm.departureCity" />
      <input type="hidden" name="date" :value="addForm.date" />
      <input type="hidden" name="schedule_id" :value="addForm.schedule" />
      <input type="hidden" name="departure_time" :value="addForm.departure_time" />
    </form>
  </section>
</template>

<script>
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import { mapGetters } from "vuex";

export default {
  name: "expense",
  components: {
    // Add,
    // Edit,
    // Delete,
  },
  data() {
    return {
      csrf: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content"),
      validationErrors: [],
      editAble: true,
      categories: [],
      isLoading: true,
      formID: "expense_form",
      editFormID: "edit_expense_form",
      // deleteFormID:'delete_city_form',
      totalAmount: 0,
      totalSale: 0,
      netProfit: 0,
      netProfit: 0,
      checkClosing: true,
      postData: {
        ticket_merge_id: "",
        category: [],
        description: [],
        amount: [],
        paid: [],
        ledger: [],
        invoice: [],
      },
      success: false,
      errors: false,
      loop: 1,
      shortages: [],
      editingRowId: null,
      editCache: {},
      expenses: [],
      details: [],
      addForm: {
        destinationCity: "",
        departureCity: "",
        date: "",
        schedule: "",
        departure_time: "",
      },
    };
  },
  async created() {
    $(".modal").remove();
    const currentRouteName = this.$route.name;
    if (currentRouteName == "booking-page") {
      window.addEventListener("keydown", this.enterKey);
      window.addEventListener("keydown", this.altM);
    } else {
      window.removeEventListener("keydown", this.enterKey);
      window.removeEventListener("keydown", this.altM);
    }
    this.postData.ticket_merge_id = this.$route.params.id;
    this.fetchData();
    this.existingExpenses();
    setTimeout(function () {
      $("#expense_table").DataTable();
    }, 300);
    // total amount sum only for show
    this.totalAmount = this.postData.amount.reduce(
      (a, b) => parseFloat(a) + parseFloat(b),
      0
    );
  },

  methods: {

    printInvoice(type) {
      const invoiceType = type == "departure" ? 0 : 1; // 'departure' or 'return'
      this.addForm = {
        destinationCity: this.details.closing[invoiceType].schedule_end,
        departureCity: this.details.closing[invoiceType].schedule_start,
        date: this.details.closing[invoiceType].schedule_date,
        schedule: this.details.closing[invoiceType].schedule_id,
        departure_time: this.details.closing[invoiceType].schedule_time,
      };
      this.$nextTick(() => {
        this.$refs.refBusInvoice.submit();
      });
    },
    async getBusInvoice() {
      const resCheckedBus = await this.callApi(
        "post",
        "booking/check/bus/assigned",
        {
          scheduleId: this.details.closing[0].schedule_id,
          date: this.details.closing[0].schedule_date,
          departureCity: this.details.closing[0].schedule_start,
          destinationCity: this.details.closing[0].schedule_end,
        }
      );

      if (resCheckedBus.status == 200) {
      } else {
        return swal({
          title: "OOPS!!",
          text: "Please Assign Bus First!",
          icon: "error",
          timer: 2000,
        });
      }
    },
    startEdit(item) {
      this.editingRowId = item.id;

      // clone row values (so cancel works)
      this.editCache[item.id] = {
        total_received_cash: item.total_received_cash,
        bank_id: item.bank_id,
        total_received_bank: item.total_received_bank,
      };
    },

    cancelEdit(item) {
      const cache = this.editCache[item.id];

      item.total_received_cash = cache.total_received_cash;
      item.bank_id = cache.bank_id;
      item.total_received_bank = cache.total_received_bank;

      this.editingRowId = null;
      delete this.editCache[item.id];
    },

    async saveEdit(item) {
      // 👉 API call here
      // axios.post('/save-shortage', item)
      const res = await this.callApi(
        "post",
        "booking/close/schedule/closing/ticket-closing-shortage/update",
        item
      );
      if (res.status == 200) {
        this.existingExpenses();
        swal({
          title: "Success",
          text: "Data Updated Successfully",
          icon: "success",
          timer: 2000,
        });
      }
      this.editingRowId = null;
      delete this.editCache[item.id];
    },

    isEditing(item) {
      return this.editingRowId === item.id;
    },
    closeModal() {
      $("#accountModal").click();
    },
    clearForm: function () {
      this.data = {};
    },
    async fetchData() {
      const res = await this.callApi("post", "expenses/categories");
      if (res.status == 200) {
        this.categories = res.data;
      }
    },
    async existingExpenses() {
      this.isLoading = true;
      const res = await this.callApi("post", "expenses", {
        ticket_merge_id: this.postData.ticket_merge_id,
      });
      if (res.status == 200) {
        const expenses = res.data.expenses;
        this.expenses = expenses;
        this.totalSale = res.data.sale;
        this.checkClosing = res.data.closing;
        this.shortages = res.data.shortage;
        this.details = res.data.details;

        if (expenses != "") {
          this.loop = expenses.length;
          for (var i = 0; i < expenses.length; i++) {
            this.postData.category.push(expenses[i].expense_category_id);
            this.postData.description.push(expenses[i].description);
            this.postData.amount.push(expenses[i].amount);
            this.postData.paid.push(expenses[i].paid);
            this.postData.ledger.push(expenses[i].ledger == 1 ? true : false);
            this.postData.invoice.push(expenses[i].invoice);
          }
          this.totalAmount = this.postData.amount.reduce(
            (a, b) => parseFloat(a) + parseFloat(b),
            0
          );
          this.netProfit = this.totalSale - this.totalAmount;
        } else {
          this.loop = 1;
          this.editAble = false;
        }
      }
      this.isLoading = false;
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
  async print() {
  // Show loader
  this.loading = true;

  // Validation for empty data
  if (
    !this.postData.ticket_merge_id ||
    this.postData.category.length === 0 ||
    this.postData.description.length === 0 ||
    this.postData.amount.length === 0
  ) {
    this.loading = false; // hide loader if validation fails
    return;
  }

  // Check if any index is empty or null in object
  for (let i = 0; i < this.postData.category.length; i++) {
    if (
      !this.postData.category[i] ||
      !this.postData.description[i] ||
      !this.postData.amount[i]
    ) {
      this.loading = false; // hide loader if validation fails
      return;
    }
  }

  try {
    if (this.$refs.refDailySummaryReport) {
      this.$refs.refDailySummaryReport.submit();
    }

    // Optional: hide loader after a short delay if needed
    setTimeout(() => {
      this.loading = false;
    }, 1000);

  } catch (error) {
    console.error("Print error:", error);
    this.loading = false; // hide loader on error
  }
},
    async add() {
      // validation for empty data
      if (
        !this.postData.ticket_merge_id ||
        this.postData.category.length == 0 ||
        this.postData.description.length == 0 ||
        this.postData.amount.length == 0
      ) {
        return swal({
          title: "Error",
          text: "Please Fill All Field",
          icon: "error",
          timer: 2000,
        });
      }

      // check if any index is empty or null in object
      for (var i = 0; i < this.postData.category.length; i++) {
        if (
          !this.postData.category[i] ||
          !this.postData.description[i] ||
          !this.postData.amount[i]
        ) {
          return swal({
            title: "Error",
            text: "Please Fill All Field Or Remove Extra",
            icon: "error",
            timer: 2000,
          });
        }
      }

      this.loading = true;
      const res = await this.callApi("post", "expenses/store", this.postData);
      if (res.status === 200) {
        this.loading = false;
        // $('#expense').DataTable().destroy();
        this.postData.category = [];
        this.postData.description = [];
        this.postData.amount = [];
        this.postData.invoice = [];
        this.loop = 0;
        this.editAble = true;
        swal({
          title: "Success",
          text: "Expense Saved",
          icon: "success",
          timer: 2000,
        });
        // this.$refs.refDailySummaryReport.submit();
        this.fetchData();
        this.existingExpenses();
        this.loading = false;
      } else {
        this.loading = false;
        if (res.status == 422) {
          let errorContent = "";
          let count = 0;
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              errorContent +=
                ++count +
                " - " + //creating serial no.
                element + // main error
                "\n"; // creating new line
            });
            swal({
              title: "Error",
              text: errorContent,
              icon: "error",
              timer: 2000,
            });
          }
        }
      }
    },
    async updateAccount() {
      // validation for empty data
      if (!this.postData.ticket_merge_id) {
        return swal({
          title: "Error",
          text: "Something is missing please refresh page",
          icon: "error",
          timer: 2000,
        });
      }

      this.loading = true;
      const res = await this.callApi("post", "accounts/closing/update", {
        ticket_merge_id: this.postData.ticket_merge_id,
      });
      if (res.status === 200) {
        this.loading = false;
        this.closeModal();
        this.postData.category = [];
        this.postData.description = [];
        this.postData.amount = [];
        this.postData.invoice = [];
        this.loop = 0;
        this.editAble = true;
        this.existingExpenses();
        swal({
          title: "Success",
          text: "Updated",
          icon: "success",
          timer: 2000,
        });
        this.loading = false;
      } else {
        this.loading = false;
        if (res.status == 422) {
          let errorContent = "";
          let count = 0;
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              errorContent +=
                ++count +
                " - " + //creating serial no.
                element + // main error
                "\n"; // creating new line
            });
            swal({
              title: "Error",
              text: errorContent,
              icon: "error",
              timer: 2000,
            });
          }
        }
      }
    },
  },
  computed: {
  totalAmount() {
    return this.postData.amount.reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
  },
  totalPaid() {
    return this.postData.paid.reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
  },
  totalBalance() {
    return this.postData.amount.reduce((sum, val, index) => {
      let paid = parseFloat(this.postData.paid[index] || 0);
      return sum + (parseFloat(val) - paid);
    }, 0);
  },
    ...mapGetters(["getDeletingObj"]),

    startShortages() {
      const shortages = this.shortages || [];
      return shortages.filter((item) => item.type === "start");
    },

    returnShortages() {
      const shortages = this.shortages || [];
      return shortages.filter((item) => item.type === "return");
    },

    totalCash() {
      const shortages = this.shortages || [];
      if (shortages.length === 0) return 0;

      return shortages.reduce(
        (sum, item) => sum + Number(item.total_received_cash ?? 0),
        0
      );
    },

    totalBank() {
      const shortages = this.shortages || [];
      if (shortages.length === 0) return 0;

      return shortages.reduce(
        (sum, item) => sum + Number(item.total_received_bank ?? 0),
        0
      );
    },

    totalShortage() {
      const shortages = this.shortages || [];
      if (shortages.length === 0) return 0;

      return shortages.reduce(
        (sum, item) => sum + Number(item.shortage ?? 0),
        0
      );
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

    profitLoss() {
      // Ensure shortages and expenses are arrays
      const shortages = this.shortages || [];
      const expenses = this.expenses || [];

      // If no shortages, return 0

      const totalReceivable = shortages.reduce(
        (sum, item) => sum + Number(item.total_receivable ?? 0),
        0
      );

      const totalKtCommission = shortages.reduce(
        (sum, item) => sum + Number(item.kt_commission ?? 0),
        0
      );

      const totalOtherCommission = shortages.reduce(
        (sum, item) => sum + Number(item.other_commission ?? 0),
        0
      );

      const totalExpenses = expenses.reduce(
        (sum, item) => sum + Number(item.amount ?? 0),
        0
      );
      // console.log(totalExpenses);

      return (
        totalReceivable -
        (totalKtCommission + totalOtherCommission + totalExpenses)
      );
    },

    startTotals() {
      return {
        totalPassengerCount: this.startShortages.reduce(
          (sum, i) => sum + Number(i.passenger_count ?? 0),
          0
        ),
        totalktCommission: this.startShortages.reduce(
          (sum, i) => sum + Number(i.kt_commission ?? 0),
          0
        ),
        totalELT: this.startShortages.reduce(
          (sum, i) => sum + Number(i.elt ?? 0),
          0
        ),
        totalCancellationAmount: this.startShortages.reduce(
          (sum, i) => sum + Number(i.cancellation_amount ?? 0),
          0
        ),
        totalReceivables: this.startShortages.reduce(
          (sum, i) => sum + Number(i.total_receivable ?? 0),
          0
        ),
        totalOtherCommission: this.startShortages.reduce(
          (sum, i) => sum + Number(i.other_commission ?? 0),
          0
        ),
        totalReceivedCash: this.startShortages.reduce(
          (sum, i) => sum + Number(i.total_received_cash ?? 0),
          0
        ),
        totalReceivedBank: this.startShortages.reduce(
          (sum, i) => sum + Number(i.total_received_bank ?? 0),
          0
        ),
        totalReceived: this.startShortages.reduce(
          (sum, i) => sum + Number(i.received ?? 0),
          0
        ),
        totalShortage: this.startShortages.reduce(
          (sum, i) => sum + Number(i.shortage ?? 0),
          0
        ),
      };
    },

    returnTotals() {
      return {
        totalPassengerCount: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.passenger_count ?? 0),
          0
        ),
        totalktCommission: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.kt_commission ?? 0),
          0
        ),
        totalELT: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.elt ?? 0),
          0
        ),
        totalCancellationAmount: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.cancellation_amount ?? 0),
          0
        ),
        totalReceivables: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.total_receivable ?? 0),
          0
        ),
        totalOtherCommission: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.other_commission ?? 0),
          0
        ),
        totalReceivedCash: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.total_received_cash ?? 0),
          0
        ),
        totalReceivedBank: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.total_received_bank ?? 0),
          0
        ),
        totalReceived: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.received ?? 0),
          0
        ),
        totalShortage: this.returnShortages.reduce(
          (sum, i) => sum + Number(i.shortage ?? 0),
          0
        ),
      };
    },
  },

  watch: {
    getDeletingObj(obj) {
      if (obj.isDeleted) {
        this.cities.splice(obj.index, 1);
        $("#expense_table").DataTable().destroy();
        this.fetchData();
        this.existingExpenses();
      }
    },
  },
};
</script>
<style scoped>
.trip-card {
  border: none;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.trip-card::before {
  content: "";
  height: 6px;
  width: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.trip-card.departure::before {
  background: linear-gradient(90deg, #28a745, #6fdf9f);
}

.trip-card.return::before {
  background: linear-gradient(90deg, #007bff, #5aa9ff);
}

.trip-card:hover {
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
}

.trip-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 600;
  color: #555;
  margin-bottom: 12px;
}

.trip-header i {
  font-size: 18px;
}

.bus-number {
  font-size: 22px;
  font-weight: 700;
  color: #222;
  margin-bottom: 15px;
}

.trip-info {
  list-style: none;
  padding: 0;
  margin: 0;
}

.trip-info li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  margin-bottom: 0px;
  color: #444;
}

.trip-info i {
  font-size: 15px;
}

.stat-card {
  border: none;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 6px;
  width: 100%;
}

.stat-card.info::before {
  background: linear-gradient(90deg, #17a2b8, #6fd6e8);
}

.stat-card.danger::before {
  background: linear-gradient(90deg, #dc3545, #ff7b89);
}

.stat-card.success::before {
  background: linear-gradient(90deg, #28a745, #7be495);
}

.stat-card.primary::before {
  background: linear-gradient(90deg, #007bff, #6aa9ff);
}

.stat-card.warning::before {
  background: linear-gradient(90deg, #ffc107, #ffe083);
}

.stat-card:hover {
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  margin-bottom: 10px;
}

.stat-header i {
  font-size: 18px;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #222;
  margin: 0;
}

.btn-print {
  border-radius: 50px;
  padding: 10px 26px;
  font-weight: 600;
  font-size: 14px;
  background: #000000 !important;
  color: #fff !important;
  border: 2px solid #000000 !important;

  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  outline: none !important;
  box-shadow: none !important;
}

/* Hover */
.btn-print:hover:not(:disabled) {
  background: #000000 !important;
  color: #fff !important;
  border: 2px solid #000000 !important;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35) !important;
}

/* Focus */
.btn-print:focus,
.btn-print:focus-visible {
  background: #000000 !important;
  color: #fff !important;
  border-color: #000000 !important;
  outline: none !important;
  box-shadow: none !important;
}

/* Active */
.btn-print:active,
.btn-print.active,
.btn-print:active:not(:disabled) {
  background: #000000 !important;
  color: #fff !important;
  border-color: #000000 !important;
  box-shadow: none !important;
  transform: scale(0.95);
}

/* Disabled */
.btn-print:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none !important;
  transform: none;
}


.btn-loading {
  display: inline-flex;
  align-items: center;
}
</style>