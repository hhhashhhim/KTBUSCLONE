<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary mb-0">
                        <div class="card-body pb-0 pt-2">
                            <div class="row">
                                <div class="col-md-12 row">  <!--v-if="showBookingDiv"-->
                                    <div class="col-md-6">
                                        <div class="p-3" style="background-color: #eceeef !important;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
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
                                                                {{ changeToUpperCase(city.name) }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="destinationCity" class="mb-0">Destination
                                                            City<span class="text-danger ml-1">*</span></label>
                                                        <select class="form-control" id="destinationCity"
                                                                @change="fetchSpecificSchedules()"
                                                                v-model="addForm.destinationCity">
                                                            <option value="0" selected>Select Destination City</option>
                                                            <option v-for="(city, i) in specificCities" :value="city.id"
                                                                    :key="i">
                                                                {{ changeToUpperCase(city.name) }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="date" class="mb-0">Date <span
                                                            class="text-danger ml-1">*</span></label>
                                                        <input type="date" :min="minDateFilter()" class="form-control"
                                                               id="dynamicDate"
                                                               v-model="addForm.date"
                                                               @change="fetchSpecificSchedules()"/>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="scheduleName" class="mb-0">Departure Time <span
                                                            class="text-danger">*</span></label>
                                                        <select class="form-control" id="scheduleName"
                                                                @change="fetchScheduleData(); busDropCheck()"
                                                                v-model="addForm.schedule">
                                                            <option value="0">Select Departure Time</option>
                                                            <option v-for="(schedule, i) in allSchedules"
                                                                    :value="schedule.schedule_id" :key="i">
                                                                {{ scheduleDropdown(schedule) }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="py-1"></div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
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
                                                <div class="form-group mb-0">
                                                    <label>Full Name <span class="text-danger ml-1">*</span></label>
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
                                                <div class="form-group mb-0">
                                                    <label>Contact <span class="text-danger ml-1">*</span></label>
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
                                                <div class="form-group mb-0">
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
                                        <div class="row mt-2">
                                            <div v-if="checkForSubmenuButtons('terminal-id')" class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="Terminals" class="mb-0"> Terminal ID</label>
                                                    <select class="form-control" id="Terminals"
                                                            v-model="addForm.terminalId">
                                                        <option value="0">Select Terminal</option>
                                                        <option
                                                            v-for="(terminal, i) in terminals"
                                                            :value="terminal.id"
                                                            :key="i"
                                                        >{{ changeToUpperCase(terminal.city.name) }} -
                                                            {{ changeToUpperCase(terminal.name) }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div :class="!checkForSubmenuButtons('terminal-id') ? 'col-md-12 mt-3 mb-3' : 'col-md-6 mt-3'">
                                                <div class="row">
                                                    <div
                                                        :class="!checkForSubmenuButtons('terminal-id') ? 'col-md-6' : 'col-md-6'"
                                                        class="align-self-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                   id="femaleCheckBox"
                                                                   v-bind:checked="addForm.gender == 0"
                                                                   @click="changeGender($event)" value="0"
                                                                   name="gender">
                                                            <label class="custom-control-label"
                                                                   for="femaleCheckBox">Female</label>
                                                        </div>
                                                    </div>
                                                    <div
                                                        :class="!checkForSubmenuButtons('terminal-id') ? 'col-md-6' : 'col-md-6'"
                                                        class="align-self-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                   id="bookingTypeCheckBox"
                                                                   v-bind:checked="addForm.type == 'advance booking'  || checkForSubmenuButtons('advance-booking')"
                                                                   @click="changeType($event)"
                                                                   value="advance booking"
                                                                   :disabled="checkForSubmenuButtons('advance-booking')"
                                                                   name="bookingType">
                                                            <label class="custom-control-label"
                                                                   for="bookingTypeCheckBox">Advanced</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 pl-0">
                                                <div class="form-group mb-0">
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
                                            <div class="col-md-2 pl-0">
                                                <div class="form-group mb-0">
                                                    <label>Seats</label>
                                                    <input
                                                        type="text"
                                                        readonly
                                                        class="form-control"
                                                        id="totalNoSeats"
                                                        v-model="selectedSeats.length"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-md-2 pl-0">
                                                <div class="form-group mb-0">
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
                                            <div class="col-md-2 pl-0">
                                                <div class="form-group mb-0">
                                                    <label>Discount <span
                                                        class="ml-2 text-muted"></span></label>
                                                    <input
                                                        type="text" @keypress="isNumber($event)"
                                                        @keyup="calculateTotal()"
                                                        readonly
                                                        class="form-control"
                                                        id="fareDiscount"
                                                        v-model="addForm.discount"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-md-2 pl-0">
                                                <div class="form-group mb-0">
                                                    <label>Receivable </label>
                                                    <input type="text"
                                                           class="form-control"
                                                           readonly
                                                           v-model="addForm.totalAmount"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="hideDivButtonsDrop" class="my-2">
                                            <div class="row">
                                                <div class="form-group mt-2 mb-2"
                                                >
                                                    <a v-if="checkForSubmenuButtons('assign-bus')" href="#"
                                                       :data-target="'#' + formID" data-toggle="modal"
                                                       class="btn btn-primary" @click="closingData()">
                                                        Assign Bus
                                                    </a>
                                                    <button v-if="checkForSubmenuButtons('terminal-invoice')"
                                                            class="btn btn-info mx-1" @click="getTerminalInvoice()">
                                                        Terminal Invoice
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('bus-invoice')"
                                                            class="btn btn-warning mx-1" @click="getBusInvoice()">
                                                        Bus Invoice
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('pax-list')"
                                                            class="btn btn-danger mx-1" @click="getCustomerList()">
                                                        Pax List
                                                    </button>
                                                    <button class="btn btn-success mx-1"
                                                            v-on:click="add()"
                                                            v-on:keyup.enter="add()">
                                                        {{
                                                            this.addForm.type == 'advance booking' ? 'Reserved Seat' :
                                                                'Generate Ticket'
                                                        }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-center mb-2">
                                                <button v-if="checkForSubmenuButtons('seat-details')"
                                                        class="btn btn-outline-secondary text-dark mr-2"
                                                        @click="seatDetails()">
                                                    Seat Details
                                                </button>
                                                <button v-if="checkForSubmenuButtons('drop-schedule')"
                                                        class="btn btn-secondary text-dark mr-2"
                                                        @click="scheduleDrop()" :disabled="dropScheduleButton">
                                                    Drop Schedule
                                                </button>
                                                <button class="btn btn-secondary text-dark"
                                                        @click="fetchScheduleData()" :disabled="getSchedule">
                                                    {{ getSchedule ? "Loading..." : 'Refresh' }}
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else class="text-center  my-2">
                                            <span class="h2 font-weight-bold">{{ labelDrop }}</span>
                                        </div>
                                    </div>
                                    <!--                                        Seat Map-->
                                    <div class="col-md-4 overflow-auto" id="seatMapDiv">
                                        <div v-if="showBookingDiv"
                                             class="d-flex justify-content-center seat-img p-0 m-0"
                                             v-for="(record, rowIndex) in schedule.bus_class.seat_map" :key="rowIndex">
                                            <div v-for="(col, colIndex) in record" :key="colIndex">
                                                <div
                                                    v-if="col.reserved"
                                                    class="image-span d-block text-center text-white shadow"
                                                    @click="selectSeat(rowIndex, colIndex, col.seatNo, col.fare, col.class); updateBookedSeat(col) "
                                                    :class="getClasses(col)"
                                                    :title="getTitle(col)"
                                                    :style="getStyle(col)"
                                                >
                                                    <small>{{ col.seatNo }}</small>
                                                    <br/>
                                                    <small v-if="col.type && col.type == 'booked'">
                                                        <i class="type-icons fas fa-check-double"></i>
                                                    </small>
                                                    <small v-if="col.type && col.type == 'advance booking'">
                                                        <i class="type-icons fas fa-check">
                                                        </i>
                                                    </small>
                                                    <small v-if="col.type && col.type == 'over-issue'">
                                                        <i class="type-icons far fa-hand-paper text-light">
                                                        </i>
                                                    </small>
                                                    <small v-if="col.type && col.type == 'not_for_sale'">
                                                        <i class="fas fa-minus-circle text-light"></i>
                                                    </small>
                                                </div>
                                                <span v-else></span>
                                            </div>
                                        </div>
                                        <div v-else style="position: absolute;left: 40%; top: 40%;" class="lds-roller">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>
                                    </div>
                                    <!-- side bar -->
                                    <div class="col-md-2 pl-3 " style="overflow-x: hidden; overflow-y: auto;">
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
                                                    <div class="my-1" style="padding-bottom: 10px !important;">
                                                        <div class="bg-danger text-dark circles mr-1 border shadow"><i
                                                            class="fas fa-minus-circle"></i></div>
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
                                                        <div class="circles icons-legend mr-1 border shadow">
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
                                                <br>
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
                                    <label>Price<span class="text-danger ml-1">*</span></label>
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
                                @click="addEltToTicket(eltData)" :disabled="this.EltButton">
                            {{ this.EltButton ? 'Loading...' : 'Add ELT' }}
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
                            <label for="over_issue_remarks">Remarks <span class="text-danger ml-1">*</span></label>
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
                                    City<span class="text-danger ml-1">*</span></label>
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
                                <label for="date" class="mb-0">Date <span class="text-danger ml-1">*</span></label>
                                <input type="date" :min="minDateFilter()" class="form-control"
                                       v-model="rescheduleData.rescheduleDate"
                                       @change="fetchReSpecificSchedules()"/>
                            </div>
                            <div class="col-md-3 class">
                                <label for="scheduleName" class="mb-0">Departure Time <span
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
                        <div class=" row mt-3 text-center" v-if="seatMapReschedule">
                            <div class="col-md-3">
                                <h4 class="mb-2">Old Booking</h4><br>
                                <div class="mb-2"><span class="h6">Old Fare : Rs {{
                                        mainAllRescheduleData.totalFare
                                    }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6"> Booked Seat Numbers </span><br>
                                    <span>{{ (mainAllRescheduleData.oldSeats) }}</span>
                                </div>
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
                                            :style="{border:'2px solid ' + col.color + ' !important',}"
                                        >
                                            <small>{{ col.seatNo }} </small>
                                            <br/>
                                            <small v-if="col.type && col.type == 'booked'">
                                                <i class="type-icons fas fa-check-double">
                                                </i>
                                            </small>
                                            <small v-if="col.type && col.type == 'advance booking'">
                                                <i class="type-icons fas fa-check">
                                                </i>
                                            </small>
                                            <small v-if="col.type && col.type == 'over-issue'">
                                                <i class="type-icons far fa-hand-paper text-light">
                                                </i>
                                            </small>
                                            <small v-if="col.type && col.type == 'not_for_sale'">
                                                <i class="fas fa-minus-circle text-light"></i>
                                            </small>
                                        </div>
                                        <span v-else></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h4 class="mb-3">Current Booking</h4>
                                <div class="mb-2"><span class="h6"> New Fare : Rs {{
                                        totalAlreadyBookedSeatFare ?? ""
                                    }} </span>
                                </div>
                                <br>
                                <div class="mb-2"><span class="h6"> Selected Seats Numbers </span><br>
                                    <span>{{ alreadyBookedSeat.join(', ') ?? "Not Selected Yet" }}</span>
                                </div>
                                <div class="mt-3">
                                    <div class="form-group">
                                        <span class="h6">Over Issue Reschedule : </span>
                                        <label class="colorinput">
                                            <input name="overIssueReschedule" type="checkbox" value="1"
                                                   class="colorinput-input"
                                                   @click="getApprovalOverIssueSeat($event)">
                                            <span class="colorinput-color bg-primary"></span>
                                        </label>
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <span class="h6">Advance Booked :</span>
                                        <label class="colorinput">
                                            <input name="overIssueRescheduleAdvance" type="checkbox" value="1"
                                                   class="colorinput-input"
                                                   @click="changeTypeReschedule($event)">
                                            <span class="colorinput-color bg-primary"></span>
                                        </label>
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <label for="name">Discount </label>
                                        <input
                                            type="text"
                                            @keypress="isNumber($event)"
                                            class="form-control"
                                            v-model="rescheduleDiscount"
                                        />
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" @click="rescheduleSeats()" :disabled="loadingRescheduleButton">
                            {{ loadingRescheduleButton ? 'Loading....' : 'Reschedule Seats' }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Model Cancel -->
        <div class="modal fade" id="dropSchedule" tabindex="3" aria-labelledby="dropScheduleLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dropScheduleLabel">Drop Schedule</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="dropSheduleRemarks">Remarks</label>
                            <textarea type="text" class="form-control" id="dropSheduleRemarks"
                                      v-model="dropScheduleFormData.reason"
                                      placeholder="Reason for drop schedule"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"
                                @click="dropScheduleData()" :disabled="dropScheduleButton">
                            Drop Schedule
                        </button>
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
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" class=" shadow-style btn btn-primary ml-2"
                                            v-if="this.allRescheduleButton && checkForSubmenuButtons('reschedule-seats')"
                                            @click="allRescheduleData(); this.rescheduleData.rescheduleSchedule = 0 ; this.seatMapReschedule = false"
                                    >Reschedule All
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card" v-for="(singleItems,  i) in selectedSeatDataBackEnd">
                                        <div class="card-body p-3" v-for="(innerItem,key , j) in singleItems">
                                            <div class="row ml-2 border-bottom" v-if="key == 0">
                                                <div class="col-md-6 d-flex justify-content-start">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Seat :</h4>
                                                    <h4 class="mb-0 text-muted">{{ innerItem.seat_no }}</h4>
                                                </div>
                                                <div class="col-md-6 d-flex justify-content-end">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Type:</h4>
                                                    <h4 class="mb-0 text-muted text-capitalize"><span
                                                        v-if="innerItem.is_partial == 1">Partial - </span>{{
                                                            innerItem.type
                                                        }}</h4>
                                                </div>

                                            </div>
                                            <div class="row my-3 pl-3">
                                                <div class="col-md-4">
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
                                                </div>
                                                <div class="col-md-4">
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
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Booking Date & Time : </p>
                                                        <p class="mb-0">{{ innerItem.bookingDate }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3"> Departure City :</p>
                                                        <p class="mb-0">{{ innerItem.departure_city.name }}</p>
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
                                                    <button type="button" class="btn btn-secondary text-dark"
                                                            v-if="innerItem.type == 'booked'  && checkForSubmenuButtons('duplicate-ticket')"
                                                            @click="duplicateTicket(innerItem)">Duplicate Ticket
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('resend-sms')" type="button"
                                                            class="btn btn-success ml-2">Resend SMS
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('add-elt')" type="button"
                                                            class="btn btn-info ml-2"
                                                            @click="passDataToEltModel(innerItem)">
                                                        Add ELT
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('reschedule-seats')"
                                                            type="button" class="btn btn-primary ml-2"
                                                            @click="passDataToRescheduleModel(innerItem); this.rescheduleData.rescheduleSchedule = 0 ; this.seatMapReschedule = false"
                                                    >Reschedule
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('overissue-seat')"
                                                            type="button" class="btn btn-warning ml-2"
                                                            @click="passDataToOverIssueModel(innerItem);this.overIssueData.percentage = 0">
                                                        Over Issue
                                                    </button>
                                                    <button v-if="checkForSubmenuButtons('cancel-ticket')" type="button"
                                                            class="btn btn-danger ml-2"
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

        <!-- Close Schedule -->
        <Add
            heading="Close Schedule"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class=" form-group col-md-6">
                    <label for="city_id">Bus <span class="text-danger ml-1">*</span></label>
                    <select class="form-control" v-model="dataForClose.bus" :disabled="checkCloseData">
                        <option value="">Select Bus Class</option>
                        <option
                            v-for="(bus, i) in buses"
                            :key="i"
                            :value="bus.id"
                        >
                            {{ bus.bus_number }}
                        </option>
                    </select>
                </div>
                <div class=" form-group col-md-6">
                    <label for="city_id">Route</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="N/A"
                        readonly
                        v-model="dataForClose.route_name"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label for="name">Date <span class="text-danger ml-1">*</span></label>
                    <input
                        type="date"
                        class="form-control"
                        placeholder="Enter Bus Name"
                        readonly
                        v-model="dataForClose.date"
                    />
                </div>
                <div class=" form-group col-md-6">
                    <label for="city_id">Schedule <span class="text-danger ml-1">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="N/A"
                        readonly
                        v-model="dataForClose.schedule_detail"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label for="name">Bus Driver <span class="text-danger ml-1">*</span></label>
                    <select class="form-control rounded-0" v-model="dataForClose.drivers" multiple
                            :disabled="checkCloseData">
                        <option
                            v-for="(driver, i) in drivers"
                            :key="i"
                            :value="driver.id"
                        >
                            {{ driver.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="name">Bus Host <span class="text-danger ml-1">*</span></label>
                    <select class="form-control rounded-0" v-model="dataForClose.hosts" multiple
                            :disabled="checkCloseData">
                        <option
                            v-for="(host, i) in hosts"
                            :key="i"
                            :value="host.user_id"
                        >
                            {{ host.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="location">Description</label>
                    <textarea
                        class="form-control"
                        placeholder="Enter Description"
                        id="location"
                        :disabled="checkCloseData"
                        v-model="dataForClose.description"
                        cols="30"
                        rows="10"
                    ></textarea>
                </div>
            </div>
            <template v-slot:button>
                <button
                    type="button"
                    class="btn btn-primary"
                    v-if="!checkCloseData"
                    @click="closeSchedule" :disabled="loading"
                >
                    {{ loading ? 'Loading...' : 'Close Booking' }}
                </button>
            </template>
        </Add>

        <!--Modal for seat details end-->
        <DetailsModal :formID="detailsFormId" :details="bookingDetails" :deleteFormID="deleteFormID"/>

        <!--Print Passesnger List Form-->
        <form :action="$store.state.app_url + 'print/pdf/passenger/list'" method="POST" ref="refPassengerList"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
        </form>
        <!--Print Terminal Invoice-->
        <form :action="$store.state.app_url + 'print/pdf/terminal/invoice'" method="POST" ref="refTerminalInvoice"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
        </form>
        <!--Print Bus Invoice -->
        <form :action="$store.state.app_url + 'print/pdf/bus/invoice'" method="POST" ref="refBusInvoice"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
        </form>
        <!--        print Customer Ticket Print-->
        <form :action="$store.state.app_url + 'print/pdf/customer/ticket'" method="POST" ref="refTicket"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="ticket_ids" :value="this.ticketsIds">
            <input type="hidden" name="duplicate" value=0>
        </form>
        <!--        print Customer Duplicate Ticket Print-->
        <form :action="$store.state.app_url + 'print/pdf/customer/ticket'" method="POST" ref="refDuplicateTicket"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="ticket_id" :value="this.ticketsId">
            <input type="hidden" name="duplicate" value=1>
        </form>
        <!--        Elt Customer PDF Form  -->
        <form :action="$store.state.app_url + 'print/pdf/customer/elt'" method="POST" ref="refElt"
              target="_blank">
            <input type="hidden" name="_token" v-bind:value="csrf">
            <input type="hidden" name="elt_ids" :value="this.eltIds">
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
                placeholder: "03xx-xxxxxxx",
            },
            buses: [],
            permissions: [],
            drivers: [],
            hosts: [],
            assignBus: 0,
            getCustomermessage: '',
            shiftingFormId: "shifting-modal",
            partialSeatFormId: "partialSeat-modal",
            detailsFormId: "details-modal",
            customers: [],
            sameDataMain: [],
            cancelData: {
                percentage: 'first',
            },
            checkCloseData: true,
            dataForClose: {
                bus: '',
                date: '',
                schedule: '',
                route_name: '',
                schedule_detail: '',
                drivers: [],
                hosts: [],
                description: '',
            },
            dropScheduleFormData: {
                reason: '',
                departure_city_id: '',
                destination_city_id: '',
                date: '',
                schedule_id: '',
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
            totalAlreadyBookedSeatFare: 0,
            alreadyBookedSeatClassName: [],
            alreadyBookedSeatClass: [],
            eltData: [],
            terminals: [],
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
            previousSumFare: 0,
            selectedSeatDataBackEnd: [],
            mainAllRescheduleData: [],
            filterDate: new Date().toISOString().substr(0, 10),
            cities: [],
            advanceSeat: [],
            overIssueScheduleCheckBox: 'general',
            rescheduleSeatType: "booked",
            rescheduleDiscount: "",
            eltIds: "",
            EltButton: false,
            loadingRescheduleButton: false,
            dropScheduleButton: false,
            showRescheduleDiscountDiv: false,
            allRescheduleButton: false,
            labelDrop: '',
            hideDivButtonsDrop: true,
            ticketsIds: "",
            ticketsId: "",
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
                terminalId: 0,
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
        this.fetchAllSchedules();
        this.showBookingDiv = false;
        this.permissions = this.$store.state.permissions;
        if (window.location.pathname.split("/").pop() == "booking") {
            window.addEventListener('keydown', this.enter);
            window.addEventListener('keydown', this.altM);
        }
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

        getApprovalOverIssueSeat: function (e) {
            if (e.target.checked) {
                this.overIssueScheduleCheckBox = 'overIssue_reschedule';
            } else {
                this.overIssueScheduleCheckBox = 'general';
            }
        },

        changeTypeReschedule: function (e) {
            if (e.target.checked) {
                this.rescheduleSeatType = 'advance booking';
            } else {
                this.rescheduleSeatType = 'booked';
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
                if (!this.hideDivButtonsDrop) {
                    return swal({
                        title: "OOPS!!",
                        text: "Selected Schedule is dropped \n You can't Booked any Seat Against it",
                        icon: "error",
                        timer: 2000,
                    });
                }
                this.add();
            }
        },

        async altM(e) {
            if ((e.metaKey || e.altKey) && (String.fromCharCode(e.which).toLowerCase() == 'm')) {
                // if(checkForSubmenuButtons('seat-details-shortcut')) {
                this.seatDetails();
                // }else{
                //     swal({
                //         title: "OOPS!!",
                //         text: "Access Denied",
                //         icon: "error",
                //         timer: 2000,
                //     });
                // }
            }
        },
        async seatDetails() {
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
                    text: "Please Select Departure Time First ",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.selectedBookedSeats.length != 0 || this.selectedBookedOverIssueSeats.length != 0) {

                const dataSeats = {
                    seatNO: (this.selectedBookedSeats.length != 0 && this.selectedBookedOverIssueSeats.length == 0) ? this.selectedBookedSeats : this.selectedBookedOverIssueSeats,
                    scheduleId: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,

                }
                const resSeatData = await this.callApi("post", "booking/advance", dataSeats);
                if (resSeatData.status == 200) {
                    this.selectedSeatDataBackEnd = resSeatData.data.tickets;
                    this.allRescheduleButton = resSeatData.data.showButton;
                    $('#seatAllDetailsModal').modal('show');
                }
                if (resSeatData.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in resSeatData.data.errors) {
                        resSeatData.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
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
            const dtToday = new Date();
            let month = dtToday.getMonth() + 1;
            let day = dtToday.getDate() - 2;
            const year = dtToday.getFullYear();
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

        async closingData() {
            const resData = await this.callApi("post", "booking/getClosingData", {
                scheduleId: this.addForm.schedule,
                date: this.addForm.date,
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
            });
            this.buses = resData.data.buses;
            this.drivers = resData.data.drivers;
            this.hosts = resData.data.hosts;
            this.dataForClose.date = resData.data.infoData.schedule_date;
            this.dataForClose.schedule_detail = resData.data.infoData.schedule;
            this.dataForClose.schedule = resData.data.infoData.schedule_id;
            this.dataForClose.route_name = resData.data.infoData.route_name;
            this.dataForClose.bus = resData.data.infoData.bus;
            this.dataForClose.drivers = resData.data.infoData.drivers;
            this.dataForClose.hosts = resData.data.infoData.hosts;
            this.dataForClose.description = resData.data.infoData.description;
            this.checkCloseData = resData.data.infoData.bus == "" ? false : true;

        },

        async closeSchedule() {
            this.validationErrors = [];
            if (!this.dataForClose.bus)
                return swal({
                    title: "Required",
                    text: "Bus is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.dataForClose.date)
                return swal({
                    title: "Required",
                    text: "Date is required",
                    icon: 'error',
                    timer: 2000
                });
            if (!this.dataForClose.schedule)
                return swal({
                    title: "Required",
                    text: "Schedule is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.dataForClose.drivers.length == 0)
                return swal({
                    title: "Required",
                    text: "Driver is required",
                    icon: 'error',
                    timer: 2000
                });
            if (this.dataForClose.hosts.length == 0)
                return swal({
                    title: "Required",
                    text: "Host is required",
                    icon: 'error',
                    timer: 2000
                });
            this.loadig = true;
            const res = await this.callApi("post", "booking/schedule/closing/store", this.dataForClose);
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Schedule Closed Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.dataForClose.bus = "";
                this.dataForClose.date = "";
                this.dataForClose.schedule = "";
                this.dataForClose.drivers = [];
                this.dataForClose.hosts = [];
                this.dataForClose.description = "";
                this.closingData();
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        },

        async getReDestinationCity() {
            this.reSpecificCities = [];
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
            const resClass = await this.callApi("post", "booking/fare_class")
            const resCity = await this.callApi("post", "booking/cities")
            const resTerminals = await this.callApi("post", "booking/terminals")
            if (resBooking.status == 200 && resClass.status == 200 && resCity.status == 200 && resTerminals.status == 200) {
                this.allBookings = resBooking.data;
                this.allSeatClasses = resClass.data;
                this.cities = resCity.data;
                this.terminals = resTerminals.data.terminals;
                this.addForm.terminalId = resTerminals.data.authTerminalId;
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
        },

        calculateTotal: function () {
            if (this.addForm.discount > this.addForm.totalFare) {
                this.addForm.discount = 0;
                this.addForm.totalAmount = parseFloat(this.addForm.totalFare);
                return swal({
                    title: "Ops",
                    text: "Discount Cannot be more than Amount Receivable",
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
        changeToUpperCase: function (string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        async fetchScheduleData() {
            this.resetingArrays();
            this.schedule = [];
            // this.addForm.customerName = '';
            // this.addForm.customerCNIC = '';
            // this.addForm.contact = '';
            // this.addForm.remarks = '';
            // this.addForm.type = 'booked';
            // this.addForm.gender = 1;
            this.addForm.totalFare = 0;
            this.addForm.totalAmount = 0;
            this.addForm.discount = '';
            this.validationErrors = [];
            this.loading = true;
            this.showBookingDiv = false;
            if (this.addForm.schedule != 0 && this.addForm.date && this.addForm.departureCity != 0 && this.addForm.destinationCity != 0) {
                const resSelected = await this.callApi("post", "booking/schedule/selected", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });
                if (resSelected.status == 200) {
                    this.loading = false
                    this.showBookingDiv = true;
                    this.schedule = resSelected.data;
                }

                if (resSelected.status == 500 && this.addForm.schedule == 0) {
                    this.loading = true
                    this.showBookingDiv = false;
                }
                if (resSelected.status == 422) {
                    this.showBookingDiv = false;
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resSelected.data.errors) {
                        resSelected.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        },
        scheduleDrop: function () {

            if (this.addForm.departureCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.destinationCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Destination City",
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
            if (this.addForm.schedule == 0) {
                return swal({
                    title: "Required!",
                    text: "Departure Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.dropScheduleFormData = {
                departure_city_id: this.addForm.departureCity,
                destination_city_id: this.addForm.destinationCity,
                date: this.addForm.date,
                schedule_id: this.addForm.schedule,
                reason: '',
            }
            $('#dropSchedule').modal('show');
        },

        async busDropCheck() {
            this.labelDrop = '';
            this.hideDivButtonsDrop = true;
            const resDropCheck = await this.callApi("post", "booking/schedule/dropCheck", {
                id: this.addForm.schedule,
                date: this.addForm.date,
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
            });
            if (resDropCheck.status == 200) {
                if (resDropCheck.data) {
                    this.labelDrop = 'This Schedule is Dropped';
                    this.hideDivButtonsDrop = false;
                } else {
                    this.hideDivButtonsDrop = true;
                    this.labelDrop = '';
                }
            }

        },

        async fetchReScheduleData() {
            this.reScheduleSeatMap = [];
            this.reScheduleSchedule = '';
            this.reScheduleDepart = '';
            this.reScheduleDest = '';
            this.reScheduleDate = '';
            this.alreadyBookedSeatClassName = [];
            this.alreadyBookedSeatClass = [];
            this.alreadyBookedSeatFare = [];
            this.totalAlreadyBookedSeatFare = 0;
            this.alreadyBookedSeat = [];
            this.seatMapReschedule = false;
            if (this.rescheduleData.rescheduleSchedule == 0) {
                this.seatMapReschedule = false;
            }
            const res = await this.callApi("post", "booking/schedule/selected", {
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
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
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
            this.addForm.alreadyBookedId = [];
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
            if (this.reScheduleSeatMap.bus_class.seat_map[row][col].type == 0) {
                let index = this.alreadyBookedSeat.indexOf(data.seatNo);
                if (index != -1) {
                    this.reScheduleSeatMap.bus_class.seat_map[row][col].alreadyBooked = false;
                    this.alreadyBookedSeat.splice(index, 1);
                    this.alreadyBookedSeatFare.splice(parseFloat(data.fare), 1);
                    this.totalAlreadyBookedSeatFare -= parseFloat(data.fare);
                    this.alreadyBookedSeatClassName.splice(index, 1);
                    this.alreadyBookedSeatClass.splice(index, 1);
                } else {
                    this.reScheduleSeatMap.bus_class.seat_map[row][col].alreadyBooked = true;
                    this.alreadyBookedSeat.push(data.seatNo);
                    this.alreadyBookedSeatFare.push(parseFloat(data.fare));
                    this.totalAlreadyBookedSeatFare += parseFloat(data.fare);
                    this.alreadyBookedSeatClassName.push(data.class_name);
                    this.alreadyBookedSeatClass.push(data.class);
                }
            } else {
                this.alreadyBookedSeat = [];
                this.fetchReScheduleData();
                return swal({
                    title: "Oops",
                    text: "Invalid Seat Combination",
                    icon: "error",
                    timer: 2000
                });
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
            let partial = col.partial == 1 ? "partial" : "";
            let over = col.type == 'over-issue' ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        }
        ,

        getClassesReschedule: function (col) {
            let gender = col.gender != undefined && col.gender == 0 ? "for-female" : col.gender && col.gender == 1 ? "for-male" : "";
            let selected = col.alreadyBooked ? "selected" : "";
            let partial = col.partial ? "partial" : "";
            let over = col.type == 'over-issue' ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        }
        ,

        getTitle: function (col) {
            if (col.type == 'booked' || col.type == 'advance booking' || col.type == 'over-issue') {
                return "Name : " + col.customer_name + '\n' + "Phone : " + col.customer_phone + '\n' + "Remarks : " + col.remarks + '\n' + "Booked By : " + col.booked_by + '\n' + "Dept City : " + col.departure_city_name + '\n' + "Dest City : " + col.destination_city_name;
            }
        }
        ,

        getStyle: function (col) {
            let disabledSeat = col.type == 'not_for_sale' ? 'pointer-events: none;' : '';
            return 'border:2px solid ' + col.color + ' !important;' + disabledSeat;
        }
        ,

        async add() {
            if (this.addForm.departureCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.destinationCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Destination City",
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
            if (this.addForm.schedule == 0) {
                return swal({
                    title: "Required!",
                    text: "Departure Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if ((this.addForm.customerCNIC == '' || typeof this.addForm.customerCNIC == 'undefined') && this.addForm.type != 'advance booking') {
                return swal({
                    title: "Required!",
                    text: "CNIC is Required ",
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
                iziToast.success({
                    title: 'Success!',
                    message: 'Booking Created Successfully',
                    position: 'topRight',
                    hideAfter: 1000
                });
                this.addForm = {
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
                this.ticketsIds = res.data.ids;
                this.addForm.date = res.data.ticket[0].date;
                this.addForm.terminalId = res.data.authTerminalId;
                this.addForm.gender = 1;
                this.addForm.type = 'booked';
                this.addForm.schedule = res.data.ticket[0].schedule_id;
                this.addForm.destinationCity = parseInt(res.data.ticket[0].destination_city_id);
                this.addForm.departureCity = parseInt(res.data.ticket[0].departure_city_id);
                this.selectedSeats.length = 0;
                this.fetchScheduleData();
                this.resetingArrays();
                $("#booking_table").DataTable().destroy();
                setTimeout(() => {
                    if (res.data.ticket[0].type == "booked") {
                        this.$refs.refTicket.submit();
                    }
                }, 700);

                setTimeout(() => {
                    $("#booking_table").DataTable();

                }, 300);

            } else {
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        }
        ,

        async resetingArrays() {
            this.selectedSeats = [];
            this.schedule = [];
            this.selectedBookedSeats = [];
            this.selectedOverIssueSeats = [];
            this.selectedBookedOverIssueSeats = [];
            this.addForm.selectedSeats = [];
            this.addForm.selectedBookedSeats = [];
            this.addForm.selectedOverIssueSeats = [];
            this.addForm.selectedBookedOverIssueSeats = [];
            this.bookedSeats = [];
            this.bookedOverIssueSeats = [];
            // let resBooking = await this.callApi("post", "booking");
            // if (resBooking.status == 200) {
            //     this.allBookings = resBooking.data
            //     $("#booking_table").DataTable().destroy();
            //     setTimeout(() => {
            //         $("#booking_table").DataTable();
            //     }, 300);
            // } else {
            //     console.log(res);
            // }
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
        }
        ,

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
        }
        ,

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
                this.fetchReScheduleData();
                this.resetingArrays();
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
        }
        ,

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
                this.fetchScheduleData();
                this.fetchReSpecificSchedules();
                this.resetingArrays();
            }

            if (resOverIssue.status == 422 && resOverIssue.data.message) {
                swal({
                    title: "Error",
                    text: resOverIssue.data.message,
                    icon: "error",
                    timer: 2000
                });
            }

            if (resOverIssue.status == 422) {
                let errorContent = "";
                let count = 0;
                for (const key in resOverIssue.data.errors) {
                    resOverIssue.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 2000
                    });

                }
            }
        }
        ,

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
        }
        ,

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
            this.EltButton = true;
            const resOverIssue = await this.callApi("post", "booking/elt", data);
            if (resOverIssue.status == 201) {
                this.EltButton = false;
                this.eltIds = resOverIssue.data.id
                setTimeout(() => {
                    this.$refs.refElt.submit();
                }, 700);
                swal({
                    title: "Success",
                    text: "ELT Added Successfully",
                    icon: "success",
                    timer: 2000
                });
            }

            if (resOverIssue.status == 422 && resOverIssue.data.message) {
                this.EltButton = false;
                swal({
                    title: "Error",
                    text: resOverIssue.data.message,
                    icon: "error",
                    timer: 2000
                });
            }

            if (resOverIssue.status == 422) {
                this.EltButton = false;
                let errorContent = "";
                let count = 0;
                for (const key in resOverIssue.data.errors) {
                    resOverIssue.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 2000
                    });

                }
            }
        }
        ,
        async allRescheduleData() {
            const arraySingleRescheduleData = [];
            const oldSeats = [];
            let totalFare = 0;
            this.reSpecificCities = [];
            this.rescheduleData.rescheduleSchedule = [];
            Object.entries(this.selectedSeatDataBackEnd).forEach(function (singleSeat, i) {
                let singlePostData = {};
                singlePostData = {
                    rescheduleDate: singleSeat[1][0].date,
                    existingDate: singleSeat[1][0].date,
                    dataCustomer: singleSeat[1][0].customer_id,
                    dataSchedule: singleSeat[1][0].schedule_id,
                    dataSeat_no: singleSeat[1][0].seat_no,
                    dataDepartureCity: singleSeat[1][0].departure_city_id,
                    dataAll: singleSeat[1][0],
                }
                arraySingleRescheduleData[i] = singlePostData;
                totalFare += (parseFloat(singleSeat[1][0].seat_fare) - parseFloat(singleSeat[1][0].discount ?? 0));
                oldSeats.push(singleSeat[1][0].seat_no);
                arraySingleRescheduleData['totalFare'] = totalFare;
                arraySingleRescheduleData['oldSeats'] = oldSeats;
            });
            this.mainAllRescheduleData = arraySingleRescheduleData;
            this.rescheduleData.dataDepartureCity = this.mainAllRescheduleData[0].dataDepartureCity;
            this.rescheduleData.rescheduleDate = this.mainAllRescheduleData[0].rescheduleDate;
            this.rescheduleData.rescheduleSchedule = 0;

            if (this.rescheduleData.dataDepartureCity == '0') {
                this.rescheduleData.rescheduleDestinationCity = 0;
            } else {
                const resReDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.mainAllRescheduleData[0].dataDepartureCity});
                if (resReDepartureCity.length == 0) {
                    this.rescheduleData.rescheduleDestinationCity = 0
                } else {
                    this.rescheduleData.rescheduleDestinationCity = 0;
                    this.reSpecificCities = resReDepartureCity.data;
                }
            }
            $("#reschedule_modal").modal('show');

        }
        ,

        // Reschedule model
        async passDataToRescheduleModel(data) {
            this.mainAllRescheduleData = [];
            this.reSpecificCities = [];
            this.rescheduleData = {
                rescheduleDate: data.date,
                existingDate: data.date,
                dataCustomer: data.customer_id,
                dataSchedule: data.schedule_id,
                dataDepartureCity: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
                dataSeatFare: data.seat_fare,
                dataAll: data,
            }
            this.mainAllRescheduleData[0] = this.rescheduleData;
            this.mainAllRescheduleData.totalFare = this.rescheduleData.dataSeatFare;
            this.mainAllRescheduleData.oldSeats = this.rescheduleData.dataSeat_no;
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

        async dropScheduleData() {
            this.dropScheduleButton = true;
            const resDropSchedule = await this.callApi("post", "booking/dropSchedule", this.dropScheduleFormData);
            if (resDropSchedule.status == 200) {
                this.dropScheduleButton = false;
                this.busDropCheck();
                swal({
                    title: "Success",
                    text: "Schedule Drop Successfully",
                    icon: "success",
                    timer: 2000
                });
            }
            if (resDropSchedule.status == 422) {
                this.dropScheduleButton = false;
                let errorContent = "";
                let count = 0;
                for (const key in resDropSchedule.data.errors) {
                    resDropSchedule.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " +
                            element +
                            "\n"
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 2000
                    });

                }
            }
        },

        async rescheduleSeats() {
            if (this.alreadyBookedSeat.length != this.mainAllRescheduleData.length) {
                this.alreadyBookedSeat = [];
                this.fetchReScheduleData();
                return swal({
                    title: "Oops",
                    text: "Your Just Select " + this.mainAllRescheduleData.length + " for Reschedule",
                    icon: "error",
                    timer: 2000
                });
            }

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
                    text: "Please Select Departure Time!!",
                    icon: "error",
                    timer: 2000
                });
            }
            this.mainAllRescheduleData.map((single, index) => {
                single.selected_seatNo = this.alreadyBookedSeat[index];
                single.selected_seatClass = this.alreadyBookedSeatClass[index];
                single.selected_seatFare = this.alreadyBookedSeatFare[index];
                single.reason = this.rescheduleData.reason;
                single.rescheduleDate = this.rescheduleData.rescheduleDate;
                single.rescheduleType = this.rescheduleSeatType;
                single.overIssueReschedule = this.overIssueScheduleCheckBox;
                single.newDepartureTime = this.rescheduleData.rescheduleSchedule;
                single.rescheduleDiscount = this.rescheduleDiscount;
                single.dataDepartureCity = this.rescheduleData.dataDepartureCity;
                single.dataDestination = this.rescheduleData.rescheduleDestinationCity;
            });
            this.loadingRescheduleButton = true;
            const resReschedule = await this.callApi("post", "booking/reschedule", {'data': this.mainAllRescheduleData});
            if (resReschedule.status == 200) {
                this.loadingRescheduleButton = false;
                swal({
                    title: "Success",
                    text: "Seat Reschedule Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();
                this.fetchReScheduleData();
            } else {
                if (resReschedule.status == 422) {
                    this.loadingRescheduleButton = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resReschedule.data.errors) {
                        resReschedule.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000
                        });

                    }
                }
            }
        }
        ,
        // Duplicate Ticket
        duplicateTicket: function (data) {

            this.ticketsId = data.id
            setTimeout(() => {
                if (data.type == "booked") {
                    this.$refs.refDuplicateTicket.submit();
                } else {
                    swal({
                        title: "OOppss!!!",
                        text: "Please Confirm Seat for Duplicate Ticket",
                        icon: "error",
                        timer: 2000
                    });
                }
            }, 700);
        }
        ,

        // Get Passengers list
        getCustomerList: function () {
            if (this.addForm.departureCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.destinationCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Destination City",
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
            if (this.addForm.schedule == 0) {
                return swal({
                    title: "Required!",
                    text: "Departure Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.$refs.refPassengerList.submit();
        }
        ,
        // Get Terminal Invoice
        getTerminalInvoice: function () {

            if (this.addForm.departureCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.destinationCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Destination City",
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
            if (this.addForm.schedule == 0) {
                return swal({
                    title: "Required!",
                    text: "Departure Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.$refs.refTerminalInvoice.submit();
        }
        ,
        // Get Bus Invoice
        getBusInvoice: function () {

            if (this.addForm.departureCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Departure City",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.destinationCity == 0) {
                return swal({
                    title: "Required!",
                    text: "Please Select Destination City",
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
            if (this.addForm.schedule == 0) {
                return swal({
                    title: "Required!",
                    text: "Departure Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.$refs.refBusInvoice.submit();
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

#seatMapDiv {
    border-radius: 10px;
    border: 3px #c5c3c3 groove;
    max-height: 100% !important;
    margin: 10px 0 10px 0 !important;
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

.femaleReserve::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    z-index: -1;
    height: 100%;
    width: 50%;
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
    background-color: hotpink !important;
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

.lds-roller {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
}

.lds-roller div {
    animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    transform-origin: 40px 40px;
}

.lds-roller div:after {
    content: " ";
    display: block;
    position: absolute;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #6777ef;
    margin: -4px 0 0 -4px;
}

.lds-roller div:nth-child(1) {
    animation-delay: -0.036s;
}

.lds-roller div:nth-child(1):after {
    top: 63px;
    left: 63px;
}

.lds-roller div:nth-child(2) {
    animation-delay: -0.072s;
}

.lds-roller div:nth-child(2):after {
    top: 68px;
    left: 56px;
}

.lds-roller div:nth-child(3) {
    animation-delay: -0.108s;
}

.lds-roller div:nth-child(3):after {
    top: 71px;
    left: 48px;
}

.lds-roller div:nth-child(4) {
    animation-delay: -0.144s;
}

.lds-roller div:nth-child(4):after {
    top: 72px;
    left: 40px;
}

.lds-roller div:nth-child(5) {
    animation-delay: -0.18s;
}

.lds-roller div:nth-child(5):after {
    top: 71px;
    left: 32px;
}

.lds-roller div:nth-child(6) {
    animation-delay: -0.216s;
}

.lds-roller div:nth-child(6):after {
    top: 68px;
    left: 24px;
}

.lds-roller div:nth-child(7) {
    animation-delay: -0.252s;
}

.lds-roller div:nth-child(7):after {
    top: 63px;
    left: 17px;
}

.lds-roller div:nth-child(8) {
    animation-delay: -0.288s;
}

.lds-roller div:nth-child(8):after {
    top: 56px;
    left: 12px;
}

@keyframes lds-roller {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

</style>
