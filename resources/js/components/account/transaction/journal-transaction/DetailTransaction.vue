<template>
    <div
        class="modal fade"
        id="showTransaction"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        {{ transactionDetail.length ? transactionDetail[0].type+'-'+transactionDetail[0].document_id : '*' }}
                    </h5>
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
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="investor_table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Code</th>
                                <th scope="col">Ledger</th>
                                <th scope="col">Narration</th>
                                <th scope="col">Debit</th>
                                <th scope="col">Credit</th>
                                <th scope="col">Added By</th>
                                <th scope="col">Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transaction, index) in transactionDetail" :key="index">
                                <th scope="row">{{ index + 1 }}</th>
                                <td>
                                    {{transaction.account_head.level_one.code}}-
                                    {{transaction.account_head.level_two.code}}-
                                    {{transaction.account_head.level_three.code}}-
                                    {{transaction.account_head.level_four.code}}-
                                    {{transaction.account_head.code}}
                                </td>
                                <td>{{transaction.account_head.name}}</td>
                                <td>{{transaction.narration}}</td>
                                <td>{{transaction.debit}}</td>
                                <td>{{transaction.credit}}</td>
                                <td>{{transaction.added_by_name.name??'N/A'}}</td>
                                <td>{{transaction.updated_by_name ? transaction.updated_by_name.name : 'N/A'}}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['transactionDetail'],
  methods: {
    add() {
      this.$emit('add');
    },
    closeModal() {
        $(".modal").click();
    },
  }
}
</script>