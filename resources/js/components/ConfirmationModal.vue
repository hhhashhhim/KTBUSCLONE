<template>
  <!-- Modal -->
  <div
    class="modal fade"
    :id="`${formID}`"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modelTitleId"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body pt-5">
          <div class="card card-danger">
            <div class="card-header d-flex justify-content-between">
              <h4
                class="modal-title text-center text-danger"
                style="width: 97%"
              >
                <i class="fas fa-exclamation-circle fa-2x"></i> Warning Crucial Data Deletion Found
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
                class="alert alert-success alert-dismissible fade show"
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
              <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
                v-if="error"
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
                {{ error }}
              </div>
              <p class="font-weight-bold">
                Are You Sure You want to Delete this Record? If Yes Please Enter Your Account Password to Further Proceed
              </p>
              <input type="password" class="form-control" v-model="password">
            </div>
          </div>
        </div>
        <div class="modal-footer d-block pt-0">
          <button
            type="button"
            class="btn btn-danger btn-block"
            @click="checkPassword"
          >
            Confirm My Action
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
export default {
  name:'ConfirmationModal',
  props: {
    formID: String,
  },
  created(){
    console.log("from confirm modal",this.formID);
  },
  data(){
    return{
      password:"",
      error:"",
    }
  },
  methods:{
    close(){
            $(`#${this.formID}`).click();
        },
    async checkPassword(){
      
      if (this.password == "")
        return this.errorsArray("Password Field Is Required");

      const res = await this.callApi("post", "double-check", {password:this.password});
      if (res.status == 200) {
        this.$emit('confirmDeleteModal',{
          details:this.getDeletingObj.data,
          url:this.getDeletingObj.url,
        })
        this.password=""
        this.success = "Company Deleted";
      } else {
        this.error = "Unauthorized !!!"
      }
    }
  },
  computed: {
    ...mapGetters(["getDeletingObj"]),
  },
};
</script>