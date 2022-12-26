<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary mb-0">
                        <div class="card-body pb-0">
                            <div class="row border-bottom mb-1">
                                <div class="col-md-2  mb-2">
                                    <label for="departureCity" class="mb-0">Departure City <span
                                        class="text-danger">*</span></label>
                                    <select class="form-control" id="departureCity"
                                            @change="fetchSpecificSchedules(); getDestinationCity()"
                                            v-model="addForm.departureCity">
                                        <option value="0" selected>Select Departure City</option>
                                        <option v-for="(city, i) in cities"
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
                                <h1 v-if="loading">Loading.........</h1>
                                <div class="col-md-12 row" v-if="showBookingDiv">
                                    <div class="col-md-6 px-1">
                                        <div class="px-3 pt-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>CNIC <span class="text-danger"
                                                                          v-if="this.addForm.type != 'advance booking'">*</span></label>
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
                                                        <label>Full Name <span class="text-danger">*</span></label>
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
                                                        <label>Contact <span class="text-danger">*</span></label>
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
                                                        <label class=" mr-3">Female : </label>
                                                        <label class="colorinput">
                                                            <input name="gender" type="checkbox" value="0"
                                                                   class="colorinput-input"
                                                                   @click="changeGender($event)"
                                                                   v-bind:checked="addForm.gender == 0">
                                                            <span class="colorinput-color bg-primary"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="mr-3">Advanced Booked : </label>
                                                        <label class="colorinput">
                                                            <input name="bookingType" type="checkbox"
                                                                   value="advance booking"
                                                                   class="colorinput-input bookingCheck"
                                                                   @click="changeType($event)"
                                                                   v-bind:checked="addForm.type == 'advance booking'">
                                                            <span class="colorinput-color bg-primary"></span>
                                                        </label>
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
                                                <div class="col-md-4">
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
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Discount <span
                                                            class="ml-2 text-muted">(Flat Amount)</span></label>
                                                        <input
                                                            type="text" @keypress="isNumber($event)"
                                                            @keyup="calculateTotal()"
                                                            class="form-control"
                                                            readonly
                                                            id="fareDiscount"
                                                            v-model="addForm.discount"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Total Receivable </label>
                                                        <input type="text"
                                                               class="form-control"
                                                               readonly
                                                               v-model="addForm.totalAmount"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group text-center"
                                                 style=" margin-bottom: 10PX !important;">
                                                <button class="btn btn-info mx-1">
                                                    Print Terminal Invoice
                                                </button>
                                                <!--                                                <button class="btn btn-info mx-1" data-toggle="modal"-->
                                                <!--                                                        data-target="#advanceCahModel" @click="openAdvanceModel()">-->
                                                <!--                                                    Advance Cash Voucher-->
                                                <!--                                                </button>-->
                                                <button class="btn btn-warning mx-1">
                                                    Print Bus Invoice
                                                </button>
                                                <button class="btn btn-primary mx-1"
                                                        v-on:click="add()"
                                                        v-on:keyup.enter="add()">
                                                    Generate Ticket
                                                </button>
                                            </div>
                                            <div class="form-group text-center">
                                                <button class="btn btn-danger mx-1" @click="getCustomerList()">
                                                    Print Pax List
                                                </button>

                                                <button class="btn btn-secondary text-dark"
                                                        @click="this.fetchScheduleData();">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 px-1  overflow-auto" style="max-height: 560px !important;">
                                        <div class="d-flex justify-content-center seat-img p-0 m-0"
                                             v-for="(record, rowIndex) in schedule.bus_class.seat_map"
                                             :key="rowIndex"
                                        >
                                            <div v-for="(col, colIndex) in record" :key="colIndex">
                                                <div
                                                    v-if="col.reserved"
                                                    class="image-span d-block text-center text-white shadow"
                                                    @click="selectSeat(rowIndex, colIndex, col.seatNo, col.fare, col.class); updateBookedSeat(col) "
                                                    :class="getClasses(col)"
                                                    :title="getTitle(col)"
                                                    :style="getStyle(col)"
                                                >
                                                    <small>{{ col.seatNo }} </small>
                                                    <br/>
                                                    <small
                                                        v-if="col.type && (col.type == 'booked' || col.type == 'advance booking')">
                                                        <i class="type-icons fas"
                                                           :class="col.type == 'booked' && col.over_issue != true ? 'fa-check-double' : 'fa-check'">
                                                        </i>
                                                    </small>
                                                    <small v-if="col.over_issue == true">
                                                        <i class="type-icons far fa-hand-paper text-danger">
                                                        </i>
                                                    </small>
                                                    <small v-if="col.type == 'not_for_sale'">
                                                        <i class="fas fa-minus-circle text-light"></i>
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
                                                    <div class="my-1" style="padding-bottom: 10px !important;">
                                                        <div
                                                            class="fas fa-minus-circle text-dark circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Not For Sale Badge</span>
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

                                                <div class="my-1" v-for="(seatClass,i) in allSeatClasses" :key="i">
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
                    </div>
                </div>
            </div>
        </div>

        <!--        &lt;!&ndash;Advance Cash Model  &ndash;&gt;-->
        <!--        <div class="modal fade" id="advanceCahModel" tabindex="0" aria-labelledby="advanceCahModelLabel"-->
        <!--             aria-hidden="true" v-if="closeAdvanceCashModel">-->
        <!--            <div class="modal-dialog modal-dialog-centered modal-lg">-->
        <!--                <div class="modal-content">-->
        <!--                    <div class="modal-header">-->
        <!--                        <h5 class="modal-title" id="advanceCahModelLabel">ADVANCE BUS TO CASH</h5>-->
        <!--                        <button type="button" class="close">-->
        <!--                            <span aria-hidden="true">&times;</span>-->
        <!--                        </button>-->
        <!--                    </div>-->
        <!--                    <div class="modal-body">-->
        <!--                        <div class="row">-->
        <!--                            <div class="col-md-6">-->
        <!--                                <div class="form-group">-->
        <!--                                    <label for="weight">Terminal Advance Sale</label>-->
        <!--                                    <input-->
        <!--                                        type="text"-->
        <!--                                        class="form-control" placeholder="Enter Terminal Advance Sale"-->
        <!--                                        @keypress="isNumber($event)"-->
        <!--                                        readonly-->
        <!--                                        v-model="advanceCash.sale"-->
        <!--                                    />-->
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                            <div class="col-md-6">-->
        <!--                                <div class="form-group"-->
        <!--                                >-->
        <!--                                    <label>Bus Voucher Amount</label>-->
        <!--                                    <input-->
        <!--                                        type="text"-->
        <!--                                        class="form-control" placeholder="Enter Elt Price" @keypress="isNumber($event)"-->
        <!--                                        readonly-->
        <!--                                        v-model="advanceCash.amount"-->
        <!--                                    />-->
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                        <div class="row">-->
        <!--                            <div class="col-md-6">-->
        <!--                                <div class="form-group">-->
        <!--                                    <label for="weight">Advance Deposit<span-->
        <!--                                        class="text-danger">*</span></label>-->
        <!--                                    <input-->
        <!--                                        type="text"-->
        <!--                                        class="form-control" placeholder="Enter Terminal Advance Deposit"-->
        <!--                                        @keypress="isNumber($event)"-->
        <!--                                        id="weight"-->
        <!--                                        v-model="advanceCash.advanceDeposit"-->
        <!--                                    />-->
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                            <div class="col-md-6">-->
        <!--                                <div class="form-group"-->
        <!--                                >-->
        <!--                                    <label>Withdraw From Bank<span class="text-danger">*</span></label>-->
        <!--                                    <input-->
        <!--                                        type="text"-->
        <!--                                        class="form-control" placeholder="Enter Withdraw From Bank"-->
        <!--                                        @keypress="isNumber($event)"-->
        <!--                                        id="fullName"-->
        <!--                                        v-model="advanceCash.withdrawBank"-->
        <!--                                    />-->
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                        <div class="row">-->
        <!--                            <div class="col-md-12">-->
        <!--                                <div class="form-group">-->
        <!--                                    <label for="description">Description <span class="text-danger">*</span></label>-->
        <!--                                    <textarea class="form-control" id="description"-->
        <!--                                              placeholder="Enter Advance Cash Description"-->
        <!--                                              v-model="advanceCash.description"-->
        <!--                                    ></textarea>-->
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                    <div class="modal-footer">-->
        <!--                        <button type="button" class="btn btn-primary"-->
        <!--                                @click="addAdvanceCash()">-->
        <!--                            Add Advance Voucher-->
        <!--                        </button>-->
        <!--                        <button type="button" class="btn btn-secondary">Close</button>-->
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--        </div>-->

        <!--Add ELT -->
        <div class="modal fade" id="addELTModel" tabindex="0" aria-labelledby="addELTModelLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addELTModelLabel">ADD ELT</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="weight">Weight <span class="text-muted mr-1">(In Kg's)</span> <span
                                        class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control" placeholder="Enter Elt Weight" @keypress="isNumber($event)"
                                        id="weight"
                                        v-model="eltData.eltWeight"
                                    />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group"
                                >
                                    <label>Price<span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control" placeholder="Enter Elt Price" @keypress="isNumber($event)"
                                        id="fullName"
                                        v-model="eltData.eltPrice"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" placeholder="Enter Elt Description"
                                              v-model="eltData.dataDescription"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"
                                @click="addEltToTicket(eltData)">
                            Add ELT
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!--Over Issue Model-->
        <div class="modal fade" id="overIssue_model" tabindex="1" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Over-Issued Seats</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="over_issue_remarks">Remarks <span class="text-danger">*</span></label>
                            <textarea type="text" class="form-control" id="over_issue_remarks"
                                      v-model="overIssueData.reason"
                                      placeholder="Reason for over-issue a seat"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary mx-1"
                                @click="addOverIssueTicket(overIssueData)">
                            Over-Issue Ticket
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!--Model Reschedule-->
        <div class="modal fade" id="reschedule_modal" tabindex="2" aria-labelledby="reschedule_modalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reschedule_modalLabel">Reschedule Seats</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="departureCity" class="mb-0">Departure City <span
                                    class="text-danger">*</span></label>
                                <select class="form-control" id="reScheduleDepartureCity"
                                        @change="fetchReSpecificSchedules(); getReDestinationCity()"
                                        v-model="rescheduleData.dataDepartureCity">
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
                            <div class="col-md-2">
                                <label for="destinationCity" class="mb-0">Destination
                                    City<span class="text-danger">*</span></label>
                                <select class="form-control" id="reScheduleDestinationCity"
                                        @change="fetchReSpecificSchedules()"
                                        v-model="rescheduleData.rescheduleDestinationCity">
                                    <option value="0" selected>Select Destination City</option>
                                    <option v-for="(city, i) in reSpecificCities" :value="city.id"
                                            :key="i">
                                        {{ city.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2 class">
                                <label for="date" class="mb-0">Date <span class="text-danger">*</span></label>
                                <input type="date" :min="minDateFilter()" class="form-control"
                                       v-model="rescheduleData.rescheduleDate"
                                       @change="fetchReSpecificSchedules()"/>
                            </div>
                            <div class="col-md-3 class">
                                <label for="scheduleName" class="mb-0">Schedule Name <span
                                    class="text-danger">*</span></label>
                                <select class="form-control" id="reScheduleName" @change="fetchReScheduleData()"
                                        v-model="rescheduleData.rescheduleSchedule">
                                    <option value="0" selected>Select Schedule</option>
                                    <option v-for="(schedule, i) in allReSchedules"
                                            :value="schedule.schedule_id" :key="i">{{ scheduleDropdown(schedule) }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="rescheduleReason" class="mb-0">Reason</label>
                                <input id="rescheduleReason" class="form-control" v-model="rescheduleData.reason"
                                       placeholder="Please Give me a Reason!!">
                            </div>
                        </div>

                        <!--Reschedule Seat Map-->
                        <div class=" row mt-3" v-if="seatMapReschedule">
                            <div class="col-md-3">
                                <h4 class="mb-2">Old Booking</h4><br>
                                <div class="mb-2"><span class="h6">Seat No # {{ rescheduleData.dataSeat_no }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Seat Class : {{ rescheduleData.dataAll.seat_class.name }} </span></div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Seat Fare :  {{ rescheduleData.dataAll.seat_fare }} </span></div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Departure City : {{ rescheduleData.dataAll.departure_city.name }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6">Destination City : {{
                                        rescheduleData.dataAll.destination_city.name
                                    }} </span></div>
                                <br>
                                <div class="mb-2"><span class="h6">Date : {{ rescheduleData.dataAll.date }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6">Schedule : {{
                                        rescheduleData.dataAll.schedule.name
                                    }} </span></div>
                                <br>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-center seat-img p-0 m-0"
                                     v-for="(record, rowIndex) in reScheduleSeatMap.bus_class.seat_map" :key="rowIndex">
                                    <div v-for="(col, colIndex) in record" :key="colIndex">
                                        <div
                                            v-if="col.reserved"
                                            class="image-span d-block text-center text-white shadow"
                                            @click="reScheduleSelectSeat(rowIndex, colIndex, col)"
                                            :class="getClassesReschedule(col)"
                                            :title="getTitle(col)"
                                            :style="{border:'2px solid ' + col.color + ' !important', }"
                                        >
                                            <small>{{ col.seatNo }} </small>
                                            <br/>
                                            <small
                                                v-if="col.type && (col.type == 'booked' || col.type == 'advance booking')">
                                                <i class="type-icons fas"
                                                   :class="col.type == 'booked' && col.over_issue != true ? 'fa-check-double' : 'fa-check'">
                                                </i>
                                            </small>
                                            <small v-if="col.over_issue == true">
                                                <i class="type-icons far fa-hand-paper text-danger">
                                                </i>
                                            </small>
                                        </div>
                                        <span v-else></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h4 class="mb-3">Current Booking</h4>
                                <div class="mb-2"><span class="h6">Seat No # {{ this.alreadyBookedSeat[0] }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6">Seat Class : {{
                                        this.alreadyBookedSeatClassName[0]
                                    }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6">Seat Fare :  {{
                                        this.alreadyBookedSeatFare[0]
                                    }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Departure City : {{ this.reScheduleDepart }} </span></div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Destination City : {{ this.reScheduleDest }} </span></div>
                                <br>
                                <div class="mb-2"><span
                                    class="h6">Date : {{ this.reScheduleDate }} </span></div>
                                <br>
                                <div class="mb-2"><span class="h6">Schedule : {{ this.reScheduleSchedule }} </span>
                                </div>
                                <br>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" @click="rescheduleSeats()">Reschedule Seats</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Model Cancel -->
        <div class="modal fade" id="cancelModel" tabindex="3" aria-labelledby="cancelModelLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModelLabel">Cancel Ticket</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cancel_percentage">Percentage <span
                                class="text-muted ml-2">(Optional)</span></label>
                            <select id="cancel_percentage" class="form-control" v-model="cancelData.percentage">
                                <option value="first">Select Cancellation Percentage</option>
                                <option value="0">0%</option>
                                <option value="10">10%</option>
                                <option value="20">20%</option>
                                <option value="30">30%</option>
                                <option value="40">40%</option>
                                <option value="50">50%</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="caceling_remakrs">Remarks</label>
                            <textarea type="text" class="form-control" id="caceling_remakrs" v-model="cancelData.reason"
                                      placeholder="Reason for canceling a seat"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"
                                @click="cancelBooking(cancelData)">
                            Cancel Ticket
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- <ReschedulePopup :formID="rescheduleFormId" :seats="bookedSeats" :formData="sameDataMain"/>-->

        <!--Modal for details-->
        <div class="modal fade" id="seatAllDetailsModal" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="seatAllDetailsModalLabel">Seat Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <!--loop for number of seats-->
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card" v-for="(singleItems,  i) in selectedSeatDataBackEnd">
                                        <div class="card-body p-3" v-for="(innerItem,key , j) in singleItems">
                                            <div class="row ml-2 border-bottom" v-if="key == 0">
                                                <div class="col-md-6 d-flex justify-content-start">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Seat :</h4>
                                                    <h4 class="mb-0 text-muted">{{ innerItem.seat_no }}</h4>
                                                </div>
                                                <div class="col-md-6 d-flex justify-content-end"
                                                     v-if="innerItem.type == 'advance booking'">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Type:</h4>
                                                    <h4 class="mb-0 text-muted text-capitalize">{{
                                                            innerItem.type
                                                        }}</h4>
                                                </div>

                                            </div>
                                            <div class="row my-2">
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Date :</p>
                                                        <p class="mb-0">{{ innerItem.date }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3"> Bus Class :</p>
                                                        <p class="mb-0">{{ innerItem.seat_class.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Schedule : </p>
                                                        <p class="mb-0">{{ innerItem.schedule.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3"> Departure City :</p>
                                                        <p class="mb-0">{{ innerItem.departure_city.name }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Name : </p>
                                                        <p class="mb-0">{{ innerItem.customer.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Cnic :</p>
                                                        <p class="mb-0">{{ cnicFormat(innerItem.customer.cnic) }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Phone : </p>
                                                        <p class="mb-0">
                                                            {{ phoneFormat(innerItem.customer.contact) }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Destination City : </p>
                                                        <p class="mb-0">{{ innerItem.destination_city.name }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--Buttons-->
                                            <div class="row mt-3">
                                                <div class="col-md-12 text-right">
                                                    <!--                                                                                                        v-if="selectedBookedOverIssueSeats.length"-->
                                                    <!--                                                    v-if="selectedBookedSeats.length"-->
                                                    <button type="button" class="btn btn-secondary text-dark"
                                                            @click="duplicateTicket(innerItem)">Duplicate Ticket
                                                    </button>
                                                    <button type="button" class="btn btn-success ml-2">Resend SMS
                                                    </button>
                                                    <button type="button" class="btn btn-info ml-2"
                                                            @click="passDataToEltModel(innerItem)">
                                                        Add ELT
                                                    </button>
                                                    <button type="button" class="btn btn-primary ml-2"
                                                            @click="passDataToRescheduleModel(innerItem); this.rescheduleData.rescheduleSchedule = 0 ; this.seatMapReschedule = false"
                                                    >Reschedule
                                                    </button>
                                                    <button type="button" class="btn btn-warning ml-2"
                                                            @click="passDataToOverIssueModel(innerItem);this.overIssueData.percentage = 0">
                                                        Over Issue
                                                    </button>
                                                    <button type="button" class="btn btn-danger ml-2"
                                                            @click="passDataToCancelModel(innerItem); this.cancelData.percentage = 0 ">
                                                        Cancel Ticket
                                                    </button>
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

        <!--Modal for seat details end-->
        <DetailsModal :formID="detailsFormId" :details="bookingDetails" :deleteFormID="deleteFormID"/>

        <!--Print Passesnger List Form-->
        <form :action="$store.state.app_url + 'print/pdf/passenger/list'" method="POST" ref="refPassengerList"  target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
        </form>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
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
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsPhone: {
                placeholder: "xxxx-xxxxxxx",
            },
            getCustomermessage: '',
            shiftingFormId: "shifting-modal",
            partialSeatFormId: "partialSeat-modal",
            detailsFormId: "details-modal",
            customers: [],
            sameDataMain: [],
            cancelData: {
                percentage: 'first',
            },
            isActive: 1,
            formID: "addBooking",
            deleteFormID: "delete_addBooking",
            validationErrors: [],
            success: false,
            error: false,
            seatMapReschedule: false,
            closeAdvanceCashModel: false,
            reScheduleSeatMap: true,
            delId: "",
            allSchedules: [],
            allReSchedules: [],
            cancel: [],
            overIssueData: [],
            alreadyBookedSeat: [],
            alreadyBookedSeatFare: [],
            alreadyBookedSeatClassName: [],
            alreadyBookedSeatClass: [],
            eltData: [],
            schedule: "",
            reScheduleSchedule: '',
            reScheduleDepart: '',
            reScheduleDest: '',
            reScheduleDate: '',
            loading: false,
            getSchedule: false,
            showBookingDiv: false,
            showReBookingDiv: false,
            selectedSeats: [],
            selectedSeatsFare: [],
            selectedSeatsClass: [],
            selectedBookedSeats: [],
            selectedOverIssueSeats: [],
            selectedBookedOverIssueSeats: [],
            bookedSeats: [],
            bookedOverIssueSeats: [],
            allBookings: [],
            bookingDetails: [],
            allSeatClasses: [],
            specificCities: [],
            reSpecificCities: [],
            selectedSeatDataBackEnd: [],
            filterDate: new Date().toISOString().substr(0, 10),
            cities: [],
            advanceSeat: [],
            addForm: {
                date: new Date().toISOString().substr(0, 10),
                type: "booked",
                gender: "1",
                customerCNIC: "",
                schedule: 0,
                totalFare: 0,
                destinationCity: 0,
                departureCity: 0,
                totalAmount: 0,
                discount: 0,
                alreadyBookedId: [],
            },
            advanceCash: {
                sale: 0,
                amount: -500,
                advanceDeposit: 0,
                withdrawBank: '',
                description: '',
            },
            rescheduleData: {
                schedule: 0,
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
        openAdvanceModel() {
            this.closeAdvanceCashModel = true;
        },
        closeAdvanceModel() {
            this.closeAdvanceCashModel = false;
        },

        changeGender: function (e) {
            if (e.target.checked) {
                this.addForm.gender = 0;
            } else {
                this.addForm.gender = 1;
            }
        },

        changeType: function (e) {
            if (e.target.checked) {
                this.addForm.type = 'advance booking';
            } else {
                this.addForm.type = 'booked';
            }
        },

        enter: function (e) {
            if (e.key == "Enter") {
                this.add();
            }
        },

        async altM(e) {
            if ((e.metaKey || e.altKey) && (String.fromCharCode(e.which).toLowerCase() === 'm')) {
                if (this.addForm.departureCity == 0) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Select Departure City First",
                        icon: "error",
                        timer: 2000,
                    });
                }
                if (this.addForm.destinationCity == 0) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Select Destination City First",
                        icon: "error",
                        timer: 2000,
                    });
                }
                if (this.addForm.schedule == 0) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Select Schedule City First",
                        icon: "error",
                        timer: 2000,
                    });
                }
                if (this.selectedBookedSeats.length != 0 || this.selectedBookedOverIssueSeats.length != 0) {

                    const dataSeats = {
                        seatNO: (this.selectedBookedSeats.length != 0 && this.selectedBookedOverIssueSeats.length == 0) ? this.selectedBookedSeats : this.selectedBookedOverIssueSeats,
                        scheduleId: this.addForm.schedule,
                        date: this.addForm.date,
                    }
                    const resSeatData = await this.callApi("post", "booking/advance", dataSeats);
                    if (resSeatData.status == 200) {
                        this.selectedSeatDataBackEnd = resSeatData.data;
                        $('#seatAllDetailsModal').modal('show');
                    }
                    if (resSeatData.status == 422) {
                        let errorContent = "";
                        let count = 0;
                        for (const key in resSeatData.data.errors) {
                            resSeatData.data.errors[key].forEach((element) => {
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
                } else {
                    return swal({
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

        async getReDestinationCity() {
            if (this.rescheduleData.dataDepartureCity == '0') {
                this.rescheduleData.rescheduleDestinationCity = 0;
            } else {
                const resReDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.rescheduleData.dataDepartureCity});
                if (resReDepartureCity.length == 0) {
                    this.rescheduleData.rescheduleDestinationCity = 0
                } else {
                    this.rescheduleData.rescheduleDestinationCity = 0;
                    this.reSpecificCities = resReDepartureCity.data;
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

        async fetchReSpecificSchedules() {
            this.allReSchedules = {};
            this.rescheduleData.rescheduleSchedule = 0;
            this.seatMapReschedule = false;
            const data = {
                departure_city_id: this.rescheduleData.dataDepartureCity,
                destination_city_id: this.rescheduleData.rescheduleDestinationCity,
                date: this.rescheduleData.rescheduleDate,
            }
            const resFetchSchedule = await this.callApi("post", "booking/fetchSchedule", data);
            if (resFetchSchedule.status == 200) {
                if (resFetchSchedule.length != 0) {
                    this.seatMapReschedule = false;
                    this.allReSchedules = resFetchSchedule.data;
                } else {
                    this.rescheduleData.rescheduleSchedule = 0;
                }
            }
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
                    if ((this.addForm.contact == '' || typeof this.addForm.contact == 'undefined') && (this.addForm.customerName == '' || typeof this.addForm.customerName == 'undefined')) {
                        this.addForm.contact = resCnic.data.contact;
                        this.addForm.customerName = resCnic.data.name;
                    }
                }
            }
            if (flag == 'overIssueCNIC') {
                if (this.addFormOverIssue.customer.cnic == '' && this.addFormOverIssue.customer.cnic == 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        cnicNumber: this.addFormOverIssue.customer.cnic,
                        status: flag,

                    });
                    if ((this.addFormOverIssue.customer.name == '' || typeof this.addFormOverIssue.customer.name == 'undefined') && (this.addFormOverIssue.customer.contact == '' || typeof this.addFormOverIssue.customer.contact == 'undefined')) {
                        this.addFormOverIssue.customer.name = resCnic.data.name;
                        this.addFormOverIssue.customer.contact = resCnic.data.contact;
                    }
                }
            }
            if (flag == 'addFormContact' && this.addForm.customerCNIC == '' && this.addForm.customerName == '') {
                if (this.addForm.contact != '' && this.addForm.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        phoneNumber: this.addForm.contact,
                        status: flag,
                    });
                    if ((this.addForm.customerName == '' || typeof this.addForm.customerName == 'undefined') && (this.addForm.customerCNIC == '' || typeof this.addForm.customerCNIC == 'undefined')) {
                        this.addForm.customerCNIC = resCnic.data.cnic;
                        this.addForm.customerName = resCnic.data.name;
                    }
                }
            }
            if (flag == 'overIssueContact' && this.addFormOverIssue.customer.name == '' && this.addFormOverIssue.customer.cnic == '') {
                if (this.addFormOverIssue.customer.contact != '' && this.addFormOverIssue.customer.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        phoneNumber: this.addFormOverIssue.customer.contact,
                        status: flag,
                    });
                    if ((this.addFormOverIssue.customer.cnic == '' || typeof this.addFormOverIssue.customer.cnic == 'undefined') && (this.addFormOverIssue.customer.name == '' || typeof this.addFormOverIssue.customer.name == 'undefined')) {
                        this.addFormOverIssue.customer.name = resCnic.data.name;
                        this.addFormOverIssue.customer.cnic = resCnic.data.cnic;
                    }
                }
            }
        },

        calculateTotal: function () {
            if (this.addForm.discount > this.addForm.totalFare) {
                this.addForm.discount = 0;
                this.addForm.totalAmount = parseFloat(this.addForm.totalFare);
                return swal({
                    title: "Ops",
                    text: "Discount Can't be more then Amount Recieveable",
                    icon: "error",
                    timer: 2000
                });
            } else {
                this.addForm.totalAmount = parseFloat(this.addForm.totalFare) - (this.addForm.discount ? parseFloat(this.addForm.discount) : this.addForm.totalFare)
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
            this.resetingArrays();
            this.addForm.customerName = '';
            this.addForm.customerCNIC = '';
            this.addForm.contact = '';
            this.addForm.remarks = '';
            this.addForm.type = 'booked';
            this.addForm.gender = 1;
            this.addForm.totalFare = 0;
            this.addForm.totalAmount = 0;
            this.addForm.discount = '';
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
                if (res.status == 422) {
                    this.showBookingDiv = false;
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
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
            }
        },

        async fetchReScheduleData() {
            this.reScheduleSchedule = '';
            this.reScheduleDepart = '';
            this.reScheduleDest = '';
            this.reScheduleDate = '';
            this.alreadyBookedSeatClassName = [];
            this.alreadyBookedSeatClass = [];
            this.alreadyBookedSeatFare = [];
            this.alreadyBookedSeat = [];
            if (this.rescheduleData.rescheduleSchedule == 0) {
                this.seatMapReschedule = false;
            }
            const res = await this.callApi("post", "schedule/selected", {
                id: this.rescheduleData.rescheduleSchedule,
                date: this.rescheduleData.rescheduleDate,
                departureCity: this.rescheduleData.dataDepartureCity,
                destinationCity: this.rescheduleData.rescheduleDestinationCity,
            });
            if (res.status == 200) {
                this.seatMapReschedule = true;
                this.reScheduleSeatMap = res.data;
            } else {
                if (res.status == 422) {
                    for (const key in res.addForm.errors) {
                        res.addForm.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async selectSeat(row, col, seatNo, fare, colClass) {
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
                    this.addForm.totalFare -= parseFloat(this.schedule.bus_class.seat_map[row][col].fare);
                } else {
                    this.schedule.bus_class.seat_map[row][col].selected = true;
                    this.selectedBookedSeats.push(seatNo);
                    this.bookedSeats.push(this.schedule.bus_class.seat_map[row][col]);
                    this.addForm.totalFare += parseFloat(this.schedule.bus_class.seat_map[row][col].fare);
                }
                this.addForm.totalAmount = this.addForm.totalFare;
                this.addForm.selectedBookedSeats = this.selectedBookedSeats;
            } else if (!this.schedule.bus_class.seat_map[row][col].type && this.selectedBookedSeats.length == 0) {

                let index = this.selectedSeats.indexOf(seatNo);
                if (index != -1) {

                    this.schedule.bus_class.seat_map[row][col].selected = false;
                    this.selectedSeats.splice(index, 1);
                    this.selectedSeatsFare.splice(index, 1);
                    this.selectedSeatsClass.splice(index, 1);
                    this.addForm.totalFare -= this.schedule.bus_class.seat_map[row][col].fare;
                } else {

                    this.schedule.bus_class.seat_map[row][col].selected = true;
                    this.selectedSeats.push(seatNo);
                    this.selectedSeatsFare.push(fare);
                    this.selectedSeatsClass.push(colClass);
                    this.addForm.totalFare += this.schedule.bus_class.seat_map[row][col].fare;

                }
                this.addForm.totalAmount = this.addForm.totalFare;
                this.addForm.selectedSeats = this.selectedSeats;
                this.addForm.selectedSeatsFare = this.selectedSeatsFare;
                this.addForm.selectedSeatsClass = this.selectedSeatsClass;
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
                    this.selectedOverIssueSeats.splice(index, 1);
                } else {
                    this.schedule.bus_class.seat_map[row][col].selected = true;
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
        },

        // update Form After  advanced Booked seat
        async updateBookedSeat(data) {
            if (data.type == 'advance booking' && data.type != 0 && data.type != 'booked') {
                let index = this.advanceSeat.indexOf(data.seatNo);
                if (index != -1) {
                    this.addForm.selectedSeats.splice(index, 1);
                    this.advanceSeat.splice(index, 1);
                    this.addForm.alreadyBookedId.splice(index, 1);
                    this.addForm.flag = 0;
                } else {
                    this.addForm.alreadyBookedId.push(data.id);
                    this.addForm.customerCNIC = data.customer_cnic == 0 ?? '';
                    this.addForm.customerName = data.customer_name;
                    this.addForm.contact = data.customer_phone;
                    this.addForm.remarks = data.remarks;
                    this.addForm.selectedSeats.push(data.seatNo);
                    this.advanceSeat.push(data.seatNo)
                    this.addForm.flag = 1;
                }
            }
        },

        reScheduleSelectSeat: function (row, col, data) {
            if (this.alreadyBookedSeat.length > 0) {
                this.alreadyBookedSeat = [];
                this.fetchReScheduleData();
                return swal({
                    title: "Oops",
                    text: "You can select just one seat ",
                    icon: "error",
                    timer: 3000
                });
            }
            let index = this.alreadyBookedSeat.indexOf(data.seatNo);
            if (index != -1) {
                this.reScheduleSeatMap.bus_class.seat_map[row][col].alreadyBooked = false;
                this.alreadyBookedSeat.splice(index, 1);
                this.alreadyBookedSeatFare.splice(index, 1);
                this.alreadyBookedSeatClassName.splice(index, 1);
                this.alreadyBookedSeatClass.splice(index, 1);
            } else {
                this.reScheduleSeatMap.bus_class.seat_map[row][col].alreadyBooked = true;
                this.alreadyBookedSeat.push(data.seatNo);
                this.alreadyBookedSeatFare.push(data.fare);
                this.alreadyBookedSeatClassName.push(data.class_name);
                this.alreadyBookedSeatClass.push(data.class);
            }
            this.reScheduleDest = $("#reScheduleDestinationCity option:selected").text();
            this.reScheduleDepart = $("#reScheduleDepartureCity option:selected").text();
            this.reScheduleSchedule = $("#reScheduleName option:selected").text();
            this.reScheduleDate = this.rescheduleData.rescheduleDate;
        },

        handler: function (col, e) {
            if (col.type == 'not_for_sale') {
                e.preventDefault();
            }
        },

        getClasses: function (col) {
            let gender = col.gender != undefined && col.gender == 0 ? "for-female" : col.gender && col.gender == 1 ? "for-male" : "";
            let selected = col.selected ? "selected" : "";
            let partial = col.partial ? "partial" : "";
            let over = col.over_issue && col.partial ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        },

        getClassesReschedule: function (col) {
            let gender = col.gender != undefined && col.gender == 0 ? "for-female" : col.gender && col.gender == 1 ? "for-male" : "";
            let selected = col.alreadyBooked ? "selected" : "";
            let partial = col.partial ? "partial" : "";
            let over = col.over_issue && col.partial ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        },

        getTitle: function (col) {
            if (col.type == 'booked' || col.type == 'advance booking') {
                return "Name : " + col.customer_name + '\n' + "Phone : " + col.customer_phone + '\n' + "Remarks : " + col.remarks + '\n' + "Booked By : " + col.booked_by + '\n' + "Dept City : " + col.departure_city_name + '\n' + "Dest City : " + col.destination_city_name;
            }
        },

        getStyle: function (col) {
            let disabledSeat = col.type == 'not_for_sale' ? 'pointer-events: none;' : '';
            return 'border:2px solid ' + col.color + ' !important;' + disabledSeat;
        },

        async add() {
            if (!this.addForm.schedule) {
                return swal({
                    title: "Required!",
                    text: "Schedule Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.date) {
                return swal({
                    title: "Required!",
                    text: "Date is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if ((this.addForm.customerCNIC == '' || this.addForm.customerCNIC.length != 15) && this.addForm.type != 'advance booking') {
                return swal({
                    title: "Required!",
                    text: "CNIC is Required and Should Contain 13 Digits",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.customerName || typeof this.addForm.customerName == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Customer Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (!this.addForm.contact || typeof this.addForm.contact == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Customer Contact Number is required,",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.selectedSeats.length == 0 && this.selectedBookedSeats.length == 0) {
                return swal({
                    title: "required!",
                    text: "Please Select At Least One Seat",
                    icon: "error",
                    timer: 2000
                });
            }
            const res = await this.callApi("post", "booking/store", this.addForm);
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Booking Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.addForm = {
                    date: new Date().toISOString().substr(0, 10),
                    totalAmount: 0,
                    discount: '',
                    totalFare: 0,
                    customerName: '',
                    contact: '',
                    remarks: '',
                    gender: "1",
                    customerCNIC: "",
                    selectedSeats: '',
                };
                this.addForm.gender = 1;
                this.addForm.type = 'booked';
                this.addForm.schedule = res.data.ticket[0].schedule_id;
                this.addForm.destinationCity = res.data.ticket[0].destination_city_id;
                this.addForm.departureCity = res.data.ticket[0].departure_city_id;
                this.selectedSeats.length = 0;
                this.fetchScheduleData();
                this.resetingArrays();
                $("#booking_table").DataTable().destroy();
                setTimeout(() => {
                    $("#booking_table").DataTable();
                }, 300);
                // window.open(this.$store.state.app_url + 'print/' + res.data + '/pdf', '_blank').focus();

            } else {
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
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
            }
        },

        async deleteModal(surcharge, i) {
            const deletingObj = {
                url: "booking/delete",
                data: surcharge,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },

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
        },

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
        },

        passDataToCancelModel: function (data) {
            this.cancelData = {
                dataDate: data.date,
                dataSchedule: data.schedule_id,
                dataCustomer: data.customer_id,
                dataDeparture: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
            }
            $("#cancelModel").modal('show');
        },

        async cancelBooking(dataEnter) {
            const data = {
                date: dataEnter.dataDate,
                schedule_id: dataEnter.dataSchedule,
                customer_id: dataEnter.dataCustomer,
                departure_id: dataEnter.dataDeparture,
                destination_id: dataEnter.dataDestination,
                seat_no: dataEnter.dataSeat_no,
                percentage: dataEnter.percentage,
                remarks: dataEnter.reason,
            }
            const resCancelBooking = await this.callApi("post", "booking/canceling", data);
            if (resCancelBooking.status == 200) {
                swal({
                    title: "Success",
                    text: "Booking Canceled Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();
            }
        },

        //over issue model complete data
        passDataToOverIssueModel: function (data) {

            this.overIssueData = {
                dataDate: data.date,
                dataSchedule: data.schedule_id,
                dataCustomer: data.customer_id,
                dataDeparture: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
            }
            $("#overIssue_model").modal('show');
        },

        async addOverIssueTicket(dataEnter) {
            if (dataEnter.reason == '' || typeof dataEnter.reason == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Remarks is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            const data = {
                date: dataEnter.dataDate,
                schedule_id: dataEnter.dataSchedule,
                customer_id: dataEnter.dataCustomer,
                departure_id: dataEnter.dataDeparture,
                destination_id: dataEnter.dataDestination,
                seat_no: dataEnter.dataSeat_no,
                percentage: dataEnter.percentage,
                remarks: dataEnter.reason,
            }
            const resOverIssue = await this.callApi("post", "booking/overIssueAdd", data);
            if (resOverIssue.status == 200) {
                swal({
                    title: "Success",
                    text: "Seat over-issued  Successfully",
                    icon: "success",
                    timer: 2000
                });

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

        //ELT MODEL DATA
        passDataToEltModel: function (data) {
            this.eltData = {
                dataDate: data.date,
                dataCustomer: data.customer_id,
                dataSchedule: data.schedule_id,
                dataDeparture: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
                dataSeatFare: data.seat_fare,
            }
            $("#addELTModel").modal('show');
        },

        async addEltToTicket(dataEnter) {
            if (dataEnter.eltWeight == '' || typeof dataEnter.eltWeight == 'undefined') {
                return swal({
                    title: "required!",
                    text: "Weight is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (dataEnter.eltPrice == '' || typeof dataEnter.eltPrice == 'undefined') {
                return swal({
                    title: "required!",
                    text: "Price is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            const data = {
                date: dataEnter.dataDate,
                schedule_id: dataEnter.dataSchedule,
                customer_id: dataEnter.dataCustomer,
                departure_id: dataEnter.dataDeparture,
                destination_id: dataEnter.dataDestination,
                seat_no: dataEnter.dataSeat_no,
                eltWeight: dataEnter.eltWeight,
                totalPrice: dataEnter.eltPrice,
                singleFare: dataEnter.dataSeatFare,
                eltDescription: dataEnter.dataDescription,
            }
            const resOverIssue = await this.callApi("post", "booking/elt", data);
            if (resOverIssue.status == 200) {
                swal({
                    title: "Success",
                    text: "ELT Added Successfully",
                    icon: "success",
                    timer: 2000
                });
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

        // Reschedule model
        async passDataToRescheduleModel(data) {
            this.reSpecificCities = [];
            this.rescheduleData = {
                rescheduleDate: data.date,
                existingDate: data.date,
                dataCustomer: data.customer_id,
                dataSchedule: data.schedule_id,
                dataDepartureCity: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
                dataAll: data,
            }
            if (this.rescheduleData.dataDepartureCity == '0') {
                this.rescheduleData.rescheduleDestinationCity = 0;
            } else {
                const resReDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.rescheduleData.dataDepartureCity});
                if (resReDepartureCity.length == 0) {
                    this.rescheduleData.rescheduleDestinationCity = 0
                } else {
                    this.rescheduleData.rescheduleDestinationCity = 0;
                    this.reSpecificCities = resReDepartureCity.data;
                }
            }

            $("#reschedule_modal").modal('show');
        },

        async rescheduleSeats() {
            if (this.rescheduleData.dataDepartureCity == 0) {
                return swal({
                    title: "Required!!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.rescheduleData.rescheduleDestinationCity == 0) {
                return swal({
                    title: "Required!!",
                    text: "Please Select Destination City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.rescheduleData.rescheduleDate == '' || typeof this.rescheduleData.rescheduleDate == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Date is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.rescheduleData.rescheduleSchedule == 0) {
                return swal({
                    title: "Required!!",
                    text: "Please Select Schedule!!",
                    icon: "error",
                    timer: 2000
                });
            }
            const reScheduleAddFormData = {
                ...this.rescheduleData,
                'selected_seatNo': this.alreadyBookedSeat[0],
                'selected_seatClass': this.alreadyBookedSeatClass[0],
                'selected_seatFare': this.alreadyBookedSeatFare[0],
            }
            const res = await this.callApi("post", "booking/reschedule", reScheduleAddFormData);
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Seat Reschedule Successfully",
                    icon: "success",
                    timer: 4000
                });
                this.fetchScheduleData();
                this.fetchReScheduleData();
            } else {
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
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
            }
        },

        // Duplicate Ticket
        duplicateTicket: function (data) {
            // window.open(this.$store.state.app_url + 'print/' + data.id + '/pdf/duplicate', '_blank').focus();
        },

        // Get Passengers list
        async getCustomerList() {
            // console.log(this.$refs.refPassengerList);
                this.$refs.refPassengerList.submit();

            // const passengerData = {
            //     'departure_city_id': this.addForm.departureCity,
            //     'destination_city_id': this.addForm.destinationCity,
            //     'date': this.addForm.date,
            //     'schedule_id': this.addForm.schedule,
            // }
            // const resPassenger = await this.callApi("post", "booking/getPassenger", passengerData);
            // console.log(resPassenger);
            // if (resPassenger.status == 200 && resPassenger.data != '') {
            //     // window.open(this.$store.state.app_url + 'print/' + resPassenger.data + '/pdf/passenger/list', '_blank').focus();
            // } else {
            //     swal({
            //         title: "OOPS!!",
            //         text: "No Booking Found in this Bus!!",
            //         icon: "error",
            //         timer: 2000
            //     });
            // }
        }
    },
};
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
    background-color: #D40B0BFF !important;
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
    height: 47px;
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
