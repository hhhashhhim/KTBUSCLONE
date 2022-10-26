+<template>
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
                          id="booking-table"
                        >
                          <thead>
                            <tr>
                              <th>Sr No.</th>
                              <th>Date</th>
                              <th>Schedule Name</th>
                              <th>No. Of Bookings</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(booking, i) in allBookings" :key="i">
                              <td>{{ parseInt(i)+1 }}</td>
                              <td>{{ booking.date }}</td>
                              <td>{{ booking.schedule.name }}</td>
                              <td>{{ booking.count }}</td>
                              <td>
                                <a
                                  :href="'#'+detailsFormId"
                                  data-toggle="modal"
                                  @click="details(booking.date,booking.schedule.id)"
                                  class="btn btn-primary mx-1"
                                >
                                  <i class="far fa-eye"></i>
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
      :success="success"
      :formID="formID"
    >
      <div class="row">
          <div class="col-md-6 form-group">
              <label for="departureCity">Departure City <span class="text-danger">*</span></label>
              <select class="form-control" id="departureCity" v-model="addForm.departureCity">
                  <option value="0" selected>Select Departure City</option>
                  <option
                      v-for="(city, i) in cities"
                      :value="city.id"
                      :key="i"
                  >
                      {{ city.name }}
                  </option>
              </select>
          </div>
          <div class="col-md-6 form-group"><label for="destinationCity">Destination City<span class="text-danger">*</span></label>
              <select class="form-control" id="destinationCity" v-model="addForm.destinationCity">
                  <option value="0" selected>Select Destination City</option>
                  <option
                      v-for="(city, i) in cities"
                      :value="city.id"
                      :key="i"
                  >
                      {{ city.name }}
                  </option>
              </select>
          </div>
          <div class="col-md-5 class form-group">
          <label for="date">Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" v-model="addForm.date"  @change="fetchSpecificSchedules()"/>
        </div>
          <div class="col-md-5 class form-group">
              <label for="scheduleName">Schedule Name <span class="text-danger">*</span></label>
              <select class="form-control" id="scheduleName" v-model="addForm.schedule" @change="resetSelectBooking($event)">
                  <option value="0" selected>Select Schedule</option>
                  <option
                      v-for="(schedule, i) in allSchedules"
                      :value="schedule.id"
                      :key="i"
                  >
                      {{ schedule.name }} - {{tConvert(schedule.time)}}
                  </option>
              </select>
          </div>
        <div class="col-md-2">
          <label>Action</label>
          <button @click="fetchScheduleData" class="btn btn-block btn-primary" :class="getSchedule ? 'disabled': ''">
            {{getSchedule ? 'Fetching Schedules...' : 'Get Record' }}
          </button>
        </div>
        <div class="col-md-6 d-flex justify-content-center mx-auto mb-3" v-if="selectedBookedSeats.length" >
          <a
            href="#reschedule-modal"
            class="btn btn-primary mx-1"
            data-toggle="modal"
            >Shifting ( Reschedule ) Seats</a
          >
        </div>
        <h1 v-if="loading">Loading.........</h1>

        <div class="col-md-12 row" v-if="showBookingDiv">
          <div class="col-md-6">
            <div class="card p-4">
              <div class="form-group row">
                <label
                  class="col-md-3 pt-3 font-weight-bold"
                  for="customer-cnic"
                  >CNIC <span class="text-danger">*</span>
                </label>
                <vue-mask
                  v-on:keyup.enter="getCustomer"
                  class="form-control col-md-9"
                  v-model="addForm.customerCNIC"
                  mask="00000-0000000-0"
                  :raw="false"
                  :options="options"
                >
                </vue-mask>
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
                <vue-mask
                  v-on:keyup.enter="getCustomer"
                  class="form-control col-md-9"
                  v-model="addForm.contact"
                  mask="0000-0000000"
                  :raw="false"
                  :options="options"
                >
                </vue-mask>
                <!-- <input
                  type="text"
                  @keypress="phoneFormat($event)"
                  class="form-control col-md-9"
                  id="contact"
                  v-model="addForm.contact"
                /> -->
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
                  class="form-control col-md-9 font-weight-bold"
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
                <button class="btn btn-primary mx-1" @click="add">Save</button>
                <button class="btn btn-secondary mx-1" @click="reset">Reset</button>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card p-4">
              <div class="col-md-12 mb-2 d-flex flex-wrap">
                <div class="my-2">
                  <div class="selected circles mr-1 border shadow"></div>
                  <span class="text-wrap">Selected</span>
                </div>
                <div class="my-2">
                  <div class="for-female circles mr-1 border shadow"></div>
                  <span class="text-wrap">For Female</span>
                </div>
                <div class="my-2">
                  <div class="for-male circles mr-1 border shadow"></div>
                  <span class="text-wrap">For Male</span>
                </div>
                <div class="my-2">
                  <div class="not-for-sale circles mr-1 border shadow"></div>
                  <span class="text-wrap">Not For Sale</span>
                </div>

                <div class="my-2" v-for="(seatClass,i) in allSeatClasses" :key="i">
                  <div class="circles mr-1 border shadow" :style="{border:'2px solid '+seatClass.color+' !important'}"></div>
                  <span class="text-wrap">{{ seatClass.name }}</span>
                </div>


                <div class="my-3">
                  <div class="circles icons-legend mr-1 border shadow">
                    <i class="fas fa-check"></i>
                  </div>
                  <span class="text-wrap">Booked</span>
                </div>
                <div class="my-3">
                  <div
                    class="fas fa-check-double circles icons-legend shadow mr-1 border"
                  ></div>
                  <span class="text-wrap">Issued</span>
                </div>
              </div>
              <div
                class="d-flex justify-content-center seat-img p-0 m-0"
                v-for="(record, rowIndex) in schedule.bus_class.seat_map"
                :key="rowIndex"
              >
                <div v-for="(col, colIndex) in record" :key="colIndex">
                  <!-- <div v-if="colIndex==0">
                                          {{ col }}
                                        </div> -->
                  <div
                    v-if="col.reserved"
                    class="image-span d-block text-center text-white shadow-sm"
                    @click="selectSeat(rowIndex, colIndex, col.seatNo)"
                    :class="getClasses(col)"
                    :style="{border:'3px solid '+col.color+' !important'}"
                  >
                    <!-- data-toggle="modal"
                                            :data-target="col.type?'#booking-options-popup':''" -->
                    <small>{{ col.seatNo }} </small>
                    <br />
                    <small v-if="col.type && (col.type == 'booked' || col.type == 'advance booking')">
                      <i
                        class="fas"
                        :class="
                          col.type == 'booked' ? 'fa-check-double' : 'fa-check'
                        "
                      ></i>
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

    <!--            DELETE MODAL-->
    <Delete :deleteForm="deleteFormID"
      confirmationMessage="Are You Sure You want To Delete This Booking ???"
    />

    <PartialSeatPopup :formID="partialSeatFormId" :seats="bookedSeats" />
    <ReschedulePopup :formID="rescheduleFormId" :seats="bookedSeats" />
    <ShiftingPopup :formID="shiftingFormId" :seats="bookedSeats" />
    <DetailsModal :formID="detailsFormId" :details="bookingDetails" />
  </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import PartialSeatPopup from "./popup/PartialSeatPopup.vue";
