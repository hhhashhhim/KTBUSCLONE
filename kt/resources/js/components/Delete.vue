<template>
  <!-- Modal -->
  <div
    class="modal fade"
    id="delete-modal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modelTitleId"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg" role="document">
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
          <div
            v-if="doubleCheckIncluded"
          >
          <ConfirmationModal 
            :formID="doubleCheckIncluded"
            v-on:deleteData="deleteData(event)"
          />
            <button
              type="button"
              class="btn btn-danger btn-block"
              @click="deleteData"
            >
              Yes, I want to Delete
            </button>
          </div>
          <div
            v-else
          >  
          <button
            type="button"
            class="btn btn-danger btn-block"
            @click="passwordInput"
          >
            Yes
          </button>
          </div>
          <button
            type="button"
            class="btn btn-secondary btn-block"
            data-dismiss="modal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
import ConfirmationModal from "./ConfirmationModal.vue";
export default {
  components: { ConfirmationModal },
  props: {
    confirmationMessage: String,
    doubleCheckIncluded: String,
  },
  data() {
    return {
      success: "",
    };
  },
  methods: {
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
        this.success = "Company Deleted !!!";
        setTimeout(() => {
          this.success = "";
          $("#delete-modal").modal("hide");
        }, 3000);
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
    authorized(data){

    }
  },
  computed: {
    ...mapGetters({
      deletModalInfo: "getDeletingObj",
    }),
  },
  watch: {
    success(newSuccess, oldSuccess) {
        swal(
            "Record Deleted!", //Heading
            newSuccess, // Message
            "success" // Status
        );
    },
  },
};
</script>