<template>
  <section class="section">
    <BasicPopup
      :heading="'Reschedule Seats'"
      :errors="this.validationErrors"
      :success="success"
      :formID="formID"
    >
      <div class="row">
        <div class="col-md-5 class form-group">
          <label for="DiscountName"
            >New Schedule Name <span class="text-danger">*</span></label
          >
          <select class="form-control" id="route" v-model="addForm.schedule">
            <option value="0" selected>Select Schedule</option>
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
          <label for="date">New Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" v-model="addForm.date" />
        </div>
        <div class="col-md-2">
          <label>Action</label>
          <button @click="fetchScheduleData" class="btn btn-block btn-primary">
            Get Record
          </button>
        </div>

        <div class="col-md-12 row" v-if="showBookingDiv">

          <div class="col-md-12 mx-auto">
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
                  <div
                    class="fas fa-check-double circles icons-legend mr-1 border"
                  ></div>
                  <span class="text-wrap">Issued</span>
                </div>
                <div class="my-4 align-self-end">
                  <button class="btn btn-primary" @click="rescheduleSeats">Reschedule Seats</button>
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
                    @click="col.type?bookingError():selectSeat(rowIndex, colIndex, col.seatNo)"
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
    </BasicPopup>
  </section>
</template>
<script>
import BasicPopup from "../../../components/BasicPopup.vue";

export default {
  name: "BookingOptionsPopup",
  props: ["formID", "seats"],
  components: {
    BasicPopup,
  },
  async created() {
    const res = await this.callApi("post", "schedule");
    if (res.status == 200) {
      this.allSchedules = res.data;
    } else {
      console.log(res);
    }
  },
  data() {
    return {
      partialSchedule: 0,
      addForm: {
        schedule: 0,
        date: "",
      },
      showBookingDiv:false,
      schedule:"",
      success:false,
      validationErrors: [],
      loading: false,
      selectedSeats: [],
      allSchedules:[],
    };
  },
  methods:{
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
        bookingSeats:this.seats,
      });
      if ( res.status == 200 ) {
        this.success = "Seats Rescheduled Successfully"
        this.fetchScheduleData()
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
      this.selectedSeats = []
      this.validationErrors = [];
      if (!this.addForm.schedule)
        return this.errorsArray("Schedule Name is Required", "Schedule");
      if (!this.addForm.date)
        return this.errorsArray("Date is Required", "Date");
      this.validationErrors = [];

      //this.loading = true
      const res = await this.callApi("post", "schedule/selected", {
        id: this.addForm.schedule,
        date: this.addForm.date,
      });
      if (res.status == 200) {
        //this.loading = false
        this.showBookingDiv = true;
        this.schedule = res.data;
      } else {
        console.log(res);
      }
    },
    selectSeat(row, col, seatNo) {

      let index = this.selectedSeats.indexOf(seatNo);
      console.log(index);
      if (index != -1) {
        this.schedule.bus_class.seat_map[row][col].selected = false;
        this.selectedSeats.splice(index, 1);
      } else {
        if (this.seats.length==this.selectedSeats.length) {
          swal('Error', "New Seats Cannot Be Greater than the Previous Seats No." , 'error');
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
      return gender + " " + selected;
    },
    bookingError(){
      swal('error','Already Booked !!!!','error')
    },
  },
  watch: {
    seatId(newValue) {
      // const resCnic = await this.callApi("post", "schedule/booking-options", {cnicNumber: this.addForm.customerCNIC});
      this.addForm.contact = resCnic.data.contact;
      this.addForm.customerName = resCnic.data.name;
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
