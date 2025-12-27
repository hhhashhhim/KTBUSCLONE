<template>
    <section class="section">
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
        <div class="section-body" v-else>
           <div class="row text-white">
  <!-- Total Shortage -->
  <div class="col-md-3 mb-3">
    <div class="card bg-danger shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Total Shortage</h6>
        <h4 class="card-text">{{ $insertComma(totalShortage) }}</h4>
      </div>
    </div>
  </div>

  <!-- Total Cash -->
  <div class="col-md-3 mb-3">
    <div class="card bg-success shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Total Cash</h6>
        <h4 class="card-text">{{ $insertComma(totalCash) }}</h4>
      </div>
    </div>
  </div>

  <!-- Total Bank -->
  <div class="col-md-3 mb-3">
    <div class="card bg-primary shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Total Bank</h6>
        <h4 class="card-text">{{ $insertComma(totalBank) }}</h4>
      </div>
    </div>
  </div>

  <!-- Profit & Loss -->
  <div class="col-md-3 mb-3">
    <div class="card bg-warning shadow-sm">
      <div class="card-body">
        <h6 class="card-title">Profit & Loss</h6>
        <h4 class="card-text">{{ $insertComma(profitLoss) }}</h4>
      </div>
    </div>
  </div>
</div>


            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Terminal Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                     <h5 class="mt-3">Start</h5>

<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th>Terminal Name</th>
      <th>Passenger Count</th>
      <th>KT Commission</th>
      <th>Total Receivable</th>
      <th>Other Commission</th>
      <th>Total Received in Cash</th>
      <th>Select Bank</th>
      <th>Total Received in Bank</th>
      <th>Shortage</th>
      <th>Received</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <tr v-for="(item, index) in startShortages" :key="'start-'+index">
      <td>{{ item?.terminal?.name }}</td>
      <td>{{ item.passenger_count }}</td>
      <td>{{ item.kt_commission }}</td>
      <td>{{ item.total_receivable }}</td>
      <td>{{ item.other_commission }}</td>
      <td>
  <input
    v-if="isEditing(item)"
    type="number"
    class="form-control form-control-sm"
    v-model.number="item.total_received_cash"
  />
  <span v-else>
    {{ item.total_received_cash }}
  </span>
</td>

     <td>
  <select
    v-if="isEditing(item)"
    v-model="item.bank_id"
    class="form-control form-control-sm"
  >
    <option value="">Select Bank</option>
    <option v-for="bank in banks" :key="bank.id" :value="bank.id">
      {{ bank.name }}
    </option>
  </select>

  <span v-else>
    {{ item?.bank?.name || '-' }}
  </span>
</td>

     <td>
  <input
    v-if="isEditing(item)"
    type="number"
    class="form-control form-control-sm"
    v-model.number="item.total_received_bank"
  />
  <span v-else>
    {{ item.total_received_bank }}
  </span>
</td>


      <td :class="{'text-danger': Number(item.shortage) > 0}">
        {{ item.shortage }}
      </td>

      <td>{{ item.received }}</td>
      <td class="text-nowrap">
  <button
    v-if="!isEditing(item)"
    class="btn btn-sm btn-primary"
    @click="startEdit(item)"
  >
    Edit
  </button>

  <template v-else>
    <button
      class="btn btn-sm btn-success mr-1"
      @click="saveEdit(item)"
    >
      Save
    </button>

    <button
      class="btn btn-sm btn-secondary"
      @click="cancelEdit(item)"
    >
      Cancel
    </button>
  </template>
</td>

    </tr>
  </tbody>
  <tfoot>
  <tr class="font-weight-bold bg-light">
    <td colspan="5" class="text-right">Total</td>
    <td>{{ $insertComma(startTotals.totalReceivedCash) }}</td>
    <td></td> <!-- bank select column -->
    <td>{{ $insertComma(startTotals.totalReceivedBank) }}</td>
    <td>{{ $insertComma(startTotals.totalShortage) }}</td>
    <td>{{ $insertComma(startTotals.totalReceived) }}</td>
    <td></td> <!-- Action column -->
  </tr>
