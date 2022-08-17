<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header">
              <h4>Companies</h4>
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
                              <th>Contact</th>
                              <th>Location</th>
                              <th>Logo</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(company, i) in companies" :key="i">
                              <td>{{ i + 1 }}</td>
                              <td>{{ company.name }}</td>
                              <td>{{ company.contact }}</td>
                              <td>{{ company.location }}</td>
                              <td>{{ company.logo }}</td>
                              <td>
                                <a
                                  href="#edit-modal"
                                  data-toggle="modal"
                                  @click="edit(company,i)"
                                  class="btn btn-warning mx-1"
                                >
                                  <i class="far fa-edit"></i>
                                </a>
                                <a
                                  href="#delete-modal"
                                  data-toggle="modal"
                                  @click="deleteModal(company, i)"
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
        heading="New Company"
        :errors="this.validationErrors"
        :success="success"
      >
        <div class="row">
          <div class="form-group col-md-4">
            <label for="name">Name</label>
            <input
              type="text"
              class="form-control"
              placeholder="Enter Name"
              id="name"
              v-model="data.name"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="contact">Contact</label>
            <input
              type="text"
              class="form-control"
              placeholder="Enter contact"
              id="contact"
              v-model="data.contact"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="Logo">Logo</label>
            <input
              type="file"
              class="form-control"
              id="Logo"
              @change="uploadLogo(e)"
            />
          </div>
          <div class="form-group col-md-12">
            <label for="location">Location</label>
            <textarea 
              class="form-control"
              placeholder="Enter Location"
              id="location"
              v-model="data.location" cols="30" rows="10"></textarea>
          </div>
          <div class="form-group col-md-12 text-center mt-4">
            <h3>Modular Permissions</h3>
          </div>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>Sr. No.</th>
              <th>Module Name</th>
              <th>Permission</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(moduleName,i) in data.modules" :key="i">
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
              <td></td>
                <td colspan="2" class="py-5">
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
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <button type="button" class="btn btn-block btn-success mt-4" @click="add">
                Add Company
              </button>
            </div>
          </div>
        </div>
      </Add>

      <!-- Add Modal -->
      <Edit
        heading="Edit Company"
        :errors="this.validationErrors"
        :success="success"
      >
        <div class="row">
          <div class="form-group col-md-4">
            <label for="name">Name</label>
            <input
              type="text"
              class="form-control"
              placeholder="Enter Name"
              id="name"
              v-model="dataEdit.name"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="contact">Contact</label>
            <input
              type="text"
              class="form-control"
              placeholder="Enter contact"
              id="contact"
              v-model="dataEdit.contact"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="Logo">Logo</label>
            <input
              type="file"
              class="form-control"
              id="Logo"
              @change="uploadLogo(e)"
            />
          </div>
          <div class="form-group col-md-12">
            <label for="location">Location</label>
            <textarea 
              class="form-control"
              placeholder="Enter Location"
              id="location"
              v-model="dataEdit.location" cols="30" rows="10"></textarea>
          </div>
          <div class="form-group col-md-12 text-center mt-4">
            <h3>Modular Permissions</h3>
          </div>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>Sr. No.</th>
              <th>Module Name</th>
              <th>Permission</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(moduleName,i) in dataEdit.modules" :key="i">
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
              <td></td>
                <td colspan="2" class="py-5">
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
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <button type="button" class="btn btn-block btn-success mt-4" @click="update">
                Update Company
              </button>
            </div>
          </div>
        </div>
      </Edit>

      <!-- Add Modal -->
      <Delete
        confirmationMessage='Are You Sure You want To Delete This "company" ???'
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
      data: {
        name: "",
        contact: "",
        logo: "",
        location: "",
        modules:[
          {name:'admin',allow:false,childs:[
            {name:"dashboard",allow:false},
            {name:"companies",allow:false},
            {name:"terminal",allow:false},
          ]},
          {name:'hrm',allow:false,childs:[
            {name:"employee",allow:false},
            {name:"salary",allow:false},
            {name:"loan",allow:false},
            {name:"leave managment",allow:false},
            {name:"attendance",allow:false},
          ]},
          {name:'users',allow:false,childs:[
            {name:"user",allow:false},
            {name:"roles",allow:false},
          ]}
        ],
        defaultModules:[],
      },
      dataEdit:{
        i: "",
        name: "",
        contact: "",
        logo: "",
        location: "",
        modules:[
          {name:'hrm',allow:false,childs:[
            {name:"employee",allow:false},
            {name:"salary",allow:false},
            {name:"loan",allow:false},
            {name:"leave managment",allow:false},
            {name:"attendance",allow:false},
          ]},
          {name:'users',allow:false,childs:[
            {name:"user",allow:false},
            {name:"roles",allow:false},
          ]}
        ]
      },
      success: false,
      companies: [],
    };
  },
  async created() {
    const companyRes = await this.callApi("post", "/company");
    if (companyRes.status==200){
      this.defaultModules = this.data.modules;
      this.companies = companyRes.data;
    }
    
  },
  methods: {
    async add() {
      // console.log(this.data.modules);
      // return ;
      this.validationErrors = [];
      if (this.data.name == "")
        return this.errorsArray("Company Name is Required", "Name");
      if (this.data.contact == "")
        return this.errorsArray("Company Contact is Required", "Contact");
        
      const res = await this.callApi("post", "/company/store", this.data);
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
    },
    edit(company,i) {
      let modules = {...this.dataEdit.modules,...company.modules};
      console.log(modules);
      this.dataEdit = {
        ...company,modules,i
      };
    },
    async update() {
      
      this.validationErrors = [];
      if (this.dataEdit.name == "")
        return this.errorsArray("Company Name is Required", "Name");
      if (this.dataEdit.contact == "")
        return this.errorsArray("Company Contact is Required", "Contact");
        
      const res = await this.callApi("post", "/company/update", this.dataEdit);

      if (res.status == 200) {
        
        console.log("From Update Function",this.dataEdit);
        this.success = "Company Updated Successfully";
        const companyRes = await this.callApi("post", "/company");
        if(companyRes.status==200){
          this.companies = companyRes.data;
        }
        this.dataEdit.name = this.dataEdit.contact = this.dataEdit.location = "";
        this.modules=[{hrm:false},{accounts:false},{booking:false}]
        setTimeout(() => {
          this.success = "";
          $("#edit-modal").modal("hide");      
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
    async deleteModal(company, i) {
      const deletingObj = {
        url: "/company/delete",
        data: company,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
    },
    uploadLogo(e){
      this.data.logo=e.target.files[0]
    }
  },
  computed: {
    ...mapGetters(["getDeletingObj"]),
  },
  watch: {
    getDeletingObj(obj) {
      console.log(obj);
      if (obj.isDeleted) {
        this.companies.splice(obj.index, 1);
      }
    },
  },
};
</script>
