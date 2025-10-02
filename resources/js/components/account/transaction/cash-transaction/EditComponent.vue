<template>
    <div
        class="modal fade"
        id="editTransaction"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        Update Cash Transaction
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
                    <div class="row">
                        <!-- Transaction Type -->
                        <div class="form-group col-md-6">
                            <label class="d-block">Transaction Type <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="cpEdit" value="CP" v-model="editData.type">
                                <label class="form-check-label" for="cpEdit">Cash Payment</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="crEdit" value="CR" v-model="editData.type">
                                <label class="form-check-label" for="crEdit">Cash Receipt</label>
                            </div>
                        </div>

                        <!-- Terminal -->
                        <div class="form-group col-md-12">
                            <label>Terminal</label>
                            <select2 
                                v-model="editData.terminal_id" 
                                :options="terminalsMapped"
                                :settings="{ width: '100%', dropdownParent: '#editTransaction', placeholder: 'Select From Following' }"
                            />
                        </div>

                        <!-- Cash Ledger -->
                        <div class="form-group col-md-6">
                            <label>Cash Ledger <span class="text-danger">*</span></label>
                            <select2 
                                v-model="editData.account_head_id" 
                                :options="cashesMapped"
                                :settings="{ width: '100%', dropdownParent: '#editTransaction', placeholder: 'Select From Following' }"
                            />
                        </div>

                        <!-- Cash Narration -->
                        <div class="form-group col-md-6">
                            <label>Cash Narration <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="editData.narration"/>
                        </div>
                    </div>

                    <!-- Transaction Rows -->
                    <div class="border p-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <h5>Transaction <small class="text-danger">(Amount  {{ finalData.total_amount }})</small></h5>
                            <div>
                                <button type="button" class="btn btn-outline-success" @click="addTransactionRow">Add Account</button>
                            </div>
                        </div>

                        <!-- Table Head -->
                        <div class="row" v-if="transactionLoop > 0">
                            <div class="form-group col-md-4"><label>Ledger <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-3"><label>Amount <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-3"><label>Narration <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-2"><label>Remove</label></div>
                        </div>

                        <!-- Transaction Row Items -->
                        <div class="row" v-for="(i, index) in transactionLoop" :key="index">
                            <div class="form-group mb-2 col-md-4">
                                <select2 
                                    v-model="editData.ledgers[index]"
                                    :options="headsMapped"
                                    @select="saveTransactionRow($event, 'first', index)"
                                    :settings="{ width: '100%', dropdownParent: '#editTransaction', placeholder: 'Select From Following' }"
                                />
                            </div>
                            <div class="form-group mb-2 col-md-3">
                                <input class="form-control" type="text" 
                                    :value="editData.amounts[index]" 
                                    @keyup="saveTransactionRow($event, 'second', index)" 
                                    @keypress="$numberValidate($event,{dot:true})"
                                >
                            </div>
                            <div class="form-group mb-2 col-md-3">
                                <input class="form-control" type="text" 
                                    :value="editData.narrations[index]" 
                                    @change="saveTransactionRow($event, 'third', index)" 
                                />
                            </div>
                            <div class="form-group mb-2 col-md-2">
                                <button type="button" class="mt-1 btn-sm btn btn-outline-danger" @click="removeTransactionRow($event, index)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-primary" :class="{ 'disabled btn-progress': btnLoading }" @click="add()">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'editData', 'terminals', 'cashes', 'heads'],
  data() {
    return {
        finalData: { total_amount : 0 },
        transactionLoop: 0,
    }
  },
  computed: {
      terminalsMapped() {
          return this.terminals.map(t => ({ id: t.id, text: t.name }));
      },
      cashesMapped() {
          return this.cashes.map(c => ({ id: c.id, text: c.name }));
      },
      headsMapped() {
          return this.heads.map(h => ({ id: h.id, text: h.name }));
      }
  },
  created() {
        this.finalData.total_amount = this.editData.credit || 0;
        this.transactionLoop = this.editData.ledgers.length;
  },
  watch: {
        editData: {
            immediate: true,
            handler(newVal) {
                if (newVal && newVal.ledgers) {
                    this.finalData.total_amount = newVal.credit || 0;
                    this.transactionLoop = newVal.ledgers.length;
                }
            }
        }
  },
  methods: {
        add() {
            this.$emit('update');
        },
        saveTransactionRow(event, fieldName, index) {
            if (fieldName === "first") this.editData.ledgers[index] = event.id;
            if (fieldName === "second") this.editData.amounts[index] = event.target.value ? event.target.value : 0;
            if (fieldName === "third") this.editData.narrations[index] = event.target.value;
            this.totalAmount();
        },
        addTransactionRow() {
            this.editData.ledgers.push(null);
            this.editData.amounts.push(0);
            this.editData.narrations.push('');
            this.transactionLoop++;
            this.totalAmount();
        },
        removeTransactionRow(event, index) {
            this.editData.ledgers.splice(index, 1);
            this.editData.amounts.splice(index, 1);
            this.editData.narrations.splice(index, 1);
            this.transactionLoop--;
            this.totalAmount();
        },
        totalAmount() {
            this.finalData.total_amount = this.editData.amounts.reduce((acc, val) => acc + parseFloat(val || 0), 0);
        },
        closeModal() {
            $(".modal").click();
        }
  }
}
</script>
