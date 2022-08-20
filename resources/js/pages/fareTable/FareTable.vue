<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header">
              <h4>Fare Table</h4>
              <div class="card-header-action w-25">
                <select v-model="data.type" class="form-control">
                    <option v-for="(fareClass,i) in fareClasses" :key="i" :value="fareClass.id"> {{ fareClass.name }} </option>
                </select>
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
                          class="table table-striped table-hover table-bordered"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                                <th></th>
                                <th v-for="(city,i) in cities" :key="i"> {{ city.name }} </th>                              
                            </tr>
                          </thead>
                          <tbody>
                                <tr v-for="(from_city,i) in cities" :key="i">
                                    <template v-for="(to_city,j) in cities" :key="j">
                                    <th v-if="j==0"> {{ from_city.name }} </th>
                                    <td :class="+i==j?'bg-danger':'modal-cell'"> 
                                        <a href="#add-modal" data-toggle="modal" @click="changeInfo(from_city,to_city)" v-if="i!=j" class="btn btn-success btn-block modal-btn"></a>
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
        :heading="from.name + icon + to.name"
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
      fareClasses: [{id:1,name:"economy"},{id:2,name:"exuctive"},{id:3,name:"business"}],
      data: {},
      from:{},
      to:{},
      dataEdit:{},
      success: false,
      icon : ' <i class="fa fa-bus"></i> '
    };
  },
  async created() {
    const cityRes = await this.callApi("post", "/city");
    this.cities = cityRes.data;
    console.log(this.cities);
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
      return ;
      if (res.status == 200) {
        this.success = "terminal Created Successfully";
        this.terminals = res.data
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
    changeInfo(from,to){
        this.from = from;
        this.to   = to;
        this.data.from = from.id
        this.data.to = to.id
    },
    async deleteModal(terminal, i) {
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
</style>