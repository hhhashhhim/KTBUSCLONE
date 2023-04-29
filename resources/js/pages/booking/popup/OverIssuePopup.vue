<template>
  <section class="section">
    <BasicPopup
      :heading="''"
      :errors="this.validationErrors"
      :success="success"
      :formID="formID"
    >
      <div class="row">
          <div class="col-md-12">
              <div class="card card-primary p-4">
                  <div class="form-group row">
                      <label
                          class="col-md-3 pt-3 font-weight-bold"
                          for="customer-cnic"
                      >CNIC <span class="text-danger ml-1">*</span>
                      </label>
                      <vue-mask
                          v-on:keyup.enter="getCustomer"
                          v-on:blur="getCustomer"
                          class="form-control col-md-9"
                          v-model="addForm.customerCNIC"
                          mask="00000-0000000-0"
                          :raw="false"
                          :options="options"
                      >
                      </vue-mask>
                  </div>
                  <div class="form-group row">
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="fullName"
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
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="contact"
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

                  </div>
                  <div class="form-group row">
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="remarks"
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
                      <label class="col-md-3 pt-3 font-weight-bold"
                      >Gender</label
                      >
                      <div class="col-md-9 pt-3">
                          <input type="radio" id="female-booking"
                                 v-model="addForm.gender" value="0"/>
                          <label class="mx-3"
                                 for="female-booking">Female</label>
                          <input type="radio" id="male-booking"
                                 v-model="addForm.gender" value="1"/>
                          <label class="mx-3" for="male-booking">Male</label>
                      </div>
                  </div>
                  <div class="form-group row">
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="contact"
                      >Issue Or Book</label
                      >
                      <div class="col-md-9 pt-3">
                          <input type="radio" id="type-issue"
                                 v-model="addForm.type" value="booked"/>
                          <label class="mx-3" for="type-issue">Issue</label>
                          <input
                              type="radio"
                              id="type-book"
                              v-model="addForm.type"
                              value="advance booking"
                          />
                          <label class="mx-3" for="type-book">Book</label>
                      </div>
                  </div>
                  <div class="form-group row">
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="seatNo"
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
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="totalFare"
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
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="totalFare"
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
                      <label class="col-md-3 pt-3 font-weight-bold"
                             for="discount"
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
                      <button class="btn btn-primary mx-1" @click="add">Save
                      </button>
                      <button class="btn btn-secondary mx-1" @click="reset">
                          Reset
                      </button>
                  </div>
              </div>
          </div>
      </div>
    </BasicPopup>
  </section>
</template>
<script>
import BasicPopup from "../../../components/BasicPopup.vue";

