<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header d-flex justify-content-between">
              <h4>Routes Details</h4>
              <div class="card-header-action">
                <a
                  href="#"
                  data-toggle="modal"
                  :data-target="'#' + formID"
                  class="btn btn-primary"
                >
                  Add New Route
                </a>
              </div>
            </div>
            <div class="card-body">
              <transition name="fade">
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
                    @click="error = !error"
                  >
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
        :formID="formID"
      >
        <div class="row">
          <div class="form-group col-md-6">
            <label for="name">Name</label>
            <input type="text" class="form-control" v-model="data.fare" />
          </div>
          <div class="form-group col-md-6">
            <label for="commission_flat">Select City</label>
            <label for="fare_class" class="font-weight-bold my-0"
              >Fare Class</label
            >
            <select class="form-control rounded-0" @change="fetchTerminal()">
              <option value="0" selected>Select Fare Class</option>
               <option v-for="(city, i) in cities" :value="city.id" :key="i">
                {{ city.name }}
              </option>
            </select>
          </div>
          <div class="col-md-12">
            <h5>Select Terminals</h5>
            <br />
          </div>
          <div class="form-group col-md-12 d-flex align-items-center">
            
          </div>

           <div class="form-group col-md-12 d-flex align-items-center">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>City From</th>
                  <th>City To</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                  <select class="form-control rounded-0">
                    <option value="0" selected>Select Fare Class</option>
                    <option v-for="(city, i) in cities" :value="city.id" :key="i">
                      {{ city.name }}
                    </option>
                  </select>
                  </td>
                  <td>
                   <span class="mx-2">
              <label class="mt-4" for="sms">Kainat Travel</label>
              <label class="colorinput mx-3 mt-3">
                <span>
                  <input
                    type="checkbox"
                    class="colorinput-input"
                    v-model="data.is_main"
                  />
                  <span class="colorinput-color bg-success"></span>
                </span>
              </label>
            </span>
                  </td>
                  <td>
                    <button class="btn btn-outline-primary">Add</button>
                    <button class="btn btn-outline-danger">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="form-group col-md-12 d-flex align-items-center">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>City From</th>
                  <th>City To</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                  <select class="form-control rounded-0">
                    <option value="0" selected>Select Fare Class</option>
                    <option v-for="(city, i) in cities" :value="city.id" :key="i">
                      {{ city.name }}
                    </option>
                  </select>
                  </td>
                  <td>
                    <select class="form-control rounded-0">
                      <option value="0" selected>Select Fare Class</option>
                       <option v-for="(city, i) in cities" :value="city.id" :key="i">
                        {{ city.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <button class="btn btn-outline-primary">Add</button>
                    <button class="btn btn-outline-danger">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="form-group col-md-12">
            <button
              type="button"
              class="btn btn-block btn-primary"
              @click="add"
            >
              Save Route Details
            </button>
          </div>
        </div>
      </Add>

  

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
  name: "RoutePage",
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
      formID: "addNewRoute",
      fareClasses: [
        { id: 1, name: "economy" },
        { id: 2, name: "exuctive" },
        { id: 3, name: "business" },
      ],
      data: {
        fare_class: "0",
      },
      dataEdit: {},
      from: {},
      to: {},
      success: false,
      error: false,
      icon: ' <i class="fa fa-bus"></i> ',
    };
  },
  created(){
    this.fetchCities();
  },
  computed: {
    heading: function () {
      return from.name + "<i class='fa fa-user'></i>" + to.name;
    },
  },
  methods: {
    async add() {
      this.validationErrors = [];
      const res = await this.callApi("post", "/fare-table/store", this.data);
      if (res.status == 200) {
        this.success = "Fare Table Updated Created Successfully";
        // Object.keys(obj).forEach((i) => obj[i] = null);
        this.data = {};
        this.cities = res.data;
        setTimeout(() => {
          this.success = "";
          $("#add-modal").modal("hide");
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
    async fetchTerminal(){
      alert('et')
    },
    async fetchCities(){
    const cityRes = await this.callApi("post", "/city");
        if (cityRes.status == 200){
          this.cities = cityRes.data;
          console.log(cityRes.data);
          console.log(this.cities);
        }   
    },
    changeInfo(from, to) {
      this.from = from.name;
      this.to = to.name;
      this.data.from = from.id;
      this.data.to = to.id;
    },
    async fetchRecord() {
      if (!this.data.fare_class) {
        this.error = true;
        return;
      }
      const res = await this.callApi("post", "/fare-table", {
        company_id: this.data.company_id,
        fare_class: this.data.fare_class,
      });
      if (res.status == 200) {
        this.cities = res.data;
        console.log(res.data);
        setTimeout(() => {
          this.success = "";
        }, 3000);
      } else {
        alert("Something Went Wrong");
      }
    },

    deleteModal(terminal, i) {
      const deletingObj = {
        url: "/terminal/delete",
        data: terminal,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
    },
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
table,
table * {
  font-size: 10px;
}
.modal-cell {
  padding: 0 !important;
  position: relative;
}
.modal-cell .modal-btn {
  height: 100%;
  transition: 0.5s transform;
}
.modal-cell:hover .modal-btn {
  position: absolute;
  z-index: 20;
  transform: scale(1.3) translateY(-20px);
  box-shadow: 0px 0px 10px black;
}
.header-select {
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