import Delete from "../../components/Delete.vue";
import { mapGetters } from "vuex";
import vueMask from "vue-jquery-mask";
import ReschedulePopup from "./popup/ReschedulePopup.vue";
import DetailsModal from "./popup/DetailsModal.vue";

export default {
  name: "SurchargePage",
  components: {
    Add,
    Edit,
    Delete,
    PartialSeatPopup,
    ReschedulePopup,
    DetailsModal,
    vueMask,
  },
  data() {
    return {
      options: {
        placeholder: "xxxxx-xxxxxxx-x",
      },
      rescheduleFormId: "reschedule-modal",
      shiftingFormId: "shifting-modal",
      partialSeatFormId: "partialSeat-modal",
      detailsFormId:"details-modal",
      customers: [],
      isActive: 1,
      formID: "addBooking",
      deleteFormID: "delete_addBooking",
      validationErrors: [],
      success: false,
      error: false,
      delId: "",
      allSchedules: [],
      schedule: "",
      loading: false,
      getSchedule: false,
      showBookingDiv: false,
      selectedSeats: [],
      selectedBookedSeats: [],
      bookedSeats: [],
      allBookings:[],
      bookingDetails:[],
      allSeatClasses:[],
      cities:[],
      addForm: {
        type: "booked",
        gender: "1",
        customerCNIC: "",
        schedule: 0,
        totalFare:0,
        destinationCity:0,
        departureCity:0,
      },
    };
  },
  async created() {
    // const res = await this.callApi("post", "schedule");
    const resBooking = await this.callApi("post", "booking");
    const resClass = await this.callApi("post","fare-class")
    const resCity = await this.callApi("post","cities")
    if (/*res.status == 200 &&*/ resBooking.status == 200 && resClass.status == 200 && resCity.status == 200 ) {
      // this.allSchedules = res.data;
      this.allBookings = resBooking.data;
      this.allSeatClasses = resClass.data;
      this.cities = resCity.data;
      setTimeout(() => {
        $("#"+this.formID).modal("show");
        $("#booking-table").dataTable();
      }, 300);
    } else {
      console.log(res);
    }
  },

  methods: {
      resetSelectBooking(evt){
        if(evt.target.value == '0'){
            this.showBookingDiv = false;
        }  else{
            this.showBookingDiv = true;
        }
      },
      tConvert:function(time) {
          time = time.toString().match(/^([01]\d|2[0-3])(:)([0-5]\d)?$/) || [time];

          if (time.length > 1) {
              time = time.slice(1);
              time[5] = +time[0] < 12 ? ' AM' : ' PM';
              time[0] = +time[0] % 12 || 12;
          }
          return time.join('');
      },

    async fetchSpecificSchedules(){
        this.getSchedule = true;
        this.showBookingDiv = false;
        this.allSchedules = {};
        this.addForm.schedule = 0;
        const data = {
            departure_city_id: this.addForm.departureCity,
            destination_city_id: this.addForm.destinationCity,
            date: this.addForm.date,
        }
        const resFetchSchedule = await this.callApi("post", "booking/fetchSchedule", data);
        if(resFetchSchedule.status == 200) {
            if (resFetchSchedule.length != 0) {
                this.getSchedule = false;
                this.allSchedules = resFetchSchedule.data;
            } else {
                this.addForm.schedule = 0;
                this.showBookingDiv = false;
            }
        }
    },
    cnicFormat: function (string) {
      return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
    },
    phoneFormat: function (string) {
      return string.replace(/(\d{4})(\d{7})/, "$1-$2");
    },
    async getCustomer() {
      const resCnic = await this.callApi("post", "booking/getCNIC", {
        cnicNumber: this.addForm.customerCNIC,
      });
      this.addForm.contact = resCnic.data.contact;
      this.addForm.customerName = resCnic.data.name;
    },
    isNumber: function (evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (
        charCode > 31 &&
        (charCode < 48 || charCode > 57) &&
        charCode !== 46
      ) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
    async fetchScheduleData() {
      this.resetingArrays();
      this.addForm.totalFare = 0;
      this.validationErrors = [];

      if (this.addForm.departureCity == 0 || typeof this.addForm.departureCity == 'undefined')
          return swal({
              title: "Required",
              text: "Please any Departure City",
              icon: "error",
              timer: 2000
          });
          if (this.addForm.destinationCity == 0 || typeof this.addForm.destinationCity == 'undefined')
          return swal({
              title: "Required",
              text: "Please Select Destination City",
              icon: "error",
              timer: 2000
          });
      if (this.addForm.date == "" || typeof this.addForm.date == 'undefined')
        return swal({
              title: "Required",
              text: "Date is Required",
              icon: "error",
              timer: 2000
          });
        if (this.addForm.schedule == 0 || typeof this.addForm.schedule == 'undefined')
            return swal({
                title: "Required",
                text: "Please Select any Schedule",
                icon: "error",
                timer: 2000
            });
      this.validationErrors = [];

      this.loading = true
      const res = await this.callApi("post", "schedule/selected", {
        id: this.addForm.schedule,
        date: this.addForm.date,
      });
      if (res.status == 200) {
        this.loading = false
        this.showBookingDiv = true;
        this.schedule = res.data;
      } else {
        console.log(res);
      }
    },
    selectSeat(row, col, seatNo) {
      this.validationErrors = [];
      if (
        this.addForm.oldBookings == 1 &&
        !this.schedule.bus_class.seat_map[row][col].type
      ) {
        this.doScroll();
        // return this.errorsArray("Please Select Already Booked Seat", "Oops");
          return swal({
              title: "Ops",
              text: "Please Select Already Booked Seat",
              icon: "error",
              timer: 2000
          });
      }
      if (
        this.schedule.bus_class.seat_map[row][col].type &&
        this.selectedSeats.length == 0
      ) {
        let index = this.selectedBookedSeats.indexOf(seatNo);
        if (index != -1) {
          this.schedule.bus_class.seat_map[row][col].selected = false;
          this.selectedBookedSeats.splice(index, 1);
          this.bookedSeats = this.bookedSeats.filter((seat) => {
            if (seat.seatNo != seatNo) {
              return seat;
            }
          });
        } else {
          this.schedule.bus_class.seat_map[row][col].selected = true;
          this.selectedBookedSeats.push(seatNo);
          this.bookedSeats.push(this.schedule.bus_class.seat_map[row][col]);
        }
        this.addForm.selectedBookedSeats = this.selectedBookedSeats;
      }else if (
        !this.schedule.bus_class.seat_map[row][col].type &&
        this.selectedBookedSeats.length == 0
      ) {
        let index = this.selectedSeats.indexOf(seatNo);
        if (index != -1) {
          this.schedule.bus_class.seat_map[row][col].selected = false;
          this.addForm.totalFare -= this.schedule.bus_class.seat_map[row][col].fare;
          this.selectedSeats.splice(index, 1);
        } else {
          this.schedule.bus_class.seat_map[row][col].selected = true;
          this.addForm.totalFare += this.schedule.bus_class.seat_map[row][col].fare;
          this.selectedSeats.push(seatNo);
        }
        this.addForm.selectedSeats = this.selectedSeats;
      } else {
        this.fetchScheduleData();
        this.resetingArrays();
        // return this.errorsArray("Invalid Seat Combination", "Oops");
          return swal({
              title: "Oops",
              text: "Invalid Seat Combination",
              icon: "error",
              timer: 2000
          });
      }

      // setTimeout(() => {
      //   const sum = this.bookedSeats.reduce((sum,seat)=>{
      //     return parseInt(sum) + parseInt(seat.fare);
      //   },0)
      //   console.log(sum);
      // }, 400);
    },
    getClasses(col) {
      let gender =
        col.gender != undefined && col.gender == 0
          ? "for-female"
          : col.gender && col.gender == 1
          ? "for-male"
          : "";
      let selected = col.selected ? "selected" : "";
      return gender + " " + selected;
    },
    async add() {
      this.validationErrors = [];
      if (!this.addForm.schedule) {
        this.doScroll();
        return this.errorsArray("Schedule Name is Required", "Schedule");
      }
      if (!this.addForm.date) {
        this.doScroll();
        return this.errorsArray("Date is Required", "Date");
      }
      if (
        !this.addForm.customerCNIC ||
        this.addForm.customerCNIC.length != 15
      ) {
        this.doScroll();
        return this.errorsArray(
          "CNIC is Required and Should Contain 15 Digits",
          "CNIC"
        );
      }
      if (this.selectedSeats.length == 0)
        return this.errorsArray("Please Select At Least One Seat", "Seat");

      this.validationErrors = [];

      const res = await this.callApi("post", "booking/store", this.addForm);
      if ( res.status === 200 ) {
        this.success = "Booking Created Successfully";
        this.fetchScheduleData();
        this.resetingArrays();
        this.addForm={};
        window.scrollTo(0, 0);
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
    doScroll: function () {
      $("#addBooking").scrollTop(10);
    },
    async deleteModal(surcharge, i) {
      const deletingObj = {
        url: "/surcharge/delete",
        data: surcharge,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
    },
    async resetingArrays() {
      this.selectedSeats = [];
      this.selectedBookedSeats = [];
      this.addForm.selectedSeats = [];
      this.addForm.selectedBookedSeats = [];
      this.bookedSeats = [];
      let resBooking = await this.callApi("post", "booking");
      if ( resBooking.status == 200 ) {
        this.allBookings = resBooking.data
        setTimeout(() => {
          $("#booking-table").dataTable();
        }, 300);
      } else {
        console.log(res);
      }
    },
    async details(date,schedule_id){

      const res = await this.callApi("post", "booking/details",{date,schedule_id});
      if (res.status == 200 ) {
        this.bookingDetails = res.data;
        console.log(this.bookingDetails);
        setTimeout(() => {
          $("#"+this.detailsFormId+" table").dataTable();
        }, 300);
      } else {
        console.log(res);
      }
    },
    reset(){
      this.addForm={};
      this.schedule = "";
      this.showBookingDiv = false;
    }
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

.economy {
  border: 3px solid #6d6e69 !important;
}

.business {
  border: 3px solid orangered !important;
}

.executive {
  border: 3px solid gold !important;
}

.for-female {
  background-color: hotpink !important;
}

.for-male {
  background-color: #3d8ff2 !important;
}

.not-for-sale {
  background-color: rgb(140, 109, 109) !important;
}

.selected {
  background-color: #6db131 !important;
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

.icons-legend {
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
