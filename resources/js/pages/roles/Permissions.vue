<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Permissions</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between">
                                            <h4>{{ role.name }} of {{
                                                    role.company ? role.company.name : "Not Found"
                                                }}</h4>
                                            <button class="btn btn-primary" @click="save">SAVE</button>
                                        </div>
                                        <div class="card-body">


                                            <div class="alert alert-success alert-dismissible fade show" role="alert"
                                                 v-if="success">
                                                <button type="button" class="close" data-dismiss="alert"
                                                        aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                    <span class="sr-only">Close</span>
                                                </button>
                                                {{ success }}
                                            </div>

                                            <div v-for="(moduleName, i) in permissions" :key="i">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="alert alert-info" style="background-color: #d9edf7 !important">
                                                            <div class="row">
                                                                <div class="col-md-2">
                                                                    <input :checked="mod" type="checkbox" :value="true"
                                                                           v-model="moduleName.allow"
                                                                           :id="moduleName.name"/>
                                                                    <label class="text-capitalize text-bold ml-1"
                                                                           :for="moduleName.name" style="color: black">
                                                                        {{
                                                                            moduleName.name
                                                                        }}</label>
                                                                </div>
                                                                <div class="col-md-10" v-if="moduleName.allow">
                                                                    <div class="alert alert-success" style="background-color: #dff0d8 !important"
                                                                         v-for="(menus, j) in moduleName.childs"
                                                                         :key="j">
                                                                        <div class="row">
                                                                            <div class="col-md-2">
                                                                                <input :checked="menus" type="checkbox"
                                                                                       :value="true"
                                                                                       v-model="menus.allow"
                                                                                       :id="menus.name"/>
                                                                                <label
                                                                                    class="text-capitalize text-bold ml-1"
                                                                                    :for="menus.name"
                                                                                    style="color: black">{{
                                                                                        menus.name
                                                                                    }}</label>
                                                                            </div>
                                                                            <div class="col-md-10" v-if="menus.allow">
                                                                                <div class="alert alert-danger"
                                                                                     style="background-color: #f2dede !important">
                                                                                    <div v-if="menus.buttons">
                                                                <span v-for="(button, k) in menus.buttons" :key="j">
                                                                    <input :checked="button.allow" type="checkbox"
                                                                           :value="true"
                                                                           v-model="button.allow" :id="button.name"/>
                                                                    <label class="text-capitalize text-bold mx-2"
                                                                           :for="button.name"
                                                                           style="color: black"> {{
                                                                            button.name
                                                                        }}</label>
                                                                </span>
                                                                                    </div>
                                                                                    <div v-else><span
                                                                                        style="color: black">NO PAGE ACTION</span>
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
                                                <!-- <table
                          class="table table-striped table-hover"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                              <th style="width: 7% !important;">Sr No.</th>
                              <th style="width: 15% !important;">Module Name</th>
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
                                    <span class="colorinput-color bg-primary"></span>
                                  </span>
                                </label>
                              </td>
                            </tr>
                            <tr v-if="moduleName.allow">
                              <td colspan="2"></td>
                                <td class="py-5">
                                <label class="colorinput m-3" v-for="(menus,j) in moduleName.childs" :key="j">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="menus"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="menus.allow"
                                    />
                                    <span class="colorinput-color bg-primary"></span>
                                    <span style="position:relative;left:5px;top:-10px;" class="text-capitalize"> {{ menus.name }} </span>
                                  </span>
                                </label>
                                </td>
                            </tr>
                            </template>
                          </tbody>
                        </table> -->
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
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        const res = await this.callApi("post", "role/get", {id: this.$route.params.id});
        if (res.status == 200) {
            this.role = res.data.role;
            this.permissions = res.data.permissions;
        } else {
            console.log(res);
        }
    },

    methods: {
        async save() {
            const res = await this.callApi("post", "role/update", {
                ...this.role,
                permissions: this.permissions,
            })
            if (res.status == 201) {
                this.success = "Role and Permissions Updated Successfully";
                setTimeout(() => {
                    this.success = "";
                }, 3000);
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
    },

};
</script>
