<template>
    <div class="card">
        <div class="card-header d-block pb-0">
            <div class="d-flex justify-content-between">
                <h4>General Ledger</h4>
                <button
                    @click="printDailyReport()"
                    class="mr-1 btn-sm btn btn-icon btn-dark"
                    ><i class="fas fa-print"></i>
                </button>
            </div>
            <div class="row mt-4 px-0">
                <div class="col-md-4">
                    <h5 class="mb-0">Terminal : {{ daily_data.terminal }}</h5>
                </div>
                <div class="col-md-4 text-center">
                    <h6 class="mb-0">Date : {{ daily_data.current }}</h6>
                </div>
            </div>
            <div class="row mt-4 px-0 text-capitalize" >
                
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" v-if="tableLoading">
                <BulletListLoader></BulletListLoader>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm" id="daily_table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Voucher</th>
                            <th scope="col">Account</th>
                            <th scope="col">Narration</th>
                            <th scope="col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-dark text-white" v-if="daily_data.record.BP">
                            <th>Bank Payments</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr v-for="(transaction, index) in daily_data.record.BP" :key="index">
                            <td>{{ formatDate(transaction.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', transaction.document_id, transaction.type)">{{ transaction.type }}-{{ transaction.document_id }}</a></td>
                            <td>{{ transaction.account }} ({{ transaction.parent_account }})</td>
                            <td>{{ transaction.narration }}</td>
                            <td>{{ transaction.amount }}</td>
                        </tr>
                        <tr class="bg-dark text-white" v-if="daily_data.record.BR">
                            <th>Bank Receipts</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr v-for="(transaction, index) in daily_data.record.BR" :key="index">
                            <td>{{ formatDate(transaction.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', transaction.document_id, transaction.type)">{{ transaction.type }}-{{ transaction.document_id }}</a></td>
                            <td>{{ transaction.account }} ({{ transaction.parent_account }})</td>
                            <td>{{ transaction.narration }}</td>
                            <td>{{ transaction.amount }}</td>
                        </tr>
                        <tr class="bg-dark text-white" v-if="daily_data.record.CP">
                            <th>Cash Payments</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr v-for="(transaction, index) in daily_data.record.CP" :key="index">
                            <td>{{ formatDate(transaction.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', transaction.document_id, transaction.type)">{{ transaction.type }}-{{ transaction.document_id }}</a></td>
                            <td>{{ transaction.account }} ({{ transaction.parent_account }})</td>
                            <td>{{ transaction.narration }}</td>
                            <td>{{ transaction.amount }}</td>
                        </tr>
                        <tr class="bg-dark text-white" v-if="daily_data.record.CR">
                            <th>Cash Receipts</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr v-for="(transaction, index) in daily_data.record.CR" :key="index">
                            <td>{{ formatDate(transaction.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', transaction.document_id, transaction.type)">{{ transaction.type }}-{{ transaction.document_id }}</a></td>
                            <td>{{ transaction.account }} ({{ transaction.parent_account }})</td>
                            <td>{{ transaction.narration }}</td>
                            <td>{{ transaction.amount }}</td>
                        </tr>
                        <tr class="bg-dark text-white" v-if="daily_data.record.JV">
                            <th>Journal Voucher</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr v-for="(transaction, index) in daily_data.record.JV" :key="index">
                            <td>{{ formatDate(transaction.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', transaction.document_id, transaction.type)">{{ transaction.type }}-{{ transaction.document_id }}</a></td>
                            <td>{{ transaction.account }} ({{ transaction.parent_account }})</td>
                            <td>{{ transaction.narration }}</td>
                            <td>{{ transaction.amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'voucher', 'daily_data'],
  data() {
        return {
        };
    },
  methods: {
    calculateBalance(index) {
        let previousBalance = parseFloat(this.general_ledgers.previous_credits) - parseFloat(this.general_ledgers.previous_debits);

        // Iterate over all the ledgers up to the current index to calculate running balance
        for (let i = 0; i <= index; i++) {
            previousBalance += parseFloat(this.general_ledgers.record[i].credit) - parseFloat(this.general_ledgers.record[i].debit);
        }

        let sign = previousBalance > 0 ? ' (Cr)' : ' (Dr)'; 
        
        return (Math.abs(previousBalance).toFixed(2)) + sign;
    },
    formatDate(date) {
        const newDate = new Date(date);
            
        const day = newDate.getDate().toString().padStart(2, '0'); // Pads the day with a leading zero if needed
        const month = newDate.toLocaleString('default', { month: 'short' }).toLowerCase(); // Gets the full month name in lowercase
        const year = newDate.getFullYear(); // Gets the full year

        return `${day}-${month}-${year}`;
    },
    printDailyReport() {
      this.$emit('print');
    },
  }
}
</script>

<style scoped>
    table.dataTable {
        border-collapse: collapse !important;
    }    
</style>