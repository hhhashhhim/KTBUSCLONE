<template>
    <div
        class="modal fade"
        id="newTransaction"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        New Journal Transaction
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
                    <!-- Terminal -->
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>Terminal</label>
                            <select2 
                                v-model="addData.terminal" 
                                :options="terminalsMapped" 
                                :settings="{ width: '100%', dropdownParent: '#newTransaction', placeholder: 'Select From Following' }"
                            />
                        </div>
                    </div>

                    <!-- Transaction Table -->
                    <div class="border p-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <h5>
                                Transaction 
                                <small :class="finalData.total_amount == 0 && (finalData.credits > 0 || finalData.debits > 0) ? 'text-success' : 'text-danger'">
                                    Credit({{ finalData.credits }}) - Debit({{ finalData.debits }}) = Difference({{ finalData.total_amount }})
                                </small>
                            </h5>
                            <div>
                                <button type="button" class="btn btn-outline-success" @click="addTransactionRow">Add Account</button>
                            </div>
                        </div>

                        <!-- Table Head -->
                        <div class="row" v-if="transactionLoop > 0">
                            <div class="form-group col-md-3"><label>Ledger <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-2"><label>Credit <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-2"><label>Debit <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-3"><label>Narration <span class="text-danger">*</span></label></div>
                            <div class="form-group col-md-2"><label>Remove</label></div>
                        </div>

                        <!-- Rows -->
                        <div class="row" v-for="(i, index) in transactionLoop" :key="index">
                            <div class="form-group mb-2 col-md-3">
                                <select2 
                                    v-model="addData.ledgers[index]" 
                                    :options="headsMapped"
                                    @select="saveTransactionRow($event, 'first', index)"
                                    :settings="{ width: '100%', dropdownParent: '#newTransaction', placeholder: 'Select From Following' }"
                                />
                            </div>

                            <div class="form-group mb-2 col-md-2">
                                <input class="form-control" type="text" 
                                    :value="addData.credits[index]" 
                                    :disabled="addData.debits[index] > 0" 
                                    @keyup="saveTransactionRow($event, 'second', index)" 
                                    @keypress="$numberValidate($event,{dot:true})">
                            </div>

                            <div class="form-group mb-2 col-md-2">
                                <input class="form-control" type="text" 
                                    :value="addData.debits[index]" 
                                    :disabled="addData.credits[index] > 0" 
                                    @keyup="saveTransactionRow($event, 'third', index)" 
                                    @keypress="$numberValidate($event,{dot:true})">
                            </div>

                            <div class="form-group mb-2 col-md-3">
                                <input class="form-control" type="text" 
                                    :value="addData.narrations[index]" 
                                    @change="saveTransactionRow($event, 'fourth', index)" 
                                    @keyup.enter="addTransactionRow" />
                            </div>

                            <div class="form-group mb-2 col-md-2">
                                <button type="button" class="mt-1 btn-sm btn btn-outline-danger" @click="removeTransactionRow($event, index)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
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
    props: ['btnLoading', 'addData', 'terminals', 'heads'],
    data() {
        return {
            finalData: { total_amount: 0, credits: 0, debits: 0 },
            transactionLoop: 1
        }
    },
    computed: {
        terminalsMapped() {
            return this.terminals.map(t => ({ id: t.id, text: t.name }));
        },
        headsMapped() {
            return this.heads.map(h => ({ id: h.id, text: h.name }));
        }
    },
    created() {
        // Initialize first row
        this.addData.ledgers.push("0");
        this.addData.credits.push(0);
        this.addData.debits.push(0);
        this.addData.narrations.push("");
    },
    methods: {
        add() {
            this.$emit('add');
        },
        saveTransactionRow(event, fieldName, index) {
            if (fieldName === "first") this.addData.ledgers[index] = event.id;
            if (fieldName === "second") this.addData.credits[index] = event.target.value ? event.target.value : 0;
            if (fieldName === "third") this.addData.debits[index] = event.target.value ? event.target.value : 0;
            if (fieldName === "fourth") this.addData.narrations[index] = event.target.value;
            this.totalAmount();
        },
        addTransactionRow() {
            this.transactionLoop++;
            this.addData.ledgers.push("0");
            this.addData.credits.push(0);
            this.addData.debits.push(0);
            this.addData.narrations.push("");
            this.totalAmount();
        },
        removeTransactionRow(event, index) {
            this.addData.ledgers.splice(index, 1);
            this.addData.credits.splice(index, 1);
            this.addData.debits.splice(index, 1);
            this.addData.narrations.splice(index, 1);
            this.transactionLoop--;
            this.totalAmount();
        },
        totalAmount() {
            this.finalData.credits = this.addData.credits.reduce((acc, val) => acc + parseFloat(val || 0), 0);
            this.finalData.debits = this.addData.debits.reduce((acc, val) => acc + parseFloat(val || 0), 0);
            this.finalData.total_amount = this.finalData.credits - this.finalData.debits;
        },
        closeModal() {
            $(".modal").click();
        }
    }
}
</script>
