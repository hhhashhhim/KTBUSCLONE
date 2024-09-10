<template>
    <div
        class="modal fade"
        id="newHeadBank"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        New Bank Ledger
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Bank Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" v-model="addData.name">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Bank Address <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" v-model="addData.address">
                        </div>
                        <div class="form-group col-md-6">
                            <label>IBAN <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" v-model="addData.iban">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Account Number <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" v-model="addData.account_number">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-primary" :class="{ 'disabled btn-progress': btnLoading }" @click=add()>Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'addData'],
  data() {
        return {
        };
    },
  methods: {
    add() {
      this.$emit('add');
    },
  }
}
</script>