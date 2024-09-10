<template>
    <div
        class="modal fade"
        id="approveTransactionModel"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        Confirmation
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
                    <div class="my-2">
                        <h6 class="mb-2">Do you want to change the status of {{voucher.type+'-'+voucher.id}} from pending to approved ?</h6>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" :class="btnLoading ? status+'disabled btn-progress' : status" @click=approve()>Yes</button>
                    <button type="button" class="btn btn-block btn-secondary mt-0" data-dismiss="modal"  @click="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['btnLoading', 'status', "voucher"],
    data() {
        return {
        }
    },
    created(){
    },
    methods: {
        approve() {
            this.$emit('approve');
        },
        closeModal() {
            $(".modal").click();
        },
    }
}
</script>