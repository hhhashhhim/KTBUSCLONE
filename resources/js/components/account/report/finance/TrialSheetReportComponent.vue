<template>
    <div class="card">
        <div class="card-header d-block pb-0">
            <div class="d-flex justify-content-between">
                <h4>Trial Sheet</h4>
                <button
                    @click="generalTrialSheetReport()"
                    class="mr-1 btn-sm btn btn-icon btn-dark"
                    ><i class="fas fa-print"></i>
                </button>
            </div>
            <div class="row mt-4 px-0">
                <div class="col-md-4">
                    <h5 class="mb-0">General Ledger Trial Statement</h5>
                </div>
                <div class="col-md-4 text-center">
                    <h6 class="mb-0">From : {{ data.from }}</h6>
                </div>
                <div class="col-md-4 text-right">
                    <h6 class="mb-0">To : {{ data.to }}</h6>
                </div>
            </div>
            <div class="row mt-4 px-0 text-capitalize" v-if="data.terminal">
                <div class="col-md-4">
                    <h5 class="mb-0">Terminal : {{ data.terminal }}</h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" v-if="tableLoading">
                <BulletListLoader></BulletListLoader>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm" id="trial_table">
                    <thead>
                        <tr>
                            <th scope="col" class="border"></th>
                            <th colspan="2" scope="col" class="border text-center">Opening</th>
                            <th colspan="2" scope="col" class="border text-center">Duration</th>
                            <th colspan="2" scope="col" class="border text-center">Closing</th>
                        </tr>
                        <tr>
                            <th scope="col" class="border">Account</th>
                            <th scope="col" class="border">Debit</th>
                            <th scope="col" class="border">Credit</th>
                            <th scope="col" class="border">Debit</th>
                            <th scope="col" class="border">Credit</th>
                            <th scope="col" class="border">Debit</th>
                            <th scope="col" class="border">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(account, index) in data.record" :key="index">
                            <tr class="bg-dark text-white">
                                <td>{{ account.level_two_name }}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="bg-indigo" v-for="(group, i) in account.level_four" :key="i">
                                <td class="border pl-3">{{ group.level_four_name }}</td>
                                <td class="border">{{ group.previous_debits }}</td>
                                <td class="border">{{ group.previous_credits }}</td>
                                <td class="border">{{ group.current_debits }}</td>
                                <td class="border">{{ group.current_credits }}</td>
                                <td class="border">
                                    {{ 
                                        parseFloat(group.closing_credits) - parseFloat(group.closing_debits) > 0 ? 
                                        0 : parseFloat(group.closing_debits) - parseFloat(group.closing_credits)
                                    }}

                                </td>
                                <td class="border">
                                    {{ 
                                        parseFloat(group.closing_credits) - parseFloat(group.closing_debits) > 0 ? 
                                        parseFloat(group.closing_credits) - parseFloat(group.closing_debits) : 0
                                    }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border">Total</td>
                                <td class="border">{{ calculateBalance("previous_debits",index) }}</td>
                                <td class="border">{{ calculateBalance("previous_credits",index) }}</td>
                                <td class="border">{{ calculateBalance("current_debits",index) }}</td>
                                <td class="border">{{ calculateBalance("current_credits",index) }}</td>
                                <td class="border">-</td>
                                <td class="border">-</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'voucher', 'data'],
  data() {
        return {
        };
    },
  methods: {
    calculateBalance(flag,group) {
        let previousBalance = 0;

        if(flag == "previous_debits")
        {
            for (let j = 0; j < this.data.record[group].level_four.length; j++) {
                previousBalance += parseFloat(this.data.record[group].level_four[j].previous_debits);
            }
        }
        if(flag == "previous_credits")
        {
            for (let j = 0; j < this.data.record[group].level_four.length; j++) {
                previousBalance += parseFloat(this.data.record[group].level_four[j].previous_credits);
            }
        }
        if(flag == "current_debits")
        {
            for (let j = 0; j < this.data.record[group].level_four.length; j++) {
                previousBalance += parseFloat(this.data.record[group].level_four[j].current_debits);
            }
        }
        if(flag == "current_credits")
        {
            for (let j = 0; j < this.data.record[group].level_four.length; j++) {
                previousBalance += parseFloat(this.data.record[group].level_four[j].current_credits);
            }
        }
        // if(flag == "closing_debits")
        // {
        //     for (let i = 0; i < this.data.record.length; i++) {
        //         for (let j = 0; j < this.data.record[i].level_four.length; j++) {
        //             previousBalance += 
        //             (parseFloat(this.data.record[i].level_four[j].previous_credits) + parseFloat(this.data.record[i].level_four[j].current_credits));
        //             -
        //             (parseFloat(this.data.record[i].level_four[j].previous_debits) + parseFloat(this.data.record[i].level_four[j].current_debits))
        //         }
        //     }
        //     if(previousBalance > 0)
        //     {
        //         return 0
        //     }
        //     else
        //     {
        //         return previousBalance;
        //     }
        // }
        // if(flag == "closing_credits")
        // {
        //     for (let i = 0; i < this.data.record.length; i++) {
        //         for (let j = 0; j < this.data.record[i].level_four.length; j++) {
        //             previousBalance += 
        //             (parseFloat(this.data.record[i].level_four[j].previous_credits) + parseFloat(this.data.record[i].level_four[j].current_credits));
        //             -
        //             (parseFloat(this.data.record[i].level_four[j].previous_debits) + parseFloat(this.data.record[i].level_four[j].current_debits))
        //         }
        //     }
        //     if(previousBalance > 0)
        //     {
        //         return previousBalance;
        //     }
        //     else
        //     {
        //         return 0
        //     }
        // }
        
        return previousBalance;
    },
    generalTrialSheetReport() {
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