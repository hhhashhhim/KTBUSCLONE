<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Terminals</h4>
                            <div class="card-header-action">
                                <a title="Add new Terminal"
                                    href="#add-modal"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Terminal
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="terminal_table"
                                        >
                                            <thead>
                                            <tr>
                                                <th>Sr No.</th>
                                                <th>City Name</th>
                                                <th>No.of Terminals</th>
                                                <th>Added By</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(terminal, i) in terminals" :key="i">
                                                <td>{{ i + 1 }}</td>
                                                <td>{{ terminal.name }}</td>
                                                <td>{{ terminal.terminal_count }}</td>
                                                <td>{{ terminal.added_by.name }}</td>
                                                <td>
                                                    <button title="View Terminals"
                                                        data-target="#detail-modal"
                                                        data-toggle="modal"
                                                        @click="terminalDetail(terminal.id); datatableReset()"
                                                        class="btn btn-info mx-2"
                                                    >
                                                        <i class="far fa-eye"></i>
                                                    </button>
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
            </div>

            <!-- Add Modal -->
            <Add
                heading="New Terminal"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="city_id">Terminal City <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="data.city_id">
                            <option value="0">Select City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{ city.name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Terminal Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="data.name" placeholder="Enter Terminal Name">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4 mt-4 pt-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="comma_separated" name="valueType"
                                   checked="" value="comma" v-model="dataCheck.seatNumberType"
                                   @click="applyMaks('comma')">
                            <label class="form-check-label" for="comma_separated">
                                Comma Separated
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="seat_range_dash" name="valueType"
                                   value="dash" v-model="dataCheck.seatNumberType" @click="applyMaks('dash')">
                            <label class="form-check-label" for="seat_range_dash">
                                Seat Range
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-4" v-if="showDivComma">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="data.available_seats"
                            mask="00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,"
                            :raw="false"
                            :options="optionComma">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4" v-if="showDivDash">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="data.available_seats"
                            mask="00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,"
                            :raw="false"
                            :options="optionDash">
                        </vue-mask>
                    </div>
<!--                </div>-->
<!--                <div class="row">-->
<!--                    <div class="form-group col-md-3 mt-4 pt-3">-->
<!--                        <div class="form-check form-check-inline">-->
<!--                            <input class="form-check-input" type="radio" id="positive_time" name="terminalTime"-->
<!--                                   checked="" value="positiveTime" v-model="dataTime.time"-->
<!--                                   @click="applyTimeMaks('positive')">-->
<!--                            <label class="form-check-label" for="positive_time">-->
<!--                                Positive-->
<!--                            </label>-->
<!--                        </div>-->
<!--                        <div class="form-check form-check-inline">-->
<!--                            <input class="form-check-input" type="radio" id="negative_time" name="terminalTime"-->
<!--                                   value="negativeTime" v-model="dataTime.time" @click="applyTimeMaks('negative')">-->
<!--                            <label class="form-check-label" for="negative_time">-->
<!--                                Negative-->
<!--                            </label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-5" v-if="showDivPositive">-->
<!--                        <label for="time_difference">Time Difference ( eg HH:MM )</label>-->
<!--                        <vue-mask-->
<!--                            class="form-control"-->
<!--                            v-model="data.time_difference"-->
<!--                            mask="00:00"-->
<!--                            :raw="false"-->
<!--                            :options="optionsPositive">-->
<!--                        </vue-mask>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-5" v-if="showDivNegative">-->
<!--                        <label for="time_difference">Time Difference ( eg HH:MM )</label>-->
<!--                        <vue-mask-->
<!--                            class="form-control"-->
<!--                            v-model="data.time_difference"-->
<!--                            mask="-00:00"-->
<!--                            :raw="false"-->
<!--                            :options="optionsNegative">-->
<!--                        </vue-mask>-->
<!--                    </div>-->
                    <div class="form-group col-md-4">
                        <label for="contact">Terminal Contact <span class="text-danger ml-1">*</span> </label>
                        <vue-mask
                            class="form-control"
                            v-model="data.contact"
                            mask="0000-0000000"
                            :raw="false"
                            :options="optionsContact">
                        </vue-mask>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="address">Address <span class="text-danger ml-2">*</span></label>
                        <textarea class="form-control" spellcheck="false" v-model="data.address" maxlength="140"
                                  placeholder="Address Must be less then 140 characters or 21 words"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="advance_booking">Advance Booking Allowed(Days)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                               v-model="data.advance_booking" placeholder="Enter Advance Booking Allowed">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" v-model="data.longitude" placeholder="Enter Longitude">
                        <small><a href="https://www.google.com/maps" target="_blank">Click Here to get</a></small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="Latitude">Latitude</label>
                        <input type="text" class="form-control" v-model="data.latitude" placeholder="Enter Latitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="online_terminal_name">Online Terminal Name</label>
                        <input type="text" class="form-control" v-model="data.online_terminal_name">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="active">Online Availability</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="data.active"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="sms">SMS</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="data.active_sms"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
<!--                    <div class="form-group col-md-2 d-flex align-items-center">-->
<!--                        <label class="mt-4" for="sms">Main Terminal</label>-->
<!--                        <label class="colorinput mx-3 mt-3">-->
<!--                            <span>-->
<!--                                <input type="checkbox" class="colorinput-input" v-model="data.is_main"/>-->
<!--                                <span class="colorinput-color bg-primary"></span>-->
<!--                            </span>-->
<!--                        </label>-->
<!--                    </div>-->
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                        {{ loading ? 'Loading...' : 'Add New Terminal' }}
                    </button>
                </template>
            </Add>

            <!-- Edit Modal -->
            <Edit
                heading="Edit terminal"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"

            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="city_id">Terminal City <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="dataEdit.city_id">
                            <option value="0">Select City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{ city.name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Terminal Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4 mt-4 pt-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="editComma_separated" name="editValueType"
                                   checked="" value="comma" v-model="dataEditCheck.seatNumberType"
                                   @click="editApplyMaks('comma')">
                            <label class="form-check-label" for="editComma_separated">
                                Comma Separated
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="editSeat_range_dash" name="editValueType"
                                   value="dash" v-model="dataEditCheck.seatNumberType" @click="editApplyMaks('dash')">
                            <label class="form-check-label" for="editSeat_range_dash">
                                Seat Range
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-4" v-if="showEditDivComma">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="dataEdit.available_seats"
                            mask="00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,"
                            :raw="false"
                            :options="optionComma">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4" v-if="showEditDivDash">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="dataEdit.available_seats"
                            mask="00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,"
                            :raw="false"
                            :options="optionDash">
                        </vue-mask>
                    </div>
<!--                </div>-->
<!--                <div class="row">-->
<!--                    <div class="form-group col-md-3 mt-4 pt-3">-->
<!--                        <div class="form-check form-check-inline">-->
<!--                            <input class="form-check-input" type="radio" id="positive_edit_time" name="editTerminalTime"-->
<!--                                   checked="" value="positiveTimeEdit" v-model="dataEditTime.time"-->
<!--                                   @click="editApplyMaks('positive')">-->
<!--                            <label class="form-check-label" for="positive_edit_time">-->
<!--                                Positive-->
<!--                            </label>-->
<!--                        </div>-->
<!--                        <div class="form-check form-check-inline">-->
<!--                            <input class="form-check-input" type="radio" id="negative_edit_time" name="editTerminalTime"-->
<!--                                   value="negativeTimeEdit" v-model="dataEditTime.time"-->
<!--                                   @click="editApplyMaks('negative')">-->
<!--                            <label class="form-check-label" for="negative_edit_time">-->
<!--                                Negative-->
<!--                            </label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-5" v-if="showEditDivPositive">-->
<!--                        <label for="time_difference">Time Difference ( eg HH:MM )</label>-->
<!--                        <vue-mask-->
<!--                            class="form-control"-->
<!--                            v-model="dataEdit.time_difference"-->
<!--                            mask="00:00"-->
<!--                            :raw="false"-->
<!--                            :options="optionsPositive">-->
<!--                        </vue-mask>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-5" v-if="showEditDivNegative">-->
<!--                        <label for="time_difference">Time Difference ( eg HH:MM )</label>-->
<!--                        <vue-mask-->
<!--                            class="form-control"-->
<!--                            v-model="dataEdit.time_difference"-->
<!--                            mask="-00:00"-->
<!--                            :raw="false"-->
<!--                            :options="optionsNegative">-->
<!--                        </vue-mask>-->
<!--                    </div>-->
                    <div class="form-group col-md-4">
                        <label for="contact">Terminal Contact <span class="text-danger ml-1">*</span> </label>
                        <vue-mask
                            class="form-control"
                            v-model="dataEdit.contact"
                            mask="0000-0000000"
                            :raw="false"
                            :options="optionsContact">
                        </vue-mask>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="address">Address <span class="text-danger ml-2">*</span></label>
                        <textarea class="form-control" spellcheck="false" v-model="dataEdit.address" maxlength="140" placeholder="Address Must be less then 140 characters or 21 words"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="advance_booking">Advance Booking Allowed(Days)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                               v-model="dataEdit.advance_booking">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" v-model="dataEdit.longitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="Latitude">Latitude</label>
                        <input type="text" class="form-control" v-model="dataEdit.latitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="online_terminal_name">Online Terminal Name</label>
                        <input type="text" class="form-control" v-model="dataEdit.online_terminal_name">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="active">Online Availability </label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="dataEdit.active" v-bind:checked="parseInt(dataEdit.status) === 1 " />
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="sms">SMS</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="dataEdit.active_sms" v-bind:checked="dataEdit.active_sms === 1" />
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
<!--                    <div class="form-group col-md-2 d-flex align-items-center">-->
<!--                        <label class="mt-4" for="sms">Main Terminal</label>-->
<!--                        <label class="colorinput mx-3 mt-3">-->
<!--                            <span>-->
<!--                                <input type="checkbox" class="colorinput-input" @change="checkBoxEdit($event)" v-bind:checked="dataEdit.is_main == 1"/>-->
<!--                                <span class="colorinput-color bg-primary"></span>-->
<!--                            </span>-->
<!--                        </label>-->
<!--                    </div>-->
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update Terminal' }}
                    </button>
                </template>
            </Edit>

            <!--View Details Model-->
            <transition duration="1000" mode="out-in" enter-active-class="loader" leave-active-class="loader">
                <div class="modal fade" id="detail-modal" tabindex="-1" aria-labelledby="detailModalLabel"
                     aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Terminal Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                        @click="close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body m-1 p-1">
                                <div class="card-body my-0 py-0">
                                    <!-- Table -->
                                    <div class="row">
                                        <div class="col-12">
                                            <table class="table table-striped table-hover" id="show_terminal">
                                                <thead>
                                                <tr>
                                                    <th>Sr No.</th>
                                                    <th>Terminal Name</th>
                                                    <th>Address</th>
                                                    <th>Contact Number</th>
                                                    <th>Added By</th>
                                                    <th>Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="(single, i) in terminalsDetails" :key="i">
                                                    <td>{{ i + 1 }}</td>
                                                    <td v-if="single.name">{{ single.name }}</td>
                                                    <td v-else>N/A</td>
                                                    <td v-if="single.address">{{ single.address }}</td>
                                                    <td v-else>N/A</td>
                                                    <td v-if="single.contact"> {{ phoneFormat(single.contact) }}</td>
                                                    <td v-else>N/A</td>
                                                    <td v-if="single.added_by">{{ single.added_by.name }}</td>
                                                    <td v-else>N/A</td>
                                                    <td style="width:200px;">
                                                        <button title="Edit"
                                                            :data-target="'#' + editFormID"
                                                            data-toggle="modal"
                                                            @click="editTerminal(single)"
                                                            class="btn btn-warning mx-2"
                                                        >
                                                            <i class="far fa-edit"></i>
                                                        </button>
                                                        <router-link class="btn btn-success mx-2" title="Commission"
                                                                     :to="{ name:'terminal-commission', params: { id:single.id }}">
                                                            <i class="fas fa-percent"></i>
                                                        </router-link>
                                                        <router-link class="btn btn-primary mx-2" title="Discount"
                                                                     :to="{ name:'terminal-discount', params: { id:single.id }}">
                                                            <i class="fas fa-tag"></i>
                                                        </router-link>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- END TABLE -->
                                </div>
                            </div>
                            <div class="modal-footer bg-whitesmoke br">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
            <!-- Delete Modal -->
            <Delete :deleteForm="deleteFormID" confirmationMessage='Are You Sure You want To Delete This Terminal ???'/>
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import vueMask from "vue-jquery-mask";
import {mapGetters} from "vuex";

export default {
    name: "Terminal",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            date: null,
            optionsNegative: {
                placeholder: '-HH:MM',
            },
            optionsPositive: {
                placeholder: 'HH:MM',
            },
            optionsContact: {
                placeholder: '0300-0000000',
            },
            optionComma: {
                placeholder: '00,00,00,...',
            },
            optionDash: {
                placeholder: '00-00,00-00,00-00,...',
            },
            validationErrors: '',
            seen: true,
            loading: false,
            showDivComma: true,
            showDivPositive: true,
            showEditDivComma: true,
            showEditDivPositive: true,
            showDivDash: false,
            showDivNegative: false,
            showEditDivDash: false,
            showEditDivNegative: false,
            terminals: [],
            terminalsDetails: [],
            companies: [],
            formID: "terminal_form",
            editFormID: "edit_terminal_form",
            deleteFormID: "delete_terminal_form",
            cities: [],
            dataEditCheck: {},
            dataCheck: {},
            dataTime: {},
            dataEditTime: {},
            data: {
                company_id: "",
                name: "",
                available_seats: "",
                contact: "",
                address: "",
                time_difference: '',
                active_sms: "",
                advance_booking: "",
                longitude: "",
                latitude: "",
                city_id: 0,
                online_terminal_name: "",
                active: "",
                inactive: "",
                order: "",
                commission: "",
                flatCommission: "",
                percentageCommission: "",
            },
            dataEdit: {},
            success: false,
        };
    },

    async created() {
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
        await this.fetchTerminals();
    },
    methods: {
        datatableReset: function () {
            setTimeout(() => {
                $("#show_terminal").DataTable();
            }, 300);
        },
        checkBoxEdit: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_main = 1;
            } else {
                this.dataEdit.is_main = 0;
            }

            console.log(this.dataEdit.is_main);
        },
        applyMaks: function (value) {
            console.log(value, typeof value);
            if (value == 'comma') {
                this.showDivComma = true;
                this.showDivDash = false;
            }
            if (value == 'dash') {
                this.showDivComma = false;
                this.showDivDash = true;
            }

            if (value == 'positive') {
                this.showDivPositive = true;
                this.showDivNegative = false;
            }
            if (value == 'negative') {
                this.showDivPositive = false;
                this.showDivNegative = true;
            }
        },
        applyTimeMaks: function (value) {
            console.log(value, typeof value);
            if (value == 'positive') {
                this.showDivPositive = true;
                this.showDivNegative = false;
            }
            if (value == 'negative') {
                this.showDivPositive = false;
                this.showDivNegative = true;
                if (this.dataTime.time == "negativeTime") {
                    this.data.time_difference == "";
                }
            }
        },
        editApplyMaks: function (value) {
            if (value == 'comma') {
                this.showEditDivComma = true;
                this.showEditDivDash = false;
            }
            if (value == 'dash') {
                this.showEditDivComma = false;
                this.showEditDivDash = true;
            }
            if (value == 'positive') {
                this.showEditDivPositive = true;
                this.showEditDivNegative = false;
            }
            if (value == 'negative') {
                this.showEditDivPositive = false;
                this.showEditDivNegative = true;
            }
        },
        clearForm: function () {
            this.data = {};
            this.data.city_id = 0;
            this.data.commission = "";
            this.data.flatCommission = "";
            this.data.percentageCommission = "";
            this.dataTime.time = "positiveTime";
            this.dataCheck.seatNumberType = "comma";
            this.showDivComma = true;
            this.showDivPositive = true;
        },
        async fetchTerminals() {
            const terminalRes = await this.callApi("post", "terminals");
            const compRes = await this.callApi("post", "terminals/company");
            const cities = await this.callApi("post", "terminals/cities");
            this.terminals = terminalRes.data;
            this.companies = compRes.data;
            this.cities = cities.data;
            setTimeout(() => {
                $("#terminal_table").DataTable();
            }, 300);
        },

        phoneFormat: function phoneFormat(string) {
            return string.replace(/(\d{4})(\d{7})/, "$1-$2");
        },
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },
        async add() {
            this.validationErrors = [];
            if (!this.data.city_id)
                return swal({
                    title: "Required",
                    text: "Terminal City is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.name)
                return swal({
                    title: "Required",
                    text: "Terminal Name is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.contact)
                return swal({
                    title: "Required",
                    text: "Terminal Contact is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.data.address)
                return swal({
                    title: "Required",
                    text: "Terminal Address is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const res = await this.callApi("post", "terminals/store", this.data);
            if (res.status == 200) {
                swal({
                    title: "Success",
                    text: "Terminal Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#terminal_table").DataTable().destroy();
                await this.fetchTerminals();
                this.terminals = res.data
                this.data = {};
                this.data.city_id = 0;

                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                    empty(this.errorsArray);
                }, 2000);
            } else {
                if (res.status === 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                if (res.status === 423) {
                    this.errorsArray(res.data.is_main, 'Main Terminal');
                }
            }
        },
        async editTerminal(single) {
            this.dataEdit = single;
        },
        async terminalDetail(id) {
            const getTerminalRes = await this.callApi("post", "terminals/getTerminal", {id: id});
            $("#show_terminal").DataTable().destroy();
            this.terminalsDetails = getTerminalRes.data;
            setTimeout(() => {
                $("#show_terminal").DataTable();
            }, 300);
        },
        async update() {
            this.validationErrors = [];

            if (this.dataEdit.city_id == "")
                return swal({
                    title: "Required",
                    text: "Terminal City is required ",
                    icon: "error",
                    timer: 2000

                });
            if (this.dataEdit.name == "")
                return swal({
                    title: "Required",
                    text: "Terminal name is required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.contact === "")
                return swal({
                    title: "Required",
                    text: "Terminal Contact is required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.address == "" || typeof this.dataEdit.address == 'undefined')
                return swal({
                    title: "Required",
                    text: "Terminal Address is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const res = await this.callApi("post", "terminals/update", this.dataEdit);
            if (res.status === 201) {
                swal({
                    title: "Success",
                    text: "Terminal Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#terminal_table").DataTable().destroy();
                await this.fetchTerminals();
                setTimeout(() => {
                    $("#edit-modal").modal("hide");
                }, 3000);
            }
            if (res.status == 422) {
                this.loading = false;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
                        this.errorsArray(element, key);
                    });
                }
            }
            if (res.status == 423) {
                this.errorsArray(res.data.is_main, 'Main Terminal');
            }
        },
        async deleteModal(terminal, i) {
            const deletingObj = {
                url: "terminals/delete",
                data: terminal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        }
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.terminalsDetails.splice(obj.index, 1);
                $("#show_terminal").DataTable().destroy();
                setTimeout(() => {
                    $("#show_terminal").DataTable();
                }, 300);
            }
        },
    },
};
</script>
