<template>
    <div class="card">
        <div class="card-header d-block pb-0">
            <div class="d-flex justify-content-between">
                <h4>Ledger</h4>
                <button
                    @click="printLedgerReport()"
                    class="mr-1 btn-sm btn btn-icon btn-dark"
                    ><i class="fas fa-print"></i>
                </button>
            </div>
            <div class="row mt-4 px-0">
                <div class="col-md-4">
                    <h5 class="mb-0">{{ ledgers.head.name }}<br>({{ ledgers.head.level_four.name }})</h5>
                </div>
                <div class="col-md-4 text-center">
                    <h6 class="mb-0">From : {{ ledgers.from }}</h6>
                </div>
                <div class="col-md-4 text-right">
                    <h6 class="mb-0">To : {{ ledgers.to }}</h6>
                </div>
            </div>
            <div class="row mt-4 px-0 text-capitalize" v-if="ledgers.terminal">
                <div class="col-md-4">
                    <h5 class="mb-0">Terminal : {{ ledgers.terminal }}</h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" v-if="tableLoading">
                <BulletListLoader></BulletListLoader>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm" id="ledger_table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Voucher</th>
                            <th scope="col">Narration</th>
                            <th scope="col">Debit</th>
                            <th scope="col">Credit</th>
                            <th scope="col">Balance</th>
                        </tr>
                        <tr class="bg-secondary">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-danger">Opening 
                                {{ Math.abs(parseFloat(ledgers.previous_credits) - parseFloat(ledgers.previous_debits)) }}
                                ({{ parseFloat(ledgers.previous_credits) > parseFloat(ledgers.previous_debits) ? 'Cr' : 'Dr' }})
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(ledger, index) in ledgers.record" :key="index">
                            <td>{{ formatDate(ledger.created_at) }}</td>
                            <td><a href="#" @click="$emit('print-voucher', ledger.document_id, ledger.type)">{{ ledger.type }}-{{ ledger.document_id }}</a></td>
                            <td>{{ ledger.narration }}</td>
                            <td>{{ Math.abs(ledger.debit) }}</td>
                            <td>{{ Math.abs(ledger.credit) }}</td>
                            <td>
                                {{
                                    calculateBalance(index)
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-dark">
                            <td class="text-white"></td>
                            <td class="text-white"></td>
                            <td class="text-white"></td>
                            <td class="text-white">{{ ledgers.debits }}</td>
                            <td class="text-white">{{ ledgers.credits }}</td>
                            <td class="text-white">
                                {{
                                    Math.abs(
                                        (parseFloat(ledgers.previous_credits) + parseFloat(ledgers.credits)) -
                                        (parseFloat(ledgers.previous_debits) + parseFloat(ledgers.debits))
                                    )
                                }}
                                ({{ (parseFloat(ledgers.credits) + parseFloat(ledgers.previous_credits)) >
                                    (parseFloat(ledgers.debits) + parseFloat(ledgers.previous_debits)) ? 'Cr' : 'Dr' }})
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'voucher', 'ledgers'],
  data() {
        return {
        };
    },
  methods: {
    calculateBalance(index) {
        let previousBalance = parseFloat(this.ledgers.previous_credits) - parseFloat(this.ledgers.previous_debits);

        // Iterate over all the ledgers up to the current index to calculate running balance
        for (let i = 0; i <= index; i++) {
            previousBalance += parseFloat(this.ledgers.record[i].credit) - parseFloat(this.ledgers.record[i].debit);
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
    printLedgerReport() {
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