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
                                                                :disabled="depLoading"
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
                                                                :disabled="desLoading"
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
                                                        <input type="date" :min="checkForSubmenuButtons('previous-date') ? '' : minDateFilter()" class="form-control"
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
                                                                @change="setScheduleValue(); fetchScheduleData(); busDropCheck();"
                                                                v-model="addForm.id">
                                                            <option value="0">Select Departure Time</option>
                                                            <option v-for="(schedule, i) in allSchedules"
                                                                    :value="schedule.id"

                                                                    :key="i">
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
                                                        v-on:blur="getCustomer('addFormCNIC'), getPoints('addFormCNIC')"
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
                                        <div class="row" v-if="this.haveLabel">
                                            <div class="col-md-6">
                                                <label class="py-2 text-danger" v-if="this.haveLabel">{{
                                                        this.label
                                                    }}</label>
                                            </div>
                                            <div class="col-md-6" v-if="this.hideCheckBox">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                           id="pointsCheckBox"
                                                           @click="usePoints($event)" :value="this.pointsCardId"
                                                           name="pointsUsage">
                                                    <label class="custom-control-label"
                                                           for="pointsCheckBox">Points Usage</label>
                                                </div>
                                                <label class="text-danger">{{
                                                        this.pointsUsage ? this.pointsUsage : ''
                                                    }}</label>
                                            </div>
                                        </div>
                                        <div class="row bg-light-green pt-2" v-if="this.pointsUsage">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="points_use">How Many Points you want to utilize</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="points_use"
                                                        placeholder="Leave Input Blank means Zero Points Usage"
                                                        v-model="addForm.pointsUseInput"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-md-6" v-if="this.pointsUsage && this.addForm.otp_valid == false">
                                                <div class="form-group mb-0">
                                                    <label for="points_use">OTP</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="points_use"
                                                        placeholder="Enter 6 digit otp"
                                                        v-model="addForm.otp"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="this.addForm.otp_valid" class="bg-light-green pb-3 row">
                                            <div class="col-md-12 text-center text-dark">
                                                OTP verified successfully
                                            </div>
                                        </div>
                                        <div class="row pt-3 bg-light-green pb-3" v-if="this.pointsUsage && this.addForm.otp_valid == false">
                                            <div class="col-md-6">
                                                <button class="btn btn-block btn-sm btn-dark" :class="{'btn-progress': otpLoader}" @click="sendOtp()">Send OTP</button>
                                            </div>
                                            <div class="col-md-6">
                                                <button class="btn btn-block btn-primary btn-sm ml-1" :class="{'btn-progress': otpLoader}" @click="verifyOtp()">Verify OTP</button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div v-if="checkForSubmenuButtons('terminal-id')" class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="Terminals" class="mb-0"> Terminal ID</label>
                                                    <select class="form-control" id="Terminals"
                                                            @change="fetchScheduleData();"
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
                                            <div
                                                :class="!checkForSubmenuButtons('terminal-id') ? 'col-md-12 mt-3 mb-3' : 'col-md-6 mt-3'">
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
                                                        v-if="checkForSubmenuButtons('advance-booking')"
                                                        class="align-self-center">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                   id="bookingTypeCheckBox"
                                                                   v-bind:checked="addForm.type == 'advance booking'"
                                                                   @click="changeType($event)"
                                                                   value="advance booking"
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
                                                        type="text" @keypress="isNumberDiscount($event)"
                                                        @keyup="calculateTotal()"
                                                        :readonly="!checkForSubmenuButtons('discount-field')"
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
                                        <div class="my-2">
                                            <div class="form-group text-center mt-2 mb-2"
                                            >
                                                <a v-if="checkForSubmenuButtons('assign-bus') && hideDivButtonsDrop"
                                                   href="#"
                                                   class="btn btn-primary btn-sm" @click="closingData()">
                                                    Assign Bus
                                                </a>
                                                <button v-if="checkForSubmenuButtons('terminal-invoice')"
                                                        class="btn btn-info ml-1 btn-sm" @click="getTerminalInvoice()">
                                                    Terminal Invoice
                                                </button>
                                                <button v-if="checkForSubmenuButtons('bus-invoice')"
                                                        class="btn btn-warning ml-1 btn-sm" @click="getBusInvoice()">
                                                    Bus Invoice
                                                </button>
                                                <button v-if="checkForSubmenuButtons('pax-list')"
                                                        class="btn btn-danger ml-1 btn-sm" @click="getCustomerList()">
                                                    Pax List
                                                </button>
                                                <button v-if="hideDivButtonsDrop" class="btn btn-success ml-1 btn-sm"
                                                        v-on:click="add()"
                                                        :disabled="bookingLoading"
                                                        v-on:keyup.enter="add()">
                                                    {{
                                                        this.addForm.type == 'advance booking' ? 'Reserved Seat' :
                                                            'Generate Ticket'
                                                    }}
                                                </button>
                                            </div>
                                            <div class="text-center mb-2">
                                                <a v-if="checkForSubmenuButtons('drop-schedule') && !hideDivButtonsDrop"
                                                    href="#"
                                                    class="btn btn-outline-secondary mr-1 btn-sm" @click="revertDropSchedule()" :disabled="revertScheduleButton">
                                                    Revert Schedule
                                                </a>
                                                <button v-if="checkForSubmenuButtons('seat-details')"
                                                        class="btn btn-outline-secondary btn-sm text-dark mr-2"
                                                        @click="seatDetails()">
                                                    Seat Details
                                                </button>
                                                <button v-if="checkForSubmenuButtons('bus-class')"
                                                        class="btn btn-outline-secondary btn-sm text-dark mr-2"
                                                        @click="busClass()">
                                                    Bus class
                                                </button>
                                                <button
                                                    v-if="checkForSubmenuButtons('drop-schedule') && hideDivButtonsDrop"
                                                    class="btn btn-secondary btn-sm text-dark mr-2"
                                                    @click="scheduleDrop()" :disabled="dropScheduleButton">
                                                    Drop Schedule
                                                </button>
                                                <button class="btn btn-secondary btn-sm text-dark"
                                                        @click="fetchScheduleData()" :disabled="getSchedule">
                                                    {{ getSchedule ? "Loading..." : 'Refresh' }}
                                                </button>
                                            </div>
                                            <div class="text-center" v-if="!hideDivButtonsDrop">
                                                <h4>{{ labelDrop }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <!--                                        Seat Map-->
                                    <div class="col-md-4 overflow-auto" id="seatMapDiv">
                                        <div v-if="showBookingDiv"
                                             class="d-flex seat-img p-0 m-0 justify-content-center"
                                             v-for="(record, rowIndex) in schedule.bus_class.seat_map" :key="rowIndex">
                                            <div v-for="(col, colIndex) in record" :key="colIndex">
                                                <div v-if="col.reserved">
                                                    <div
                                                        v-if="allowedSeats !== 0 ? allowedSeats.includes(parseInt(col.seatNo)) : true"
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
                                                    <div v-else
                                                         class="image-span d-block text-center text-white shadow"
                                                         :class="getClasses(col)"
                                                         :title="getTitle(col)"
                                                         style="pointer-events: none !important; background-color: #444444 !important;"
                                                    >
                                                        <small>{{ col.seatNo }}</small>
                                                        <br/>
                                                        <small>
                                                            <i class="type-icons fa fa-times text-danger"></i>
                                                        </small>
                                                    </div>
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
                                    <div class="col-md-2 px-2 " style="overflow-x: hidden; overflow-y: auto;">
                                        <div class="">
                                            <div class="col-md-12 mb-2 px-0 d-flex flex-wrap">
                                                <div class="border-bottom w-100">
                                                    <div class="my-1">
                                                        <div 
                                                            class="bg-danger text-dark circles mr-1 border shadow">
                                                            <i class="fas fa-minus-circle"></i></div>
                                                        <span class="text-wrap">Not For Sale</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="selected circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Selected</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-male-reserved circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Male Reserved</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-female-reserved circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Female Reserved</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-male-booked circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Male Confirmed</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-female-booked circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Female Confirmed</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-online-male-reserved circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Online M Reserved</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-online-female-reserved circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Online F Reserved</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-online-male-booked circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Online M Confirmed</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="for-online-female-booked circles mr-1 border shadow"></div>
                                                        <span class="text-wrap">Online F Confirmed</span>
                                                    </div>
                                                    <div class="my-1">
                                                        <div
                                                            class="partial-seat circles mr-1 border shadow"></div>
                                                        <span class="text-wrap"
                                                              >Partial Seat</span>
                                                    </div>
                                                </div>
                                                <!-- <div class="border-bottom w-100">
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
                                                        <span class="text-wrap mrn">Advance Issued</span>
                                                    </div>
                                                    <div class="my-2">
                                                        <div
                                                            class="fas fa-check-double circles icons-legend shadow mr-1 border"
                                                        ></div>
                                                        <span class="text-wrap mrn">Confirmed Booked</span>
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
                                                </div> -->

                                                <div class="my-1" v-for="(seatClass,i) in allSeatClasses" :key="i">
                                                    <div class="circles mr-1 border shadow"
                                                         :style="{border:'2px solid '+seatClass.color+' !important'}"></div>
                                                    <span class="text-wrap">{{ seatClass.name }}</span>
                                                </div>
                                                <div class="my-1 border-top w-100" v-if="showBookingDiv">
                                                    <br>
                                                    <span>Bus: <span class="text-dark"
                                                                        style="font-weight: 700 !important">{{
                                                            this.BusNo
                                                        }}</span> </span><br>
                                                    <span>Booked: <span class="text-dark"
                                                                        style="font-weight: 700 !important">{{
                                                            this.totalSeatsBooked
                                                        }}</span> </span><br>
                                                    <span>Issued: <span class="text-dark"
                                                                        style="font-weight: 700 !important">{{
                                                            this.totalSeatsIssued
                                                        }}</span></span><br>
                                                    <span>Available: <span class="text-dark"
                                                                           style="font-weight: 700 !important">{{
                                                            this.totalSeatsAvailable
                                                        }}</span></span><br>
                                                    <span>ELT:
                                                        <button title="View ELT Details"
                                                                data-target="#elt_detail_modal"
                                                                data-toggle="modal"
                                                                :disabled=" eltDetailsModel.length == 0 "
                                                                class="btn-primary btn btn-sm">{{
                                                                this.eltDetailsModel.length
                                                            }}</button>
                                                        </span><br>
                                                    <span>OverIssue Seat:
                                                        <button title="View Over Issue Seat  Details"
                                                                data-target="#overIssue_detail_modal"
                                                                data-toggle="modal"
                                                                :disabled=" overIssueSeatsRevert.length == 0 "
                                                                class="btn-primary btn btn-sm">{{
                                                                this.overIssueSeatsRevert.length
                                                            }}</button>
                                                        </span>
                                                    <br>
                                                    <span>T/Discount: <span class="text-dark"
                                                                            style="font-weight: 700 !important">{{
                                                            this.terminalDiscount
                                                        }}</span></span><br>
                                                    <span>S/Discount: <span class="text-dark"
                                                                            style="font-weight: 700 !important">{{
                                                            this.appliedDiscount
                                                        }}</span></span><br>
                                                    <span>Surcharge: <span class="text-dark"
                                                                           style="font-weight: 700 !important">{{
                                                            this.appliedSurcharge
                                                        }}</span></span>
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

        <!--        ELT Deatils MOdel-->
        <div class="modal fade" id="elt_detail_modal" tabindex="-1" aria-labelledby="eltDetailModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">ELT Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeEltDetail()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body m-1 p-1">
                        <div class="card-body my-0 py-0">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Customer Name</th>
                                            <th>Seat No</th>
                                            <th>ELT Weight</th>
                                            <th>ELT Price</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="(single, i) in eltDetailsModel" :key="i">
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ single.customer.name }}</td>
                                            <td>{{ single.seat_no }}</td>
                                            <td>{{ single.elt_weight }}</td>
                                            <td>{{ single.elt_price }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- END TABLE -->
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeEltDetail()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!--        OverIssue Detail Modal -->
        <div class="modal fade" id="overIssue_detail_modal" tabindex="-1" aria-labelledby="overIssueDetailModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="overIssueDetailModalLabel">OverIssue Seat Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeOverIssueDetail()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body m-1 p-1">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card" v-for="(singleSeat,  i) in overIssueSeatsRevert">
                                        <div class="card-body p-3">
                                            <div class="row ml-2 border-bottom">
                                                <div class="col-md-4 d-flex justify-content-start">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Seat :</h4>
                                                    <h4 class="mb-0 text-muted">{{ singleSeat.seat_no }}</h4>
                                                </div>
                                                <div class="col-md-4 d-flex justify-content-start">
                                                    <h6 class="mb-0 font-weight-bold mr-3">OverIssue Time :</h6>
                                                    <h6 class="mb-0  text-danger">{{ singleSeat.OverIssueDate }}</h6>
                                                </div>
                                                <div class="col-md-4 d-flex justify-content-end">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Type:</h4>
                                                    <h4 class="mb-0 text-muted text-capitalize">{{
                                                            singleSeat.type
                                                        }}</h4>
                                                </div>
                                            </div>
                                            <div class="row my-3 pl-3">
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Date :</p>
                                                        <p class="mb-0">{{ singleSeat.date }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3"> Bus Class :</p>
                                                        <p class="mb-0">{{ singleSeat.bus_class.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Schedule : </p>
                                                        <p class="mb-0">{{ singleSeat.schedule.name }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Name : </p>
                                                        <p class="mb-0">{{ singleSeat.customer.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Cnic :</p>
                                                        <p class="mb-0">{{ cnicFormat(singleSeat.customer.cnic) }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Phone : </p>
                                                        <p class="mb-0">
                                                            {{ phoneFormat(singleSeat.customer.contact) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Booking Date & Time : </p>
                                                        <p class="mb-0">{{ singleSeat.bookingDate }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3"> Departure City :</p>
                                                        <p class="mb-0">{{ singleSeat.departure_city.name }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Destination City : </p>
                                                        <p class="mb-0">{{ singleSeat.destination_city.name }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                </div>
                                                <div class="col-md-4">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Overissue By :</p>
                                                        <p class="mb-0">{{ singleSeat.overIssueBy }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--Buttons-->
                                            <div class="row mt-3">
                                                <div class="col-md-12 text-right">
                                                    <button type="button" class="btn btn-secondary text-dark"
                                                            v-if="checkForSubmenuButtons('duplicate-ticket')"
                                                            @click="duplicateTicket(singleSeat)">Duplicate Ticket
                                                    </button>
                                                    <button @click="revertOverIssueFunction(singleSeat)"
                                                            type="button" class="btn btn-info ml-2"
                                                            :disabled="loadingRevertButton">{{
                                                            loadingRevertButton ? 'Loading...' : 'Revert Over Issue'
                                                        }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                @click="closeOverIssueDetail()">
                            Close
                        </button>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeElt()">
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
                                        :disabled="editAbleELT"
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
                                        :disabled="editAbleELT"
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
                                              :disabled="editAbleELT"
                                              v-model="eltData.dataDescription"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary"
                                @click="addEltToTicket(eltData)" v-if="EltButton || !editAbleELT">
                            {{ this.EltButton ? 'Loading...' : 'Add ELT Button' }}
                        </button>
                        <button type="button" class="btn btn-outline-info"
                                @click="editAbleELT = false" v-if="editAbleELT">Edit
                        </button>
                        <button type="button" class="btn btn-outline-danger"
                                @click="editAbleELT = true "
                                v-if="!editAbleELT">Cancel
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeElt()">Close
                        </button>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeOverissue()">
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeOverissue()">
                            Close
                        </button>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeReschedule()">
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
                                <select class="form-control" id="reScheduleName" @change="setRescheduleValue($event); fetchReScheduleData()"
                                        v-model="rescheduleData.id">
                                    <option value="0" selected>Select Schedule</option>
                                    <option v-for="(schedule, i) in allReSchedules"

                                            :value="schedule.id" :key="i">{{ scheduleDropdown(schedule) }}
                                    </option>
                                </select>
                                <!--                                :disabled="disabledOptionsReschedule.includes(schedule)"-->
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
                                        <div v-if="col.reserved">
                                            <div
                                                v-if="allowedSeats !== 0 ? allowedSeats.includes(parseInt(col.seatNo)) : true"
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
                                            <div v-else
                                                 class="image-span d-block text-center text-white shadow"
                                                 :class="getClasses(col)"
                                                 :title="getTitle(col)"
                                                 style="pointer-events: none !important; background-color: #444444 !important;"
                                            >
                                                <small>{{ col.seatNo }}</small>
                                                <br/>
                                                <small>
                                                    <i class="type-icons fa fa-times text-danger"></i>
                                                </small>
                                            </div>
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
                                            @keypress="isNumberDiscount($event)"
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeReschedule()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Model  -->
        <div class="modal fade" id="dropSchedule" tabindex="3" aria-labelledby="dropScheduleLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dropScheduleLabel">Drop Schedule</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">
                            Close
                        </button>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeCancel()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" v-if="cancelData.dataType == 'booked' ">
                        <div class="form-group">
                            <label for="cancel_percentage">Percentage  {{cancelData.dataType}}<span
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
                    <div class="modal-body" v-if="cancelData.dataType == 'advance booking' ">
                        
                        Are you sure you want to cancel ticket ?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" :disabled="cancelLoading"
                                @click="cancelBooking(cancelData)">
                                {{ getSchedule ? "Loading..." : 'Cancel Ticket' }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeCancel()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Model Cancel All ticket-->
        <div class="modal fade" id="cancel_all_ticket" tabindex="4" aria-labelledby="cancelAllModelLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelAllModelLabel">Cancel Ticket</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" v-if="cancelAllData.cancelAllSeatType[0] == 'booked' ">
                        <div class="form-group">
                            <label for="cancel_percentage">Percentage</label>
                            <select id="cancel_percentage" class="form-control" v-model="cancelAllData.percentage">
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
                            <label for="">Remarks</label>
                            <textarea type="text" class="form-control" id="" v-model="cancelAllData.reason"
                                      placeholder="Reason for canceling a seat"></textarea>
                        </div>
                    </div>
                    <div class="modal-body" v-if="cancelAllData.cancelAllSeatType[0] =='advance booking'">
                        Are you sure you want to cancel all ticket ?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" :disabled="cancelLoading"
                                @click="allSeatCancel()">
                                {{ getSchedule ? "Loading..." : 'Cancel Ticket' }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">
                            Close
                        </button>
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
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
                                    <button v-if="this.duplicateAllSeatType[0] == 'booked'" type="button"
                                            @click="allTicketDuplicate();"
                                            class="btn btn-secondary text-dark ml-2">
                                        Duplicate All Ticket
                                    </button>
                                    <button v-if="(this.cancelAllData.cancelAllSeatType[0] == 'booked' && checkForSubmenuButtons('cancel-ticket') || this.cancelAllData.cancelAllSeatType[0] == 'advance booking' && checkForSubmenuButtons('reserved-cancel'))" type="button"
                                            @click="cancelAllModal();"
                                            class="btn btn-danger ml-2">
                                        Cancel All Ticket
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card" v-for="(singleItems,  i) in selectedSeatDataBackEnd">
                                        <div class="card-body p-3" v-for="(innerItem,key , j) in singleItems">
                                            <div class="row ml-2 border-bottom" v-if="key == 0">
                                                <div class="col-md-4 d-flex justify-content-start">
                                                    <h4 class="mb-0 font-weight-bold mr-3">Seat :</h4>
                                                    <h4 class="mb-0 text-muted">{{ innerItem.seat_no }}</h4>
                                                </div>
                                                <div class="col-md-4 d-flex justify-content-start">
                                                    <h6 class="mb-0 font-weight-bold mr-3">Booked By :</h6>
                                                    <h6 class="mb-0 text-muted">{{ innerItem.added_name.name }}</h6>
                                                </div>
                                                <div class="col-md-4 d-flex justify-content-end">
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
                                                        <p class="mb-0">{{ innerItem.bus_class.name }}</p>
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
                                                        <p class="mb-0">{{ auth_terminal.other_terminal_passenger_detail == 1 || innerItem.terminal_id == this.auth_terminal.id ? cnicFormat(innerItem.customer.cnic) : "---" }}</p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Customer Phone : </p>
                                                        <p class="mb-0">
                                                            {{ auth_terminal.other_terminal_passenger_detail == 1 || innerItem.terminal_id == this.auth_terminal.id ? phoneFormat(innerItem.customer.contact) : "---" }}
                                                        </p>
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
                                                <div class="col-md-4">
                                                    <div class="d-flex">
                                                        <p class="mb-0 font-weight-bold mr-3">Tracking Id</p>
                                                        <p class="mb-0">T{{ innerItem.id }}@{{ innerItem.invoice_id }}</p>
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
                                                            @click="resendSms(innerItem.invoice_id,innerItem.type)"
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
                                                    <button v-if="(checkForSubmenuButtons('cancel-ticket') && innerItem.type=='booked') || (checkForSubmenuButtons('reserved-cancel') && innerItem.type=='advance booking')" type="button"
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
        
        <div class="modal fade" id="busClassModal" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="">Bus Class Update</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <!--loop for number of seats-->

                        <div class="card-body">
                            <div class="row">
                                <div class=" form-group col-md-12">
                                    <label for="city_id">Bus Class<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="busClassData.bus_class">
                                        <option value="0">Select Bus Class</option>
                                        <option v-for="(bus_class,i) in bus_classes" :key="i" :value="bus_class.id">
                                            {{ bus_class.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                        <button type="button" class="btn btn-primary" @click="updateBusClass()">
                            Update
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Close Schedule -->
        <Add
            heading="Close Schedule"
            :errors="this.validationErrors"
            :success="success"
            :formID="formAddID"
        >
            <div class="row">
                <div class=" form-group col-md-6">
                    <label for="city_id">Bus <span class="text-danger ml-1">*</span></label>
                    <select class="form-control" v-model="dataForClose.bus">
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
                    <select class="form-control rounded-0" id="assignDriver" v-model="dataForClose.drivers" multiple
                    >
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
                    <select class="form-control rounded-0" id="assignHost" v-model="dataForClose.hosts" multiple
                    >
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
                        v-model="dataForClose.description"
                        cols="30"
                        rows="10"
                    ></textarea>
                </div>
            </div>
            <template v-slot:button>
                <!-- v-if="!checkCloseData || !editAble" -->
                <button
                    v-if="checkCloseData"
                    type="button"
                    class="btn btn-primary"
                    @click="updateCloseSchedule" :disabled="loading"
                >
                    {{ loading ? 'Loading...' : 'Update Schedule' }}
                </button>
                <button
                    v-else
                    type="button"
                    class="btn btn-primary"
                    @click="closeSchedule" :disabled="loading"
                >
                    {{ loading ? 'Loading...' : 'Close Booking' }}
                </button>
            </template>
        </Add>

        <!--Modal for seat details end-->
        <DetailsModal :formID="detailsFormId" :details="bookingDetails" :deleteFormID="deleteFormID"/>

        <!--Print Passesnger List Form-->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/passenger/list'" method="POST" ref="refPassengerList"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
            <input type="hidden" name="departure_time" :value="this.addForm.departure_time">
        </form>
        <!--Print Terminal Invoice-->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/terminal/invoice'" method="POST" ref="refTerminalInvoice"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
            <input type="hidden" name="terminal_id" :value="this.addForm.terminalId">
            <input type="hidden" name="departure_time" :value="this.addForm.departure_time">
        </form>
        <!--Print Bus Invoice -->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/bus/invoice'" method="POST" ref="refBusInvoice"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="destination_city_id" :value="this.addForm.destinationCity">
            <input type="hidden" name="departure_city_id" :value="this.addForm.departureCity">
            <input type="hidden" name="date" :value="this.addForm.date">
            <input type="hidden" name="schedule_id" :value="this.addForm.schedule">
            <input type="hidden" name="departure_time" :value="this.addForm.departure_time">
        </form>
        <!--        print Customer Ticket Print-->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/customer/ticket'" method="POST" ref="refTicket"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="ticket_ids" :value="this.ticketsIds">
            <input type="hidden" name="duplicate" value=0>
        </form>
        <!--        print Customer Duplicate Ticket Print-->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/customer/ticket'" method="POST" ref="refDuplicateTicket"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="ticket_id" :value="this.ticketsId">
            <input type="hidden" name="duplicate" value=1>
        </form>
        <!--        print Customer Duplicate All Ticket Print-->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/customer/ticket'" method="POST" ref="refDuplicateAllTicket"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
            <input type="hidden" name="ticket_ids" :value="this.duplicateAllTicket">
            <input type="hidden" name="duplicate" value=0>
        </form>
        <!--        Elt Customer PDF Form  -->
        <form :action="$store.state.api_url + 'api/web/v1/print/pdf/customer/elt'" method="POST" ref="refElt"
              target="_blank">
            <input type="hidden" name="token" :value="this.$store.state.token">
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
            BusNo: "N/A",
            bus_classes: [],
            permissions: [],
            drivers: [],
            hosts: [],
            assignBus: 0,
            auth_terminal: [],
            shiftingFormId: "shifting-modal",
            partialSeatFormId: "partialSeat-modal",
            detailsFormId: "details-modal",
            customers: [],
            sameDataMain: [],
            eltDetailsModel: [],
            overIssueSeatsRevert: [],
            cancelData: {
                percentage: 'first',
            },
            cancelAllData: {
                percentage: '0',
                cancelAllSeat: [],
                cancelAllSeatType: [],
                reason:"",
            },
            duplicateAllTicket: [],
            duplicateAllSeatType: [],
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
                ticket_closing_id: '',
                alreadyAssigned: '',
                ticket_merge_id: '',
            },
            dropScheduleFormData: {
                reason: '',
                departure_city_id: '',
                destination_city_id: '',
                date: '',
                schedule_id: '',
            },
            isActive: 1,
            formAddID: "addBooking",
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
            otpLoader: false,
            editAble: true,
            editAbleELT: true,
            getSchedule: false,
            showBookingDiv: false,
            showReBookingDiv: false,
            cancelLoading: false,
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
            allowedSeats: [],
            reSpecificCities: [],
            previousSumFare: 0,
            totalSeats: 0,
            totalSeatsAvailable: 0,
            totalSeatsIssued: 0,
            totalSeatsBooked: 0,
            selectedSeatDataBackEnd: [],
            mainAllRescheduleData: [],
            filterDate: new Date().toISOString().substr(0, 10),
            cities: [],
            advanceSeat: [],
            overIssueScheduleCheckBox: 'general',
            rescheduleSeatType: "booked",
            rescheduleDiscount: "",
            eltIds: "",
            bookingLoading: false,
            EltButton: false,
            loadingRescheduleButton: false,
            dropScheduleButton: false,
            showRescheduleDiscountDiv: false,
            allRescheduleButton: false,
            revertScheduleButton: false,
            labelDrop: '',
            hideDivButtonsDrop: true,
            ticketsIds: "",
            label: "",
            haveLabel: false,
            hideCheckBox: false,
            runUpdateFun: true,
            pointsCardId: "",
            ticketsId: "",
            appliedSurcharge: "",
            appliedDiscount: "",
            terminalDiscount: "",
            pointsValidation: "",
            pointsUsage: "",
            checkedUsagePoints: false,
            checkSameType: [],
            loadingRevertButton: false,
            depLoading: false,
            desLoading: false,
            busClassData: {
                bus_class : "0",
            },
            addForm: {
                date: new Date().toISOString().substr(0, 10),
                type: "booked",
                gender: "1",
                customerCNIC: "",
                otp: "",
                otp_valid: false,
                otp_cnic: false,
                id: 0,
                schedule: 0,
                departure_time: "",
                totalFare: 0,
                destinationCity: 0,
                departureCity: 0,
                totalAmount: 0,
                discount: 0,
                terminalId: 0,
                alreadyBookedId: [],
                reservedFare: [],
                advanceSeatClass: [],
            },
            advanceCash: {
                sale: 0,
                amount: -500,
                advanceDeposit: 0,
                withdrawBank: '',
                description: '',
            },
            rescheduleData: {
                rescheduleSchedule: 0,
                rescheduleDate: '',
                id: "0",
                departure_time: "",
                rescheduleDestinationCity: 0,
                dataDepartureCity: 0,
            },

            addFormOverIssue: {
                date: new Date().toISOString().substr(0, 10),
                type: "booked",
                gender: "1",
                customerCNIC: "",
                id: 0,
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
        $('.modal').remove();
        this.fetchAllSchedules();
        this.showBookingDiv = false;
        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },
    mounted() {
        const self = this;
        // assignDriver
        const assignDriver = $('#assignDriver');
        assignDriver.on('change', function() {
            const selectedValues = $(this).val();
            self.dataForClose.drivers = selectedValues;
        });
        // assignHost
        const assignHost = $('#assignHost');
        assignHost.on('change', function() {
            const selectedValues = $(this).val();
            self.dataForClose.hosts = selectedValues;
        });
        
        setTimeout(() => {
            
            // departureCity
            const departureCity = $('#departureCity');
            departureCity.on('change', (e) => {
                this.addForm.departureCity = e.target.value;
                this.fetchSpecificSchedules();
                this.getDestinationCity();
            });
            // destinationCity
            const destinationCity = $('#destinationCity');
            destinationCity.on('change', (e) => {
                this.addForm.destinationCity = e.target.value;
                this.fetchSpecificSchedules();
            });
            // scheduleName
            const scheduleName = $('#scheduleName');
            scheduleName.on('change', (e) => {
                this.addForm.schedule = e.target.value;
                this.setScheduleValue(e);
                this.fetchScheduleData();
                this.busDropCheck();
            });
        }, 500);
    },
    methods: {
        // modal close
        closeModal() {
            $(".modal").modal('hide');
        },
        closeEltDetail() {
            $("#elt_detail_modal").click();
        },
        closeOverIssueDetail() {
            $("#overIssue_detail_modal").click();
        },
        closeElt() {
            $("#addELTModel").modal('hide');
        },
        closeReschedule() {
            $("#reschedule_modal").modal('hide');
        },
        closeOverissue() {
            $("#overIssue_model").modal('hide');
        },
        closeCancel() {
            $("#cancelModel").modal('hide');
        },
        // end
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

        enterKey: function (e) {
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
                if (this.checkForSubmenuButtons('seat-details-shortcut')) {
                    this.seatDetails();
                } else {
                    swal({
                        title: "OOPS!!",
                        text: "Access Denied",
                        icon: "error",
                        timer: 2000,
                    });
                }
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
                    departure_time: this.addForm.departure_time,

                }
                this.selectedSeatDataBackEnd = [];
                const resSeatData = await this.callApi("post", "booking/advance", dataSeats);
                if (resSeatData.status == 200) {
                    this.selectedSeatDataBackEnd = resSeatData.data.tickets;
                    this.allRescheduleButton = resSeatData.data.showButton;
                    $('#seatAllDetailsModal').modal('show');

                    // for cancel all ticket and duplicate all ticket functionality
                    this.cancelAllData.cancelAllSeat = [];
                    this.cancelAllData.cancelAllSeatType = [];
                    this.duplicateAllTicket = [];
                    this.duplicateAllSeatType = [];
                    Object.entries(this.selectedSeatDataBackEnd).forEach(([key1, single]) => {
                        Object.entries(single).forEach(([key2, seat]) => {
                            this.cancelAllData.cancelAllSeat.push(seat.id)
                            this.cancelAllData.cancelAllSeatType.push(seat.type)
                            this.duplicateAllTicket.push(seat.id)
                            this.duplicateAllSeatType.push(seat.type)
                        });
                    });

                    // to check all ticket type are same or not
                    if(!this.cancelAllData.cancelAllSeatType.every(value => value === this.cancelAllData.cancelAllSeatType[0]))
                    {
                        this.cancelAllData.cancelAllSeat = [];
                        this.cancelAllData.cancelAllSeatType = [];
                    }
                    // to check all ticket type are same or not
                    if(!this.duplicateAllSeatType.every(value => value === this.duplicateAllSeatType[0]))
                    {
                        this.duplicateAllTicket = [];
                        this.duplicateAllSeatType = [];
                    }

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
            const resDateFilter = await this.callApi("post", "booking", {date: this.filterDate});
            if (resDateFilter.status == 200) {
                if (resDateFilter.data.length != 0) {
                    this.allBookings = resDateFilter.data;
                }
            }
        },
        
        async allSeatCancel() {
            this.cancelLoading = true;
            const resCancelSeats = await this.callApi("post", "booking/canceling/all", this.cancelAllData);
            if (resCancelSeats.status == 200) {
                this.cancelLoading = true;
                swal({
                    title: "Success",
                    text: "Seats Canceled Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();
                this.addForm.flag = 0;
                this.cancelAllData.cancelAllSeat = [];
                this.cancelAllData.cancelAllSeatType = [];
                this.cancelAllData.percentage = "0";
                this.cancelAllData.reason = "";
                this.closeModal();
                const resBookingDetail = await this.callApi("post", "booking/whatsapp/cancel/message", {tickets: resCancelSeats.data.tickets});
            }
            if (resCancelSeats.status == 422) {
                this.dropScheduleButton = false;
                let errorContent = "";
                let count = 0;
                for (const key in resCancelSeats.data.errors) {
                    resCancelSeats.data.errors[key].forEach((element) => {
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
            this.cancelLoading = false;
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
        
        setScheduleValue(event) {
            this.addForm.departure_time = this.allSchedules[event.target.selectedIndex-1].departure_city_time;
            this.addForm.schedule = this.allSchedules[event.target.selectedIndex-1].schedule_id;
        },
        
        setRescheduleValue(event) {
            this.rescheduleData.departure_time = this.allReSchedules[event.target.selectedIndex-1].departure_city_time;
            this.rescheduleData.rescheduleSchedule = this.allReSchedules[event.target.selectedIndex-1].schedule_id;
        },

        async getDestinationCity() {
            this.desLoading = true;
            this.specificCities = [];
            this.addForm.destinationCity = "0";
            if (this.addForm.departureCity == '0') {
                this.addForm.destinationCity = 0;
            } else {
                const resDepartureCity = await this.callApi("post", "booking/getDestination", {id: this.addForm.departureCity});
                if (resDepartureCity.length == 0) {
                    this.addForm.destinationCity = 0
                    this.desLoading = false;
                } else {
                    this.addForm.destinationCity = 0;
                    this.specificCities = resDepartureCity.data;
                    this.desLoading = false;
                    $('#destinationCity').select2();
                }
            }
        },

        async closingData() {
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
            if (this.addForm.date == "" || typeof this.addForm.date == 'undefined') {
                return swal({
                    title: "OOPS!!",
                    text: "Please Select Date First ",
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

            const resData = await this.callApi("post", "booking/getClosingData", {
                scheduleId: this.addForm.schedule,
                date: this.addForm.date,
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
                departure_time: this.addForm.departure_time,
            });
            if (resData.status == 200) {
                this.buses = resData.data.buses;
                this.drivers = resData.data.drivers;
                this.hosts = resData.data.hosts;
                this.BusNo = resData.data.infoData.bus_no;
                this.dataForClose.date = resData.data.infoData.schedule_date;
                this.dataForClose.ticket_closing_id = resData.data.infoData.ticket_closing_id;
                this.dataForClose.alreadyAssigned = resData.data.infoData.alreadyAssigned;
                this.dataForClose.ticket_merge_id = resData.data.infoData.merge_id;
                this.dataForClose.schedule_detail = resData.data.infoData.schedule;
                this.dataForClose.schedule = resData.data.infoData.schedule_id;
                this.dataForClose.route_name = resData.data.infoData.route_name;
                this.dataForClose.bus = resData.data.infoData.bus;
                this.dataForClose.drivers = resData.data.infoData.drivers;
                this.dataForClose.hosts = resData.data.infoData.hosts;
                this.dataForClose.description = resData.data.infoData.description;
                this.checkCloseData = resData.data.infoData.bus == "" ? false : true;
                $(`#${this.formAddID}`).modal('show');
                setTimeout(() => {
                    $("#assignDriver").select2();
                    $("#assignHost").select2();
                }, 200);
            }
        },
        
        async busClass() {
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
            if (this.addForm.date == "" || typeof this.addForm.date == 'undefined') {
                return swal({
                    title: "OOPS!!",
                    text: "Please Select Date First ",
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

            const resData = await this.callApi("post", "booking/getBusClasses", {
                scheduleId: this.addForm.schedule,
                date: this.addForm.date,
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
            });
            if (resData.status == 200) {
                this.bus_classes = resData.data.bus_classes;
                $('#busClassModal').modal('show');
            }
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
            this.loading = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/store", {
                ...this.dataForClose, // Spread the properties from this.dataForClose (assuming it is an object)
                departureCity: this.addForm.departureCity,
                destinationCity: this.addForm.destinationCity,
                departure_time: this.addForm.departure_time,
            });
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Schedule Closed Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.editAble = true;
                this.dataForClose.bus = "";
                this.dataForClose.date = "";
                this.dataForClose.schedule = "";
                this.dataForClose.drivers = [];
                this.dataForClose.hosts = [];
                this.dataForClose.description = "";
                this.closingData();
                setTimeout(() => this.closeModal(), 1500);
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

        async updateCloseSchedule() {
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
            this.loading = true;
            this.dataForClose.mergeId = this.dataForClose.ticket_merge_id
            this.dataForClose.closingId = this.dataForClose.ticket_closing_id
            const res = await this.callApi("post", "booking/close/schedule/closing/update", this.dataForClose);
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.editAble = true;
                this.dataForClose.bus = "";
                this.dataForClose.date = "";
                this.dataForClose.schedule = "";
                this.dataForClose.drivers = [];
                this.dataForClose.hosts = [];
                this.dataForClose.description = "";
                this.closingData();
                setTimeout(() => this.closeModal(), 1500);
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
        async updateBusClass() {
            this.validationErrors = [];
            if (this.busClassData.bus_class == 0)
            {
                return swal({
                    title: "Required",
                    text: "Bus Class is required",
                    icon: 'error',
                    timer: 2000
                });
            }
          
            const res = await this.callApi("post", "booking/busclass/update", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                    dropTerminal: this.addForm.terminalId,
                    departure_time: this.addForm.departure_time,
                    bus_class: this.busClassData.bus_class,
                });
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();
                this.closeModal();
                

                
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
            if (parseInt(this.rescheduleData.dataDepartureCity) == 0) {
                this.rescheduleData.rescheduleDestinationCity = 0;
            } else {
                const resReDepartureCity = await this.callApi("post", "booking/getDestination", {id: parseInt(this.rescheduleData.dataDepartureCity)});
                if (resReDepartureCity.length == 0) {
                    this.rescheduleData.rescheduleDestinationCity = 0
                } else {
                    this.rescheduleData.rescheduleDestinationCity = 0;
                    this.reSpecificCities = resReDepartureCity.data;
                }
            }
        },

        async fetchAllSchedules() {
            this.depLoading = true;
            // const resBooking = await this.callApi("post", "booking");
            const resCity = await this.callApi("post", "booking/cities");
            const resTerminals = await this.callApi("post", "booking/terminals");
            const resClass = await this.callApi("post", "booking/fare_class");
            if (resClass.status == 200 && resCity.status == 200 && resTerminals.status == 200) {
                // this.allBookings = resBooking.data;
                this.cities = resCity.data;
                this.terminals = resTerminals.data.terminals;
                this.allSeatClasses = resClass.data;
                this.addForm.terminalId = resTerminals.data.authTerminalId;
                this.depLoading = false;
                $('#departureCity').select2();
            } else {
                this.depLoading = false;
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
                terminal: this.addForm.terminalId,
            }
            const resFetchSchedule = await this.callApi("post", "booking/fetchSchedule", data);
            if (resFetchSchedule.status == 200) {
                if (resFetchSchedule.length != 0) {
                    this.getSchedule = false;
                    this.allSchedules = resFetchSchedule.data;
                    $('#scheduleName').select2();
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
                departure_city_id: parseInt(this.rescheduleData.dataDepartureCity),
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
        async getPoints(value) {
            if (this.addForm.customerCNIC) {
                const resCnicPoints = await this.callApi("post", "booking/getPoints", {
                    cnicNumber: this.addForm.customerCNIC,
                    status: value,
                });

                this.pointsUsage = false;
                if (resCnicPoints.data != "" && resCnicPoints.status == 200) {
                    this.label = "This Customer Have a loyalty Card with " + resCnicPoints.data.starting_points + " Points";
                    this.pointsValidation = resCnicPoints.data.starting_points;
                    this.hideCheckBox = resCnicPoints.data.starting_points == 0 ? false : true;
                    this.pointsCardId = resCnicPoints.data.id;
                    this.haveLabel = true;
                }
                if (resCnicPoints.data == "" && resCnicPoints.status == 200) {
                    this.label = "";
                    this.pointsValidation = "";
                    this.hideCheckBox = false;
                    this.haveLabel = false;
                }
                if (resCnicPoints.status == 201) {
                    this.label = resCnicPoints.data.expiredData;
                    this.pointsValidation = "";
                    this.hideCheckBox = false;
                    this.haveLabel = true;
                }
                if (resCnicPoints.status == 404) {
                    this.label = "";
                    this.pointsValidation = "";
                    this.hideCheckBox = false;
                    this.haveLabel = false;
                }
            }
        },

        async revertOverIssueFunction(item) {
            const resFinalRevert = await this.callApi("post", "booking/revert/over/issue/seat", {
                ticket_id: item.id,
                schedule_id: item.schedule_id,
                schedule_date: item.schedule_date,
                seat_no: item.seat_no,
            });
            this.loadingRevertButton = true;
            if (resFinalRevert.status == 200) {
                this.loadingRevertButton = false
                swal({
                    title: "Success!",
                    text: "Seat Revert Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.fetchScheduleData();

            } else if (resFinalRevert.status == 422) {
                this.loadingRevertButton = false
                let errorContent = "";
                let count = 0;
                for (const key in resFinalRevert.data.errors) {
                    resFinalRevert.data.errors[key].forEach((element) => {
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

        async usePoints(e) {
            if (e.target.checked) {
                const resUsagePoints = await this.callApi("post", "booking/usagePoints", {
                    id: this.pointsCardId,
                    points: this.pointsCardId,
                });
                if (resUsagePoints.status == 200) {
                    this.pointsUsage = "You Have " + resUsagePoints.data;
                    this.checkedUsagePoints = true;
                } else {
                    this.pointsUsage = "";
                    this.addForm.pointsUseInput = "";
                    this.checkedUsagePoints = false;
                }
            } else {
                this.pointsUsage = "";
                this.checkedUsagePoints = false;
            }
        },


        async getCustomer(flag) {
            if (flag == 'addFormCNIC') {
                if (this.addForm.customerCNIC != '' && this.addForm.customerCNIC != 'undefined') {
                    // this.addForm.contact = "";
                    // this.addForm.customerName = "";
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        cnicNumber: this.addForm.customerCNIC,
                        status: flag,

                    });
                    if (resCnic.data) {
                        this.addForm.contact = resCnic.data.contact;
                        this.addForm.customerName = resCnic.data.name;
                    }
                }
            }
            if (flag == 'addFormContact') {
                if (this.addForm.contact != '' && this.addForm.contact != 'undefined') {
                    // this.addForm.customerName = "";
                    // this.addForm.customerCNIC = "";
                    const resCnic = await this.callApi("post", "booking/getCNIC", {
                        phoneNumber: this.addForm.contact,
                        status: flag,
                    });
                    if (resCnic.data) {
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
                this.addForm.totalAmount = parseFloat(this.addForm.totalFare) - (this.addForm.discount ? (this.addForm.discount) : this.addForm.totalFare)
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
        isNumberDiscount(event) {
            const charCode = event.which ? event.which : event.keyCode;
            if (
                (charCode < 48 || charCode > 57) && // Not a digit
                charCode !== 45 // Not a minus sign
            ) {
                event.preventDefault();
            }
        },
        changeToUpperCase: function (string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        async fetchScheduleData() {
            if (this.addForm.terminalId == 0 && this.$store.state.user.terminal_id == null) {
                return swal({
                    title: "Required!",
                    text: "Terminal is Required! Please Select it From DropDown or Assign Terminal to your Account",
                    icon: "error",
                    timer: 2000
                });
            }
            this.getSchedule = true;
            this.resetArrays();
            this.schedule = [];
            this.addForm.otp_valid = false;
            this.addForm.otp = "";
            this.addForm.otp_cnic = "";
            this.addForm.customerCNIC = "";
            this.pointsUsage = "";
            this.checkedUsagePoints = false;
            this.hideCheckBox = false;
            this.haveLabel = false;
            this.addForm.customerName = "";
            this.addForm.contact = "";
            this.addForm.flag = 0;
            this.addForm.alreadyBookedId = [];
            this.addForm.reservedFare = [];
            this.addForm.advanceSeatClass = [];
            this.addForm.remarks = "";
            this.addForm.totalFare = 0;
            this.selectedSeats = [];
            this.selectedSeatsFare = [];
            this.selectedSeatsClass = [];
            this.selectedOverIssueSeats = [];
            this.addForm.selectedSeats = [];
            this.addForm.selectedSeatsFare = [];
            this.addForm.selectedSeatsClass = [];
            this.addForm.totalAmount = 0;
            this.addForm.discount = '';
            this.advanceSeat = [];
            this.validationErrors = [];
            this.loading = true;
            this.showBookingDiv = false;
            this.checkedUsagePoints = false;
            if (this.addForm.schedule != 0 && this.addForm.date && this.addForm.departureCity != 0 && this.addForm.destinationCity != 0) {
                const resSelected = await this.callApi("post", "booking/schedule/selected", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                    dropTerminal: this.addForm.terminalId,
                    departure_time: this.addForm.departure_time,
                });
                if (resSelected.status == 200) {
                    this.loading = false
                    this.showBookingDiv = true;
                    this.schedule = resSelected.data;
                    this.totalSeats = 0;
                    this.BusNo = resSelected.data.bus_no;
                    this.totalSeatsBooked = 0;
                    this.auth_terminal = resSelected.data.auth_terminal;
                    this.totalSeatsIssued = 0;
                    this.totalSeatsAvailable = 0;
                    for (let i = 0; i < resSelected.data.bus_class.seat_map.length; i++) {
                        for (let j = 0; j < resSelected.data.bus_class.seat_map[i].length; j++) {
                            if (resSelected.data.bus_class.seat_map[i][j].hasOwnProperty("seatNo") && resSelected.data.bus_class.seat_map[i][j].type !== "not_for_sale") {
                                this.totalSeats++;
                            }
                            if (resSelected.data.bus_class.seat_map[i][j].type == 'booked') {
                                this.totalSeatsBooked++;
                            }
                            if (resSelected.data.bus_class.seat_map[i][j].type == 'advance booking') {
                                this.totalSeatsIssued++;
                            }
                        }
                    }
                    this.totalSeatsAvailable = this.totalSeats - (this.totalSeatsBooked + this.totalSeatsIssued);
                }
                this.addForm.customerCNIC = "";
                this.addForm.customerName = "";
                this.addForm.contact = "";
                this.addForm.flag = 0;
                this.addForm.alreadyBookedId = [];
                this.addForm.reservedFare = [];
                this.addForm.advanceSeatClass = [];
                this.addForm.remarks = "";
                this.addForm.totalFare = 0;
                this.selectedSeats = [];
                this.selectedSeatsFare = [];
                this.selectedSeatsClass = [];
                this.addForm.selectedSeatsFare = [];
                this.selectedBookedSeats = [];
                this.selectedOverIssueSeats = [];
                this.addForm.selectedSeatsClass = [];
                this.addForm.totalAmount = 0;
                this.addForm.discount = '';
                const terminalSeats = await this.callApi("post", "booking/terminal/seats", {
                    terminal_id: this.$store.state.user.terminal_id,
                });
                const restDiscount = await this.callApi("post", "booking/schedule/terminal/discount/fetch", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                    dropTerminal: this.addForm.terminalId,
                });
                const resFetchDiscountSurcharge = await this.callApi("post", "booking/discount/surcharge/fetch", {
                    schedule_id: this.addForm.schedule,
                });
                const responseEltDetails = await this.callApi("post", "booking/booked/seats/elt/detail", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });

                const resFetchOverIssueSeat = await this.callApi("post", "booking/fetch/over/issue/seat", {
                    id: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });
                if (resFetchOverIssueSeat.status == 200) {
                    this.overIssueSeatsRevert = resFetchOverIssueSeat.data
                    if (resFetchOverIssueSeat.data.length == 0) {
                        this.closeOverIssueDetail();
                    }
                } else {
                    console.log(resFetchOverIssueSeat);
                }

                if (responseEltDetails.status == 200) {
                    this.eltDetailsModel = responseEltDetails.data
                } else if (responseEltDetails.status == 204) {
                    this.eltDetailsModel = [];
                }
                // Fetch Discount and Surcharge  against schedule

                if (resFetchDiscountSurcharge.status == 200) {
                    this.appliedDiscount = resFetchDiscountSurcharge.data.discount ? (resFetchDiscountSurcharge.data.discount.type == 'percentage' ? resFetchDiscountSurcharge.data.discount.percentage + '%' : resFetchDiscountSurcharge.data.discount.flat) : 'N/A';
                    this.appliedSurcharge = resFetchDiscountSurcharge.data.surcharge ? (resFetchDiscountSurcharge.data.surcharge.type == 'percentage' ? resFetchDiscountSurcharge.data.surcharge.percentage + '%' : resFetchDiscountSurcharge.data.surcharge.flat) : 'N/A';
                }

                if (restDiscount.status == 200) {
                    this.terminalDiscount = restDiscount.data.discount ? restDiscount.data.discount + '%' : 'N/A';
                }

                if (terminalSeats.status == 200) {
                    this.allowedSeats = terminalSeats.data;
                } else if (terminalSeats.status == 204) {
                    this.allowedSeats = 0;
                }

                

                if (resSelected.status == 500 && this.addForm.schedule == 0) {
                    this.getSchedule = false;
                    this.loading = true
                    this.showBookingDiv = false;
                }
                if (resSelected.status == 422) {
                    this.getSchedule = false;
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
                this.getSchedule = false;
            }
        },
        async revertDropSchedule () {

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
            const data = {
                departure_city_id: this.addForm.departureCity,
                destination_city_id: this.addForm.destinationCity,
                date: this.addForm.date,
                schedule_id: this.addForm.schedule,
            }
            this.revertScheduleButton = true;
            const revertDropSchedule = await this.callApi("post", "booking/revertDropSchedule", data);
            if (revertDropSchedule.status == 200) {
                this.revertScheduleButton = false;
                this.busDropCheck();
                swal({
                    title: "Success",
                    text: "Schedule Revert Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.busDropCheck();
            }
            if (revertDropSchedule.status == 422) {
                this.revertScheduleButton = false;
                let errorContent = "";
                let count = 0;
                for (const key in revertDropSchedule.data.errors) {
                    revertDropSchedule.data.errors[key].forEach((element) => {
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
                departure_time: this.addForm.departure_time,
            });
            if (resDropCheck.status == 200) {
                if (resDropCheck.data.checkDrop) {
                    this.labelDrop = "This schedule is dropped by " + resDropCheck.data.checkDrop.drop_by.name??'N/A';
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
                departureCity: parseInt(this.rescheduleData.dataDepartureCity),
                destinationCity: this.rescheduleData.rescheduleDestinationCity,
                dropTerminal: this.addForm.terminalId,
                departure_time: this.rescheduleData.departure_time,
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
            this.runUpdateFun = true;
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
                    this.checkSameType.splice(index, 1);
                    this.bookedSeats = this.bookedSeats.filter((seat) => {
                        if (seat.seatNo != seatNo) {
                            return seat;
                        }
                    });
                    this.addForm.totalFare -= parseFloat(this.schedule.bus_class.seat_map[row][col].fare);
                } else {
                    
                    this.schedule.bus_class.seat_map[row][col].selected = true;
                    this.checkSameType.push(this.schedule.bus_class.seat_map[row][col].type);
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
             
                this.runUpdateFun = false;
                this.fetchScheduleData();
                this.resetArrays();
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
                this.runUpdateFun = false;
                this.fetchScheduleData();
                this.resetArrays();
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
            if(this.runUpdateFun == true)
            {
                if (data.type == 'advance booking' && data.type != 0 && data.type != 'booked') {
                    let index = this.advanceSeat.indexOf(data.seatNo);
                    if (index != -1) {
                        this.addForm.selectedSeats.splice(index, 1);
                        this.advanceSeat.splice(index, 1);
                        this.addForm.alreadyBookedId.splice(index, 1);
                        this.addForm.reservedFare.splice(index, 1);
                        this.addForm.advanceSeatClass.splice(index, 1);
                        this.addForm.customerName = "";
                        this.addForm.customerCNIC = "";
                        this.addForm.contact = "";
                        if(this.advanceSeat.length == 0)
                        {
                            this.addForm.flag = 0;
                        }
                        this.getPoints('addFormCNIC')
                    } else {
                        this.addForm.alreadyBookedId.push(data.id);
                        this.addForm.reservedFare.push(data.fare);
                        this.addForm.advanceSeatClass.push(data.class);
                        if(this.auth_terminal.other_terminal_passenger_detail == 1 || data.terminal == this.auth_terminal.id)
                        {
                            this.addForm.customerCNIC = data.customer_cnic != 0 ? data.customer_cnic : "";
                            this.addForm.customerName = data.customer_name;
                            this.addForm.contact = data.customer_phone;
                        }
                        // this.addForm.remarks = data.remarks;
                        this.addForm.selectedSeats.push(data.seatNo);
                        this.advanceSeat.push(data.seatNo)
                        this.addForm.flag = 1;
                        this.getPoints('addFormCNIC')
                    }
                }
            }
            // this validation only for if types is different selected liked booked or advance booking mixed
            const uniqueArray = [...new Set(this.checkSameType)];
            if(uniqueArray.length > 1)
            {
                this.fetchScheduleData();
                this.resetArrays();
                return swal({
                    title: "Oops",
                    text: "Invalid Seat Combination",
                    icon: "error",
                    timer: 2000
                });
            }
           
        },

        reScheduleSelectSeat: function (row, col, data) {
            if (this.reScheduleSeatMap.bus_class.seat_map[row][col].type == 0
                || this.reScheduleSeatMap.bus_class.seat_map[row][col].type == undefined) 
            {
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
            let gender = "";
            if(col.online_terminal == 0 && col.gender == 1 && col.type == "advance booking")
            {
                gender = "for-male-reserved";
            }
            else if(col.online_terminal == 0 && col.gender == 1 && col.type == "booked")
            {
                gender = "for-male-booked";
            }
            else if(col.online_terminal == 0 && col.gender == 0 && col.type == "advance booking")
            {
                gender = "for-female-reserved";
            }
            else if(col.online_terminal == 0 && col.gender == 0 && col.type == "booked")
            {
                gender = "for-female-booked";
            }

            else if(col.online_terminal == 1 && col.gender == 1 && col.type == "advance booking")
            {
                gender = "for-online-male-reserved";
            }
            else if(col.online_terminal == 1 && col.gender == 1 && col.type == "booked")
            {
                gender = "for-online-male-booked";
            }
            else if(col.online_terminal == 1 && col.gender == 0 && col.type == "advance booking")
            {
                gender = "for-online-female-reserved";
            }
            else if(col.online_terminal == 1 && col.gender == 0 && col.type == "booked")
            {
                gender = "for-online-female-booked";
            }
            else
            {
                gender = "";
            }
            
            let selected = col.selected ? "selected" : "";
            let partial = col.partial == 1 ? "partial" : "";
            let over = col.type == 'over-issue' ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        },

        getClassesReschedule: function (col) {
            let gender = "";
            if(col.online_terminal == 0 && col.gender == 1 && col.type == "advance booking")
            {
                gender = "for-male-reserved";
            }
            else if(col.online_terminal == 0 && col.gender == 1 && col.type == "booked")
            {
                gender = "for-male-booked";
            }
            else if(col.online_terminal == 0 && col.gender == 0 && col.type == "advance booking")
            {
                gender = "for-female-reserved";
            }
            else if(col.online_terminal == 0 && col.gender == 0 && col.type == "booked")
            {
                gender = "for-female-booked";
            }

            else if(col.online_terminal == 1 && col.gender == 1 && col.type == "advance booking")
            {
                gender = "for-online-male-reserved";
            }
            else if(col.online_terminal == 1 && col.gender == 1 && col.type == "booked")
            {
                gender = "for-online-male-booked";
            }
            else if(col.online_terminal == 1 && col.gender == 0 && col.type == "advance booking")
            {
                gender = "for-online-female-reserved";
            }
            else if(col.online_terminal == 1 && col.gender == 0 && col.type == "booked")
            {
                gender = "for-online-female-booked";
            }
            else
            {
                gender = "";
            }
            let selected = col.alreadyBooked ? "selected" : "";
            let partial = col.partial == 1 ? "partial" : "";
            let over = col.type == 'over-issue' ? "bg-secondary" : "";
            let disabledSeat = col.type == 'not_for_sale' ? 'not-for-sale' : "";
            return gender + " " + selected + " " + partial + " " + over + " " + disabledSeat;
        },

        getTitle: function (col) {
            if (col.type == 'booked' || col.type == 'advance booking' || col.type == 'over-issue' || col.id) {
                return "Name : " + col.customer_name + '\n' + "Phone : " + (this.auth_terminal.other_terminal_passenger_detail == 1 || col.terminal == this.auth_terminal.id ? col.customer_phone : "---") + '\n' + "Seat Fare : " + col.seat_fare + '\n' + "Remarks : " + col.remarks + '\n' + "Booked By : " + col.booked_by + '\n' + "Dept City : " + col.departure_city_name + '\n' + "Dest City : " + col.destination_city_name;
            }
        },

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
            if ((this.addForm.customerCNIC == '' || this.addForm.customerCNIC.length < 13) && this.addForm.type != 'advance booking') {
                return swal({
                    title: "Required!",
                    text: "13 Digit CNIC is Required ",
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
            if (!this.addForm.contact || this.addForm.contact.length < 11) {
                return swal({
                    title: "Required!",
                    text: "11 Digit Contact Number is required,",
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
            if (this.addForm.pointsUseInput > this.pointsValidation) {
                return swal({
                    title: "OOPS!",
                    text: "Enter Numbers of points must be less then the points Card have",
                    icon: "error",
                    timer: 2000
                });
            }
            this.addForm.pointsCardId = this.pointsCardId;
            this.addForm.usagePoints = this.checkedUsagePoints;

        
            
            if(this.addForm.usagePoints == true && this.addForm.otp_valid == false)
            {
                return swal({
                    title: "OOPS!",
                    text: "Please verify otp to use loyalty card otherwise uncheck the box",
                    icon: "error",
                    timer: 2000
                });
            }
            if(this.addForm.usagePoints == true && this.addForm.otp_valid == false && this.addForm.otp_cnic != this.addForm.customerCNIC)
            {
                return swal({
                    title: "OOPS!",
                    text: "Cnic changed please verify it",
                    icon: "error",
                    timer: 2000
                });
            }
            if(this.bookingLoading)
            {
                return swal({
                    title: "OOPS!",
                    text: "Please Wait",
                    icon: "error",
                    timer: 2000
                });
            }
            this.bookingLoading = true;
            const resTicket = await this.callApi("post", "booking/store", this.addForm);
            if (resTicket.status == 200) {
                iziToast.success({
                    title: 'Success!',
                    message: 'Booking Created Successfully',
                    position: 'topRight',
                    hideAfter: 1000
                });
                const bookType = this.addForm.type;
                const departureTime = this.addForm.departure_time;
                const date = this.addForm.date;
                this.addForm = {
                    totalAmount: 0,
                    discount: '',
                    totalFare: 0,
                    customerName: '',
                    contact: '',
                    remarks: '',
                    gender: "1",
                    customerCNIC: "",
                };
                
                this.label = "";
                this.hideCheckBox = false;
                this.haveLabel = false;
                this.pointsUsage = false;
                this.ticketsIds = resTicket.data.ids;
                this.addForm.date = date;
                this.addForm.terminalId = resTicket.data.authTerminalId;
                this.addForm.gender = 1;
                this.addForm.type = bookType;
                this.addForm.departure_time = departureTime;
                this.addForm.schedule = resTicket.data.ticket[0].schedule_id;
                this.addForm.destinationCity = parseInt(resTicket.data.ticket[0].destination_city_id);
                this.addForm.departureCity = parseInt(resTicket.data.ticket[0].departure_city_id);
                this.selectedSeats.length = 0; 
                this.addForm.alreadyBookedId = [];           
                this.addForm.reservedFare = [];           
                this.addForm.advanceSeatClass = [];           
                this.fetchScheduleData();
                this.resetArrays();

                 setTimeout(() => {
                    this.bookingLoading = false;
                }, 1000);
                setTimeout(() => {
                    if (resTicket.data.ticket[0].type == "booked") {
                        this.$refs.refTicket.submit();
                    }
                }, 700);

                const resBookingDetail = await this.callApi("post", "booking/whatsapp/message", {invoice_id: resTicket.data.ticket[0].invoice_id,type:bookType});


            } else {``
                if (resTicket.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in resTicket.data.errors) {
                        resTicket.data.errors[key].forEach((element) => {
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
                            timer: 5000
                        });

                    }
                }
                setTimeout(() => {
                    this.bookingLoading = false;
                }, 1000);
            }
        },
        
        async resendSms(invoice_id,type) {
            const resMessage = await this.callApi("post", "booking/whatsapp/message", {invoice_id: invoice_id,type:type});
            if (resMessage.status == 200) {
                swal({
                    title: "Success",
                    text: "Sent Succesfuly",
                    icon: "success",
                    timer: 2000
                });
            }
        },
        
        async sendOtp() {
            if(!this.addForm.customerCNIC)
            {
                return swal({
                    title: "OOPS!",
                    text: "Please enter valid cnic",
                    icon: "error",
                    timer: 2000
                });
            }
            this.otpLoader = true;
            const resMessage = await this.callApi("post", "booking/send-otp",this.addForm)
            if (resMessage.status == 200) {
                swal({
                    title: "Success",
                    text: "Sent Succesfuly",
                    icon: "success",
                    timer: 2000
                });
            }
            if(resMessage.status == 409)
            {
                swal({
                    title: "OOPS!",
                    text: resMessage.data.error.join("\n"), 
                    icon: "error",
                    timer: 2000
                });
                console.log(resMessage.data.error);
            }
            this.otpLoader = false;
        },
        
        async verifyOtp() {
            if (!this.addForm.otp || this.addForm.otp.length !== 6) {
                return swal({
                    title: "OOPS!",
                    text: "Please enter a valid 6-digit OTP",
                    icon: "error",
                    timer: 2000
                });
            }
            this.otpLoader = true;
            const resMessage = await this.callApi("post", "booking/verify-otp",this.addForm)
            if (resMessage.status == 200) {
                this.addForm.otp_valid = true;
                this.addForm.otp_cnic = this.addForm.customerCNIC;
                swal({
                    title: "Success",
                    text: "Verified Successfully",
                    icon: "success",
                    timer: 2000
                });
            }
            if(resMessage.status == 409)
            {
                this.addForm.otp_valid = false
                swal({
                    title: "OOPS!",
                    text: resMessage.data.error.join("\n"), 
                    icon: "error",
                    timer: 2000
                });
                console.log(resMessage.data.error);
            }
            this.otpLoader = false;
        },

        async resetArrays() {
            this.selectedSeats = [];
            this.schedule = [];
            this.selectedSeatsFare = [];
            this.selectedSeatsClass = [];
            this.checkSameType = [];
            this.selectedBookedSeats = [];
            this.selectedOverIssueSeats = [];
            this.selectedBookedOverIssueSeats = [];
            this.addForm.selectedSeats = [];
            this.addForm.selectedBookedSeats = [];
            this.addForm.selectedOverIssueSeats = [];
            this.addForm.alreadyBookedId = [];
            this.addForm.reservedFare = [];
            this.addForm.advanceSeatClass = [];
            this.advanceSeat = [];
            this.addForm.selectedBookedOverIssueSeats = [];
            this.bookedSeats = [];
            this.bookedOverIssueSeats = [];
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
                dataType: data.type,
            }
            $("#cancelModel").modal('show');
        },
        
        cancelAllModal: function () {
            $("#cancel_all_ticket").modal('show');
        }
        ,

        async cancelBooking(dataEnter) {
            this.cancelLoading = true;
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
                this.cancelLoading = false;
                swal({
                    title: "Success",
                    text: "Booking Canceled Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.addForm.flag = 0;
                this.fetchScheduleData();
                this.resetArrays();
                this.closeModal();
                const resBookingDetail = await this.callApi("post", "booking/whatsapp/cancel/message", {tickets: resCancelBooking.data.tickets});
            }
            if (resCancelBooking.status == 422) {
                this.dropScheduleButton = false;
                let errorContent = "";
                let count = 0;
                for (const key in resCancelBooking.data.errors) {
                    resCancelBooking.data.errors[key].forEach((element) => {
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
            this.cancelLoading = false;
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
                this.closeModal();
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
        async passDataToEltModel(data) {
            this.eltData = {
                dataDate: data.date,
                dataCustomer: data.customer_id,
                dataSchedule: data.schedule_id,
                dataDeparture: data.departure_city_id,
                dataDestination: data.destination_city_id,
                dataSeat_no: data.seat_no,
                dataSeatFare: data.seat_fare,
            }
            const resELT = await this.callApi("post", "booking/elt/fetch/old", data);
            if (resELT.status == 200) {
                this.editAbleELT = true;
                this.eltData.eltWeight = resELT.data.elt_weight;
                this.eltData.eltPrice = resELT.data.elt_price;
                this.eltData.dataDescription = resELT.data.elt_description;
                this.eltData.alreadyExist = resELT.data.alreadyExist;

            } else if (resELT.status == 204) {
                this.editAbleELT = false;
                this.eltData.eltWeight = '';
                this.eltData.eltPrice = '';
                this.eltData.dataDescription = '';
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
                alreadyExist: dataEnter.alreadyExist,
            }
            this.EltButton = true;
            const resOverIssue = await this.callApi("post", "booking/elt", data);
            if (resOverIssue.status == 201) {
                this.EltButton = false;
                this.eltIds = resOverIssue.data.id
                this.fetchScheduleData();
                setTimeout(() => {
                    this.$refs.refElt.submit();
                }, 700);
                swal({
                    title: "Success",
                    text: "ELT Added Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.closeModal();
            }
            if (resOverIssue.status == 200) {
                this.EltButton = false;
                this.eltIds = resOverIssue.data.id
                this.fetchScheduleData();
                setTimeout(() => {
                    this.$refs.refElt.submit();
                }, 700);
                swal({
                    title: "Success",
                    text: "ELT Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.closeModal();
            }
            if (resOverIssue.status == 422 && resOverIssue.data.message) {
                this.EltButton = false;
                swal({
                    title: "Error",
                    text: resOverIssue.data.message,
                    icon: "error",
                    timer: 2000
                });
                this.closeModal();
            }

            if (resOverIssue.status == 422) {
                this.EltButton = false;
                this.closeModal();
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
                    dataDepartureCity: parseInt(singleSeat[1][0].departure_city_id),
                    dataAll: singleSeat[1][0],
                }
                arraySingleRescheduleData[i] = singlePostData;
                totalFare += (parseFloat(singleSeat[1][0].seat_fare) - parseFloat(singleSeat[1][0].discount ?? 0));
                oldSeats.push(singleSeat[1][0].seat_no);
                arraySingleRescheduleData['totalFare'] = totalFare;
                arraySingleRescheduleData['oldSeats'] = oldSeats;
            });
            this.mainAllRescheduleData = arraySingleRescheduleData;
            this.rescheduleData.dataDepartureCity = parseInt(this.mainAllRescheduleData[0].dataDepartureCity);
            this.rescheduleData.rescheduleDate = this.mainAllRescheduleData[0].rescheduleDate;
            this.rescheduleData.rescheduleSchedule = 0;

            if (parseInt(this.rescheduleData.dataDepartureCity) == 0) {
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
                dataSeatDiscount: data.discount,
                dataAll: data,
            }
            this.mainAllRescheduleData[0] = this.rescheduleData;
            this.mainAllRescheduleData.totalFare = parseInt(this.rescheduleData.dataSeatFare) - parseInt(this.rescheduleData.dataSeatDiscount);
            this.mainAllRescheduleData.oldSeats = this.rescheduleData.dataSeat_no;
            if (parseInt(this.rescheduleData.dataDepartureCity) == 0) {
                this.rescheduleData.rescheduleDestinationCity = 0;
            } else {
                const resReDepartureCity = await this.callApi("post", "booking/getDestination", {id: parseInt(this.rescheduleData.dataDepartureCity)});
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
                this.closeModal();
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

            if (parseInt(this.rescheduleData.dataDepartureCity) == 0) {
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
                single.rescheduleSchedule = this.rescheduleData.rescheduleSchedule;
                single.rescheduleType = this.rescheduleSeatType;
                single.overIssueReschedule = this.overIssueScheduleCheckBox;
                single.newDepartureTime = this.rescheduleData.rescheduleSchedule;
                single.rescheduleDiscount = this.rescheduleDiscount;
                single.dataDepartureCity = parseInt(this.rescheduleData.dataDepartureCity);
                single.dataDestination = this.rescheduleData.rescheduleDestinationCity;
                single.departure_time = this.rescheduleData.departure_time;
            });
            this.loadingRescheduleButton = true;
            const resReschedule = await this.callApi("post", "booking/store", {'data': this.mainAllRescheduleData,"rc_flag": 1});
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
                this.closeModal();
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
                if (data.type == "booked" || data.type == "over-issue") {
                    this.$refs.refDuplicateTicket.submit();
                    this.closeModal();
                } else {
                    swal({
                        title: "OOppss!!!",
                        text: "Please Confirm Seat for Duplicate Ticket",
                        icon: "error",
                        timer: 2000
                    });
                    this.closeModal();
                }
            }, 700);
        }
        ,
        // Duplicate All Ticket
        allTicketDuplicate: function () {
            this.duplicateAllTicket =  this.duplicateAllTicket.join('-');
           setTimeout(() => {
               this.$refs.refDuplicateAllTicket.submit();
               this.closeModal();
           }, 700);
                
        }
        ,

        // Get Passengers list
        async getCustomerList() {
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
            if (this.checkForSubmenuButtons('check-assigned-bus')) {
                // check Buss Assigned or not
                const resCheckedBus = await this.callApi("post", "booking/check/bus/assigned", {
                    scheduleId: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });
                if (resCheckedBus.status == 200) {
                    this.$refs.refPassengerList.submit();
                } else if (resCheckedBus.status == 204) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Assign Bus First!",
                        icon: "error",
                        timer: 2000
                    });
                }
            } else {
                this.$refs.refPassengerList.submit();
            }
        }
        ,
        // Get Terminal Invoice
        async getTerminalInvoice() {
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
            if (this.addForm.terminalId == 0 && this.$store.state.user.terminal_id == null) {
                return swal({
                    title: "Required!",
                    text: "Terminal is Required! Please Select it From DropDown or Assign Terminal to your Account",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.checkForSubmenuButtons('check-assigned-bus')) {
                // check Buss Assigned or not
                const resCheckedBus = await this.callApi("post", "booking/check/bus/assigned", {
                    scheduleId: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });
                if (resCheckedBus.status == 200) {
                    this.$refs.refTerminalInvoice.submit();
                } else if (resCheckedBus.status == 204) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Assign Bus First!",
                        icon: "error",
                        timer: 2000
                    });
                }
            } else {
                this.$refs.refTerminalInvoice.submit();
            }
        }
        ,
        // Get Bus Invoice
        async getBusInvoice() {

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
            if (this.checkForSubmenuButtons('check-assign-bus')) {
                // check Buss Assigned or not
                const resCheckedBus = await this.callApi("post", "booking/check/bus/assigned", {
                    scheduleId: this.addForm.schedule,
                    date: this.addForm.date,
                    departureCity: this.addForm.departureCity,
                    destinationCity: this.addForm.destinationCity,
                });
                if (resCheckedBus.status == 200) {
                    this.$refs.refBusInvoice.submit();
                } else if (resCheckedBus.status == 204) {
                    return swal({
                        title: "OOPS!!",
                        text: "Please Assign Bus First!",
                        icon: "error",
                        timer: 2000
                    });
                }
            } else {
                this.$refs.refBusInvoice.submit();
            }

        },
    },

    watch: {
        'addForm.terminalId': function (newVal) {
            this.$store.state.user.terminal_id = newVal;
        }
    },

    computed: {
        disabledOptions() {
            const now = new Date();
            return this.allSchedules.filter(option => new Date(option.departure_date + ' ' + option.departure_time) < now);
        },
        disabledOptionsReschedule() {
            const now = new Date();
            return this.allReSchedules.filter(option => new Date(option.departure_date + ' ' + option.departure_time) < now);
        },
    },
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

.for-male-reserved {
    background-color: #3d8ff2 !important;
}
.for-female-reserved {
    background-color: hotpink !important;
}
.for-male-booked {
    background-color: #731631 !important;
}
.for-female-booked {
    background-color: #ff7276 !important;
}

.for-online-male-reserved {
    background-color: #0c6077 !important;
}
.for-online-female-reserved {
    background-color: #9d92f0 !important;
}
.for-online-male-booked {
    background-color: #343434 !important;
}
.for-online-female-booked {
    background-color: #d71e7a !important;
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
    line-height: 1.01;
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
