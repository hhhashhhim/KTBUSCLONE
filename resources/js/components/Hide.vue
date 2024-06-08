<template>
    <!--       id="delete-modal"  Modal -->
    <div
        class="modal fade"
        :id="hideForm"
        tabindex="-1"
        role="dialog"
        aria-labelledby="modelTitleId"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body pt-5">
                    <div class="card card-danger">
                        <div class="card-header d-flex justify-content-between">
                            <h4
                                class="modal-title text-center text-danger"
                                style="width: 97%"
                            >
                                <i class="fas fa-exclamation-circle fa-2x"></i> Confirmation
                            </h4>
                            <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                                aria-label="Close"
                                 @click="close()"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card-body text-center">
                            <div
                                class="alert alert-danger alert-dismissible fade show"
                                role="alert"
                                v-if="success"
                            >
                                <button
                                    type="button"
                                    class="close"
                                    data-dismiss="alert"
                                    aria-label="Close"
                                     @click="close()"
                                >
                                    <span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Close</span>
                                </button>
                                {{ success }}
                            </div>
                            <p class="font-weight-bold">
                                {{ confirmationMessage }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-block pt-0">
                    <slot name="button"></slot>
                    <button
                        type="button"
                        class="btn btn-secondary btn-block"
                        data-dismiss="modal"
                         @click="close()"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {mapGetters} from "vuex";

export default {
    props: {
        hideForm: String
    },
    data() {
        return {
            success: "",
        };
    },
    methods: {
        close(){
            $(`#${this.hideForm}`).click();
        },
    },
};
</script>
