<template>
    <!--       id="delete-modal"  Modal -->
    <div
        class="modal fade"
        :id="deleteForm"
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
                                <i class="fas fa-exclamation-circle fa-2x"></i> Delete
                                Confirmation
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

                    <button
                        type="button"
                        data-toggle="modal"
                        :data-target="`#${confirmModalID}`"
                        @click="checkAlert"
                        v-if="confirmModalID"
                        class="btn btn-danger btn-block"
                    >
                        Yes, I want to Delete
                    </button>

                    <button
                        type="button"
                        @click="deleteData"
                        v-else
                        class="btn btn-danger btn-block"
                    >
                        Yes, I want to Delete
                    </button>
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
        confirmationMessage: String,
        confirmModalID: String,
        deleteForm: String
    },
    data() {
        return {
            success: "",
        };
    },
    methods: {
        close(){
            $(`#${this.deleteForm}`).click();
        },
        async deleteData() {
            const res = await this.callApi(
                "post",
                this.deletModalInfo.url,
                this.deletModalInfo.data
            );
            if (res.status == 200) {
                const deletingObj = {
                    ...this.deletModalInfo,
                    url: "",
                    data: "",
                    isDeleted: true,
                };
                this.$store.commit("setDeleteObj", deletingObj);
                // this.success = "Record Deleted !!!";
                swal({
                    title: "Success",
                    text: "Record Deleted !!! ",
                    icon: "success",
                    timer: 2000
                });
                setTimeout(() => {
                    this.success = "";
                    $("#delete-modal").modal("hide");
                }, 1000);
            } else {
                if (res.status == 422) {
                    console.log();
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errors(element, key);
                        });
                    }
                }
            }
        },
    },
    computed: {
        ...mapGetters({
            deletModalInfo: "getDeletingObj",
        }),
    },
    watch: {
        success(newSuccess, oldSuccess) {
            if (newSuccess != "") {
                swal(
                    "Record Deleted!", //Heading
                    newSuccess, // Message
                    "success" // Status
                );
            }
        },
    },
};
</script>