</tfoot>

</table>
<h5 class="mt-4">Return</h5>

<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th>Terminal Name</th>
      <th>Passenger Count</th>
      <th>KT Commission</th>
      <th>Total Receivable</th>
      <th>Other Commission</th>
      <th>Total Received in Cash</th>
      <th>Select Bank</th>
      <th>Total Received in Bank</th>
      <th>Shortage</th>
      <th>Received</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
    <tr v-for="(item, index) in returnShortages" :key="'return-'+index">
      <td>{{ item?.terminal?.name }}</td>
      <td>{{ item.passenger_count }}</td>
      <td>{{ item.kt_commission }}</td>
      <td>{{ item.total_receivable }}</td>
      <td>{{ item.other_commission }}</td>
      <td>
  <input
    v-if="isEditing(item)"
    type="number"
    class="form-control form-control-sm"
    v-model.number="item.total_received_cash"
  />
  <span v-else>
    {{ item.total_received_cash }}
  </span>
</td>

     <td>
  <select
    v-if="isEditing(item)"
    v-model="item.bank_id"
    class="form-control form-control-sm"
  >
    <option value="">Select Bank</option>
    <option v-for="bank in banks" :key="bank.id" :value="bank.id">
      {{ bank.name }}
    </option>
  </select>

  <span v-else>
    {{ item?.bank?.name || '-' }}
  </span>
</td>

     <td>
  <input
    v-if="isEditing(item)"
    type="number"
    class="form-control form-control-sm"
    v-model.number="item.total_received_bank"
  />
  <span v-else>
    {{ item.total_received_bank }}
  </span>
</td>


      <td :class="{'text-danger': Number(item.shortage) > 0}">
        {{ item.shortage }}
      </td>

      <td>{{ item.received }}</td>
      <td class="text-nowrap">
  <button
    v-if="!isEditing(item)"
    class="btn btn-sm btn-primary"
    @click="startEdit(item)"
  >
    Edit
  </button>

  <template v-else>
    <button
      class="btn btn-sm btn-success mr-1"
      @click="saveEdit(item)"
    >
      Save
    </button>

    <button
      class="btn btn-sm btn-secondary"
      @click="cancelEdit(item)"
    >
      Cancel
    </button>
  </template>
