<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Schedule</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                    @click="getData()"
                                >
                                    Add Schedule
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                        @click="error = !error"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
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
                                                    id="edit_schedule"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date </th>
                                                        <th>Fare Class</th>
                                                        <th>Route</th>
<!--                                                        <th>Bus Name</th>-->
                                                        <th>Bus Class</th>
<!--                                                        <th>No of Rows</th>-->
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(schedule, i) in schedules" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ schedule.name }}</td>
                                                        <td>{{ schedule.start_date }}</td>
                                                        <td>{{ schedule.end_date }}</td>
                                                        <td>{{ schedule.single_bus_class.name }}</td>
                                                        <td>{{ schedule.single_route.name }}</td>
<!--                                                        <td>{{ schedule.single_bus.bus_number }}</td>-->
                                                        <td>{{ schedule.selective_bus.name }}</td>
<!--                                                        <td>{{ schedule.no_of_rows }}</td>-->
                                                        <td v-if="schedule.added_by">{{ schedule.added_by.name }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(schedule); genericData()"
                                                               class="btn btn-primary mr-1">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteSchedule(schedule, i)"
                                                                class="btn btn-danger"
                                                            >
                                                                <i class="far fa-trash-alt"></i>
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
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <Add
                :heading="'ADD NEW SCHEDULE'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID">
                <div class="row mb-3">
                    <div class="col-md-3 text-center" :class="activeSection!=0?'':'border p-3  text-light bg-primary'">
                        Step 1
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step1'?'':'border p-3  text-light bg-info'">Step 2
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step2'?'':'border p-3  text-light bg-success'">Step 3
                    </div>
                    <div class="col-md-3 text-center"
                         :class="activeSection!='step3'?'':'border p-3  text-light bg-warning'">Step 4
                    </div>
                </div>
                <section class="section1" :class="activeSection!=0?'d-none':''">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">Name</label>
                            <input type="text" id="name" class="form-control"
                                   v-model="data.name"/>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="start">Start Date</label>
                            <input type="date" id="start" class="form-control"
                                   v-model="data.StartDate"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="end">End Date</label>
                            <input type="date" id="end" class="form-control"
                                   v-model="data.EndDate"/>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="busType">Fare Class</label>
                            <select class="form-control" id="busType" v-model="data.fareClass"
                            >
                                <option value="" selected>Select Fare Class</option>
                                <option v-for="(type, i) in fareClasses" :value="type.id" :key="i">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <button class="btn btn-success step1 float-right" @click="nextSection('step1')">Next<i
                                class="fas fa-arrow-right pr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section2" :class="activeSection!='step1'?'d-none':''">
                    <div class="row">
                        <div class="col-md-12 class form-group">
                            <label for="DiscountName">Routes</label>
                            <select class="form-control" id="route"
                                    @change="getSelectiveData( 'route'); this.stepTwoAddSchedule = true"
                                    v-model="data.route"
                            >
                                <option value="" selected>Select Route</option>
                                <option v-for="(route, i) in routes" :value="route.id" :key="i">
                                    {{ route.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row d-flex justify-content-center" v-if="stepTwoAddSchedule">
                        <div class="col-md-12 class form-group mx-2">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="addScheduleStep2">
                                    <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>City Name</th>
                                        <th>Terminals</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(city, i) in  cities " :key="i">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ city.name }}</td>
                                        <td>
                                            <span v-for="(item) in terminals[i]" :key="item.id">
                                                <label class="colorinput mx-3">
                                                    <span>
                                                        <input type="checkbox" class="colorinput-input" @click="addTerminal($event, city.id)" id="terminal" :value="item.id"/>
                                                        <span class="colorinput-color bg-success"></span>
                                                    </span>
                                                </label>
                                                <label class="checkbox-inputs" for="terminal">{{ item.name }}</label>
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back1 float-left" @click="previousSection(0)"><i
                                class="fas fa-arrow-left mr-1"></i>Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success step2 float-right" @click="nextSection('step2')">Next<i
                                class="fas fa-arrow-right mr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section3" :class="activeSection!='step2'?'d-none':''">
                    <div class="row">
<!--                        <div class="col-md-6 class form-group">-->
<!--                            <label for="">Bus</label>-->
<!--                            <select class="form-control" id="terminal" @change="getSelectiveData( 'bus')"-->
<!--                                    v-model="data.bus">-->
<!--                                <option value="0" selected>Select bus</option>-->
<!--                                <option v-for="(bus, i) in buses" :value="bus.id" :key="i">-->
<!--                                    {{ bus.bus_number }}-->
<!--                                </option>-->
<!--                            </select>-->
<!--                        </div>-->
                        <div class="col-md-12 class form-group">
                            <label for="busCLass">Bus Class</label>
                            <select class="form-control" id="busCLass" v-model="data.busClass">
                                <option value="0" selected>Select Route Bus CLass</option>
                                <option v-for="(type, i) in busClasses" :value="type.id" :key="i">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="surcharge">Surcharge</label>
                            <select class="form-control" id="surcharge"
                                    v-model="data.surcharge">
                                <option value="0" selected>Select Surcharge</option>
                                <option v-for="(surcharge, i) in surcharges" :value="surcharge.id" :key="i">
                                    {{ surcharge.name }} - {{ surcharge.percentage }}%
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="discount">Discount</label>
                            <select class="form-control" id="discount" v-model="data.discount">
                                <option value="0" selected>Select Discount</option>
                                <option v-for="(discount, i) in discounts" :value="discount.id" :key="i">
                                    {{ discount.name }} - {{ discount.percentage }}%
                                </option>
                            </select>
                        </div>
                    </div>
<!--                    <div class="row">-->
<!--                        <div class="col-md-3 class form-group">-->
<!--                            <label for="">No of Rows</label>-->
<!--                            <input type="text" class="form-control" v-model="data.noRows"-->
<!--                                   @keypress="isNumber($event)">-->
<!--                        </div>-->
<!--                        <div class="col-md-3 class form-group">-->
<!--                            <label for="">No of Cols</label>-->
<!--                            <input type="text" class="form-control" v-model="data.noCols"-->
<!--                                   @keypress="isNumber($event)">-->
<!--                        </div>-->
<!--                        <div class="form-group col-md-4 my-4 pt-2">-->
<!--                            <button-->
<!--                                type="button"-->
<!--                                class="btn btn-block btn-warning"-->
<!--                                @click="editGenerateMap(data.bus)"-->
<!--                            >-->
<!--                                Generate Seat Map-->
<!--                            </button>-->
<!--                        </div>-->
<!--                    </div>-->

<!--                    <div class="row mx-1 mainRow" v-if="isShowEditDiv">-->
<!--                        <div class="form-group col-md-6 border py-3">-->
<!--                            <tr class="seat-img p-0 m-0" v-for="(record, rowIndex) in data.seatMap" :key="rowIndex">-->
<!--                                <td v-for="(col, colIndex) in record" :key="colIndex">-->
<!--                                    <img :class="getStyleClass(col)" data-toggle="modal" data-target="#setSeatClass"-->
<!--                                         :src= "$store.state.app_url +'assets/img/buses/available_seat_img.gif'" alt=""/>-->
<!--                                </td>-->
<!--                            </tr>-->
<!--                        </div>-->
<!--                        <div class="col-md-4 mr-3">-->
<!--                            <div class="row">-->
<!--                                <div class="col-md-6">-->
<!--                                    <ul style="list-style: none;">-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="selected-row mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Reserved</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline;">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="booked_Seat mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Booked</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="notForSale mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Not For Sale</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="reservedForFemale mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Reserved For Female</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                    </ul>-->
<!--                                </div>-->
<!--                                <div class="col-md-6">-->
<!--                                    <ul style="list-style: none;">-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="economy mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Economy</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="exective mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Executive</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="business mr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Business</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                        <li style="display:inline; ">-->
<!--                                            <div-->
<!--                                                style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                class="anyElseClass pr-1 border">-->
<!--                                            </div>-->
<!--                                            <span>Other Class</span>-->
<!--                                        </li>-->
<!--                                        <br>-->
<!--                                    </ul>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back2 float-left" @click="previousSection('step1')"><i
                                class="fas fa-arrow-left mr-1 border-dark"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success step2 float-right"
                                    @click="nextSection('step3'); getEntireForm()">Next<i
                                class="fas fa-arrow-right mr-1 border-dark"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section4" :class="activeSection!='step3'?'d-none':''">
                    <div class="row my-3 py-2">
                        <div class="col-md-12 text-center">
                            <span class="h3 font-weight-bold text-muted"> Review </span>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-md-12 form-group">
                            <table
                                class="table table-striped table-borderless table-responsive text-dark  font-weight-bold my-3 ml-4">
                                <tbody>
                                <tr>
                                    <td class="mr-3">Name</td>
                                    <td v-if="this.dataPreview.Name">{{ this.dataPreview.Name }}</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">Start Date</td>
                                    <td v-if="this.dataPreview.start_date">{{ this.dataPreview.start_date }}</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">End Date</td>
                                    <td v-if="this.dataPreview.end_date">{{ this.dataPreview.end_date }}</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">Fare Class</td>
                                    <td v-if="this.dataPreview.fareClass">{{ this.dataPreview.fareClass }}</td>
                                    <td v-else>N/A</td>
                                </tr>
<!--                                <tr>-->
<!--                                    <td class="mr-3">Bus Name</td>-->
<!--                                    <td v-if="this.dataPreview.busName">{{ this.dataPreview.busName }}</td>-->
<!--                                    <td v-else>N/A</td>-->
<!--                                </tr>-->
                                <tr>
                                    <td class="mr-3">Selected Bus Class</td>
                                    <td v-if="this.dataPreview.busClass">{{ this.dataPreview.busClass }}</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">Route</td>
                                    <td v-if="this.dataPreview.route">{{ this.dataPreview.route }}</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">Discount</td>
                                    <td v-if="this.dataPreview.discount">{{ this.dataPreview.discount }}%</td>
                                    <td v-else>N/A</td>
                                </tr>
                                <tr>
                                    <td class="mr-3">Surcharge</td>
                                    <td v-if="this.dataPreview.surcharge">{{ this.dataPreview.surcharge }}%</td>
                                    <td v-else>N/A</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
<!--                        <div class="col-md-6">-->
<!--                            <div class="row">-->
<!--                                <div class="col-md-12">-->
<!--                                    <tr class="seat-img p-0 m-0" v-for="(record, rowIndex) in data.seatMap"-->
<!--                                        :key="rowIndex">-->
<!--                                        <td v-for="(col, colIndex) in record" :key="colIndex">-->
<!--                                            <img :class="getStyleClass(col)" data-toggle="modal"-->
<!--                                                 data-target="#setSeatClass"-->
<!--                                                 :src="$store.state.app_url +'assets/img/buses/available_seat_img.gif'"-->
<!--                                                 alt=""/>-->
<!--                                        </td>-->
<!--                                    </tr>-->
<!--                                </div>-->

<!--                                <div class="col-md-12 mt-3">-->
<!--                                    <div class="row">-->
<!--                                        <div class="col-md-6">-->
<!--                                            <ul style="list-style: none;" class="p-0 m-0">-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="selected-row mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Reserved</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="booked_Seat mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Booked</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="notForSale mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Not For Sale</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="reservedForFemale mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Reserved For Female</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                            </ul>-->
<!--                                        </div>-->
<!--                                        <div class="col-md-6">-->
<!--                                            <ul style="list-style: none;">-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="economy mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Economy</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="exective mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Executive</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="business mr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Business</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                                <li style="display:inline; ">-->
<!--                                                    <div-->
<!--                                                        style="width: 50px; height: 50px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                                        class="anyElseClass pr-1 border">-->
<!--                                                    </div>-->
<!--                                                    <span>Other Class</span>-->
<!--                                                </li>-->
<!--                                                <br>-->
<!--                                            </ul>-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back2 float-left" @click="previousSection('step2')"><i
                                class="fas fa-arrow-left mr-1"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="submitFormButton" class=" btn btn-success float-right" @click="addSchedule()">
                                Save
                                Schedule
                            </button>
                        </div>
                    </div>
                </section>
            </Add>

            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Schedule"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mb-3">
                    <div class="col-md-4 text-center"
                         :class="editActiveSection!=0?'':'border p-3  text-light bg-primary'">
                        Step 1
                    </div>
                    <div class="col-md-4 text-center"
                         :class="editActiveSection!='step1'?'':'border p-3  text-light bg-info'">Step 2
                    </div>
                    <div class="col-md-4 text-center"
                         :class="editActiveSection!='step2'?'':'border p-3  text-light bg-success'">Step 3
                    </div>
                </div>
                <section class="section1" :class="editActiveSection!=0?'d-none':''">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">Name</label>
                            <input type="text" id="name" class="form-control"
                                   v-model="dataEdit.schedules.name"/>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="start">Start Date </label>
                            <input type="date" id="start" class="form-control"
                                   v-model="dataEdit.schedules.start_date"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="end">End Date </label>
                            <input type="date" id="end" class="form-control"
                                   v-model="dataEdit.schedules.end_date"/>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="busType">Bus Type</label>
                            <select class="form-control" id="busType" v-model="dataEdit.schedules.bus_class_id"
                            >
                                <option value="" selected>Select Type</option>
                                <option v-for="(type, i) in editClasses" :value="type.id" :key="i">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <button class="btn btn-success step1 float-right" @click="editNextSection('step1'); this.stepTwoAddSchedule = true">Next<i
                                class="fas fa-arrow-right pr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section2" :class="editActiveSection!='step1'?'d-none':''">
                    <div class="row">
                        <div class="col-md-12 class form-group">
                            <label for="DiscountName">Routes</label>
                            <select class="form-control" id="route"
                                    @change="getSelectiveData( 'routeEdit')" v-model="dataEdit.schedules.route_id"
                            >
                                <option value="" selected>Select Route</option>
                                <option v-for="(route, i) in editRoutes" :value="route.id" :key="i">
                                    {{ route.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row d-flex justify-content-center" v-if="stepTwoAddSchedule">
                        <div class="col-md-12 class form-group mx-2">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="addScheduleStep2">
                                    <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>City Name</th>
                                        <th>Terminals</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(city, i) in  dataEdit.cities" :key="i">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ city.name}}</td>
                                        <td>
                                            <span v-for="(item) in  city.terminal" :key="item.id">
                                                <label class="colorinput mx-3">
                                                    <span>
                                                        <input type="checkbox" class="colorinput-input" @click="editTerminal($event, city.id)"  v-bind:checked="checkedSelectedTerminals(item.id)"  id="terminal" :value="item.id"/>
                                                        <span class="colorinput-color bg-success"></span>
                                                    </span>
                                                </label>
                                                <label class="checkbox-inputs" for="terminal">{{ item.name }}</label>
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back1 float-left" @click="editPreviousSection(0)"><i
                                class="fas fa-arrow-left mr-1"></i>Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success step2 float-right" @click="editNextSection('step2')">Next<i
                                class="fas fa-arrow-right mr-1"></i></button>
                        </div>
                    </div>
                </section>

                <section class="section3" :class="editActiveSection!='step2'?'d-none':''">
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="">Bus</label>
                            <select class="form-control" id="terminal" @change="getSelectiveData( 'bus')"
                                    v-model="dataEdit.schedules.bus_id">
                                <option value="0" selected>Select bus</option>
                                <option v-for="(bus, i) in editBuses" :value="bus.id" :key="i">
                                    {{ bus.bus_number }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="busCLass">Bus Class</label>
                            <select class="form-control" id="busCLass" v-model="dataEdit.schedules.selected_bus_class_id">
                                <option value="0" selected>Select Route Bus CLass</option>
                                <option v-for="(fareClass, i) in editRouteClasses" :value="fareClass.id"
                                        :key="i">
                                    {{ fareClass.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="surcharge">Surcharge</label>
                            <select class="form-control" id="surcharge"
                                    v-model="dataEdit.schedules.surcharge_id">
                                <option value="0" selected>Select Surcharge</option>
                                <option v-for="(surcharge, i) in editSurcharges" :value="surcharge.id" :key="i">
                                    {{ surcharge.name }} - {{ surcharge.percentage }}%
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="discount">Discount</label>
                            <select class="form-control" id="discount" v-model="dataEdit.schedules.discount_id">
                                <option value="0" selected>Select Discount</option>
                                <option v-for="(discount, i) in editDiscounts" :value="discount.id" :key="i">
                                    {{ discount.name }} - {{ discount.percentage }}%
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info back2 float-left" @click="editPreviousSection('step1')"><i
                                class="fas fa-arrow-left mr-1 border-dark"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="submitFormButton" class=" btn btn-success float-right" @click="updateSchedule">
                                Update Schedule
                            </button>
                        </div>
                    </div>
                </section>
            </Edit>
            <!--            Edit Model End-->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This Schedule ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";

export default {
    name: "SchedulePage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            schedules: [],
            discounts: [],
            surcharges: [],
            formID: "addNewSchedule",
            validationErrors: [],
            value: [],
            editClasses: [],
            editDiscounts: [],
            editSurcharges: [],
            editRouteClasses: [],
            editBuses: [],
            editTerminals: [],
            editCities: [],
            editRoutes: [],
            success: false,
            error: false,
            routes: '',
            cities: '',
            terminals: '',
            classes: '',
            routeClasses: '',
            isShowEditDiv: false,
            stepTwoAddSchedule: false,
            buses: '',
            addTerminalId: '',
            TripDuration: '',
            activeSection: 0,
            editActiveSection: 0,
            data: {
                name: '',
                StartDate: '',
                EndDate: '',
                fareClass: '',
                route: '',
                surcharge: 0,
                discount: 0,
                busClass: 0,
                addTerminalsOnClick: [],
            },
            dataEdit: {
                schedules: [],
                cities: [],
                terminals: [],
                compare_array: [],
            },
            dataPreview: {},
        };
    },
    async created() {
        const res = await this.callApi("post", 'schedule');
        if (res.status === 200) {
            this.schedules = res.data
        } else {
            console.log(res);
        }
    },


    methods: {
        async fetchTerminals(event, index) {
            const terminalRes = await this.callApi("post", "cities/terminals", {id: value});
            if (terminalRes.status === 200) {
                this.terminals[index] = terminalRes.data;
            }
        },

        async getEntireForm() {
            const resEntire = await this.callApi("post", 'schedule/getEntire', this.data);
            this.dataPreview = resEntire.data;
            this.dataPreview.start_date = this.data.StartDate;
            this.dataPreview.end_date = this.data.EndDate;
            this.dataPreview.Name = this.data.name;
        },

        tConvert: function (time) {
            // Check correct time format and split into components
            time = time.toString().match(/^([01]\d|2[0-3])(:)([0-5]\d)(:[0-5]\d)?$/) || [time];
            if (time.length > 1) { // If time format correct
                time = time.slice(1);  // Remove full string match value
                time[5] = +time < 12 ? ' AM' : ' PM'; // Set AM/PM
                time = +time % 12 || 12; // Adjust hours
            }
            return time.join(''); // return adjusted time or original string
        },

        // getStyleClass: function (col) {
        //     if (col.reserved && col.type == 'reserved_for_female') {
        //         return "reservedForFemale border";
        //     }
        //     if (col.reserved && col.type == 'not_for_sale') {
        //         return "notForSale border";
        //     }
        //     if (col.reserved) {
        //         return "booked_Seat border";
        //     }
        //     if (col.class == 'Economy') {
        //         return "economy border";
        //     }
        //     if (col.class == 'Business') {
        //         return "business border";
        //     }
        //     if (col.class == 'Exective') {
        //         return "exective border";
        //     }
        //     if (col.reserved && col.class == 'Economy' && col.class !== 'Business' && col.class !== 'Exective') {
        //         return "anyElseClass border";
        //     }
        //     return '';
        //
        // },

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

        editGenerateMap: function (val) {
            if (val === "0") {
                this.isShowEditDiv = false;
            }
            this.isShowEditDiv = true;
        },

        addTerminal(event, id) {
            const value = event.target.value
            if (event.target.checked) {
                const index = this.data.addTerminalsOnClick.indexOf(value);
                if (index === -1) {
                    this.data.addTerminalsOnClick.push({
                        'city_id': id,
                        'terminal_id': parseInt(value),
                        'allow': true,
                    });
                }
            } else {
                const index = this.data.addTerminalsOnClick.indexOf(value);
                this.data.addTerminalsOnClick.splice(index, 1);
            }
        },

        editTerminal(event, id) {
            if (event.target.checked) {
                const value = event.target.value
                this.dataEdit.compare_array = this.dataEdit.compare_array.map((arr) => {
                    if (arr.terminal_id == value) {
                        return {...arr, allow: !arr.allow}
                    }
                    return arr;
                })

                this.dataEdit.updated_route_city_terminal = this.dataEdit.compare_array;
                console.log(this.dataEdit.updated_route_city_terminal);
            }
        },

        checkedSelectedTerminals(id){
            let status = '';
            status = this.dataEdit.compare_array.filter((arr) => {
                if( arr.terminal_id == id ){
                    return arr
                }
            })
            return status[0].allow;
        },

        async getSelectiveData(name) {
            if (name == 'route') {
                const resRoute = await this.callApi("post", 'schedule/getCity', {id: this.data.route});
                this.cities = resRoute.data.cities;
                this.dataEdit.cities = resRoute.data.cities;
                this.terminals = resRoute.data.terminal;
                this.dataEdit.terminals = resRoute.data.terminal;

            }
            if (name == 'routeEdit') {
                const resRouteEdit = await this.callApi("post", 'schedule/getCity', {id: this.dataEdit.route_id});
                this.cities = resRouteEdit.dataEdit;
                this.terminals = resRouteEdit.dataEdit;

                const resRouteFareClass = await this.callApi("post", 'schedule/getRouteFare', {id: this.data.route});
                this.routeClasses = resRouteFareClass.data;

            }
            // if (name == 'bus') {
            //     const resBus = await this.callApi("post", 'buses/getBusData', {id: this.data.bus});
            //     console.log(resBus.data);
            //     this.data.busClass = resBus.data.fare_class_id;
            //     this.data.noRows = resBus.data.no_of_rows;
            //     this.data.noCols = resBus.data.no_of_cols;
            //     this.data.seatMap = resBus.data.seat_map;
            //     this.isShowEditDiv = false;
            // }
        },

        async getData() {
            const resGetAllRoutes = await this.callApi("post", "schedule/getRoute");
            this.routes = resGetAllRoutes.data;

            const resGetAllClasses = await this.callApi("post", "fare-class");
            this.fareClasses = resGetAllClasses.data;

            const resGetBusClasses = await this.callApi("post", "bus_classes");
            this.busClasses = resGetBusClasses.data;

            const resGetAllBus = await this.callApi("post", "buses");
            this.buses = resGetAllBus.data;

            const resSurcharge = await this.callApi("post", 'surcharge/getSelective');
            this.surcharges = resSurcharge.data;

            const resDiscount = await this.callApi("post", 'discount/getSelective');
            this.discounts = resDiscount.data;
        },

        nextSection(nextBtn) {
            this.activeSection = nextBtn
        },

        editNextSection(nextBtn) {
            this.editActiveSection = nextBtn
        },

        previousSection(prvBtn) {
            this.activeSection = prvBtn;
        },

        editPreviousSection(prvBtn) {
            this.editActiveSection = prvBtn;
        },

        async addSchedule() {
            this.validationErrors = [];
            if (this.data.name === "")
                return this.errorsArray("Schedule Name is Required", "Name");
            if (this.data.StartDate === "")
                return this.errorsArray("Departure Date and Time is Required", "StartDate");
            if (this.data.EndDate === "")
                return this.errorsArray("End Date and Time is Required", "EndDate");
            if (this.data.busClass === "")
                return this.errorsArray("Bus Class is Required", "BusClass");
            if (this.data.fareClass === "")
                return this.errorsArray("Fare Class is Required", "Class");
            if (this.data.route === "")
                return this.errorsArray("Route is Required", "Route");

            const res = await this.callApi("post", "schedule/store", this.data);
            if (res.status === 201 && res.statusText === "Created") {
                this.success = "Schedule Created Successfully";
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                        res.data.errors.name.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateSchedule() {
            this.validationErrors = [];
            if (this.dataEdit.schedules.name === "")
                return this.errorsArray("Schedule Name is Required", "Name");
            if (this.dataEdit.schedules.startDate === "")
                return this.errorsArray("Departure Date and Time is Required", "StartDate");
            if (this.dataEdit.schedules.endDate === "")
                return this.errorsArray("End Date and Time is Required", "DestinationDateTime");
            if (this.dataEdit.schedules.bus_class_id === "")
                return this.errorsArray("Fare Class is Required", "BusClass");
            if (this.dataEdit.schedules.selected_bus_class_id === "")
                return this.errorsArray("Selected Bus Class is Required", "Selected Bus Class");
            if (this.dataEdit.schedules.route_id === "")
                return this.errorsArray("Route is Required", "Route");

            const resEdit = await this.callApi("post", 'schedule/update', this.dataEdit);
            console.log(resEdit)
            if (resEdit.status === 200 && resEdit.statusText === "OK") {
                this.success = "Schedule Updated Successfully";
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            } else {
                if (resEdit.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async edit(schema) {
            const resEditSchedule = await this.callApi("post", 'schedule/edit', schema);
            this.dataEdit.schedules = resEditSchedule.data.schedules;
            this.dataEdit.compare_array = resEditSchedule.data.compare_array;
            this.dataEdit.cities = resEditSchedule.data.cities;

        },

        async genericData() {
            const resCommon = await this.callApi("post", 'schedule/genericCommon');
            this.editClasses = resCommon.data.class;
            this.editDiscounts = resCommon.data.discount;
            this.editSurcharges = resCommon.data.surcharge;
            this.editRouteClasses = resCommon.data.routeClass;
            this.editBuses = resCommon.data.bus;
            this.editTerminals = resCommon.data.terminal;
            this.editCities = resCommon.data.city;
            this.editRoutes = resCommon.data.route;
        },

        async deleteSchedule(schVal, i) {
            const deletingObj = {
                url: "/schedule/delete",
                data: schVal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
            setTimeout(() => {
                // window.location.reload();
            }, 3000);
        },
    },
    computed: {
        ...
            mapGetters(['getDeletingObj'])
    },

    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        }
    }
}
;

</script>
<style scoped>
.selected-row {
    background-color: yellow !important;
}

.booked_Seat {
    background-color: rgb(255, 0, 0) !important;
}

.notForSale {
    background-color: rgb(140, 109, 109) !important;
}

.reservedForFemale {
    background-color: rgb(250, 185, 250) !important;
}


.economy {
    background-color: rgb(250, 97, 64) !important;
}

.exective {
    background-color: rgb(64, 250, 81) !important;
}

.business {
    background-color: rgb(31, 126, 91) !important;
}


.anyElseClass {
    background-color: rgb(131, 163, 199) !important;
}

.seat-img img,
.seat-img span {
    height: 40px;
    width: 40px;
    display: inline-block;
}
</style>
