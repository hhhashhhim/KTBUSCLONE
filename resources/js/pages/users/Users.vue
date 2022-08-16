<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header">
              <h4>Users</h4>
              <div class="card-header-action">
                <a
                  href="#add-modal"
                  data-toggle="modal"
                  class="btn btn-success"
                >
                  Add New
                </a>
              </div>
            </div>
            <div class="card-body">
              <!-- Table -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h4></h4>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table
                          class="table table-striped table-hover"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                              <th>Sr No.</th>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Contact</th>
                              <th>Company</th>
                              <th>Role</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(user, i) in users" :key="i">
                              <td>{{ i + 1 }}</td>
                              <td>{{ user.name }}</td>
                              <td>{{ user.email }}</td>
                              <td>{{ user.contact }}</td>
                              <th>{{ user.company?user.company.name:"Not Found" }}</th>
                              <th>{{ user.role?user.role.name:"Not Found" }}</th>
                              <td>
                                <a
                                  href="#edit-modal"
                                  data-toggle="modal"
                                  @click="edit(user)"
                                  class="btn btn-warning mx-1"
                                >
                                  <i class="far fa-edit"></i>
                                </a>
                                <a
                                  href="#delete-modal"
                                  data-toggle="modal"
                                  @click="deleteModal(user, i)"
                                  class="btn btn-danger"
                                >
                                  <i class="far fa-trash-alt"></i>
                                </a>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- END TABLE -->
            </div>
          </div>
        </div>
      </div>

      <!-- Add Modal -->
      <Add
        heading="New User"
        :errors="this.validationErrors"
        :success="success"
      >
      <div class="row">
        <div class="form-group col-md-6">
          <label for="name">Name</label>
          <input
            type="text"
            class="form-control"
            placeholder="Enter Name"
            id="name"
            v-model="data.name"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="email">Email</label>
          <input
            type="text"
            class="form-control"
            placeholder="Enter Email"
            id="email"
            v-model="data.email"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="contact">Contact</label>
          <input
            type="contact"
            class="form-control"
            placeholder="Enter Password"
            id="contact"
            v-model="data.contact"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="password">Password</label>
          <input
            type="password"
            class="form-control"
            placeholder="Enter Password"
            id="password"
            v-model="data.password"
          />
        </div>
        <div class="form-group col-md-12">
          <label for="company">Company</label>
          <select
            type="text"
            class="form-control"
            id="company"
            @change="fetchCompanyRoles"
            v-model="data.company_id"
          >
            <option value="">Select Company</option>
            <option v-for="(company, i) in companies" :value="company.id" :key="i">
              {{ company.name }}
            </option>
          </select>
        </div>
        <div class="form-group col-md-12" v-if="data.company_id">
          <label for="role">Role</label>
          <select
            type="text"
            class="form-control"
            placeholder="Enter role"
            id="role"
            v-model="data.role"
          >
            <option value="" selected>Select Role</option>
            <option v-for="(role, i) in roles" :value="role.id" :key="i">
              {{ role.name }}
            </option>
          </select>
        </div>
        <div class="form-group col-md-12">
          <button type="button" class="btn btn-block btn-success" @click="add">
            Add User
          </button>
        </div>
      </div>
      </Add>

      <!-- Add Modal -->
      <Edit
        heading="Edit User"
        :errors="this.validationErrors"
        :success="success"
      >
      <div class="row">
        <div class="form-group col-md-6">
          <label for="name">Name</label>
          <input
            type="text"
            class="form-control"
            placeholder="Enter Name"
            id="name"
            v-model="dataEdit.name"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="email">Email</label>
          <input
            type="text"
            class="form-control"
            placeholder="Enter Email"
            id="email"
            v-model="dataEdit.email"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="contact">Contact</label>
          <input
            type="text"
            class="form-control"
            placeholder="Enter Contact"
            id="contact"
            v-model="dataEdit.contact"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="password">Password</label>
          <input
            type="password"
            class="form-control"
            placeholder="Enter Password"
            id="password"
            v-model="dataEdit.password"
          />
        </div>
        <div class="form-group col-md-12" v-if="dataEdit.company_id">
          <label for="company">Company</label>
          <select
              type="text"
              class="form-control"
              id="company"
              @change="fetchCompanyRoles"
              v-model="dataEdit.company_id"
            >
              <option value="">Select Company</option>
              <option v-for="(company, i) in companies" :value="company.id" :key="i">
                {{ company.name }}
              </option>
            </select>
        </div>
        <div class="form-group col-md-12">
          <label for="role">Role</label>
          <select
            type="text"
            class="form-control"
            id="role"
            v-model="dataEdit.role"
          >
            <option value="">Select Role</option>
            <option v-for="(role, i) in roles" :value="role.id" :key="i">
              {{ role.name }}
            </option>
          </select>
        </div>
        <div class="form-group col-md-12">
          <button
            type="button"
            class="btn btn-block btn-success"
            @click="update"
          >
            Update User
          </button>
        </div>
      </div>
      </Edit>

      <!-- Add Modal -->
      <Delete
        confirmationMessage='Are You Sure You want To Delete This "USER" ???'
      />
    </div>
  </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import { mapGetters } from "vuex";