export default {
  name: "OverIssuePopup",
  props: ["formID", "seat_no"],
  components: {
    BasicPopup,
  },
  async created() {
      // window.removeEventListener('keydown', this.enterKey);
      // window.removeEventListener('keydown', this.altM);
      const currentRouteName = this.$route.name;
      if (currentRouteName !== 'booking-page') {
          window.removeEventListener('keydown', this.enterKey);
          window.removeEventListener('keydown', this.altM);
      }
  },
  data() {
    return {
      partialSchedule: 0,
      addForm: {
        type: "booked",
        gender: "1",
        customerCNIC: "",
        schedule: 0,
        totalFare: 0,
        destinationCity: 0,
        departureCity: 0,
      },
      showBookingDiv: false,
      schedule: "",
      success: false,
      validationErrors: [],
        specificCities: [],
      loading: false,
      selectedSeats: [],
      cities: [],
      allSchedules: [],
      getSchedule: false,
    };
  },

  methods: {
    tConvert: function (time) {
      time = time.toString().match(/^([01]\d|2[0-3])(:)([0-5]\d)?$/) || [time];
      if (time.length > 1) {
        time = time.slice(1);
        time[5] = +time[0] < 12 ? " AM" : " PM";
        time[0] = +time[0] % 12 || 12;
      }
      return time.join("");
    },
      async getDestinationCity() {
          if (this.addForm.departureCity == '0') {
              this.addForm.destinationCity = 0;
          } else {
              const resDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.addForm.departureCity});
              this.specificCities = resDepartureCity.data;
          }
      },
    async fetchSpecificSchedules() {
      this.getSchedule = true;
      this.showBookingDiv = false;
      this.allSchedules = {};
      this.addForm.schedule = 0;
      const data = {
        departure_city_id: this.addForm.departureCity,
        destination_city_id: this.addForm.destinationCity,
        date: this.addForm.date,
      };
      const resFetchSchedule = await this.callApi(
        "post",
        "booking/fetchSchedule",
        data
      );
      if (resFetchSchedule.status == 200) {
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
    resetSelectBooking(evt) {
      if (evt.target.value == "0") {
        this.showBookingDiv = false;
      } else {
        this.showBookingDiv = true;
      }
    },
    async rescheduleSeats() {
      this.validationErrors = [];
      if (!this.addForm.schedule) {
        this.doScroll();
        return this.errorsArray("Schedule Name is Required", "Schedule");
      }
      if (!this.addForm.date) {
        this.doScroll();
        return this.errorsArray("Date is Required", "Date");
      }
      if (this.selectedSeats.length == 0)
        return this.errorsArray("Please Select At Least One Seat", "Seat");
        this.validationErrors = [];
        const res = await this.callApi("post", "booking/reschedule", {
          ...this.addForm,
          bookingSeats: this.seats,
        });
      if (res.status == 200) {
        this.success = "Seats Rescheduled Successfully";
        this.fetchScheduleData();
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
    async fetchScheduleData() {
      this.validationErrors = [];
      if (
        this.addForm.departureCity == 0 ||
        typeof this.addForm.departureCity == "undefined"
      )
        return swal({
          title: "Required",
          text: "Please any Departure City",
          icon: "error",
          timer: 2000,
        });
      if (
        this.addForm.destinationCity == 0 ||
        typeof this.addForm.destinationCity == "undefined"
      )
        return swal({
          title: "Required",
          text: "Please Select Destination City",
          icon: "error",
          timer: 2000,
        });
      if (this.addForm.date == "" || typeof this.addForm.date == "undefined")
        return swal({
          title: "Required",
          text: "Date is Required",
          icon: "error",
          timer: 2000,
        });
      if (
        this.addForm.schedule == 0 ||
        typeof this.addForm.schedule == "undefined"
      )
        return swal({
          title: "Required",
          text: "Please Select any Schedule",
          icon: "error",
          timer: 2000,
        });
      this.validationErrors = [];
      this.loading = true;
      const res = await this.callApi("post", "schedule/selected", {
        id: this.addForm.schedule,
        date: this.addForm.date,
        departureCity: this.addForm.departureCity,
        destinationCity: this.addForm.destinationCity,
      });
      if (res.status == 200) {
        this.loading = false;
        this.showBookingDiv = true;
        this.schedule = res.data;
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
    selectSeat(row, col, seatNo) {
      let index = this.selectedSeats.indexOf(seatNo);
      console.log(this.seats, this.selectedSeats);
      if (index != -1) {
        this.schedule.bus_class.seat_map[row][col].selected = false;
        this.selectedSeats.splice(index, 1);
      } else {
        if (this.seats.length == this.selectedSeats.length) {
          swal(
            "Error",
            "New Seats Cannot Be Greater than the Previous Seats No.",
            "error"
          );
          return;
        }
        this.schedule.bus_class.seat_map[row][col].selected = true;
        this.selectedSeats.push(seatNo);
      }
      this.addForm.selectedSeats = this.selectedSeats;
    },
    getClasses(col) {
      let gender =
        col.gender != undefined && col.gender == 0
          ? "for-female"
          : col.gender && col.gender == 1
          ? "for-male"
          : "";
      let selected = col.selected ? "selected" : "";
      let partial = col.partial ? "partial" : "";

      return gender + " " + selected + " " + partial;
    },
    bookingError() {
      swal("error", "Already Booked !!!!", "error");
    },
  },
  watch: {
    seats(newValue) {
      console.log(newValue);
    },
  },
};
</script>
<style scoped>
.image-span {
  background-color: #b9dea0;
  border-radius: 10px;
  cursor: pointer;
  position: relative;
  isolation: isolate;
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
.partial::after {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  z-index: -1;
  height: 100%;
  width: 50%;
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
  background-color: rgba(0, 0, 0, 0.8);
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
.type-icons {
  position: relative;
  z-index: 10;
}
.partial-seat {
  width: 30px;
  height: 30px;
  background: linear-gradient(90deg, white 50%, black 50%);
  border-radius: 50%;
  display: inline-block;
  box-sizing: content-box;
  -moz-border-radius: 25px;
  -webkit-border-radius: 25px;
}
</style>