</td>

    </tr>
  </tbody>
  <tfoot>
  <tr class="font-weight-bold bg-light">
    <td colspan="5" class="text-right">Total</td>
    <td>{{ $insertComma(returnTotals.totalReceivedCash) }}</td>
    <td></td> <!-- bank select column -->
    <td>{{ $insertComma(returnTotals.totalReceivedBank) }}</td>
    <td>{{ $insertComma(returnTotals.totalShortage) }}</td>
    <td>{{ $insertComma(returnTotals.totalReceived) }}</td>
    <td></td> <!-- Action column -->
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
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Expenses</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Description</th>
                                                        <th>Amount</th>
                                                        <th>Paid</th>
                                                        <th>Entry In Ledger</th>
                                                        <th>Invoice number</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(i,index) in loop" :key="index">
                                                        <td>
                                                            <!-- {{ items[0] ? items[0].price : '' }} -->
                                                            <select class="form-control rounded-0"
                                                                    @change="saveRow($event,'first',index)"
                                                                    :value="postData.category[index]"
                                                                    :disabled="editAble">
                                                                <option value="" selected>Select Category</option>
                                                                <option v-for="(category, i) in categories"
                                                                        :value="category.id" :key="i">
                                                                    {{ category.name }}
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                   @keyup="saveRow($event,'second',index)"
                                                                   :value="postData.description[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" class="form-control"
                                                                   @keyup="saveRow($event,'third',index)"
                                                                   :value="postData.amount[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input v-if="postData.ledger[index]" type="number" min="0" class="form-control"
                                                                   @keyup="saveRow($event,'five',index)"
                                                                   :value="postData.paid[index]"
                                                                   :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="checkbox" :checked="postData.ledger[index]" @change="saveRow($event, 'six', index)" :disabled="editAble">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                   @keyup="saveRow($event,'fourth',index)"
                                                                   :value="postData.invoice[index]"
                                                                   disabled/>
                                                        </td>
                                                        <td v-if="!editAble">
                                                            <button class="btn btn-outline-primary mx-2"
                                                                    @click="addRow">Add
                                                            </button>
                                                            <button class="btn btn-outline-danger"
                                                                    @click="removeRow($event,index)" v-if="loop != 1">
                                                                Remove
                                                            </button>
                                                        </td>
                                                        <td v-else></td>
                                                    </tr>
                                                    <tr class="mt-1">
                                                        <td></td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="totalNums">Total Sale</label>
                                                                <input id="totalSale" type="text"
                                                                       class="form-control mr-4" disabled
                                                                       :value="totalSale"/>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="totalNums">Total Amount</label>
                                                                <input id="totalNums" type="text"
                                                                       class="form-control mr-4" disabled
                                                                       :value="totalAmount"/>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-group">
                                                                <label for="netProfit">Net Profit</label>
                                                                <input id="netProfit" type="text"
                                                                       class="form-control mr-4" disabled
                                                                       :value="netProfit"/>
                                                            </div>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                                <div class="d-flex justify-content-end">
                                                    <button v-if="!checkClosing" type="button" class=" text-light btn btn-danger mr-1" 
                                                        data-target="#accountModal" data-toggle="modal"
                                                        :disabled="loading">Update Account
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success mr-4"
                                                            @click="add" :disabled="loading" v-if="!editAble">
                                                        {{ loading ? 'Loading...' : 'Save' }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4"
                                                            @click="editAble=false" :disabled="loading" v-else>Edit
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary mr-4"
                                                            @click="editAble=true"
                                                            v-if="!editAble && postData.category.length != 0">Cancel
                                                    </button>
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

            <!--Daily Summery Report Form-->
            <form :action="$store.state.api_url + 'api/web/v1/print/pdf/daily/summary/report'" method="POST"
                  ref="refDailySummaryReport"
                  target="_blank">
                <input type="hidden" name="token" :value="this.$store.state.token">
                <input type="hidden" name="ticket_merge_id" :value="this.postData.ticket_merge_id">
            </form>

            <div
                class="modal fade"
                id="accountModal"
                tabindex="-1"
                role="dialog"
                aria-labelledby="modelTitleId"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-body pt-5">
                            <div class="card card-danger">
                                <div class="card-header d-flex justify-content-between">
                                    <h4
                                        class="modal-title text-center text-danger"
                                        style="width: 97%"
                                    >
                                        <i class="fas fa-exclamation-circle fa-2x"></i> Confirmation
                                    </h4>
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="modal"
                                        aria-label="Close"
                                       @click="closeModal()"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body text-center">
                                    <div
                                        class="alert alert-danger alert-dismissible fade show"
                                        role="alert"
                                        v-if="success"
                                    >
                                        <button
                                            type="button"
                                            class="close"
                                            data-dismiss="alert"
                                            aria-label="Close"
                                            @click="closeModal()"
                                        >
                                            <span aria-hidden="true">&times;</span>
                                            <span class="sr-only">Close</span>
                                        </button>
                                    </div>
                                    <p class="font-weight-bold">
                                        You can't edit this once you close the summary. Do you want to procceed ?
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-block pt-0">
                            <button
                                type="button"
                                class="btn btn-danger btn-block"
                                data-dismiss="modal"
                                :disabled="loading"
                                @click="updateAccount"
                            >
                                Yes
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary btn-block"
                                data-dismiss="modal"
                                @click="closeModal()"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "expense",
    components: {
        // Add,
        // Edit,
        // Delete,
    },
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            validationErrors: [],
            editAble: true,
            categories: [],
            isLoading: true,
            formID: 'expense_form',
            editFormID: 'edit_expense_form',
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
            shortages : [],
            editingRowId: null,
            editCache: {},
            expenses: [],
        }
    },
    async created() {
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        this.postData.ticket_merge_id = this.$route.params.id;
        this.fetchData();
        this.existingExpenses();
        setTimeout(function () {
            $("#expense_table").DataTable();
        }, 300);
        // total amount sum only for show
        this.totalAmount = this.postData.amount.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
        
    },

    methods: {
          startEdit(item) {
    this.editingRowId = item.id;

    // clone row values (so cancel works)
    this.editCache[item.id] = {
      total_received_cash: item.total_received_cash,
      bank_id: item.bank_id,
      total_received_bank: item.total_received_bank
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
    const res = await this.callApi("post", 'booking/close/schedule/closing/ticket-closing-shortage/update', item)
    if (res.status == 200) {
        this.existingExpenses();
              swal({
                    title: "Success",
                    text: "Data Updated Successfully",
                    icon: "success",
                    timer: 2000
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
            const res = await this.callApi("post", 'expenses/categories');
            if (res.status == 200) {
                this.categories = res.data;
            }
        },
        async existingExpenses() {
           
            this.isLoading = true;
            const res = await this.callApi("post", 'expenses', {ticket_merge_id: this.postData.ticket_merge_id});
            if (res.status == 200) {
                const expenses = res.data.expenses;
                this.expenses = expenses;
                this.totalSale = res.data.sale;
                this.checkClosing = res.data.closing;
                this.shortages = res.data.shortage;
          
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
                    this.totalAmount = this.postData.amount.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
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
            this.totalAmount = this.postData.amount.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
            this.netProfit = this.totalSale - this.totalAmount;
        },
        addRow() {
            this.loop++;
        },
        removeRow(event, index) {
            this.postData.category.splice(index, 1);
            this.postData.description.splice(index, 1);
            this.postData.amount.splice(index, 1);
            this.postData.invoice.splice(index, 1);
            this.loop--;

            // total amount sum only for show
            this.totalAmount = this.postData.amount.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
            this.netProfit = this.totalSale - this.totalAmount;
        },
        async add() {

            // validation for empty data
            if (!this.postData.ticket_merge_id || this.postData.category.length == 0 || this.postData.description.length == 0 ||
                this.postData.amount.length == 0) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for (var i = 0; i < this.postData.category.length; i++) {
                if (!this.postData.category[i] || !this.postData.description[i] || !this.postData.amount[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
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
                    timer: 2000
                });
                this.$refs.refDailySummaryReport.submit();
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
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
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
                    timer: 2000
                });
            }

            this.loading = true;
            const res = await this.callApi("post", "accounts/closing/update", {ticket_merge_id:this.postData.ticket_merge_id});
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
                    timer: 2000
                });
                this.loading = false;
            } else {
                this.loading = false;
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        },
    },
   computed: {
    ...mapGetters(['getDeletingObj']),

    startShortages() {
        return this.shortages.filter(item => item.type === 'start');
    },

    returnShortages() {
        
        return this.shortages.filter(item => item.type === 'return');
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

    return totalReceivable - (totalKtCommission + totalOtherCommission + totalExpenses);
},

    startTotals() {
        return {
            totalReceivedCash: this.startShortages.reduce((sum, i) => sum + Number(i.total_received_cash ?? 0), 0),
            totalReceivedBank: this.startShortages.reduce((sum, i) => sum + Number(i.total_received_bank ?? 0), 0),
            totalReceived: this.startShortages.reduce((sum, i) => sum + Number(i.received ?? 0), 0),
            totalShortage: this.startShortages.reduce((sum, i) => sum + Number(i.shortage ?? 0), 0),
        };
    },

    returnTotals() {
        return {
            totalReceivedCash: this.returnShortages.reduce((sum, i) => sum + Number(i.total_received_cash ?? 0), 0),
            totalReceivedBank: this.returnShortages.reduce((sum, i) => sum + Number(i.total_received_bank ?? 0), 0),
            totalReceived: this.returnShortages.reduce((sum, i) => sum + Number(i.received ?? 0), 0),
            totalShortage: this.returnShortages.reduce((sum, i) => sum + Number(i.shortage ?? 0), 0),
        };
    }
},

    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#expense_table").DataTable().destroy();
                this.fetchData();
                this.existingExpenses();
            }
        }
    }
}
</script>
