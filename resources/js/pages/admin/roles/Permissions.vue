<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header">
              <h4>Permissions</h4>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between">
                      <h4>{{ role.name }}</h4>
                      <button class="btn btn-success" @click="save">SAVE</button>
                    </div>
                    <div class="card-body">


                      <div class="alert alert-success alert-dismissible fade show" role="alert" v-if="success">
                          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                              <span class="sr-only">Close</span>
                          </button>
                          {{ success }}
                      </div>

                      <div class="table-responsive">
                        <table class="table table-striped table-hover"
                        >
                          <thead>
                            <tr>
                              <th style="width: 7% !important;">Sr No.</th>
                              <th style="width: 15% !important">Module Name</th>
                              <th>Permissions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(permission, index) in permissions" :key="index">
                              <td>{{ (parseInt(index)+11) }}</td>
                              <td>{{ permission.name }}</td>
                              <td>
                                <label class="colorinput m-3" v-for="(operation,i) in permission" :key="i">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="permission[i]"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="permission[i]"
                                    />
                                    <span class="colorinput-color bg-primary"></span>
                                    <span style="position:relative;top:-10px;left:5px;"> {{ i }}</span>
                                  </span>
                                </label>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>

export default {
  name: "Role",
  data() {
    return {
      role: "",
      permissions:[],
      success: false,
    };
  },
  async created() {
    $('.modal').remove();
      const currentRouteName = this.$route.name;
      if (currentRouteName !== 'booking-page') {
          window.removeEventListener('keydown', this.enterKey);
          window.removeEventListener('keydown', this.altM);
      }
      // window.removeEventListener('keydown', this.enterKey);
      // window.removeEventListener('keydown', this.altM);
    let role_id = this.$route.params.id;
    const res = await this.callApi("post", "role/get", { id: role_id });
    if (res.status == 200) {
      this.role = res.data;
      this.permissions = {
        ...this.permissions,
          ...this.role.permissions
      };
    } else {
      console.log(res);
    }
  },

  methods: {
    async save() {
      const res = await this.callApi("post", "role/update",{
        id:this.role.id,
        permissions:this.permissions,
      })
      console.log(res);
      if (res.status == 201) {
        this.success = "Role and Permissions Updated Successfully";
        setTimeout(() => {
          this.success = "";
        }, 3000);
      }else {
        if (res.status == 422) {
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
      }
    }
  },
};
</script>
