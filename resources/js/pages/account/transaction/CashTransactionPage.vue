<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Cash Transaction</h4>
                <a href="#" class="mr-1 btn btn-primary" data-toggle="modal" data-target="#newTransaction">New</a>
            </div>
            <div class="card-body">
                <div class="table-responsive" v-if="tableLoading">
                    <BulletListLoader></BulletListLoader>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm" id="transaction_table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Terminal</th>
                                <th scope="col">Voucher</th>
                                <th scope="col">Cash Name</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Approved By</th>
                                <th scope="col">Added By</th>
                                <th scope="col">Posted Date</th>
                                <th scope="col" width="200px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transaction, index) in alltransactions" :key="index">
                                <th scope="row">{{ index + 1 }}</th>
                                <td>{{transaction.terminal}}</td>
                                <td>{{transaction.type}}-{{transaction.document_id}}</td>
                                <td>{{transaction.cash_name}}</td>
                                <td>{{transaction.amount}}</td>
                                <td>{{transaction.type}}</td>
                                <td>
                                    <small :class="transaction.approved==1 ? 'font-10 badge badge-success' : 'font-10 badge badge-warning'">
                                        {{transaction.approved==1 ? 'Approved' : 'Pending'}}
                                    </small>
                                </td>
                                <td>{{transaction.approved_by}}</td>
                                <td>{{transaction.added_by}}</td>
                                <td>{{transaction.posted_date}}</td>
                                <td>
                                    <a
                                        v-if="transaction.approved==0"
                                        href="#"
                                        class="mr-1 btn-sm btn btn-icon btn-success"
                                        @click="approveTransactionModal(transaction.document_id,transaction.type)"
                                        ><i class="fas fa-check"></i
                                    ></a>
                                    <a
                                        v-if="transaction.approved==0"
                                        href="#"
                                        class="mr-1 btn-sm btn btn-icon btn-primary"
                                        @click="editTransaction(transaction.document_id,transaction.type)"
                                        ><i class="far fa-edit"></i
                                    ></a>
                                    <a
                                        href="#"
                                        class="mr-1 btn-sm btn btn-icon btn-info"
                                        @click="showTransaction(transaction.document_id,transaction.type)"
                                        ><i class="far fa-eye"></i
                                    ></a>
                                    <button
                                        @click="pdfTransaction(transaction.document_id,transaction.type)"
                                        class="mr-1 btn-sm btn btn-icon btn-dark"
                                        ><i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- External Form Submission -->
        <form :action="`${this.$store.state.api_url}api/web/v1/accounts/transactions/data/pdf`" method="post" ref="transactionFormPdf" target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="id" :value="this.voucher.id">
            <input type="hidden" name="type" :value="this.voucher.type">
        </form>
        <!-- add modal -->
        <NewTransaction
            :btnLoading="btnLoading"
            :addData="addData"
            :terminals="terminals"
            :cashes="cashes"
            :heads="heads"
            ref="childComponentRef"
            @add="addTransaction"
        />
        <!-- update modal -->
        <EditTransaction
            :btnLoading="btnLoading"
            :editData="editData"
            :terminals="terminals"
            :cashes="cashes"
            :heads="heads"
            @update="updateTransaction"
        />
        <!-- show modal -->
        <DetailTransaction
            :transactionDetail="transactionDetail"
        />
        <!-- show modal -->
        <ApproveTransaction
            :status="'btn btn-block btn-success'"
            :voucher="voucher"
            @approve="approveTransaction"
        />

    </section>