export default {
  name: "Role",
  components: {
    Add,
    Edit,
    Delete,
  },
  data() {
    return {
      roles: [],
      users: [],
      companies: [],
      data: {
        name: "",
        email: "",
        contact: "",
        password: "",
        role: "",
        company_id: "",
      },
      dataEdit:{},
      success: false,
    };
  },
  async created() {
    const userRes = await this.callApi("post", "/user", {});
    const compRes = await this.callApi("post", "/company", {});
    this.users = userRes.data;
    this.companies = compRes.data;
  },
  methods: {
    async add() {
      this.validationErrors = [];

      if (this.data.name == "")
        return this.errorsArray("User Name is Required", "Name");
      if (this.data.email == "")
        return this.errorsArray("User Email is Required", "Email");
      if (this.data.password == "")
        return this.errorsArray("User Password is Required", "Password");
      if (this.data.role == "")
        return this.errorsArray("User Role is Required", "Role");
      // return "Reaching";
      const res = await this.callApi("post", "/user/store", this.data);
      if (res.status == 200) {
        this.success = "User Created Successfully";
        this.users.unshift(res.data);
        this.data.name = this.data.email = this.data.password = this.data.role = this.data.company_id = "";
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
    },
    async edit(user) {
      this.dataEdit = user;
      this.dataEdit.role=user.role_id;
      const roleRes = await this.callApi("post", "/company/roles", {id:user.company_id});
      this.roles = roleRes.data;
    },
    async update() {
      this.validationErrors = [];
      if (this.dataEdit.name == "")
      return this.errorsArray("User Name is Required", "Name");
      const res = await this.callApi("post", "/user/update", this.dataEdit);
      if (res.status == 201) {
        this.success = "User Updated Successfully";
        this.dataEdit = "";
        const userRes = await this.callApi("post", "/user", {});
        this.users = userRes.data;
        setTimeout(() => {
          this.success = "";
          $("#edit-modal").modal("hide");
        }, 3000);
      } else {
        if (res.status == 422) {
          console.log();
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
      }
    },
    async deleteModal(user, i) {
      const deletingObj = {
        url: "/user/delete",
        data: user,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
    },
    async fetchCompanyRoles(){
      const roleRes = await this.callApi("post", "/company/roles", {id:this.data.company_id});
      this.roles = roleRes.data;
    }
  },
  computed: {
    ...mapGetters(["getDeletingObj"]),
  },
  watch: {
    getDeletingObj(obj) {
      console.log(obj);
      if (obj.isDeleted) {
        this.users.splice(obj.index, 1);
      }
    },
  },
};
</script>