<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Terminals</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-terminal')"
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
                                                <th v-if="checkForSubmenuButtons('view-terminal') || checkForSubmenuButtons('edit-terminal') || checkForSubmenuButtons('delete-terminal')|| checkForSubmenuButtons('commission')|| checkForSubmenuButtons('discount')">
                                                    Action
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(terminal, i) in terminals" :key="i">
                                                <td>{{ i + 1 }}</td>
                                                <td>{{ terminal.name }}</td>
                                                <td>{{ terminal.terminal_count }}</td>
                                                <td>{{ terminal.added_by.name }}</td>
                                                <td v-if="checkForSubmenuButtons('view-terminal') || checkForSubmenuButtons('edit-terminal') || checkForSubmenuButtons('delete-terminal')|| checkForSubmenuButtons('commission')|| checkForSubmenuButtons('discount')">
                                                    <button title="View Terminals"
                                                            data-target="#detail-modal"
                                                            data-toggle="modal"
                                                            @click="terminalDetail(terminal.id); datatableReset()"
                                                            class="btn btn-info mx-2"
                                                            v-if="checkForSubmenuButtons('view-terminal')"
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
                    <div class="form-group col-md-4">
                        <label for="city_id">Terminal City <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="data.city_id">
                            <option value="0">Select City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{ city.name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Terminal Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="data.name" placeholder="Enter Terminal Name">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Terminal Name In Urdu<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="data.urdu_name" dir="rtl"
                               placeholder="ٹرمینل نام">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group  mt-4 pt-3"
                         :class=" !this.showDivComma && !this.showDivDash  ? 'col-md-6' : 'col-md-5' ">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="all" name="valueType"
                                   checked="" value="all" v-model="data.seatNumberType"
                                   @click="applyMask('all')">
                            <label class="form-check-label" for="all">
                                All
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="comma_separated" name="valueType"
                                   value="comma" v-model="data.seatNumberType"
                                   @click="applyMask('comma')">
                            <label class="form-check-label" for="comma_separated">
                                Comma Separated
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="seat_range_dash" name="valueType"
                                   value="dash" v-model="data.seatNumberType" @click="applyMask('dash')">
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
                    <div class="form-group"
                         :class=" !this.showDivComma && !this.showDivDash  ? 'col-md-6' : 'col-md-3' ">
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
                    <div class="form-group col-md-9">
                        <label for="address">Address <span class="text-danger ml-2">*</span></label>
                        <textarea class="form-control" spellcheck="false" v-model="data.address" maxlength="140"
                                  placeholder="Address Must be less then 140 characters or 21 words"></textarea>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="mt-4" for="is_online">Is Online Terminal</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" id="is_online" class="colorinput-input"
                                       v-model="data.is_online"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="">Reservation Cancel(in minutes)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                                v-model="data.reservation_cancel" placeholder="">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="advance_booking">Advance Booking Allowed(Days)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                               v-model="data.advance_booking" placeholder="Enter Advance Booking Allowed">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" v-model="data.longitude" placeholder="Enter Longitude">
                        <small><a href="https://www.google.com/maps" target="_blank">Click Here to get</a></small>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="Latitude">Latitude</label>
                        <input type="text" class="form-control" v-model="data.latitude" placeholder="Enter Latitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="online_terminal_name">Online Terminal Name</label>
                        <input type="text" class="form-control" v-model="data.online_terminal_name">
                    </div>
                    <!-- <div class="form-group col-md-2 d-flex align-items-center">
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
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="sms">Mian Terminal</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="data.is_main"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div> -->
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
                    <div class="form-group col-md-4">
                        <label for="city_id">Terminal City <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="dataEdit.city_id">
                            <option value="0">Select City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{ city.name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Terminal Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Terminal Name In Urdu <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.urdu_name" dir="rtl">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group mt-4 pt-3"
                         :class="dataEdit.allowed_type == 'all' ?  'col-md-6' : 'col-md-5'">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="editAll" name="editValueType"
                                   value="all"
                                   v-model="dataEdit.allowed_type"
                                   v-bind:checked="dataEdit.allowed_type == 'all'"
                                   @click="this.dataEdit.available_seats = ''">
                            <label class="form-check-label" for="editAll">
                                All
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="editComma_separated" name="editValueType"
                                   value="comma" v-model="dataEdit.allowed_type"
                                   v-bind:checked="dataEdit.allowed_type == 'comma'"
                                   @click="this.dataEdit.available_seats = ''">
                            <label class="form-check-label" for="editComma_separated">
                                Comma Separated
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="editSeat_range_dash" name="editValueType"
                                   value="dash" v-model="dataEdit.allowed_type"
                                   v-bind:checked="dataEdit.allowed_type == 'dash'"
                                   @click="this.dataEdit.available_seats = ''">
                            <label class="form-check-label" for="editSeat_range_dash">
                                Seat Range
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-4" v-if="dataEdit.allowed_type == 'comma'">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="dataEdit.available_seats"
                            mask="00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,00,"
                            :raw="false"
                            :options="optionComma">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4" v-if="dataEdit.allowed_type == 'dash'">
                        <label for="available_seats">Allowed Seats</label>
                        <vue-mask
                            class="form-control"
                            v-model="dataEdit.available_seats"
                            mask="00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,00-00,"
                            :raw="false"
                            :options="optionDash">
                        </vue-mask>
                    </div>
                    <div class="form-group" :class="dataEdit.allowed_type == 'all' ? 'col-md-6' : 'col-md-3'">
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
                    <div class="form-group col-md-9">
                        <label for="address">Address <span class="text-danger ml-2">*</span></label>
                        <textarea class="form-control" spellcheck="false" v-model="dataEdit.address" maxlength="140"
                                  placeholder="Address Must be less then 140 characters or 21 words"></textarea>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="mt-4" for="is_online">Is Online Terminal</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" id="is_online" class="colorinput-input"
                                       v-model="dataEdit.is_online_terminal"
                                       v-bind:checked="dataEdit.is_online_terminal == 1"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="">Reservation Cancel(in minutes)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                                v-model="dataEdit.reservation_cancel" placeholder="">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="advance_booking">Advance Booking Allowed(Days)</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)"
                               v-model="dataEdit.advance_booking">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="longitude">Longitude</label>
                        <input type="text" class="form-control" v-model="dataEdit.longitude">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="Latitude">Latitude</label>
                        <input type="text" class="form-control" v-model="dataEdit.latitude">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="online_terminal_name">Online Terminal Name</label>
                        <input type="text" class="form-control" v-model="dataEdit.online_terminal_name">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phoneNumber">Show other terminal passenger detail</label>
                        <div class="d-block">
                            <input type="checkbox" v-model="dataEdit.other_terminal_passenger_detail">
                            <lable class="mx-1">Show detail</lable>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phoneNumber">Send whatsapp message</label>
                        <div class="d-block">
                            <input type="checkbox" v-model="dataEdit.send_message">
                            <lable class="mx-1">yes</lable>
                        </div>
                    </div>
                    <!-- <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="active">Online Availability </label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="dataEdit.active"
                                       v-bind:checked="parseInt(dataEdit.status) === 1 "/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="sms">SMS</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="dataEdit.active_sms"
                                       v-bind:checked="dataEdit.active_sms == 1"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-center">
                        <label class="mt-4" for="sms">Main Terminal</label>
                        <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" v-model="dataEdit.is_main"
                                       v-bind:checked="dataEdit.is_main == 1"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                        </label>
                    </div> -->
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update Terminal' }}
                    </button>
                </template>
            </Edit>

            <!--View Details Model-->

            <div class="modal fade" id="detail-modal" tabindex="-1" aria-labelledby="detailModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Terminal Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    @click="closeModal()">
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
                                                <th v-if="checkForSubmenuButtons('edit-terminal') || checkForSubmenuButtons('delete-terminal')|| checkForSubmenuButtons('commission')|| checkForSubmenuButtons('discount')">
                                                    Action
                                                </th>
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
                                                <td style="width:270px;"
                                                    v-if="checkForSubmenuButtons('edit-terminal') || checkForSubmenuButtons('delete-terminal')|| checkForSubmenuButtons('commission')|| checkForSubmenuButtons('discount')">
                                                    <button title="Edit Terminal"
                                                            :data-target="'#' + editFormID"
                                                            data-toggle="modal"
                                                            @click="editTerminal(single)"
                                                            class="btn btn-warning mx-1"
                                                            v-if="checkForSubmenuButtons('edit-terminal')"
                                                    >
                                                        <i class="far fa-edit"></i>
                                                    </button>
                                                    <router-link target="_blank" class="btn btn-success mx-1"
                                                                 title="Commission"
                                                                 v-if="checkForSubmenuButtons('commission')"
                                                                 :to="{ name:'terminal-commission', params: { id:single.id }}">
                                                        <i class="fas fa-percent"></i>
                                                    </router-link>
                                                    <router-link target="_blank" class="btn btn-primary mx-1"
                                                                 title="Discount"
                                                                 v-if="checkForSubmenuButtons('discount')"
                                                                 :to="{ name:'terminal-discount', params: { id:single.id }}">
                                                        <i class="fas fa-tag"></i>
                                                    </router-link>
                                                    <router-link target="_blank" class="btn btn-warning mx-1"
                                                                 title="Terminal Time"
                                                                 v-if="checkForSubmenuButtons('edit-terminal')"
                                                                 :to="{ name:'terminal-time', params: { id:single.id }}">
                                                        <i class="fas fa-clock"></i>
                                                    </router-link>
                                                    <button title="Delete Terminal"
                                                            :data-target="'#' + hideFormID" @click="delId = single.id" data-toggle="modal"
                                                            class="btn btn-danger mx-1"
                                                            v-if="checkForSubmenuButtons('delete-terminal')"
                                                    >
                                                        <i class="far fas fa-trash"></i>
                                                    </button>
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
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This City ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideTerminal"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Delete' }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Hide from "../../components/Hide.vue";
import vueMask from "vue-jquery-mask";
import {mapGetters} from "vuex";

export default {
    name: "Terminal",
    components: {
        Add,
        Edit,
        Hide,
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
            hideFormID: "hide_terminal_form",
            cities: [],
            permissions: [],
            dataTime: {},
            dataEditTime: {},
            data: {
                company_id: "",
                name: "",
                urdu_name: "",
                available_seats: "",
                contact: "",
                address: "",
                time_difference: '',
                active_sms: "",
                is_main: "",
                advance_booking: "",
                reservation_cancel: "",
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
                seatNumberType: "all",
            },
            dataEdit: {},
            delId: "",
            success: false,
        };
    },

    async created() {
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        this.fetchTerminals();
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        closeModal() {
            $("#detail-modal").click();
        },
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
        },
        applyMask: function (value) {
            this.data.available_seats = "";
            if (value == 'all') {
                this.showDivComma = false;
                this.showDivDash = false;
            }
            if (value == 'comma') {
                this.showDivComma = true;
                this.showDivDash = false;
            }
            if (value == 'dash') {
                this.showDivComma = false;
                this.showDivDash = true;
            }
        },

        clearForm: function () {
            this.data = {};
            this.data.city_id = 0;
            this.data.commission = "";
            this.data.flatCommission = "";
            this.data.percentageCommission = "";
            this.dataTime.time = "positiveTime";
            this.data.seatNumberType = "all";
            this.showDivComma = false;
            this.showDivDash = false;
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
            if (!this.data.urdu_name)
                return swal({
                    title: "Required",
                    text: "Terminal Urdu Name is required",
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
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Terminal Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#terminal_table").DataTable().destroy();
                this.fetchTerminals();
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
            this.dataEdit.other_terminal_passenger_detail = single.other_terminal_passenger_detail == 1 ? true : false;
            this.dataEdit.send_message = single.send_message == 1 ? true : false;
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
            if (this.dataEdit.urdu_name == "")
                return swal({
                    title: "Required",
                    text: "Terminal urdu name is required",
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
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Terminal Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#terminal_table").DataTable().destroy();
                this.fetchTerminals();
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
        async hideTerminal() {
            this.loading = true;
            const resHide = await this.callApi("post", 'terminals/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Terminal Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#employee_table").DataTable().destroy();
                await this.fetchEmployees();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },
    },
};
</script>
