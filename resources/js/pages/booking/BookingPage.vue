<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary mb-0">
                        <!--                        <div class="card-header d-flex justify-content-between">-->
                        <!--                            <h4>Booking</h4>-->
                        <!--                        </div>-->
                        <div class="card-body">
                            <div class="row border-bottom mb-1">
                                <div class="col-md-2  mb-2">
                                    <label for="departureCity" class="mb-0">Departure City <span
                                        class="text-danger">*</span></label>
                                    <select class="form-control" id="departureCity"
                                            @change="fetchSpecificSchedules(); getDestinationCity()"
                                            v-model="addForm.departureCity">
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
                                <div class="col-md-2  mb-2"><label for="destinationCity" class="mb-0">Destination
                                    City<span class="text-danger">*</span></label>
                                    <select class="form-control" id="destinationCity"
                                            @change="fetchSpecificSchedules()"
                                            v-model="addForm.destinationCity">
                                        <option value="0" selected>Select Destination City</option>
                                        <option v-for="(city, i) in specificCities" :value="city.id"
                                                :key="i">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-2 class  mb-2">
                                    <label for="date" class="mb-0">Date <span class="text-danger">*</span></label>
                                    <input type="date" :min="minDateFilter()" class="form-control"
                                           v-model="addForm.date"
                                           @change="fetchSpecificSchedules()"/>
                                </div>
                                <div class="col-md-4 class  mb-2">
                                    <label for="scheduleName" class="mb-0">Schedule Name <span
                                        class="text-danger">*</span></label>
                                    <select class="form-control" id="scheduleName" @change="fetchScheduleData()"
                                            v-model="addForm.schedule">
                                        <option value="0" selected>Select Schedule</option>
                                        <option v-for="(schedule, i) in allSchedules"
                                                :value="schedule.schedule_id" :key="i">{{ scheduleDropdown(schedule) }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="mb-0">Action</label>
                                    <button @click="fetchScheduleData" class="btn btn-block btn-danger"
                                            :class="getSchedule ? 'disabled': ''">
                                        {{ getSchedule ? 'Loading...' : 'Refresh' }}
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <!--                                <div class="col-md-6 d-flex justify-content-center mx-auto mb-3"-->
                                <!--                                     v-if="selectedBookedSeats.length">-->
                                <!--                                    <a-->
                                <!--                                        @click="sameDataAsMain()"-->
                                <!--                                        href="#reschedule-modal"-->
                                <!--                                        class="btn btn-primary mx-1"-->
                                <!--                                        data-toggle="modal"-->
                                <!--                                    >Shifting ( Reschedule ) Seats</a>-->
                                <!--                                </div>-->
                                <!--                                <div class="col-md-6 d-flex justify-content-center mx-auto mb-3"-->
                                <!--                                     v-if="selectedBookedOverIssueSeats.length">-->
                                <!--                                    <a-->
                                <!--                                        href="#overIssue_model"-->
                                <!--                                        class="btn btn-primary mx-1"-->
                                <!--                                        data-toggle="modal"-->
                                <!--                                    >Over Issue Seats</a>-->
                                <!--                                </div>-->
                                <h1 v-if="loading">Loading.........</h1>

                                <div class="col-md-12 row" v-if="showBookingDiv">

                                    <div class="col-md-6 px-1">
                                        <div class="px-3 pt-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>CNIC <span class="text-danger">*</span></label>
                                                        <vue-mask
                                                            v-on:blur="getCustomer('addFormCNIC')"
                                                            class="form-control"
                                                            v-model="addForm.customerCNIC"
                                                            mask="00000-0000000-0"
                                                            :raw="false"
                                                            :options="options"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Full Name  <span class="text-danger">*</span></label>
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            id="fullName"
                                                            v-model="addForm.customerName"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Contact  <span class="text-danger">*</span></label>
                                                        <vue-mask
                                                            v-on:blur="getCustomer('addFormContact')"
                                                            class="form-control"
                                                            v-model="addForm.contact"
                                                            mask="0000-0000000"
                                                            :raw="false"
                                                            :options="optionsPhone"
                                                        >
                                                        </vue-mask>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Remarks</label>
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            id="remarks"
                                                            v-model="addForm.remarks"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Gender</label>
                                                        <div class="col-md-12 px-1 pt-3">
                                                            <input type="radio" id="female-booking"
                                                                   v-model="addForm.gender" value="0"/>
                                                            <label class="mx-2"
                                                                   for="female-booking">Female</label>
                                                            <input type="radio" id="male-booking"
                                                                   v-model="addForm.gender" value="1"/>
                                                            <label class="mx-2" for="male-booking">Male</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Issue Or Book </label>
                                                        <div class="col-md-12 px-1 pt-3">
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
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Seat No.</label>
                                                        <input
                                                            type="text"
                                                            readonly
                                                            class="form-control"
                                                            id="seatNo"
                                                            v-model="addForm.selectedSeats"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Total Seats</label>
                                                        <input
                                                            type="text"
                                                            readonly
                                                            class="form-control"
                                                            id="totalNoSeats"
                                                            v-model="selectedSeats.length"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Total Fare</label>
                                                        <input
                                                            type="text"
                                                            readonly
                                                            class="form-control font-weight-bold"
                                                            id="totalFare"
                                                            v-model="addForm.totalFare"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Discount ( % )</label>
                                                        <input
                                                            type="text"
                                                            readonly
                                                            class="form-control"
                                                            id="discount"
                                                            v-model="addForm.discount"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <!--                                                >-->
                                            <div class="form-group text-right">
                                                <button class="btn btn-primary mx-1"
                                                        v-on:click="add()"
                                                   v-on:keyup.enter="add()">
                                                    Generate Ticket
                                                </button>
                                                <button class="btn btn-secondary mx-1" @click="reset">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 px-1  overflow-auto" style="max-height: 530px !important;">
                                        <div
                                            class="d-flex justify-content-center seat-img p-0 m-0"
                                            v-for="(record, rowIndex) in schedule.bus_class.seat_map"
                                            :key="rowIndex"
                                        >
                                            <div v-for="(col, colIndex) in record" :key="colIndex">
                                                <div
                                                    v-if="col.reserved"
                                                    class="image-span d-block text-center text-white shadow"
                                                    @click="selectSeat(rowIndex, colIndex, col.seatNo)"
                                                    :class="getClasses(col)"
                                                    :style="{border:'2px solid ' + col.color + ' !important'}"
                                                    :title="col.departure_city + ' to ' + col.destination_city">
                                                    <small>{{ col.seatNo }} </small>
                                                    <br/>
                                                    <small
                                                        v-if="col.type && (col.type == 'booked' || col.type == 'advance booking')">
                                                        <i class="type-icons fas"
                                                           :class="col.type == 'booked' && col.over_issue != true ? 'fa-check-double' : 'fa-check'">
                                                        </i>
                                                        <!--                                                                            <i class="type-icons fas"-->
                                                        <!--                                                                               :class="col.over_issue == true ? 'fa-people-carry' : ''"> </i>-->
                                                    </small>
                                                    <small v-if="col.over_issue == true">
                                                        <!--                                                                            <i class="type-icons fas fa-people-carry text-danger"></i>-->
                                                        <i class="type-icons far fa-hand-paper text-dark">
                                                        </i>
                                                    </small>
                                                </div>
                                                <span v-else></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-2 px-1 " style="overflow-x: hidden; overflow-y: auto;">
                                        <div class="">
                                            <div class="col-md-12 mb-2 px-0 d-flex flex-wrap">
                                                <div class="border-bottom w-100">
                                                    <div class="my-1">
                                                        <div
                                                            class="selected circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Selected</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-female circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Female</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-male circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Male</span>
                                                    </div>
                                                </div>
                                                <div class="border-bottom w-100">
                                                    <div class="my-1">
                                                        <div
                                                            class="not-for-sale circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Not For Sale</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="circles icons-legend mr-1 border shadow">
                                                            <i class="fas fa-check"></i>
                                                        </div>
                                                        <span class="text-wrap mrn">Booked</span>
                                                    </div>
                                                    <div class="my-2">
                                                        <div
                                                            class="fas fa-check-double circles icons-legend shadow mr-1 border"
                                                        ></div>
                                                        <span class="text-wrap mrn">Issued</span>
                                                    </div>
                                                    <div class="my-2">
                                                        <div
                                                            class="partial-seat circles mr-1 border shadow"></div>
                                                        <span class="text-wrap mrn"
                                                              style="margin-top:-10px;">Partial Seat</span>
                                                    </div>
                                                    <div class="my-2">
                                                        <div
                                                            class="circles icons-legend mr-1 border shadow">
                                                            <i class="fas fa-people-carry text-danger"></i>
                                                        </div>
                                                        <span class="text-wrap mrn">Over Issue</span>
                                                    </div>
                                                    <div class="my-2">
                                                        <div
                                                            class="circles icons-legend mr-1 border shadow">
                                                            <i class="far fa-hand-paper text-dark"></i>
                                                        </div>
                                                        <span
                                                            class="text-wrap mrn">Over Issue</span>
                                                    </div>
                                                </div>

                                                <div class="my-1"
                                                     v-for="(seatClass,i) in allSeatClasses" :key="i">
                                                    <div class="circles mr-1 border shadow"
                                                         :style="{border:'2px solid '+seatClass.color+' !important'}"></div>
                                                    <span class="text-wrap">{{ seatClass.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Booking Preview-->
                        <!--                        <div class="card-body">-->
                        <!--                            &lt;!&ndash; Table &ndash;&gt;-->
                        <!--                            <div class="row">-->
                        <!--                                <div class="col-12">-->
                        <!--                                    <div class="card">-->
                        <!--                                        <div class="card-body">-->
                        <!--                                            <div class="row mb-4">-->
                        <!--                                                <div class="col-md-8 ">-->
                        <!--                                                    <input type="date" class="form-control" v-model="filterDate">-->
                        <!--                                                </div>-->
                        <!--                                                <div class="col-md-4">-->
                        <!--                                                    <button class="btn btn-block btn-lg btn-primary"-->
                        <!--                                                            @click="getFilterRecord()">Filter-->
                        <!--                                                    </button>-->
                        <!--                                                </div>-->
                        <!--                                            </div>-->
                        <!--                                            <div class="table-responsive">-->
                        <!--                                                <table-->
                        <!--                                                    class="table table-striped table-hover"-->
                        <!--                                                    id="booking_table"-->
                        <!--                                                >-->
                        <!--                                                    <thead>-->
                        <!--                                                    <tr>-->
                        <!--                                                        <th>Sr No.</th>-->
                        <!--                                                        <th>Date</th>-->
                        <!--                                                        <th>Schedule Name</th>-->
                        <!--                                                        <th>No. Of Bookings</th>-->
                        <!--                                                        <th>Action</th>-->
                        <!--                                                    </tr>-->
                        <!--                                                    </thead>-->
                        <!--                                                    <tbody>-->
                        <!--                                                    <tr v-for="(booking, i) in allBookings" :key="i">-->
                        <!--                                                        <td>{{ parseInt(i) + 1 }}</td>-->
                        <!--                                                        <td>{{ booking.date }}</td>-->
                        <!--                                                        <td>{{ booking.schedule.name }}</td>-->
                        <!--                                                        <td>{{ booking.count }}</td>-->
                        <!--                                                        <td>-->
                        <!--                                                            <a-->
                        <!--                                                                :href="'#'+detailsFormId"-->
                        <!--                                                                data-toggle="modal"-->
                        <!--                                                                @click="details(booking.date,booking.schedule.id)"-->
                        <!--                                                                class="btn btn-primary mx-1"-->
                        <!--                                                            >-->
                        <!--                                                                <i class="far fa-eye"></i>-->
                        <!--                                                            </a>-->
                        <!--                                                        </td>-->
                        <!--                                                    </tr>-->
                        <!--                                                    </tbody>-->
                        <!--                                                </table>-->
                        <!--                                            </div>-->
                        <!--                                        </div>-->
                        <!--                                    </div>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                        <!--                        </div>-->
                        <!-- END TABLE -->
                    </div>
                </div>
            </div>
        </div>


        <!--Over Issue Model-->
        <div class="modal fade" id="overIssue_model" tabindex="-1" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Re-Booking Over-Issued Seats</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pb-0">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-primary p-4">
                                    <div class="form-group row">
                                        <label
                                            class="col-md-3 pt-3 font-weight-bold"
                                            for="customer-cnic"
                                        >CNIC <span class="text-danger">*</span>
                                        </label>
                                        <vue-mask
                                            v-on:blur="getCustomer('overIssueCNIC')"
                                            class="form-control col-md-9"
                                            v-model="addFormOverIssue.customer.cnic"
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
                                            v-model="addFormOverIssue.customer.name"
                                        />
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-md-3 pt-3 font-weight-bold"
                                               for="contact"
                                        >Contact</label
                                        >
                                        <vue-mask
                                            v-on:blur="getCustomer('overIssueContact')"
                                            class="form-control col-md-9"
                                            v-model="addFormOverIssue.customer.contact"
                                            mask="0000-0000000"
                                            :raw="false"
                                            :options="optionsPhone"
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
                                            v-model="addFormOverIssue.ticket.remarks"
                                        />
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-md-3 pt-3 font-weight-bold"
                                        >Gender</label
                                        >
                                        <div class="col-md-9 pt-3">
                                            <input type="radio" id="female-booking_over_Issue"
                                                   :checked="addFormOverIssue.ticket.gender == 0"
                                                   v-model="addFormOverIssue.gender" value="0"/>
                                            <label class="mx-3"
                                                   for="female-booking_over_Issue">Female</label>
                                            <input type="radio" id="male-booking_over_Issue"
                                                   :checked="addFormOverIssue.ticket.gender == 1"
                                                   v-model="addFormOverIssue.gender" value="1"/>
                                            <label class="mx-3" for="male-booking_over_Issue">Male</label>
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
                                            v-model="addFormOverIssue.ticket.seat_no"
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
                                            v-model="addFormOverIssue.ticket.fare"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right">
                            <button class="btb btn-info mx-1"> Add ELT</button>
                            <button class="btn btn-primary mx-1" @click="addOverIssueTicket()"> Generate
                                Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--        modal for details-->
        <div class="modal fade" id="seatAllDetailsModal" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="seatAllDetailsModalLabel">Seat Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <!--                        loop for number of seats-->
                        <div class="card-body"> <!--v-for="(city, i) in cities"-->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <!--                                            seat Details-->
                                            <!--                                            <div class="row mb-1">-->
                                            <!--                                                <div class="col-md-12">-->
                                            <!--                                                    <div class="d-flex justify-content-between">-->
                                            <!--                                                    <p class="mb-0 font-weight-bold ">Seat : </p><p class="mb-0">1</p>-->
                                            <!--                                                    <p class="mb-0 font-weight-bold ">Date :</p><p class="mb-0">Schedule Date</p>-->
                                            <!--                                                    <p class="mb-0 font-weight-bold ">Bus Class :</p><p class="mb-0">Economy</p>-->
                                            <!--                                                    <p class="mb-0 font-weight-bold ">Route : </p><p class="mb-0">Islamabad- Karachi</p>-->
                                            <!--                                                    <p class="mb-0 font-weight-bold ">Schedule : </p><p class="mb-0">Schedule Name</p>-->
                                            <!--                                                    </div>-->
                                            <!--                                                </div>-->

                                            <!--                                            </div>-->
                                            <!--                                            Progress Bar-->
                                            <div class="row my-1">
                                                <div class="col-md-3">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold ">Seat : </p>
                                                        <p class="mb-0">1</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold ">Date :</p>
                                                        <p class="mb-0">Schedule Date</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold ">Bus Class :</p>
                                                        <p class="mb-0">Economy</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold ">Schedule : </p>
                                                        <p class="mb-0">Schedule Name</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="progress my-2" style="height: 30px;">
                                                        <div class="progress-bar" role="progressbar"
                                                             style="width: 50%; margin: auto;"
                                                             aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                                                            Faisalabad - Multan
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <p class="mb-0">Lahore</p>
                                                        <p class="mb-0">Faisalabad</p>
                                                        <p class="mb-0">Toba</p>
                                                        <p class="mb-0">Multan</p>
                                                        <p class="mb-0">Karachi </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--                                            Buttons-->
                                            <div class="row mt-1">
                                                <div class="col-md-12 text-right">
<!--                                                    v-if="selectedBookedOverIssueSeats.length"-->
<!--                                                    v-if="selectedBookedSeats.length"-->
                                                    <button type="button" class="btn btn-primary" >Reschedule</button>
                                                    <button type="button" class="btn btn-warning ml-2">Over Issue
                                                    </button>
                                                    <button type="button" class="btn btn-danger ml-2">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="addELTModel" tabindex="-1" aria-labelledby="addELTModelLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addELTModelLabel">ADD NEW CARGO</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary">Add Cargo</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!--        modal for seat details end-->
        <!--End Over Issue Model-->
        <!--            DELETE MODAL-->
        <Delete :deleteForm="deleteFormID"
                confirmationMessage="Are You Sure You want To Delete This Booking ???"
        />
        <ReschedulePopup :formID="rescheduleFormId" :seats="bookedSeats" :formData="sameDataMain"/>
        <!--        <OverIssuePopup :formID="overissueFormId" :seat_no="bookedOverIssueSeats"/>-->
        <DetailsModal :formID="detailsFormId" :details="bookingDetails" :deleteFormID="deleteFormID"/>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";
import ReschedulePopup from "./popup/ReschedulePopup.vue";
import OverIssuePopup from "./popup/OverIssuePopup.vue";
import DetailsModal from "./popup/DetailsModal.vue";

export default {
    name: "BookingPage",
    components: {
        Add,
        Edit,
        Delete,
        ReschedulePopup,
        OverIssuePopup,
        DetailsModal,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsPhone: {
                placeholder: "xxxx-xxxxxxx",
            },
            rescheduleFormId: "reschedule-modal",
            // overissueFormId: "overIssue_model",
            getCustomermessage: '',
            shiftingFormId: "shifting-modal",
            partialSeatFormId: "partialSeat-modal",
            detailsFormId: "details-modal",
            customers: [],
            sameDataMain: [],
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
            selectedOverIssueSeats: [],
            selectedBookedOverIssueSeats: [],
            bookedSeats: [],
            bookedOverIssueSeats: [],
            allBookings: [],
            bookingDetails: [],
            allSeatClasses: [],
            specificCities: [],
            filterDate: new Date().toISOString().substr(0, 10),
            cities: [],
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

            addFormOverIssue: {
                date: new Date().toISOString().substr(0, 10),
                type: "booked",
                gender: "1",
                customerCNIC: "",
                schedule: 0,
                totalFare: 0,
                destinationCity: 0,
                departureCity: 0,
                ticket: [],
                customer: [],
            },
        };
    },
    async created() {
        await this.fetchAllSchedules();
        window.addEventListener('keydown', this.enter);
        window.addEventListener('keydown', this.altM);
    },

    methods: {
        enter: function (e) {
            console.log(e);
            if (e.key == "Enter") {
                this.add();
            }
        },
        altM: function (e) {
            if ((e.metaKey || e.altKey) && (String.fromCharCode(e.which).toLowerCase() === 'm')) {
                if (this.selectedBookedSeats.length != 0 || this.selectedBookedOverIssueSeats.length != 0) {
                    $('#seatAllDetailsModal').modal('show');
                } else {
                    swal({
                        title: "OOPS!!",
                        text: "Please Select Already Booked Seat",
                        icon: "error",
                        timer: 2000,
                    });
                }
            }
        },
        scheduleDropdown: function (schedule) {
            return schedule.departure_date + ' ' + schedule.departure_time + ' - ' + schedule.schedule.name;
        },
        async getFilterRecord() {
            this.allBookings = [];
            const table = $("#booking_table").DataTable();
            table.destroy()
            const resDateFilter = await this.callApi("post", "booking", {date: this.filterDate});
            if (resDateFilter.status == 200) {
                if (resDateFilter.data.length != 0) {
                    this.allBookings = resDateFilter.data;
                    setTimeout(() => {
                        $("#booking_table").DataTable();
                    }, 300);
                } else {
                    setTimeout(() => {
                        $("#booking_table").DataTable();
                    }, 300);
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

        async getDestinationCity() {
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

        async fetchAllSchedules() {
            const resBooking = await this.callApi("post", "booking");
            const resClass = await this.callApi("post", "fare-class")
            const resCity = await this.callApi("post", "cities")
            if (resBooking.status == 200 && resClass.status == 200 && resCity.status == 200) {
                this.allBookings = resBooking.data;
                this.allSeatClasses = resClass.data;
                this.cities = resCity.data;
                setTimeout(() => {
                    // $("#" + this.formID).modal("show");
                    $("#booking_table").DataTable();
                }, 300);
            } else {
                console.log(res);
            }
        },

        resetSelectBooking(evt) {
            if (evt.target.value == '0') {
                this.showBookingDiv = false;
            } else {
                this.showBookingDiv = true;
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
            this.fetchScheduleData();
        },

        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },

        phoneFormat: function (string) {
            return string.replace(/(\d{4})(\d{7})/, "$1-$2");
        },

        async getCustomer(flag) {
            if (flag == 'addFormCNIC') {
                if (this.addForm.customerCNIC != '' && this.addForm.customerCNIC != 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        cnicNumber: this.addForm.customerCNIC,
                        status: flag,

                    });
                    this.addForm.contact = resCnic.data.contact;
                    this.addForm.customerName = resCnic.data.name;
                }
            }
            if (flag == 'overIssueCNIC') {
                if (this.addFormOverIssue.customer.cnic == '' && this.addFormOverIssue.customer.cnic == 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        cnicNumber: this.addFormOverIssue.customer.cnic,
                        status: flag,

                    });
                    this.addFormOverIssue.customer.name = resCnic.data.name;
                    this.addFormOverIssue.customer.contact = resCnic.data.contact;
                }
            }
            if (flag == 'addFormContact' && this.addForm.customerCNIC == '' && this.addForm.customerName == '') {
                if (this.addForm.contact != '' && this.addForm.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        phoneNumber: this.addForm.contact,
                        status: flag,
                    });
                    this.addForm.customerCNIC = resCnic.data.cnic;
                    this.addForm.customerName = resCnic.data.name;
                }
            }
            if (flag == 'overIssueContact' && this.addFormOverIssue.customer.name == '' && this.addFormOverIssue.customer.cnic == '') {
                if (this.addFormOverIssue.customer.contact != '' && this.addFormOverIssue.customer.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        phoneNumber: this.addFormOverIssue.customer.contact,
                        status: flag,
                    });
                    this.addFormOverIssue.customer.name = resCnic.data.name;
                    this.addFormOverIssue.customer.cnic = resCnic.data.cnic;
                }

            }
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
            if (this.addForm.schedule == 0) {
                this.showBookingDiv = false;
            }
            this.resetingArrays();
            this.addForm.totalFare = 0;
            this.validationErrors = [];
            this.loading = true
            const res = await this.callApi("post", "schedule/selected", {
                id: this.addForm.schedule,
                date: this.addForm.date,
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
            });
            if (res.status == 200) {
                this.loading = false
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
        async selectSeat(row, col, seatNo) {
            console.log(this.schedule.bus_class.seat_map[row][col])
            this.validationErrors = [];
            if (this.addForm.oldBookings == 1 && !this.schedule.bus_class.seat_map[row][col].type) {
                return swal({
                    title: "Ops",
                    text: "Please Select Already Booked Seat",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.schedule.bus_class.seat_map[row][col].type && this.selectedSeats.length == 0) {
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
            } else if (!this.schedule.bus_class.seat_map[row][col].type && this.selectedBookedSeats.length == 0) {
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
                return swal({
                    title: "Oops",
                    text: "Invalid Seat Combination",
                    icon: "error",
                    timer: 2000
                });
            }

            /*Over Issue Seats*/
            if (this.schedule.bus_class.seat_map[row][col].over_issue && this.selectedOverIssueSeats.length == 0) {
                let index = this.selectedBookedOverIssueSeats.indexOf(seatNo);
                if (index != -1) {
                    this.schedule.bus_class.seat_map[row][col].selected = false;
                    this.selectedBookedOverIssueSeats.splice(index, 1);
                    this.bookedOverIssueSeats = this.bookedOverIssueSeats.filter((seat) => {
                        if (seat.seatNo != seatNo) {
                            return seat;
                        }
                    });
                } else {
                    this.schedule.bus_class.seat_map[row][col].selected = true;
                    this.selectedBookedOverIssueSeats.push(seatNo);
                    this.bookedOverIssueSeats.push(this.schedule.bus_class.seat_map[row][col]);
                }
                this.addForm.selectedBookedOverIssueSeats = this.selectedBookedOverIssueSeats;

            } else if (!this.schedule.bus_class.seat_map[row][col].over_issue && this.selectedBookedOverIssueSeats.length == 0) {
                let index = this.selectedOverIssueSeats.indexOf(seatNo);
                if (index != -1) {
                    this.schedule.bus_class.seat_map[row][col].selected = false;
                    this.addForm.totalFare -= this.schedule.bus_class.seat_map[row][col].fare;
                    this.selectedOverIssueSeats.splice(index, 1);
                } else {
                    this.schedule.bus_class.seat_map[row][col].selected = true;
                    this.addForm.totalFare += this.schedule.bus_class.seat_map[row][col].fare;
                    this.selectedOverIssueSeats.push(seatNo);
                }
                this.addForm.selectedOverIssueSeats = this.selectedOverIssueSeats;
            } else {
                this.fetchScheduleData();
                this.resetingArrays();
                return swal({
                    title: "Oops",
                    text: "Invalid Seat Combination",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.schedule.bus_class.seat_map[row][col].over_issue) {
                const resOverIssue = await this.callApi("post", "booking/overIssue", {
                    date: this.addForm.date,
                    seat_no: seatNo,
                    schedule_id: this.addForm.schedule,
                    seat_fare: this.schedule.bus_class.seat_map[row][col].fare,
                    departureCity: this.schedule.bus_class.seat_map[row][col].departure_city,
                    destinationCity: this.schedule.bus_class.seat_map[row][col].destination_city,
                });
                if (resOverIssue.status == 200) {
                    this.addFormOverIssue.ticket = resOverIssue.data.ticket;
                    this.addFormOverIssue.customer = resOverIssue.data.customer;
                }
            }
        },

        async addOverIssueTicket() {
            if (this.addFormOverIssue.customer.cnic == '' || this.addFormOverIssue.customer.cnic == 'undefined') {
                swal({
                    title: "Required",
                    text: "CNIC is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            const dataNewTicket = {
                departure_city: this.addForm.departureCity,
                destination_city: this.addForm.destinationCity,
                date: this.addForm.date,
                schedule_id: this.addForm.schedule,
                cnic: this.addFormOverIssue.customer.cnic,
                name: this.addFormOverIssue.customer.name,
                contact: this.addFormOverIssue.customer.contact,
                remarks: this.addFormOverIssue.ticket.remarks,
                gender: this.addFormOverIssue.gender,
                seat_no: this.addFormOverIssue.ticket.seat_no,
                fare: this.addFormOverIssue.ticket.fare,

            };
            console.log(dataNewTicket);
            const resOverIssue = await this.callApi("post", "booking/overIssueAdd", dataNewTicket);
            console.log(resOverIssue);
            if (resOverIssue.status == 201) {
                swal({
                    title: "Success",
                    text: "Booking Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.addFormOverIssue.ticket = '';
                this.addFormOverIssue.customer = '';


            }

            if (resOverIssue.status == 422 && resOverIssue.data.message) {
                swal({
                    title: "Error",
                    text: resOverIssue.data.message,
                    icon: "error",
                    timer: 4000
                });
            }

            if (resOverIssue.status == 422) {
                let errorContent = "";
                let count = 0;
                for (const key in resOverIssue.data.errors) {
                    resOverIssue.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " + //creating serial no.
                            element + // main error
                            "\n" // creating new line
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 4000
                    });

                }
            }
        },

        getClasses(col) {
            let gender = col.gender != undefined && col.gender == 0 ? "for-female" : col.gender && col.gender == 1 ? "for-male" : "";
            let selected = col.selected ? "selected" : "";
            let partial = col.partial ? "partial" : "";
            let over = col.over_issue && col.partial ? "bg-secondary" : "";
            return gender + " " + selected + " " + partial + " " + over;
        },
        adddELT() {
            if(this.selectedSeats.length == 0){
                 return swal({
                    title: "Required!!",
                    text: "Please Select Any Seat First!",
                    icon: "error",
                    timer: 2000
                });
            }else{
                $("#addELTModel").modal("show");
            }
        },
        async add() {
            if (!this.addForm.schedule) {
                // return this.errorsArray("Schedule Name is Required", "Schedule");
                return swal({
                    title: "Required!",
                    text: "Schedule Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.date) {
                // return this.errorsArray("Date is Required", "Date");
                return swal({
                    title: "Required!",
                    text: "Date is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.customerCNIC || this.addForm.customerCNIC.length != 15) {
                // return this.errorsArray(
                //     "CNIC is Required and Should Contain 15 Digits",
                //     "CNIC"
                // );
                return swal({
                    title: "Required!",
                    text: "CNIC is Required and Should Contain 15 Digits",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.customerName || typeof  this.addForm.customerName == 'undefined') {
                // return this.errorsArray(
                //     "CNIC is Required and Should Contain 15 Digits",
                //     "CNIC"
                // );
                return swal({
                    title: "Required!",
                    text: "Customer Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.contact || typeof this.addForm.contact == 'undefined') {
                // return this.errorsArray(
                //     "CNIC is Required and Should Contain 15 Digits",
                //     "CNIC"
                // );
                return swal({
                    title: "Required!",
                    text: "Customer Contact Number is required,",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.selectedSeats.length == 0) {
                // return this.errorsArray("Please Select At Least One Seat", "Seat");
                return swal({
                    title: "required!",
                    text: "Please Select At Least One Seat",
                    icon: "error",
                    timer: 2000
                });
            }

            const res = await this.callApi("post", "booking/store", this.addForm);
            if (res.status === 200) {
                // this.success = "Booking Created Successfully";
                swal({
                    title: "Success",
                    text: "Booking Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();
                this.resetingArrays();
                this.addForm = {
                    date: new Date().toISOString().substr(0, 10),
                    type: "booked",
                    gender: "1",
                    customerCNIC: "",
                    schedule: 0,
                    totalFare: 0,
                    destinationCity: 0,
                    departureCity: 0,
                };
                this.showBookingDiv = false;
                this.allSchedules = '';
                $("#booking_table").DataTable().destroy();
                setTimeout(() => {
                    $("#booking_table").DataTable();
                }, 300);
                // window.scrollTo(0, 0);

            } else {
                if (res.status == 422) {
                    for (const key in res.addForm.errors) {
                        res.addForm.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        }
        ,

        doScroll: function () {
            $("#addBooking").scrollTop(10);
        }
        ,

        async deleteModal(surcharge, i) {
            const deletingObj = {
                url: "booking/delete",
                data: surcharge,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        }
        ,

        async resetingArrays() {
            this.selectedSeats = [];
            this.selectedBookedSeats = [];
            this.selectedOverIssueSeats = [];
            this.selectedBookedOverIssueSeats = [];
            this.addForm.selectedSeats = [];
            this.addForm.selectedBookedSeats = [];
            this.addForm.selectedOverIssueSeats = [];
            this.addForm.selectedBookedOverIssueSeats = [];
            this.bookedSeats = [];
            this.bookedOverIssueSeats = [];
            let resBooking = await this.callApi("post", "booking");
            if (resBooking.status == 200) {
                this.allBookings = resBooking.data
                $("#booking_table").DataTable().destroy();
                setTimeout(() => {
                    $("#booking_table").DataTable();
                }, 300);
            } else {
                console.log(res);
            }
        }
        ,

        async details(date, schedule_id) {
            $("#" + this.detailsFormId + " table").DataTable().destroy();
            const resBookingDetail = await this.callApi("post", "booking/details", {date, schedule_id});
            if (resBookingDetail.status === 200) {
                this.bookingDetails = resBookingDetail.data;
                setTimeout(() => {
                    $("#" + this.detailsFormId + " table").DataTable();
                }, 300);
            } else {
                console.log(resBookingDetail);
            }
        }
        ,

        reset() {
            this.addForm = {
                date: new Date().toISOString().substr(0, 10),
                type: "booked",
                gender: "1",
                customerCNIC: "",
                schedule: 0,
                totalFare: 0,
                destinationCity: 0,
                departureCity: 0,
            };
            this.showBookingDiv = false;
            this.allSchedules = '';
            this.selectedBookedSeats = '';
            this.selectedBookedOverIssueSeats = '';
        }
    },
    computed: {
        ...
            mapGetters(["getDeletingObj"]),
    }
    ,
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.surcharges.splice(obj.index, 1);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        }
        ,
    }
    ,
}
;
</script>
<style scoped>
.image-span {
    background-color: #a2a3a7;
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
    height: 45px;
    width: 45px;
    display: inline-block;
    cursor: pointer !important;
    margin: 2px;
}

img {
    cursor: pointer !important;
}

.circles {
    width: 15px;
    height: 15px;
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
    top: -4px;
    padding: 5px;
    color: black;
}

.type-icons {
    position: relative;
    z-index: 10;
}

.partial-seat {
    width: 15px;
    height: 15px;
    background: linear-gradient(90deg, white 50%, black 50%);
    border-radius: 50%;
    display: inline-block;
    box-sizing: content-box;
    -moz-border-radius: 25px;
    -webkit-border-radius: 25px;

}

.mrn {
    top: -10px !important;
}
</style>