</template>
<script>
import NewTransaction from '../../../components/account/transaction/cash-transaction/NewComponent.vue';
import EditTransaction from '../../../components/account/transaction/cash-transaction/EditComponent.vue';
import DetailTransaction from '../../../components/account/transaction/cash-transaction/DetailTransaction.vue';
import ApproveTransaction from '../../../components/account/transaction/cash-transaction/ApproveTransaction.vue';
export default {
    name: "CashPaymentPage",
    components: {
        NewTransaction,
        EditTransaction,
        DetailTransaction,
        ApproveTransaction,
    },
    data() {
        return {
            btnLoading: false,
            tableLoading: false,
            alltransactions: [],
            terminals: [],
            cashes: [],
            heads: [],
            voucher: {
                id: null,
                type: null,
            },
            transactionDetail: {},
            editData: {
                ledgers: [],
                amounts: [],
                narrations: [],
            },
            addDataReset: {},
            addData: {
                type: "",
                terminal: "0",
                cash_ledger: "0",
                narration: "",
                ledgers: [],
                amounts: [],
                narrations: [],
            },
        };
    },
    created() {
        this.csrf = $('meta[name=csrf-token]').attr('content');
        this.cashTransactions();
        this.addDataReset = JSON.parse(JSON.stringify(this.addData));
    },
    mounted() {
    },
    computed: {
        optionsWithDefault() {
            return [{ id: 0, text: 'Select/None' }, ...this.pools];
        }
    },
    methods: {
        async cashTransactions() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/transactions/cash-transactions");
            if(res.status == 200)
            {
                this.alltransactions = res.data.cashTransactions;
                this.terminals = res.data.terminals;
                this.cashes = res.data.cashes;
                this.heads = res.data.heads;
                if ($.fn.DataTable.isDataTable("#transaction_table")) {
                    $('#transaction_table').DataTable().destroy();
                }
                setTimeout(function () {
                    $("#transaction_table").DataTable();
                }, 300);
            }
            this.tableLoading = false;
        },
        async addTransaction() {
            if(!this.addData.type)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select transaction type',
                });
            }
            if(this.addData.cash_ledger == 0)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Cash ledger is required',
                });
            }
            if(!this.addData.narration)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Cash Narration is required',
                });
            }
            if(this.addData.ledgers.length == 0 || this.addData.amounts.length == 0 || !this.addData.narrations.length)
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please add at least one receiver ledger record',
                });
                return false;
            }
            if(this.addData.ledgers.length != this.addData.amounts.length || this.addData.amounts.length != this.addData.narrations.length)
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ledger, Amount and Narration should not be empty in any row',
                });
                return false;
            }
            if (this.addData.amounts.some(amount => amount <= 0))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: "Amount should greater than 0 of all receivers",
                });
                return false;
            }
            if (this.indicateDuplication(this.addData.ledgers))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: "Please remove duplicate entry",
                });
                return false;
            }
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/transactions/cash-transactions/add", this.addData);
            if (res.status === 201) {
                this.cashTransactions();
                this.addData = JSON.parse(JSON.stringify(this.addDataReset));
                this.$refs.childComponentRef.transactionLoop = 0;
                this.$refs.childComponentRef.finalData = {
                    total_amount : 0,
                }
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Added',
                });
            }
            this.btnLoading = false;
        },
        async editTransaction(id,type) {
            this.voucher.id = id;
            this.voucher.type = type;
            const res = await this.callApi("post", "accounts/transactions/cash-transactions/edit", this.voucher);
            if (res.status === 200) {
                this.editData = res.data.transaction;
                $("#editTransaction").modal('show');
            }
        },
        async approveTransactionModal(id,type) {
            this.voucher.id = id;
            this.voucher.type = type;
            $("#approveTransactionModel").modal('show');
        },
        async approveTransaction() {
            const res = await this.callApi("post", "accounts/transactions/cash-transactions/do/approve", this.voucher);
            if (res.status === 200) {
                this.cashTransactions();
                this.addData = JSON.parse(JSON.stringify(this.addDataReset));
                $(".modal").modal('hide');
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Changed',
                });
            }
        },
        async showTransaction(id,type) {
            this.voucher.id = id;
            this.voucher.type = type;
            const res = await this.callApi("post", "accounts/transactions/cash-transactions/show", this.voucher);
            if (res.status === 200) {
                this.transactionDetail = res.data.transaction;
                $("#showTransaction").modal('show');
            }
        },
        pdfTransaction(id,type) {
            this.voucher.id = id;
            this.voucher.type = type;
            setTimeout(() => {
                this.$refs.transactionFormPdf.submit();
            }, 500);
        },
        async updateTransaction() {
            if(!this.editData.type)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select transaction type',
                });
            }
            if(this.editData.cash_ledger == 0)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Cash ledger is required',
                });
            }
            if(!this.editData.narration)
            {
                return swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Cash Narration is required',
                });
            }
            if(this.editData.ledgers.length == 0 || this.editData.amounts.length == 0 || !this.editData.narrations.length)
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please add at least one receiver ledger record',
                });
                return false;
            }
            if(this.editData.ledgers.length != this.editData.amounts.length || this.editData.amounts.length != this.editData.narrations.length)
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ledger, Amount and Narration should not be empty in any row',
                });
                return false;
            }
            if (this.editData.amounts.some(amount => amount <= 0))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: "Amount should greater than 0 of all receivers",
                });
                return false;
            }
            if (this.indicateDuplication(this.editData.ledgers))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: "Please remove duplicate entry",
                });
                return false;
            }
        
            this.btnLoading = true;
            const res = await this.callApi("post", "accounts/transactions/cash-transactions/update", this.editData);
            if (res.status === 200) {
                this.cashTransactions();
                $(".modal").modal('hide');
                swal({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfully Updated',
                });
            }
            this.btnLoading = false;
        },
        indicateDuplication(data) {
            const values = data.filter((item, index) => data.indexOf(item) !== index)

            if(values.length > 0)
            {
                return true;
            }
            return false
            
        },
    }
};
</script>
