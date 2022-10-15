<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-success">
            <div class="card-header d-flex justify-content-between">
              <h4>Booking</h4>
              <div class="card-header-action">
                <a
                  href="#"
                  data-toggle="modal"
                  :data-target="'#' + formID"
                  class="btn btn-primary"
                >
                  Add Booking
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
                      <div class="table-responsive">
                        <table
                          class="table table-striped table-hover"
                          id="edit_dis"
                        >
                          <thead>
                            <tr>
                              <th>Sr No.</th>
                              <th>Name</th>
                              <th>Percentage</th>
                              <th>Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(surcharge, i) in surcharges" :key="i">
                              <td>{{ i + 1 }}</td>
                              <td>{{ surcharge.name }}</td>
                              <td>{{ surcharge.percentage }}%</td>
                              <td>
                                {{
                                  surcharge.is_active === 1
                                    ? "Active"
                                    : "InActive"
                                }}
                              </td>
                              <td>
                                <a
                                  href="#edit-modal"
                                  data-toggle="modal"
                                  @click="edit(surcharge)"
                                  class="btn btn-primary mx-1"
                                >
                                  <i class="far fa-edit"></i>
                                </a>
                                <a
                                  href="#delete-modal"
                                  data-toggle="modal"
                                  @click="deleteModal(surcharge, i)"
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
        :heading="'Create Booking'"
        :errors="this.validationErrors"
        :formID="formID"
      >
        <div class="row">
          <div class="col-md-5 class form-group">
            <label for="DiscountName">Schedule Name <span class="text-danger">*</span></label>
            <select
              class="form-control"
              id="route"
              v-model="addForm.schedule"
            >
              <option value="" selected>Select Schedule</option>
              <option
                v-for="(schedule, i) in allSchedules"
                :value="schedule.id"
                :key="i"
              >
                {{ schedule.name }}
              </option>
            </select>
          </div>

          <div class="col-md-5 class form-group">
            <label for="date">Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" v-model="addForm.date">
          </div>
          <div class="col-md-2">
            <label>Action</label>
            <button @click="fetchScheduleData" class="btn btn-block btn-primary">Get Record</button>
          </div>
          
          <h1 v-if="loading">Loading.........</h1>

          <div class="col-md-12 row" v-if="showBookingDiv">
            <div class="col-md-6">
              <div class="card p-4">
                <div class="form-group row">
                  <label
                    class="col-md-3 pt-3 font-weight-bold"
                    for="customer-cnic"
                    >CNIC</label
                  >
                  <input
                    type="text"
                    class="form-control col-md-9"
                    id="customer-cnic"
                    v-model="addForm.customerCNIC"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="fullName"
                    >Full Name</label
                  >
                  <input
                    type="text"
                    class="form-control col-md-9"
                    id="fullName"
                    v-model="addForm.customerName"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                    >Contact</label
                  >
                  <input
                    type="text"
                    class="form-control col-md-9"
                    id="contact"
                    v-model="addForm.contact"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="remarks"
                    >Remarks</label
                  >
                  <input
                    type="text"
                    class="form-control col-md-9"
                    id="remarks"
                    v-model="addForm.remarks"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                    >Gender</label
                  >
                  <div class="col-md-9 pt-3">
                    <input type="radio" v-model="addForm.gender" value="0" />
                    <label class="mx-3">Female</label>
                    <input type="radio" v-model="addForm.gender" value="1" />
                    <label class="mx-3">Male</label>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                    >Issue Or Book</label
                  >
                  <div class="col-md-9 pt-3">
                    <input type="radio" v-model="addForm.type" value="booked" />
                    <label class="mx-3">Issue</label>
                    <input
                      type="radio"
                      v-model="addForm.type"
                      value="advance booking"
                    />
                    <label class="mx-3">Book</label>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="seatNo"
                    >Seat No.</label
                  >
                  <input
                    type="text"
                    readonly
                    class="form-control col-md-9"
                    id="seatNo"
                    v-model="addForm.selectedSeats"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="totalFare"
                    >Total Seats</label
                  >
                  <input
                    type="text"
                    readonly
                    class="form-control col-md-9"
                    id="totalNoSeats"
                    v-model="selectedSeats.length"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="totalFare"
                    >Total Fare</label
                  >
                  <input
                    type="text"
                    readonly
                    class="form-control col-md-9"
                    id="totalFare"
                    v-model="addForm.totalFare"
                  />
                </div>
                <div class="form-group row">
                  <label class="col-md-3 pt-3 font-weight-bold" for="discount"
                    >Discount ( % )</label
                  >
                  <input
                    type="text"
                    readonly
                    class="form-control col-md-9"
                    id="discount"
                    v-model="addForm.discount"
                  />
                </div>

                <div class="form-group text-right">
                  <button class="btn btn-primary mx-1" @click="add">
                    Save
                  </button>
                  <button class="btn btn-secondary mx-1">Reset</button>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card p-4">
                <div class="col-md-12 mb-2 d-flex flex-wrap">
                  <div class="my-2">
                    <div class="selected circles mr-1 border"></div>
                    <span class="text-wrap">Selected</span>
                  </div>
                  <div class="my-2">
                    <div class="for-female circles mr-1 border"></div>
                    <span class="text-wrap">For Female</span>
                  </div>
                  <div class="my-2">
                    <div class="for-male circles mr-1 border"></div>
                    <span class="text-wrap">For Male</span>
                  </div>
                  <div class="my-2">
                    <div class="not-for-sale circles mr-1 border"></div>
                    <span class="text-wrap">Not For Sale</span>
                  </div>
                  <div class="my-3">
                      <div class="circles icons-legend mr-1 border">
                        <i class="fas fa-check"></i>
                      </div>
                      <span class="text-wrap">Booked</span>
                  </div>
                  <div class="my-3">
                    <div class="fas fa-check-double circles icons-legend mr-1 border"></div>
                    <span class="text-wrap">Issued</span>                      
                  </div>
                </div>
                <div
                  class="d-flex justify-content-center seat-img p-0 m-0"
                  v-for="(record, rowIndex) in schedule.single_bus.seat_map"
                  :key="rowIndex"
                >
                  <div v-for="(col, colIndex) in record" :key="colIndex">
                    <!-- <div v-if="colIndex==0">
                      {{ col }}
                    </div> -->
                    <div
                      v-if="col.reserved"
                      class="image-span d-block text-center text-white"
                      @click="selectSeat(rowIndex, colIndex, col.seatNo)"
                      :class="getClasses(col)"
                    >
                      <small>{{ col.seatNo }}</small>
                      <br>
                      <small v-if="col.type">
                        <i class="fas" :class="col.type=='booked'?'fa-check-double':'fa-check'"></i>
                      </small>
                    </div>
                    <span v-else></span>
                  </div>
                </div>
                <tr></tr>
                <!-- schedule -->
              </div>
            </div>
          </div>
        </div>
      </Add>

      <!--            Edit MOdel End-->
      <Delete
        confirmationMessage="Are You Sure You want To Delete This Surcharge ???"
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
  name: "SurchargePage",
  components: {
    Add,
    Edit,
    Delete,
  },
  data() {
    return {
      surcharges: [],
      isActive: 1,
      formID: "addBooking",
      validationErrors: [],
      success: false,
      error: false,
      SurchargeName: "",
      delId: "",
      SurchargePercentage: "",
      allSchedules: [],
      schedule: "",
      loading: false,
      showBookingDiv: false,
      selectedSeats: [],
      // seatMap:[],
      addForm: {
        type: "booked",
        gender: "1",
      },
      dataEdit: {
        id: "",
        name: "",
        percentage: "",
        is_Active: "",
      },
    };
  },
  async created() {
    const res = await this.callApi("post", "schedule");
    if (res.status == 200) {
      this.allSchedules = res.data;
    } else {
      console.log(res);
    }
  },

  methods: {
    async fetchScheduleData() {
      if (this.addForm.date == "")
        return this.errorsArray("Date is Required", "Date");
      if (this.addForm.schedule == "")
        return this.errorsArray("Schedule is Required", "Schedule");

      this.loading = true;
      const res = await this.callApi("post", "schedule/selected", {
        id: this.addForm.schedule,
        date: this.addForm.date,
      });
      if (res.status == 200) {
        this.loading = false;
        this.showBookingDiv = true;
        this.schedule = res.data;
      } else {
        console.log(res);
      }
    },
    selectSeat(row, col, seatNo) {
      let index = this.selectedSeats.indexOf(seatNo);
      if (index != -1) {
        this.schedule.single_bus.seat_map[row][col].selected = false;
        this.selectedSeats.splice(index, 1);
      } else {
        this.schedule.single_bus.seat_map[row][col].selected = true;
        this.selectedSeats.push(seatNo);
      }

      this.addForm.selectedSeats = this.selectedSeats;
    },
    getClasses(col){
      let gender = col.gender != undefined && col.gender==0? 'for-female' : col.gender && col.gender==1?'for-male':''
      let selected = col.selected?"selected":"";
      return gender+" "+selected;
    },
    async add() {

      this.validationErrors = [];
      if (this.schedule == "")
        return this.errorsArray("Schedule is Required", "Schedule");

      const res = await this.callApi("post", "booking/store", this.addForm);
      if (res.status === 201 && res.statusText === "Created") {
        window.scrollTo(0,0);
      } else {
        if (res.status === 422) {
          for (const key in res.addForm.errors) {
            res.addForm.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
      }
    },

    async deleteModal(surcharge, i) {
      const deletingObj = {
        url: "/surcharge/delete",
        data: surcharge,
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
      if (obj.isDeleted) {
        this.surcharges.splice(obj.index, 1);
        setTimeout(function () {
          window.location.reload();
        }, 2000);
      }
    },
  },
};
</script>
<style scoped>
.image-span {
  background-color: #b9dea0;
  border-radius: 10px;
  cursor: pointer;
}
.image-span:hover {
  background-color: #6db131;
}
.selected{
  background-color: #6db131 !important;
}
.economy{
  border: 3px solid #6D6E69 !important;
}
.business{
  border: 3px solid orangered !important;
}
.executive{
  border: 3px solid gold !important;
}
.for-female{
  background-color: hotpink !important;
}
.for-male{
  background-color: #3D8FF2 !important;
}

.not-for-sale {
  background-color: rgb(140, 109, 109) !important;
}

.seat-img {
  height: 55px;
  margin: 10px 0px;
}

.seat-img .image-span,
.seat-img span {
  height: 50px;
  width: 50px;
  display: inline-block;
  cursor: pointer !important;
  margin: 5px;
}
img {
  cursor: pointer !important;
}
.circles {
  width: 30px;
  height: 30px;
  -moz-border-radius: 25px;
  -webkit-border-radius: 25px;
  border-radius: 50px;
  display: inline-block;
  box-sizing: content-box;
}
.icons-legend{
  position: relative;
  bottom: 12px;
  color: rgb(62, 61, 61);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.circles + span {
  position: relative;
  top: -10px;
  padding: 5px;
  color: black;
}
</style>
