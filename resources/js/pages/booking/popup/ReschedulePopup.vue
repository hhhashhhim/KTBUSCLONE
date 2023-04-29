<template>
    <section class="section">
        <BasicPopup
            :heading="'Reschedule Seats'"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="departureCity"
                    >Departure City <span class="text-danger ml-1">*</span></label
                    >
                    <select
                        class="form-control"
                        id="departureCity"
                        @change="fetchSpecificSchedules(); getDestinationCity()"
                        v-model="addForm.departureCity"
                    >
                        <option value="0" selected>Select Departure City</option>
                        <option v-for="(city, i) in cities" :value="city.id" :key="i">
                            {{ city.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="destinationCity"
                    >Destination City<span class="text-danger ml-1">*</span></label
                    >
                    <select
                        class="form-control"
                        id="destinationCity"
                        @change="fetchSpecificSchedules()"
                        v-model="addForm.destinationCity"
                    >
                        <option value="0" selected>Select Destination City</option>
                        <option v-for="(city, i) in specificCities" :value="city.id" :key="i">
                            {{ city.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-5 class form-group">
                    <label for="date">Date <span class="text-danger ml-1">*</span></label>
                    <input
                        type="date"
                        class="form-control"
                        :min="minDateFilter()"
                        v-model="addForm.date"
                        @change="fetchSpecificSchedules()"
                    />
                </div>
                <div class="col-md-5 class form-group">
                    <label for="scheduleName"
                    >Schedule Name <span class="text-danger ml-1">*</span></label
                    >
                    <select
                        class="form-control"
                        id="scheduleName"
                        v-model="addForm.schedule"
                        @change="resetSelectBooking($event)"
                    >
                        <option value="0" selected>Select Schedule</option>
                        <option
                            v-for="(schedule, i) in allSchedules"
                            :value="schedule.id"
                            :key="i"
                        >{{ scheduleDropdown(schedule) }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Action</label>
                    <button
                        @click="fetchScheduleData"
                        class="btn btn-block btn-primary"
                        :class="getSchedule ? 'disabled' : ''"
                    >
                        {{ getSchedule ? "Fetching Schedules..." : "Get Record" }}
                    </button>
                </div>

                <div class="col-md-12 row" v-if="showBookingDiv">
                    <div class="col-md-12 mx-auto">
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

                                <div
                                    class="my-2"
                                    v-for="(seatClass, i) in allSeatClasses"
                                    :key="i"
                                >
                                    <div
                                        class="circles mr-1 border shadow"
                                        :style="{
                      border: '2px solid ' + seatClass.color + ' !important',
                    }"
                                    ></div>
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
                                        class="
                      fas
                      fa-check-double
                      circles
                      icons-legend
                      shadow
                      mr-1
                      border
                    "
                                    ></div>
                                    <span class="text-wrap">Issued</span>
                                </div>

                                <div class="my-2">
                                    <div class="partial-seat circles mr-1 border shadow"></div>
                                    <span class="text-wrap" style="margin-top: -10px"
                                    >Partial Seat</span
                                    >
                                </div>
                                <div class="my-3">
                                    <div class="circles icons-legend mr-1 border shadow">
                                        <i class="fas fa-people-carry text-danger"></i>
                                    </div>
                                    <span class="text-wrap">Over Issue</span>
                                </div>
                            </div>
                            <div class="col-md-12 my-4">
                                <button class="btn btn-primary btn-block" @click="rescheduleSeats">Reschedule Seats
                                </button>
                            </div>
                            <div class="d-flex justify-content-center seat-img p-0 m-0"
                                v-for="(record, rowIndex) in schedule.bus_class.seat_map"
                                :key="rowIndex" >
                                <div v-for="(col, colIndex) in record" :key="colIndex">
                                    <div
                                        v-if="col.reserved"
                                        class="image-span d-block text-center text-white shadow"
                                        @click="col.type?bookingError():selectSeat(rowIndex, colIndex, col.seatNo)"
                                        :class="getClasses(col)"
                                        :style="{ border: '3px solid ' + col.color + ' !important',  }" :title=" col.partial ? col.departure_city + ' to ' + col.destination_city : '' ">
                                        <small>{{ col.seatNo }} </small>
                                        <br/>
                                        <small
                                            v-if=" col.type && (col.type == 'booked' || col.type == 'advance booking') " >
                                            <i
                                                class="type-icons fas"
                                                :class=" col.type == 'booked' && col.over_issue != true ? 'fa-check-double': 'fa-check'
                        "
                                            >
                                            </i>
                                            <i
                                                class="type-icons fas"
                                                :class="col.over_issue == true ? 'fa-people-carry' : ''"
                                            >
                                            </i>
                                        </small>
                                        <small v-if="col.over_issue == true">
                                            <i class="type-icons fas fa-people-carry text-danger"></i>
                                        </small>
                                    </div>
                                    <span v-else></span>
                                </div>
                            </div>

                            <tr></tr>
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
    name: "ReschedulePopup",
    props: ["formID", "seats", "formData"],
    components: {
        BasicPopup,
    },
    async created() {
        this.fetchData();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },
    data() {
        return {
            partialSchedule: 0,
            addForm: {
                date: new Date().toISOString().substr(0, 10),
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
        scheduleDropdown: function (schedule) {
            return schedule.departure_date + ' ' + schedule.departure_time + ' - ' + schedule.schedule.name;
        },

        async fetchData() {
            const resCity = await this.callApi("post", "cities");
            const res = await this.callApi("post", "schedule");
            if (res.status == 200 && resCity.status == 200) {
                this.allSchedules = res.data;
                this.cities = resCity.data;
            } else {
                console.log(res);
            }
        },

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
            this.specificCities = [];
            if (this.addForm.departureCity == '0') {
                this.addForm.destinationCity = 0;
            } else {
                const resDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.addForm.departureCity});
                if (resDepartureCity.length == 0) {
                    this.addForm.destinationCity = 0
                } else {
                    this.addForm.destinationCity = 0;
                    this.specificCities = resDepartureCity.data;
                }
            }
        },

        minDateFilter: function () {
            var dtToday = new Date();
            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();
            return year + '-' + month + '-' + day;
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
            }
            const resFetchSchedule = await this.callApi("post", "booking/fetchSchedule", data);
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
