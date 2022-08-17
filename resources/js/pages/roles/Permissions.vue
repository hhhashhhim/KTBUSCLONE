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
                      <h4>{{ role.name }} of {{ role.company?role.company.name:"Not Found" }}</h4>
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
                        <table
                          class="table table-striped table-hover"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                              <th>Sr No.</th>
                              <th>Module Name</th>
                              <th>Permissions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="(moduleName,i) in permissions" :key="i">
                            <tr>
                              <td>{{ i+1 }}</td>
                              <td> 
                                <div class="text-capitalize">
                                  {{ moduleName.name }}
                                </div>
                              </td>
                              <td>
                                <label class="colorinput mx-3">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="mod"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="moduleName.allow"
                                    />
                                    <span class="colorinput-color bg-success"></span>
                                  </span>
                                </label>
                              </td>
                            </tr>
                            <tr v-if="moduleName.allow">
                              <td colspan="2"></td>
                                <td class="py-5">
                                  <label class="colorinput mx-3" v-for="(menus,j) in moduleName.childs" :key="j">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="menus"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="menus.allow"
                                    />
                                    <span class="colorinput-color bg-success"></span>
                                    <span style="position:relative;left:5px;top:-10px;" class="text-capitalize"> {{ menus.name }} </span>
                                  </span>
                                </label>
                                </td>
                            </tr>
                            </template>
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
      permissions: [],
      success: false,
    };
  },
  async created() {
    const res = await this.callApi("post", "/role/get", { id: this.$route.params.id });
    if (res.status == 200) {
      this.role = res.data.role;
      this.permissions = res.data.permissions; 
    } else {
      console.log(res);
    }
  },
  methods: {
    async save() {
      const res = await this.callApi("post", "/role/update",{
        ...this.role,
        permissions:this.permissions,
      })
      if (res.status == 201) {
        this.success = "Role and Permissions Updated Successfully";
        setTimeout(() => {
          this.success = "";
        }, 3000);
      }
      else {
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