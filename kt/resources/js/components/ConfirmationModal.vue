<template>
  <!-- Modal -->
  <div
    class="modal fade"
    :id="`#${formID}`"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modelTitleId"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-body pt-5">
          <div class="card card-danger">
            <div class="card-header d-flex justify-content-between">
              <h4
                class="modal-title text-center text-danger"
                style="width: 97%"
              >
                <i class="fas fa-exclamation-circle fa-2x"></i> Warning Cruicial Data Deletion Found
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
export default {
  props: {
    formID: String,
  },
  data(){
    return{
      password:"",
    }
  },
  methods:{
    async checkPassword(){
      
      if (this.password == "")
        return this.errorsArray("Password Field Is Required");

      const res = await this.callApi("post", "/double-check", {password:this.password});
      if (res.status == 201) {
        this.success = "Company Created Successfully";
        this.companies.unshift(res.data);
        this.data.name = this.data.contact = this.data.location = "";
        this.data.modules = this.defaultModules;
        setTimeout(() => {
          this.success = "";
          $("#add-modal").modal("hide");
        }, 2000);
      } else {
        if (res.status == 422) {
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
      }
    }
  }
};
</script>