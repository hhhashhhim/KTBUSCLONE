<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header d-flex justify-content-between">
              <h4>Fare Table</h4>
              <div class="w-50 d-flex align-items-center">
                <div class="header-select mx-2" v-if="$store.state.user.is_super_admin==1">
                    <label for="company_id" class="font-weight-bold my-0">Company</label>
                    <select v-model="data.company_id" class="form-control">
                        <option value="" selected>Select Company</option>
                        <option v-for="(company,i) in companies" :key="i" :value="company.id"> {{ company.name }} </option>
                    </select>
                </div>
                <div class="header-select mx-2">
                    <label for="fare_class" class="font-weight-bold my-0">Fare Class</label>
                    <select v-model="data.fare_class" class="form-control">
                        <option value="" selected>Select Fare Class</option>
                        <option v-for="(fareClass,i) in fareClasses" :key="i" :value="fareClass.id"> {{ fareClass.name }} </option>
                    </select>
                </div>
                <button class="btn btn-success btn-sm mt-4" type="button" @click="fetchRecord">Fetch Record</button>
              </div>
            </div>
            <div class="card-body">
              <transition name="fade">
                
                <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="error">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" @click="error=!error">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Close</span>
                    </button>
                    Please Enter All Required Fields !!!
                </div>
                
              </transition>
              <!-- Table -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h4></h4>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive" v-if="cities">
                        <table
                          class="table table-striped table-hover table-bordered"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                                <th></th>
                                <th v-for="(city,i) in cities" :key="i"> {{ i }} </th>                              
                            </tr>
                          </thead>
                          <tbody>
                                <tr v-for="(to_city_array,i) in cities" :key="i">

                                    <template v-for="(from_city,j) in to_city_array" :key="j">
                                      <th v-if="j==0"> {{ from_city.from_name }} </th>
                                      <td :class="from_city.from_id==from_city.to_id?'bg-danger':'modal-cell'"> 
                                          <a href="#add-modal" data-toggle="modal" @click="changeInfo(from_city,from_city)" v-if="from_city.from_id!=from_city.to_id" class="btn btn-success btn-block modal-btn">
                                          {{ from_city.fare }}
                                          </a>
                                      </td>
                                    </template>

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
        :heading="from + icon + to"
        :errors="this.validationErrors"
        :success="success"
      >
      <div class="row">
        <div class="form-group col-md-4">
          <label for="fare">Fare</label>
          <input type="number" class="form-control" v-model="data.fare">
        </div>
        <div class="form-group col-md-4">
          <label for="commission_flat">Commission Flat</label>
          <input type="number" class="form-control" v-model="data.commission_flat">
        </div>
        <div class="form-group col-md-4">
          <label for="commission_percentage">Commission Percentage</label>
          <input type="number" class="form-control" v-model="data.commission_percentage">
        </div>
        <div class="form-group col-md-4">
          <label for="terminal_commission">Terminal Commission</label>
          <input type="number" class="form-control" v-model="data.terminal_commission">
        </div>
        <div class="form-group col-md-4">
          <label for="time_difference">Time Difference ( e.g 1:30 )</label>
          <input type="text" class="form-control" v-model="data.time_difference">
        </div>
        <div class="form-group col-md-4">
          <label for="surcharge">Surcharge</label>
          <input type="number" class="form-control" v-model="data.surcharge">
        </div>
        <div class="form-group col-md-4">
          <label for="surcharge_start_date">Surcharge Start Date</label>
          <input type="date" class="form-control" v-model="data.surcharge_start_date">
        </div>
        <div class="form-group col-md-4">
          <label for="surcharge_end_date">Surcharge End Date</label>
          <input type="date" class="form-control" v-model="data.surcharge_end_date">
        </div>
        <div class="form-group col-md-4">
          <label for="advance_booking">Advance Booking Allowed(Days)</label>
          <input type="number" class="form-control" v-model="data.advance_booking">
        </div>
        
        <div class="form-group col-md-12">
          <button type="button" class="btn btn-block btn-success" @click="add">
            Save Fare Details
          </button>
        </div>
      </div>
      </Add>

      <!-- Add Modal -->
      <Edit
        heading="Edit terminal"
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
            Update terminal
          </button>
        </div>
      </div>
      </Edit>

      <!-- Add Modal -->
      <Delete
        confirmationMessage='Are You Sure You want To Delete This "terminal" ???'
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
  name: "FareTable",
  components: {
    Add,
    Edit,
    Delete,
  },
  data() {
    return {
      cities: [],
      companies: [],
      fetchedData: [],
      fareClasses: [{id:1,name:"economy"},{id:2,name:"exuctive"},{id:3,name:"business"}],
      data:{},
      dataEdit:{},
      from:{},
      to:{},
      success: false,
      error: false,
      icon : ' <i class="fa fa-bus"></i> ',

    };
  },
  async created() {
    const compRes = await this.callApi("post", "/company");
    this.companies = compRes.data;
  },
  computed:{
    heading : function(){
      return (from.name+"<i class='fa fa-user'></i>"+to.name);
    }
  },
  methods: {
    async add() {
      this.validationErrors = [];
      const res = await this.callApi("post", "/fare-table/store", this.data);
      if (res.status == 200) {
        this.success = "Fare Table Updated Created Successfully";
        // Object.keys(obj).forEach((i) => obj[i] = null);
        this.data = {}
        this.cities = res.data
        setTimeout(() => {
          this.success = "";
          $("#add-modal").modal("hide")
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
    changeInfo(from,to){
        this.from = from.from_name;
        this.to   = to.to_name;
        this.data.from = from.from_id
        this.data.to = to.to_id
    },
    async fetchRecord(){
        if (!this.data.company_id || !this.data.fare_class){
          this.error=true;
          return
        }
      const res = await this.callApi("post", "/fare-table", {
        company_id:this.data.company_id,fare_class:this.data.fare_class
      });
      if (res.status == 200) {
        this.cities = res.data
        console.log(res.data);
        setTimeout(() => {
          this.success = "";
        }, 3000);
      } else {
        alert("Something Went Wrong")
      }
    },
    
    deleteModal(terminal, i) {
      const deletingObj = {
        url: "/terminal/delete",
        data: terminal,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
    }
  },
  computed: {
    ...mapGetters(["getDeletingObj"]),
  },
  watch: {
    getDeletingObj(obj) {
      console.log(obj);
      if (obj.isDeleted) {
        this.terminals.splice(obj.index, 1);
      }
    },
  },
};
</script>
<style scoped>
table,table *{
    font-size: 10px;
}
.modal-cell{
    padding: 0 !important;
    position: relative;
}
.modal-cell .modal-btn{
    height: 100%;
    transition: 0.5s transform;
}
.modal-cell:hover .modal-btn{
    position:absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}
.header-select{
  width: 35%;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 1s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
}
</style>