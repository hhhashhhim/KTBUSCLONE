<template>
    <div class="card">
        <div class="card-header justify-content-between">
            <h4>Receipt Report</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive" v-if="tableLoading">
                <BulletListLoader></BulletListLoader>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm" id="receipt_table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Account</th>
                            <th scope="col">Terminal</th>
                            <th scope="col">Receipt</th>
                            <th scope="col">Voucher</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Narration</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(receipt, index) in receipts" :key="index">
                            <td>{{formatDate(receipt.created_at)}}</td>
                            <td>{{receipt.account_head.name}}</td>
                            <td>{{receipt.terminal ? receipt.terminal.name : 'N/A'}}</td>
                            <td>{{receipt.receipt_id}}</td>
                            <td><a href="#" @click="$emit('print-voucher', receipt.document_id, receipt.type)">{{receipt.type}}-{{receipt.document_id}}</a></td>
                            <td>{{receipt.credit > 0 ? receipt.credit : receipt.debit }}</td>
                            <td>{{receipt.narration}}</td>
                            <td>
                                <button
                                    @click="printReceiptSlip(receipt.document_id,receipt.type,receipt.id)"
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
</template>

<script>
export default {
  props: ['btnLoading', 'voucher', 'receipts'],
  data() {
        return {
        };
    },
  methods: {
    formatDate(date) {
        const newDate = new Date(date);
            
        const day = newDate.getDate().toString().padStart(2, '0'); // Pads the day with a leading zero if needed
        const month = newDate.toLocaleString('default', { month: 'short' }).toLowerCase(); // Gets the full month name in lowercase
        const year = newDate.getFullYear(); // Gets the full year

        return `${day}-${month}-${year}`;
    },
    printReceiptSlip(id,type,posting_id) {
        this.voucher.id = id;
        this.voucher.type = type;
        this.voucher.posting_id = posting_id;
      this.$emit('print');
    },

  }
}
</script>