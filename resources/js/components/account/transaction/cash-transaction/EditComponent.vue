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
                        <div class="form-group col-md-6">
                            <label class="d-block">Transaction Type <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="cpEdit" value="CP" v-model="editData.type">
                            <label class="form-check-label" for="cpEdit">Cash Payment</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="brEdit" value="CR" v-model="editData.type">
                            <label class="form-check-label" for="brEdit">Cash Receipt</label>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Terminal</label>
                            <select class="form-control" v-model="editData.project_id">
                                <option value="0">Select From Following</option>
                                <option
                                    v-for="(terminal, i) in terminals"
                                    :key="i"
                                    :value="terminal.id"
                                >
                                    {{ terminal.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Cash Ledger <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="editData.account_head_id">
                                <option value="0">Select From Following</option>
                                <option
                                    v-for="(cash, i) in cashes"
                                    :key="i"
                                    :value="cash.id"
                                >
                                    {{ cash.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Cash Narration <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="editData.narration"/>
                        </div>
                    </div>
                    <div class="border p-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <h5>Transaction <small class="text-danger">(Amount  {{ finalData.total_amount }})</small></h5>
                            <div>
                                <button type="button" class="btn btn-outline-success" @click="addTransactionRow">Add Account</button>
                            </div>
                        </div>
                        <div class="row" v-if="transactionLoop > 0">
                            <div class="form-group col-md-4">
                                <label>Ledger <span class="text-danger">*</span></label>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Amount <span class="text-danger">*</span></label>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Narration <span class="text-danger">*</span></label>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Remove</label>
                            </div>
                        </div>
                        <div class="row" v-for="(i, index) in transactionLoop" :key="index">
                            <div class="form-group mb-2 col-md-4">
                                <select class="form-control" v-model="editData.ledgers[index]">
                                    <option value="0">Select From Following</option>
                                    <option
                                        v-for="(head, i) in heads"
                                        :key="i"
                                        :value="head.id"
                                    >
                                        {{ head.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group mb-2 col-md-3">
                                <input class="form-control" type="text" :value="editData.amounts[index]" @keyup="saveTransactionRow($event, 'second', index)" onkeypress="numberValidate(event,{dot:true})">
                            </div>
                            <div class="form-group mb-2 col-md-3">
                                <input class="form-control" type="text" :value="editData.narrations[index]" @change="saveTransactionRow($event, 'third', index)" />
                            </div>
                            <div class="form-group mb-2 col-md-2">
                                <button type="button" class="mt-1 btn-sm btn btn-outline-danger" @click="removeTransactionRow($event, index)"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-primary" :class="{ 'disabled btn-progress': btnLoading }" @click=add()>Save</button>
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
        finalData: {
            total_amount : 0,
        },
        transactionLoop: 0,
    }
  },
  created(){
        this.finalData.total_amount = this.editData.credit;
        this.transactionLoop = this.editData.ledgers.length;

  },
    watch: {
        editData: {
            immediate: true, // This will also run the watcher when the component is created
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
            if (fieldName == "first") {
                this.editData.ledgers[index] = event.id;
            }
            if (fieldName == "second") {
                this.editData.amounts[index] = event.target.value ? event.target.value : 0;
            }
            if (fieldName == "third") {
                this.editData.narrations[index] = event.target.value;
            }
            this.totalAmount();
        },
        addTransactionRow() {
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
        totalAmount()
        {
            this.finalData.total_amount = this.editData.amounts.reduce((acc, current) => acc + parseFloat(current), 0);
        },
        closeModal() {
            $(".modal").click();
        },
    }
}
</script>