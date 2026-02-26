<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Counter Income / Expenses</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#' + formID" @click="clearForm()"
                                    class="btn btn-primary" v-if="checkForSubmenuButtons('add-counter-expenses')">
                                    Add Counter Income / Expenses
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <form @submit.prevent="fetchCounterExpenses" class=" mb-4">
                                                <div class="card-body p-3">
                                                    <div class="row g-3">

                                                        <!-- Cash Ledger -->
                                                        <div class="col-md-4 col-sm-6">
                                                            <label class="form-label small fw-bold text-muted">Cash
                                                                Ledger</label>
                                                            <select v-model="filters.cash_id"
                                                                class="form-control form-select-sm">
                                                                <option value="">All Cash Ledgers</option>
                                                                <option v-for="cash in cashes" :key="cash.id"
                                                                    :value="cash.id">{{ cash.name }}</option>
                                                            </select>
                                                        </div>

                                                        <!-- Bank Ledger -->
                                                        <div class="col-md-4 col-sm-6">
                                                            <label class="form-label small fw-bold text-muted">Bank
                                                                Ledger</label>
                                                            <select v-model="filters.bank_id"
                                                                class="form-control form-select-sm">
                                                                <option value="">All Bank Ledgers</option>
                                                                <option v-for="bank in banks" :key="bank.id"
                                                                    :value="bank.id">{{ bank.name }}</option>
                                                            </select>
                                                        </div>

                                                        <!-- Category -->
                                                        <div class="col-md-4 col-sm-6">
                                                            <label
                                                                class="form-label small fw-bold text-muted">Category</label>
                                                            <select v-model="filters.category_id"
                                                                class="form-control form-select-sm">
                                                                <option value="">All Categories</option>
                                                                <option v-for="cat in categories" :key="cat.id"
                                                                    :value="cat.id">{{ cat.name }}</option>
                                                            </select>
                                                        </div>

                                                        <!-- Amount -->
                                                        <div class="col-md-4 mt-3 col-sm-6">
                                                            <label
                                                                class="form-label small fw-bold text-muted">Amount</label>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs</span>
                                                                <input type="text" v-model="filters.amount"
                                                                    class="form-control" @keypress="isNumber($event)"
                                                                    placeholder="0.00" />
                                                            </div>
                                                        </div>

                                                        <!-- Narration -->
                                                        <div class="col-md-4 mt-3 col-sm-6">
                                                            <label
                                                                class="form-label small fw-bold text-muted">Narration</label>
                                                            <input type="text" v-model="filters.narration"
                                                                class="form-control form-select-sm"
                                                                placeholder="Search description..." />
                                                        </div>

                                                        <!-- Buttons -->
                                                        <div
                                                            class="col-md-4 mt-3 col-sm-12 d-flex gap-2 align-items-end">
                                                            <button type="button" class="btn btn-danger flex-fill mx-1"
                                                                @click="resetFilters">
                                                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                                                            </button>
                                                            <button type="submit"
                                                                class="btn btn-primary flex-fill mx-1">
                                                                <i class="bi bi-funnel"></i> Apply Filters
                                                            </button>
                                                        </div>

                                                    </div>
                                                </div>
                                            </form>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover"
                                                    id="counter_expenses_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Attachments</th>
                                                            <th>expen Cate / Income</th>
                                                            <th>Cash Payment</th>
                                                            <th>Bank Payment</th>
                                                            <th>Total Payment</th>
                                                            <th>Cash Ledger</th>
                                                            <th>Bank Ledger</th>
                                                            <th>Date</th>
                                                            <th>Narration</th>
                                                            <th v-if="checkForSubmenuButtons('edit-counter-expenses')">
                                                                Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(single, i) in counterExpenses" :key="i">
                                                            <td>{{ i + 1 }}</td>

                                                            <!-- Attachments -->
                                                            <td>
                                                                <template v-if="single.bill_post">
                                                                    <template v-if="isImage(single.bill_post)">
                                                                        <a :href="API_URL + 'storage/' + single.bill_post"
                                                                            target="_blank">
                                                                            <img :src="API_URL + 'storage/' + single.bill_post"
                                                                                style="width:80px;height:80px;object-fit:cover;"
                                                                                alt="Bill Image">
                                                                        </a>
                                                                    </template>
                                                                    <template v-else>
                                                                        <a :href="API_URL + 'storage/' + single.bill_post"
                                                                            target="_blank">
                                                                            {{ getFileName(single.bill_post) }}
                                                                        </a>
                                                                    </template>
                                                                </template>
                                                                <template v-else>
                                                                    -
                                                                </template>
                                                            </td>
                                                            <td>
                                                                {{ single.type === 'expense' ? (single.category ?
                                                                    single.category.name : '-') : (single.other_income ??
                                                                        '-') }}
                                                            </td>

                                                            <!-- Payments -->
                                                            <td>{{ single.cash_payment || 0 }}</td>
                                                            <td>{{ single.bank_payment || 0 }}</td>
                                                            <td>{{ single.total || 0 }}</td>

                                                            <!-- Ledgers -->
                                                            <td>{{ single.cash_id ? getCashName(single.cash_id) : "-" }}
                                                            </td>
                                                            <td>{{ single.bank_id ? getBankName(single.bank_id) : "-" }}
                                                            </td>

                                                            <!-- Category & Narration -->

                                                            <td>{{ single.date }}</td>
                                                            <td>{{ single.narration }}</td>

                                                            <!-- Actions -->
                                                            <td v-if="checkForSubmenuButtons('edit-counter-expenses')">
                                                                <button title="Edit Expenses"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(single)"
                                                                    class="btn btn-primary text-light mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>

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

            <!-- Add Modal -->
            <Add heading="Add Counter Income / Expenses" :errors="validationErrors" :success="success" :formID="formID">
                <div class="row">
                    <div class="form-group col-md-12 mb-3">
                        <label class="fw-semibold d-block mb-2">Type</label>

                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_category"
                                    value="expense" v-model="data.type">
                                <label class="form-check-label fw-normal" for="type_category">
                                    Expense
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_other" value="income"
                                    v-model="data.type">
                                <label class="form-check-label fw-normal" for="type_other">
                                    Income
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- Category dropdown -->
                    <div class="form-group col-md-3" v-if="data.type === 'expense'">
                        <label class="fw-semibold">Expense Category</label>
                        <select v-model="data.category_id" class="form-control">
                            <option value="">Select Category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Other expense input -->
                    <div class="form-group col-md-3" v-if="data.type === 'income'">
                        <label class="fw-semibold">Other Income Name</label>
                        <input type="text" v-model="data.other_income" class="form-control"
                            placeholder="Enter income name">
                    </div>
                    <!-- Amount -->
                    <div class="form-group col-md-3">
                        <label class="fw-semibold">Cash Payment</label>
                        <input type="text" v-model.number="data.cash_payment" @keypress="isNumber($event)"
                            class="form-control text-end" placeholder="Enter cash amount">

                    </div>

                    <div class="form-group col-md-3">
                        <label class="fw-semibold">Bank Payment</label>
                        <input type="text" v-model.number="data.bank_payment" @keypress="isNumber($event)" min="0"
                            class="form-control text-end" placeholder="Enter bank amount">

                    </div>
                    <!-- Total Payment -->
                    <div class="form-group col-md-3">
                        <label class="fw-semibold">Total Payment</label>
                        <input type="text" v-model="totalPayment" class="form-control text-end fw-bold text-success"
                            readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Bill Posting <span class="text-danger ml-1">(Optional)</span></label>
                        <input class="form-control" type="file" @change="handleBillPost">
                    </div>
                    <div class="form-group col-md-4">
                        <!-- Cash Ledger -->
                        <div>
                            <label class="fw-semibold">Cash Ledger</label>
                            <select v-model="data.cash_id" class="form-control">
                                <option value="">Select Cash</option>
                                <option v-for="cash in cashes" :key="cash.id" :value="cash.id">
                                    {{ cash.name }}
                                </option>
                            </select>
                        </div>

                    </div>
                    <div class="form-group col-md-4">
                        <!-- Bank Ledger -->
                        <div>
                            <label class="fw-semibold">Bank Ledger</label>
                            <select v-model="data.bank_id" class="form-control">
                                <option value="">Select Bank</option>
                                <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                    {{ bank.head_bank ? bank.head_bank.name : bank.name }}
                                </option>
                            </select>
                        </div>

                    </div>
                    <div class="form-group col-md-3">
                        <label class="fw-semibold">Date</label>
                        <input type="date" v-model="data.date" class="form-control text-end fw-bold text-success">
                    </div>

                    <!-- Narration -->
                    <div class="form-group col-md-12">
                        <label for="narration">Narration <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" placeholder="Describe Narration"
                            v-model="data.narration"></textarea>
                    </div>
                </div>
                <!-- Button -->
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add()">
                        {{ loading ? 'Loading...' : 'Add Counter Expenses' }}
                    </button>
                </template>
            </Add>


            <Edit heading="Edit Counter Income / Expenses" :errors="validationErrors" :success="success"
                :editForm="editFormID">
                <div class="row">
                    <div class="form-group col-md-12 mb-3">
                        <label class="fw-semibold d-block mb-2">Type</label>

                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios1"
                                    value="expense" v-model="dataEdit.type" checked>
                                <label class="form-check-label" for="exampleRadios1">
                                    Expense
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exampleRadios" value="income"
                                    v-model="dataEdit.type" id="exampleRadios2">
                                <label class="form-check-label" for="exampleRadios2">
                                    Income
                                </label>
                            </div>
                            <!-- <div class="form-check">
                                <input id="type_category" class="form-check-input" type="radio" value="expense" v-model="dataEdit.type">
                                <label for="type_category" class="form-check-label">Expense</label>
                            </div>

                            <div class="form-check">
                                <input id="type_other" class="form-check-input" type="radio" value="income" v-model="dataEdit.type">
                                <label for="type_other" class="form-check-label">Income</label>
                            </div> -->
                        </div>
                    </div>

                    <!-- Expense Category -->
                    <div class="form-group col-md-4" v-if="dataEdit.type == 'expense'">
                        <label class="fw-semibold">Expense Category</label>
                        <select v-model="dataEdit.category_id" class="form-control">
                            <option value="">Select Category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Other Income Name -->
                    <div class="form-group col-md-4" v-if="dataEdit.type == 'income'">
                        <label class="fw-semibold">Other Income Name</label>
                        <input type="text" v-model="dataEdit.other_income" class="form-control"
                            placeholder="Enter income name">
                    </div>
                    <!-- Cash Payment -->
                    <div class="form-group col-md-4">
                        <label class="fw-semibold">Cash Payment</label>
                        <input type="text" v-model.number="dataEdit.cash_payment" @keypress="isNumber($event)"
                            class="form-control text-end" placeholder="Enter cash amount">

                        <div v-if="dataEdit.cash_payment > 0" class="mt-2">
                            <label class="fw-semibold">Cash Ledger</label>
                            <select v-model="dataEdit.cash_id" class="form-control">
                                <option value="">Select Cash</option>
                                <option v-for="cash in cashes" :key="cash.id" :value="cash.id">{{ cash.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bank Payment -->
                    <div class="form-group col-md-4">
                        <label class="fw-semibold">Bank Payment</label>
                        <input type="text" v-model.number="dataEdit.bank_payment" @keypress="isNumber($event)"
                            class="form-control text-end" placeholder="Enter bank amount">

                        <div v-if="dataEdit.bank_payment > 0" class="mt-2">
                            <label class="fw-semibold">Bank Ledger</label>
                            <select v-model="dataEdit.bank_id" class="form-control">
                                <option value="">Select Bank</option>
                                <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                    {{ bank.head_bank ? bank.head_bank.name : bank.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Total Payment -->
                    <div class="form-group col-md-4">
                        <label class="fw-semibold">Total Payment</label>
                        <input type="text" :value="totalPaymentEdit" class="form-control text-end fw-bold text-success"
                            readonly>
                    </div>

                    <!-- Bill Posting -->
                    <div class="form-group col-md-4">
                        <label>Bill Posting <span class="text-danger ml-1">(Optional)</span></label>
                        <input class="form-control" type="file" @change="handleEditBillPost">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="fw-semibold">Date</label>
                        <input type="date" v-model="dataEdit.date" class="form-control text-end fw-bold text-success">
                    </div>
                    <!-- Narration -->
                    <div class="form-group col-md-12">
                        <label>Narration <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" placeholder="Describe Narration"
                            v-model="dataEdit.narration"></textarea>
                    </div>
                </div>

                <!-- Button -->
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loadingEdit" @click="update()">
                        {{ loadingEdit ? 'Loading...' : 'Update Counter Expenses' }}
                    </button>
                </template>
            </Edit>


        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';

export default {
    name: "CounterExpensesPage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            API_URL: process.env.MIX_API_URL,
            validationErrors: [],
            counterExpenses: [],
            permissions: [],
            loading: false,
            loadingEdit: false,
            formID: 'counter_expenses',
            editFormID: 'edit_counter_expenses',
            deleteFormID: 'delete_counter_expenses',

            data: {
                type: 'expense', // default selected
                other_income: "",
                category_id: "",
                bill_post: null,
                amount: 0,
                narration: "",
                payment_method: "",
                total: 0,
                cash_payment: 0,
                bank_payment: 0,
                cash_id: "",
                bank_id: "",
                date: new Date().toISOString().split('T')[0]
            },
            dataEdit: {
                id: null,
                amount: 0,
                total: 0,
                narration: "",
                payment_method: "",
                cash_payment: 0,
                bank_payment: 0,
                cash_id: "",
                bank_id: "",
                category_id: "",
                bill_post: null,
                date: "",
                type: "expense",   // ✅ ADD THIS
                other_income: "",  // ✅ ADD THIS
            },
            filters: {
                cash_id: '',
                bank_id: '',
                category_id: '',
                amount: '',
                narration: ''
            },
            categories: [],    // fill from API
            banks: [],
            cashes: [],
            subtotal: 0,
            delId: "",
            success: false,
            errors: false,
        }
    },
    computed: {
        totalPayment() {
            return (Number(this.data.cash_payment) || 0) + (Number(this.data.bank_payment) || 0);
        },
        totalPaymentEdit() {
            return (Number(this.dataEdit.cash_payment) || 0) + (Number(this.dataEdit.bank_payment) || 0);
        },
        remaining() {
            return Math.max(this.data.total - this.totalPayment, 0);
        }
    },
    async created() {
        $('.modal').remove();
        await this.fetchCounterExpenses();
        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },

    methods: {
        isImage(file) {
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const name = typeof file == 'string' ? file : file.name;
            const ext = name.split('.').pop().toLowerCase();
            return imageExtensions.includes(ext);
        },
        async fetchBanks() {
            try {
                const res = await this.callApi("get", "accounts/heads/banks");
                this.banks = res.data.accountHeadBanks;
            } catch (err) {
                console.error("Bank API error:", err.response?.data || err);
            }
        },

        async fetchCashes() {
            try {
                const res = await this.callApi("get", "accounts/heads/cash");
                this.cashes = res.data.accountHeadCash;
            } catch (err) {
                console.error("Cash API error:", err.response?.data || err);
            }
        },
        async fetchData() {
            try {
                const res = await this.callApi("post", "expenses/categories");
                if (res.status == 200) {
                    this.categories = res.data;
                }
            } catch (e) {
                console.error(e);
            }
        },
        recalculateTotal() {
            this.data.total =
                this.subtotal -
                (parseFloat(this.data.discount) || 0) +
                (parseFloat(this.data.tax) || 0);
        },
        getCashName(cashId) {
            const cash = this.cashes.find(c => c.id == cashId);
            return cash ? cash.name : "-";
        },
        resetFilters() {
            this.filters = {
                cash_id: '',
                bank_id: '',
                category_id: '',
                amount: '',
                narration: ''
            };
            this.fetchCounterExpenses(); // Reload full table
        },
        getBankName(bankId) {
            const bank = this.banks.find(b => b.id == bankId);
            return bank ? (bank.head_bank ? bank.head_bank.name : bank.name) : "-";
        },
        getFileName(file) {
            // Return filename from path
            return typeof file == 'string' ? file.split('/').pop() : file.name;
        },
        handleBillPost(e) {
            this.data.bill_post = e.target.files[0];
        },
        handleEditBillPost(e) {
            this.dataEdit.bill_post = e.target.files[0];
        },
        clearForm: function () {
            this.data = {};
        },
        async fetchCounterExpenses() {
            // Send filters to backend
            const resCounterExpenses = await this.callApi(
                "post",
                'counter/expenses',
                this.filters
            );

            if (resCounterExpenses.status == 200) {
                this.counterExpenses = resCounterExpenses.data;
            }

            setTimeout(() => {
                $("#counter_expenses_table").DataTable();
            }, 300);
        },
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
        async add() {
            this.validationErrors = [];

            // ✅ Basic validations
            if (!this.data.narration) {
                return swal({
                    title: "Required",
                    text: "Expenses Narration is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            // ✅ Category validation
            if (this.data.type == 'expense' && !this.data.category_id) {
                return swal({
                    title: "Required",
                    text: "Expense Category is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            // ✅ Other expense validation
            if (this.data.type == 'income' && !this.data.other_income) {
                return swal({
                    title: "Required",
                    text: "Other Expense Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            // ✅ Payment validation
            const cashPayment = Number(this.data.cash_payment) || 0;
            const bankPayment = Number(this.data.bank_payment) || 0;
            const totalPayment = cashPayment + bankPayment;

            if (totalPayment <= 0) {
                return swal({
                    title: "Required",
                    text: "Enter Cash or Bank Payment",
                    icon: "error",
                    timer: 2000
                });
            }

            if (cashPayment > 0 && !this.data.cash_id) {
                return swal({
                    title: "Required",
                    text: "Select Cash Ledger",
                    icon: "error",
                    timer: 2000
                });
            }

            if (bankPayment > 0 && !this.data.bank_id) {
                return swal({
                    title: "Required",
                    text: "Select Bank Ledger",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            try {
                // 🔥 Prepare FormData safely
                const formData = new FormData();

                formData.append('total', this.data.total || totalPayment); // total fallback
                formData.append('narration', this.data.narration);
                formData.append('type', this.data.type);

                if (this.data.type === 'expense') {
                    formData.append('category_id', this.data.category_id);
                } else {
                    formData.append('other_income', this.data.other_income);
                }
                formData.append('date', this.data.date);

                // Only append cash/bank fields if values exist
                if (cashPayment > 0) {
                    formData.append('cash_payment', cashPayment);
                    formData.append('cash_id', this.data.cash_id);
                }

                if (bankPayment > 0) {
                    formData.append('bank_payment', bankPayment);
                    formData.append('bank_id', this.data.bank_id);
                }

                // Bill file
                if (this.data.bill_post instanceof File) {
                    formData.append('bill_post', this.data.bill_post);
                }

                // 🚀 Call API
                const resCounter = await this.callApi(
                    "post",
                    "counter/expenses/store",
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );

                // ✅ Success
                if (resCounter.status === 201) {
                    $(".modal").click();

                    swal({
                        title: "Success",
                        text: "Expenses Added Successfully",
                        icon: "success",
                        timer: 2000
                    });

                    $("#counter_expenses_table").DataTable().destroy();
                    this.fetchCounterExpenses();
                    this.clearForm();
                }

            } catch (error) {
                // ✅ Handle validation errors from backend
                if (error.response && error.response.status === 422) {
                    this.validationErrors = error.response.data.errors || {};
                    let errorContent = "";
                    let count = 0;

                    for (const key in this.validationErrors) {
                        this.validationErrors[key].forEach(msg => {
                            errorContent += (++count) + " - " + msg + "\n";
                        });
                    }

                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 3000
                    });

                } else {
                    swal({
                        title: "Error",
                        text: "Something went wrong. Please try again.",
                        icon: "error",
                        timer: 3000
                    });
                }
            } finally {
                this.loading = false;
            }
        },

        edit(singleRecord) {
            this.dataEdit = singleRecord;
        },
        async update() {
            this.validationErrors = [];

            // ✅ Basic validations
            if (!this.dataEdit.narration) {
                return swal({
                    title: "Required",
                    text: "Expenses Narration is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.dataEdit.type == 'expense' && !this.dataEdit.category_id) {
                return swal({
                    title: "Required",
                    text: "Expense Category is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.dataEdit.type == 'income' && !this.dataEdit.other_income) {
                return swal({
                    title: "Required",
                    text: "Income Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            const cashPayment = Number(this.dataEdit.cash_payment) || 0;
            const bankPayment = Number(this.dataEdit.bank_payment) || 0;
            const totalPayment = cashPayment + bankPayment;

            // ✅ Ensure at least one payment is provided
            if (totalPayment <= 0) {
                return swal({
                    title: "Required",
                    text: "Enter either Cash or Bank Payment",
                    icon: "error",
                    timer: 2000
                });
            }

            // ✅ Ensure ledger is selected if payment > 0
            if (cashPayment > 0 && !this.dataEdit.cash_id) {
                return swal({
                    title: "Required",
                    text: "Select Cash Ledger",
                    icon: "error",
                    timer: 2000
                });
            }

            if (bankPayment > 0 && !this.dataEdit.bank_id) {
                return swal({
                    title: "Required",
                    text: "Select Bank Ledger",
                    icon: "error",
                    timer: 2000
                });
            }

            

            this.loadingEdit = true;

            try {
                const formData = new FormData();
                formData.append('id', this.dataEdit.id);
                formData.append('total', this.dataEdit.total);
                formData.append('narration', this.dataEdit.narration);
                formData.append('category_id', this.dataEdit.category_id);
formData.append('type', this.dataEdit.type);

                if (this.dataEdit.type === 'expense') {
                    formData.append('category_id', this.dataEdit.category_id);
                } else {
                    formData.append('other_income', this.dataEdit.other_income);
                }
                formData.append('cash_payment', cashPayment);
                formData.append('bank_payment', bankPayment);
                formData.append('cash_id', this.dataEdit.cash_id);
                formData.append('bank_id', this.dataEdit.bank_id);
                formData.append('date', this.dataEdit.date);

                // ✅ Optional bill upload
                if (this.dataEdit.bill_post instanceof File) {
                    formData.append('bill_post', this.dataEdit.bill_post);
                }

                const resEdit = await this.callApi(
                    "post",
                    "counter/expenses/update",
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );

                if (resEdit.status === 200) {
                    $(".modal").click();
                    swal({
                        title: "Success",
                        text: "Expenses updated Successfully",
                        icon: "success",
                        timer: 2000
                    });

                    this.loadingEdit = false;
                    $("#counter_expenses_table").DataTable().destroy();
                    this.fetchCounterExpenses();
                    setTimeout(() => { $('#edit-modal').modal('hide'); }, 3000);

                } else if (resEdit.status === 422) {
                    this.loadingEdit = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach(msg => {
                            errorContent += (++count) + " - " + msg + "\n";
                        });
                    }
                    swal("Error", errorContent, "error");
                }

            } catch (error) {
                this.loadingEdit = false;
                swal({
                    title: "Error",
                    text: "Something went wrong. Please try again.",
                    icon: "error",
                    timer: 3000
                });
            }
        }


    },
    watch: {
        totalPayment(val) {
            this.data.total = val;
        },
        totalPaymentEdit(val) {
            this.dataEdit.total = val;
        }
    },
    mounted() {
        this.fetchBanks();
        this.fetchCashes();
        this.fetchData();
    },
    watch: {
        watch: {
            'dataEdit.type'(newValue) {
                if (newValue == 'expense') {
                    // Clear income field
                    this.dataEdit.other_income = "";
                } else if (newValue == 'income') {
                    // Clear category field
                    this.dataEdit.category_id = "";
                }
            }
        },
        'data.type'(newValue) {
            if (newValue == 'expense') {
                this.data.other_income = "";
            } else if (newValue == 'income') {
                this.data.category_id = "";
            }
        }
    }

}
</script>
