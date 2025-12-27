<template>
    <section class="section">
        <div class="section-body">
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
            loading: false,
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
            // dataEdit:{
            //     id:"",
            //     name:"",
            // },
            // delId:"",
            success: false,
            errors: false,
            loop: 1,
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
           
            
            const res = await this.callApi("post", 'expenses', {ticket_merge_id: this.postData.ticket_merge_id});
            if (res.status == 200) {
                const expenses = res.data.expenses;
                this.totalSale = res.data.sale;
                this.checkClosing = res.data.closing;
          
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
        ...mapGetters(['getDeletingObj'])
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
