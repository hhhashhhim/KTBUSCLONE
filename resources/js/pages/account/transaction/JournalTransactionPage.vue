<template>
    <section>
        <div class="card">
            <div class="card-header justify-content-between">
                <h4>Journal Transaction</h4>
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
            :heads="heads"
            ref="childComponentRef"
            @add="addTransaction"
        />
        <!-- update modal -->
        <EditTransaction
            :btnLoading="btnLoading"
            :editData="editData"
            :terminals="terminals"
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
import NewTransaction from '../../../components/account/transaction/journal-transaction/NewComponent.vue';
import EditTransaction from '../../../components/account/transaction/journal-transaction/EditComponent.vue';
import DetailTransaction from '../../../components/account/transaction/journal-transaction/DetailTransaction.vue';
import ApproveTransaction from '../../../components/account/transaction/journal-transaction/ApproveTransaction.vue';
export default {
    name: "JournalPaymentPage",
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
            heads: [],
            voucher: {
                id: null,
                type: null,
            },
            transactionDetail: {},
            editData: {
                ledgers: [],
                credits: [],
                debits: [],
                narrations: [],
            },
            addDataReset: {},
            addData: {
                terminal: "0",
                ledgers: [],
                credits: [],
                debits: [],
                narrations: [],
            },
        };
    },
    created() {
        this.csrf = $('meta[name=csrf-token]').attr('content');
        this.journalTransactions();
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
        async journalTransactions() {
            this.tableLoading = true;
            const res = await this.callApi("get", "accounts/transactions/journal-transactions");
            if(res.status == 200)
            {
                this.alltransactions = res.data.journalTransactions;
                this.terminals = res.data.terminals;
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
            for (let i = 0; i < this.addData.credits.length; i++) {
                if (this.addData.ledgers[i] === 0 || (this.addData.credits[i] === 0 && this.addData.debits[i] === 0) || this.addData.narrations[i] === "") {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please fill all field of every row or remove extra row',
                    });
                    return false;
                }
                if (this.addData.credits[i] === this.addData.debits[i]) {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter some amount for credit/debit and second one should be 0',
                    });
                    return false;
                }
            }
            if (this.addData.ledgers.length < 2){
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter at least two record',
                });
                return false;
            }
            if(this.addData.credits.reduce((acc, current) => acc + parseFloat(current), 0) != this.addData.debits.reduce((acc, current) => acc + parseFloat(current), 0))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Sum of debit and credit should be equal',
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
            const res = await this.callApi("post", "accounts/transactions/journal-transactions/add", this.addData);
            if (res.status === 201) {
                this.journalTransactions();
                this.addData = JSON.parse(JSON.stringify(this.addDataReset));
                this.$refs.childComponentRef.transactionLoop = 1;
                this.$refs.childComponentRef.finalData = {
                    total_amount : 0,
                    credit : 0,
                    debit : 0,
                };
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
            const res = await this.callApi("post", "accounts/transactions/journal-transactions/edit", this.voucher);
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
            const res = await this.callApi("post", "accounts/transactions/journal-transactions/do/approve", this.voucher);
            if (res.status === 200) {
                this.journalTransactions();
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
            const res = await this.callApi("post", "accounts/transactions/journal-transactions/show", this.voucher);
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
            for (let i = 0; i < this.editData.credits.length; i++) {
                if (this.editData.ledgers[i] === 0 || (this.editData.credits[i] === 0 && this.editData.debits[i] === 0) || this.editData.narrations[i] === "") {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please fill all field of every row or remove extra row',
                    });
                    return false;
                }
                if (this.editData.credits[i] === this.editData.debits[i]) {
                    swal({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter some amount for credit/debit and second one should be 0',
                    });
                    return false;
                }
            }
            if (this.editData.ledgers.length < 2){
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter at least two record',
                });
                return false;
            }
            if(this.editData.credits.reduce((acc, current) => acc + parseFloat(current), 0) != this.editData.debits.reduce((acc, current) => acc + parseFloat(current), 0))
            {
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: 'Sum of debit and credit should be equal',
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
            const res = await this.callApi("post", "accounts/transactions/journal-transactions/update", this.editData);
            if (res.status === 200) {
                this.journalTransactions();
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
