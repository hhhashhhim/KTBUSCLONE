<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Schedule</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                    @click="clearForm()"
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
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="schedule_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Via</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Time</th>
                                                        <th>Route</th>
                                                        <th>Bus Class</th>
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
                                                        <td>{{ tConvert(schedule.time) }}</td>
                                                        <td> {{ schedule.route ? schedule.route.name : "N/A" }}</td>
                                                        <td> {{
                                                                schedule.bus_class ? schedule.bus_class.name : "N/A"
                                                            }}
                                                        </td>
                                                        <td> {{
                                                                schedule.added_by ? schedule.added_by.name : "N/A"
                                                            }}
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-info btn-sm mr-1"
                                                                    @click="addDays(schedule)"
                                                                    data-target="#addDaysModal" data-toggle="modal"><i
                                                                class="fas fa-plus"></i></button>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click=" edit(schedule); genericData(); "
                                                                    class="btn btn-primary mr-1 btn-sm"><i
                                                                class="far fa-edit"></i></button>
                                                            <!--                                                            <button :data-target="'#' + deleteFormID"-->
                                                            <!--                                                                    data-toggle="modal"-->
                                                            <!--                                                                    @click="deleteSchedule(schedule, i)"-->
                                                            <!--                                                                    class="btn btn-danger btn-sm"><i-->
                                                            <!--                                                                class="far fa-trash-alt"></i></button>-->
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

            <!--Extend Schedule-->
            <div class="modal fade" id="addDaysModal" tabindex="-1" aria-labelledby="addDaysModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content ">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDaysModalLabel">Extend Schedule</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>No. of Days (e.g: DD)</label>
                                        <vue-mask
                                            class="form-control"
                                            v-model="extendDate.extended_days"
                                            mask="00"
                                            :raw="false"
                                            :options="options">
                                        </vue-mask>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="extendedDate()" :disabled="loading">
                                {{ loading ? 'Loading... ' : 'Extend Schedule' }}
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <Add
                :heading="'ADD NEW SCHEDULE'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mb-3">
                    <div
                        class="col-md-3 text-center"
                        :class="
              activeSection != 0 ? '' : 'border p-3  text-light bg-primary'
            "
                    >
                        Step 1
                    </div>
                    <div
                        class="col-md-3 text-center"
                        :class="
              activeSection != 'step1' ? '' : 'border p-3  text-light bg-info'
            "
                    >
                        Step 2
                    </div>
                    <div
                        class="col-md-3 text-center"
                        :class="
              activeSection != 'step2'
                ? ''
                : 'border p-3  text-light bg-success'
            "
                    >
                        Step 3
                    </div>
                    <div
                        class="col-md-3 text-center"
                        :class="
              activeSection != 'step3'
                ? ''
                : 'border p-3  text-light bg-warning'
            "
                    >
                        Step 4
                    </div>
                </div>
                <section class="section1" :class="activeSection != 0 ? 'd-none' : ''">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">Via <span class="text-danger ml-1">*</span></label>
                            <input
                                type="text"
                                id="name"
                                class="form-control"
                                v-model="data.name"
                            />
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="start">Start Date <span class="text-danger ml-1">*</span></label>
                            <input
                                type="date"
                                id="start"
                                class="form-control"
                                v-model="data.StartDate"
                            />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="end">End Date <span class="text-danger ml-1">*</span></label>
                            <input
                                type="date"
                                id="end"
                                class="form-control"
                                v-model="data.EndDate"
                            />
                        </div>

                        <div class="col-md-6 class form-group">
                            <label for="busCLass">Time <span class="text-danger ml-1">*</span></label>
                            <input type="time" class="form-control" v-model="data.time">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <button
                                class="btn btn-success step1 float-right"
                                @click=" validateStep('step1');"
                            >
                                Next<i class="fas fa-arrow-right pr-1"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    class="section2"
                    :class="activeSection != 'step1' ? 'd-none' : ''"
                >
                    <div class="row">
                        <div class="col-md-8 class form-group">
                            <label for="route">Routes <span class="text-danger ml-1">*</span></label>
                            <select
                                class="form-control"
                                id="route"
                                @change=" getSelectiveData('route', $event);"
                                v-model="data.route"
                            >
                                <option value="0" selected>Select Route</option>
                                <option v-for="(route, i) in routes" :value="route.id" :key="i">
                                    {{ route.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4 class form-group">
                            <label for="busCLass">Bus Class <span class="text-danger ml-1">*</span></label>
                            <select
                                class="form-control"
                                id="busCLass"
                                v-model="data.busClass"
                            >
                                <option value="0" selected>Select Route Bus CLass</option>
                                <option
                                    v-for="(type, i) in busClasses"
                                    :value="type.id"
                                    :key="i"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div
                        class="row d-flex justify-content-center"
                        v-if="stepTwoAddSchedule"
                    >
                        <div class="col-md-12 class form-group mx-2">
                            <div class="table-responsive">
                                <table
                                    class="table table-striped table-hover"
                                    id="addScheduleStep2"
                                >
                                    <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>City Name</th>
                                        <th>Terminals</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(city, i) in cities" :key="i">
                                        <template v-if="city.terminal.length >= 2">
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ city.name }}</td>
                                            <td><span v-for="item in city.terminal" :key="item.id">
                                                    <label class="colorinput mx-3">
                                                        <span>
                                                            <input type="checkbox" class="colorinput-input"
                                                                   @click="addTerminal($event, city.id)" id="routeTerminalName"
                                                                   :value="item.id"/>
                                                            <span class="colorinput-color bg-primary"></span>
                                                        </span>
                                                    </label>
                                                    <label class="checkbox-inputs" for="terminal">{{
                                                            item.name
                                                        }}</label>
                                            </span>
<!--                                                <span v-if="groupByCategory[city.id]">-->
<!--                                                    {{ allTerminalsIds }}-->
<!--                                                </span>-->
                                            </td>
                                        </template>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button
                                class="btn btn-info back1 float-left"
                                @click="previousSection(0)"
                            >
                                <i class="fas fa-arrow-left mr-1"></i>Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button
                                class="btn btn-success step2 float-right"
                                @click=" validateStep('step2');"
                            >
                                Next<i class="fas fa-arrow-right mr-1"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <section class="section3" :class="activeSection != 'step2' ? 'd-none' : ''">
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="surcharge">Surcharge</label>
                            <select
                                class="form-control"
                                id="surcharge"
                                v-model="data.surcharge"
                            >
                                <option value="0" selected>Select Surcharge</option>
                                <option
                                    v-for="(surcharge, i) in surcharges"
                                    :value="surcharge.id"
                                    :key="i"
                                >
                                    {{ surcharge.name }} -
                                    {{ surcharge.percentage != null ? surcharge.percentage + '%' : surcharge.flat }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 class form-group">
                            <label for="discount">Discount</label>
                            <select
                                class="form-control"
                                id="discount"
                                v-model="data.discount"
                            >
                                <option value="0" selected>Select Discount</option>
                                <option
                                    v-for="(discount, i) in discounts"
                                    :value="discount.id"
                                    :key="i"
                                >
                                    {{ discount.name }} -
                                    {{ discount.percentage != null ? discount.percentage + '%' : discount.flat }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button
                                class="btn btn-info back2 float-left"
                                @click="previousSection('step1')"
                            >
                                <i class="fas fa-arrow-left mr-1 border-dark"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button
                                class="btn btn-success step2 float-right"
                                @click=" validateStep('step3'); getEntireForm(); ">
                                Next<i class="fas fa-arrow-right mr-1 border-dark"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    class="section4"
                    :class="activeSection != 'step3' ? 'd-none' : ''"
                >
                    <div class="row my-3 py-2">
                        <div class="col-md-12 text-center">
                            <span class="h3 font-weight-bold text-muted"> Review </span>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-md-12 form-group table-responsive">
                            <table class="table text-dark" id="tableSchedulePreview">
                                <tbody>
                                <tr>
                                    <th colspan="2" class="mr-3">Name</th>
                                    <td colspan="2" class="border-left">{{ this.dataPreview.Name ?? "N/A" }}</td>
                                </tr>
                                <tr>
                                    <th class="mr-3">Start Date</th>
                                    <td>{{ this.dataPreview.start_date ?? "N/A" }}</td>
                                    <th class="mr-3 border-left">End Date</th>
                                    <td>{{ this.dataPreview.end_date ?? "N/A" }}</td>
                                </tr>
                                <tr>
                                    <th>Time</th>
                                    <td> {{ this.dataPreview.time ?? "N/A" }}</td>
                                    <th class="mr-3 border-left">Selected Bus Class</th>
                                    <td>{{ this.dataPreview.busClass ?? "N/A" }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2" class="mr-3">Route</th>
                                    <td colspan="2" class="border-left">{{ this.dataPreview.route ?? "N/A" }}</td>
                                </tr>
                                <tr>
                                    <th class="mr-3">Discount</th>
                                    <td> {{
                                            this.dataPreview.discount != null ? (this.dataPreview.discount.type == "percentage" ? (this.dataPreview.discount.percentage != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.percentage + "%" : "N/A") : (this.dataPreview.discount.flat != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.flat : "N/A")) : "N/A"
                                        }}
                                    </td>
                                    <th class="mr-3 border-left">Surcharge</th>
                                    <td> {{
                                            this.dataPreview.surcharge != null ? (this.dataPreview.surcharge.type == "percentage" ? (this.dataPreview.surcharge.percentage != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.percentage + "%" : "N/A") : (this.dataPreview.surcharge.flat != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.flat : "N/A")) : "N/A"
                                        }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button
                                class="btn btn-info back2 float-left"
                                @click="previousSection('step2')"
                            >
                                <i class="fas fa-arrow-left mr-1"></i> Previous
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button
                                id="submitFormButton"
                                class="btn btn-success float-right"
                                @click="addSchedule()" :disabled="loading"> {{
                                    loading ? 'Loading...' : 'Save Schedule'
                                }}
                            </button>
                        </div>
                    </div>
                </section>
            </Add>
            <!-- Add Modal End -->


            <!--            Edit Model-->
            <Edit heading="Edit Schedule" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                <!--                <div class="row mb-3">-->
                <!--                    <div class="col-md-3 text-center"-->
                <!--                         :class=" editActiveSection != 0 ? '' : 'border p-3  text-light bg-primary' ">-->
                <!--                        Step 1-->
                <!--                    </div>-->
                <!--                    <div class="col-md-3 text-center"-->
                <!--                         :class=" editActiveSection != 'step1' ? '' : 'border p-3  text-light bg-info' ">-->
                <!--                        Step 2-->
                <!--                    </div>-->
                <!--                    <div class="col-md-3 text-center"-->
                <!--                         :class=" editActiveSection != 'step2' ? '' : 'border p-3  text-light bg-success' ">-->
                <!--                        Step 3-->
                <!--                    </div>-->
                <!--                    <div class="col-md-3 text-center"-->
                <!--                         :class=" editActiveSection != 'step3' ? '' : 'border p-3  text-light bg-warning' ">-->
                <!--                        Step 4-->
                <!--                    </div>-->
                <!--                </div>-->

                <!--                <section-->
                <!--                    class="section1"-->
                <!--                    :class="editActiveSection != 0 ? 'd-none' : ''"-->
                <!--                >-->
                <div class="row">
                    <div class="col-md-6">
                        <label for="name">Name <span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            id="name"
                            class="form-control"
                            v-model="dataEdit.schedules.name"
                        />
                    </div>
                    <div class="col-md-6 class form-group">
                        <label for="start">Start Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            id="start"
                            class="form-control"
                            v-model="dataEdit.schedules.start_date"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 class form-group">
                        <label for="end">End Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            id="end"
                            class="form-control"
                            v-model="dataEdit.schedules.end_date"
                        />
                    </div>
                    <div class="col-md-6 class form-group">
                        <label for="time">Time<span class="text-danger ml-1">*</span></label>
                        <input type="time" id="time" class="form-control" v-model="dataEdit.schedules.time"
                        />
                    </div>
                </div>
                <!--                <div class="row">-->
                <!--                    <div class="col-md-6"></div>-->
                <!--                    <div class="col-md-6">-->
                <!--                        <button-->
                <!--                            class="btn btn-success step1 float-right"-->
                <!--                            @click=" editNextSection('step1'); this.stepTwoAddSchedule = true; ">-->
                <!--                            Next<i class="fas fa-arrow-right pr-1"></i>-->
                <!--                        </button>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--                </section>-->

                <!--                <section class="section2" :class="editActiveSection != 'step1' ? 'd-none' : ''">-->
                <!--                    <div class="row">-->
                <!--                        <div class="col-md-8 class form-group">-->
                <!--                            <label for="DiscountName">Routes <span class="text-danger ml-1">*</span></label>-->
                <!--                            <select-->
                <!--                                class="form-control"-->
                <!--                                id="route" @change="getSelectiveData('routeEdit', $event)"-->
                <!--                                v-model="dataEdit.schedules.route_id"-->
                <!--                            >-->
                <!--                                <option value="0" selected>Select Route</option>-->
                <!--                                <option-->
                <!--                                    v-for="(route, i) in editRoutes"-->
                <!--                                    :value="route.id"-->
                <!--                                    :key="i"-->
                <!--                                >-->
                <!--                                    {{ route.name }}-->
                <!--                                </option>-->
                <!--                            </select>-->
                <!--                        </div>-->
                <!--                        <div class="col-md-4 class form-group">-->
                <!--                            <label for="busClassEdit">Bus Class <span class="text-danger ml-1">*</span></label>-->
                <!--                            <select-->
                <!--                                class="form-control"-->
                <!--                                id="busClassEdit"-->
                <!--                                v-model="dataEdit.schedules.bus_class_id"-->
                <!--                            >-->
                <!--                                <option value="0" selected>Select Bus Class</option>-->
                <!--                                <option-->
                <!--                                    v-for="(type, i) in busClasses"-->
                <!--                                    :value="type.id"-->
                <!--                                    :key="i"-->
                <!--                                >-->
                <!--                                    {{ type.name }}-->
                <!--                                </option>-->
                <!--                            </select>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                    <div-->
                <!--                        class="row d-flex justify-content-center"-->
                <!--                        v-if="stepTwoAddSchedule"-->
                <!--                    >-->
                <!--                        <div class="col-md-12 class form-group mx-2">-->
                <!--                            <div class="table-responsive">-->
                <!--                                <table-->
                <!--                                    class="table table-striped table-hover"-->
                <!--                                    id="addScheduleStep2"-->
                <!--                                >-->
                <!--                                    <thead>-->
                <!--                                    <tr>-->
                <!--                                        <th>Sr No.</th>-->
                <!--                                        <th>City Name</th>-->
                <!--                                        <th>Terminals</th>-->
                <!--                                    </tr>-->
                <!--                                    </thead>-->
                <!--                                    <tbody>-->
                <!--                                    <tr v-for="(city, i) in dataEdit.cities" :key="i">-->
                <!--                                        <td>{{ i + 1 }}</td>-->
                <!--                                        <td>{{ city.name }}</td>-->
                <!--                                        <td>-->
                <!--                                            <span v-for="item in city.terminal" :key="item.id">-->
                <!--                                            <label class="colorinput mx-3">-->
                <!--                                                <span> <input type="checkbox" class="colorinput-input"-->
                <!--                                                              @click="editTerminal($event, city.id)"-->
                <!--                                                              v-bind:checked=" checkedSelectedTerminals(item.id) "-->
                <!--                                                              id="terminal" :value="item.id"/>-->
                <!--                                                    <span class="colorinput-color bg-primary"></span>-->
                <!--                                                </span>-->
                <!--                                            </label>-->
                <!--                                                <label class="checkbox-inputs" for="terminal">{{ item.name }}</label>\-->
                <!--                                            </span>-->
                <!--                                        </td>-->
                <!--                                    </tr>-->
                <!--                                    </tbody>-->
                <!--                                </table>-->
                <!--                            </div>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                    <div class="row">-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                class="btn btn-info back1 float-left"-->
                <!--                                @click="editPreviousSection(0)"-->
                <!--                            >-->
                <!--                                <i class="fas fa-arrow-left mr-1"></i>Previous-->
                <!--                            </button>-->
                <!--                        </div>-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                class="btn btn-success step2 float-right"-->
                <!--                                @click="editNextSection('step2')"-->
                <!--                            >-->
                <!--                                Next<i class="fas fa-arrow-right mr-1"></i>-->
                <!--                            </button>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                </section>-->

                <!--                <section-->
                <!--                    class="section3"-->
                <!--                    :class="editActiveSection != 'step2' ? 'd-none' : ''"-->
                <!--                >-->
                <!--                    <div class="row">-->
                <!--                        <div class="col-md-6 class form-group">-->
                <!--                            <label for="surcharge">Surcharge</label>-->
                <!--                            <select-->
                <!--                                class="form-control"-->
                <!--                                id="surcharge"-->
                <!--                                v-model="dataEdit.schedules.surcharge_id"-->
                <!--                            >-->
                <!--                                <option value="0" selected>Select Surcharge</option>-->
                <!--                                <option-->
                <!--                                    v-for="(surcharge, i) in editSurcharges"-->
                <!--                                    :value="surcharge.id"-->
                <!--                                    :key="i"-->
                <!--                                > {{ surcharge.name }} - -->
                <!--                                    {{ surcharge.percentage != null ? surcharge.percentage + '%' : surcharge.flat }}-->
                <!--                                </option>-->
                <!--                            </select>-->
                <!--                        </div>-->
                <!--                        <div class="col-md-6 class form-group">-->
                <!--                            <label for="discount">Discount</label>-->
                <!--                            <select-->
                <!--                                class="form-control"-->
                <!--                                id="discount"-->
                <!--                                v-model="dataEdit.schedules.discount_id"-->
                <!--                            >-->
                <!--                                <option value="0" selected>Select Discount</option>-->
                <!--                                <option-->
                <!--                                    v-for="(discount, i) in editDiscounts"-->
                <!--                                    :value="discount.id"-->
                <!--                                    :key="i"-->
                <!--                                >-->
                <!--                                    {{ discount.name }} - -->
                <!--                                    {{ discount.percentage != null ? discount.percentage + '%' : discount.flat }}-->
                <!--                                </option>-->
                <!--                            </select>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                    <div class="row">-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                class="btn btn-info back2 float-left"-->
                <!--                                @click="editPreviousSection('step1')"-->
                <!--                            >-->
                <!--                                <i class="fas fa-arrow-left mr-1 border-dark"></i> Previous-->
                <!--                            </button>-->
                <!--                        </div>-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                class="btn btn-success step2 float-right"-->
                <!--                                @click="editNextSection('step3')"-->
                <!--                            >-->
                <!--                                Next<i class="fas fa-arrow-right mr-1"></i>-->
                <!--                            </button>-->
                <!--                        </div>-->

                <!--                    </div>-->
                <!--                </section>-->

                <!--                <section-->
                <!--                    class="section4"-->
                <!--                    :class="editActiveSection != 'step3' ? 'd-none' : ''"-->
                <!--                >-->
                <!--                    <div class="row my-3 py-2">-->
                <!--                        <div class="col-md-12 text-center">-->
                <!--                            <span class="h3 font-weight-bold text-muted"> Review </span>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                    <div class="row justify-content-center">-->
                <!--                        <div class="col-md-12 form-group table-responsive">-->
                <!--                            <table class="table table-striped table-bordered text-dark">-->
                <!--                                <tbody>-->
                <!--                                <tr>-->
                <!--                                    <th class="mr-3">Name</th>-->
                <!--                                    <td colspan="3">{{ this.dataPreview.Name }}</td>-->
                <!--                                </tr>-->
                <!--                                <tr>-->
                <!--                                    <th class="mr-3">Start Date</th>-->
                <!--                                    <td>{{ this.dataPreview.start_date ?? "N/A" }}</td>-->
                <!--                                    <th class="mr-3">End Date</th>-->
                <!--                                    <td>{{ this.dataPreview.end_date ?? "N/A" }}</td>-->
                <!--                                </tr>-->
                <!--                                <tr>-->
                <!--                                    <th>Time</th>-->
                <!--                                    <td> {{ this.dataPreview.time }}</td>-->
                <!--                                    <th class="mr-3">Selected Bus Class</th>-->
                <!--                                    <td>{{ this.dataPreview.busClass }}</td>-->
                <!--                                </tr>-->
                <!--                                <tr>-->
                <!--                                    <th class="mr-3">Route</th>-->
                <!--                                    <td colspan="3">{{ this.dataPreview.route }}</td>-->
                <!--                                </tr>-->
                <!--                                <tr>-->
                <!--                                    <th class="mr-3">Discount</th>-->
                <!--                                    <td> {{-->
                <!--                                            this.dataPreview.discount != null ? (this.dataPreview.discount.type == "percentage" ? (this.dataPreview.discount.percentage != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.percentage + "%" : "N/A") : (this.dataPreview.discount.flat != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.flat : "N/A")) : "N/A"-->
                <!--                                        }}-->
                <!--                                    </td>-->
                <!--                                    <th class="mr-3">Surcharge</th>-->
                <!--                                    <td> {{-->
                <!--                                            this.dataPreview.surcharge != null ? (this.dataPreview.surcharge.type == "percentage" ? (this.dataPreview.surcharge.percentage != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.percentage + "%" : "N/A") : (this.dataPreview.surcharge.flat != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.flat : "N/A")) : "N/A"-->
                <!--                                        }}-->
                <!--                                    </td>-->
                <!--                                </tr>-->
                <!--                                </tbody>-->
                <!--                            </table>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                    <div class="row">-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                class="btn btn-info back2 float-left"-->
                <!--                                @click="editPreviousSection('step2')"-->
                <!--                            >-->
                <!--                                <i class="fas fa-arrow-left mr-1"></i> Previous-->
                <!--                            </button>-->
                <!--                        </div>-->
                <!--                        <div class="col-md-6">-->
                <!--                            <button-->
                <!--                                id="submitFormButton"-->
                <!--                                class="btn btn-success float-right"-->
                <!--                                @click="updateSchedule" :disabled="loading"-->
                <!--                            >-->
                <!--                                {{ loading ? 'Loading...' : 'Update Schedule' }}-->
                <!--                            </button>-->
                <!--                        </div>-->
                <!--                    </div>-->
                <!--                </section>-->
                <template v-slot:button>
                    <button id="submitFormButton" class="btn btn-success" @click="updateSchedule"
                            :disabled="loading"> {{ loading ? 'Loading...' : 'Update Schedule' }}
                    </button>
                </template>
            </Edit>
            <!-- Edit Model End -->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage="Are You Sure You want To Delete This Schedule ???"
            />
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import vueMask from 'vue-jquery-mask';
import {mapGetters} from "vuex";

export default {
    name: "SchedulePage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: '00',
            },
            loading: false,
            schedules: [],
            fareClasses: [],
            busClasses: [],
            discounts: [],
            surcharges: [],
            formID: "schedule_form",
            editFormID: "edit_schedule_form",
            deleteFormID: "delete_schedule_form",
            validationErrors: [],
            value: [],
            editDiscounts: [],
            editSurcharges: [],
            editTerminals: [],
            groupByCategory: [],
            allTerminalsIds : [],
            editRoutes: [],
            success: false,
            error: false,
            routes: "",
            cities: "",
            terminals: "",
            classes: "",
            routeClasses: "",
            isShowEditDiv: false,
            stepTwoAddSchedule: false,
            buses: "",
            addTerminalId: "",
            TripDuration: "",
            extendDate: "",
            activeSection: 0,
            editActiveSection: 0,
            data: {
                name: "",
                StartDate: "",
                EndDate: "",
                route: 0,
                time: "",
                surcharge: 0,
                discount: 0,
                busClass: 0,
                fareClass: 0,
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
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
        await this.fetchSchedule();
    },
    methods: {
        async addDays(sche) {
            this.extendDate = sche;
        },
        async extendedDate() {
            this.loading = true;
            const resExtend = await this.callApi("post", "schedule/extend", this.extendDate);
            if (resExtend.status == 200) {
                swal({
                    title: "Success",
                    text: "Schedule Extended successfully",
                    icon: "success",
                    timer: 2000
                });
                setTimeout(() => {
                    this.loading = false;
                }, 500);
                await this.fetchSchedule();
            }
        },
        async fetchSchedule() {

            const res = await this.callApi("post", "schedule");
            if (res.status == 200) {
                this.schedules = res.data;
            } else {
                console.log(res);
            }
            setTimeout(() => {
                $('#schedule_table').DataTable();
            }, 300);
            const resGetAllRoutes = await this.callApi("post", "schedule/getRoute");
            this.routes = resGetAllRoutes.data;

            const resGetAllClasses = await this.callApi("post", "fare-class");
            this.fareClasses = resGetAllClasses.data;

            const resGetBusClasses = await this.callApi("post", "bus_classes");
            this.busClasses = resGetBusClasses.data;

            const resSurcharge = await this.callApi("post", "surcharge/getSelective");
            this.surcharges = resSurcharge.data;

            const resDiscount = await this.callApi("post", "discount/getSelective");
            this.discounts = resDiscount.data;
        },

        async fetchTerminals(event, index) {
            const terminalRes = await this.callApi("post", "cities/terminals", {
                id: value,
            });
            if (terminalRes.status == 200) {
                this.terminals[index] = terminalRes.data;
            }
        },

        async getEntireForm() {
            const resEntire = await this.callApi(
                "post",
                "schedule/getEntire",
                this.data
            );
            this.dataPreview = resEntire.data;
            this.dataPreview.start_date = this.data.StartDate;
            this.dataPreview.end_date = this.data.EndDate;
            this.dataPreview.Name = this.data.name;
            this.dataPreview.time = this.data.time
        },

        tConvert: function (time) {
            time = time.toString().match(/^([01]\d|2[0-3])(:)([0-5]\d)?$/) || [time];

            if (time.length > 1) {
                time = time.slice(1);
                time[5] = +time[0] < 12 ? ' AM' : ' PM';
                time[0] = +time[0] % 12 || 12;
            }
            return time.join('');
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

        editGenerateMap: function (val) {
            if (val == "0") {
                this.isShowEditDiv = false;
            }
            this.isShowEditDiv = true;
        },

        addTerminal(event, cityId) {
            const value = event.target.value
            if (event.target.checked) {
                const index = this.data.addTerminalsOnClick.indexOf(value);
                if (index == -1) {
                    this.data.addTerminalsOnClick.push({
                        city_id: cityId,
                        terminal_id: parseInt(value),
                        allow: true,
                    });
                }
                this.groupByCategory = this.data.addTerminalsOnClick.reduce((group, product) => {
                    const {city_id} = product;
                    group[city_id] = group[city_id] ?? [];
                    group[city_id].push(product);
                    return group;
                }, {});
                Object.entries(this.groupByCategory[cityId]).forEach(function(item){
                    this.allTerminalsIds.push(item.terminal_id);
                });
                console.log(typeof this.groupByCategory[cityId])
            } else {
                const index = this.data.addTerminalsOnClick.indexOf(value);
                this.data.addTerminalsOnClick.splice(index, 1);
                const abc = this.groupByCategory[cityId];
                const index1 = abc.findIndex(x => x.terminal_id == value);
                this.groupByCategory[cityId].splice(index1, 1);
            }
        },

        editTerminal(event, id) {
            if (event.target.checked) {
                const value = event.target.value;
                this.dataEdit.compare_array = this.dataEdit.compare_array.map((arr) => {
                    if (arr.terminal_id == value) {
                        return {...arr, allow: !arr.allow};
                    }
                    return arr;
                });

                this.dataEdit.updated_route_city_terminal = this.dataEdit.compare_array;
            }
        },

        checkedSelectedTerminals(id) {
            let status = "";
            status = this.dataEdit.compare_array.filter((arr) => {
                if (arr.terminal_id == id) {
                    return arr;
                }
            });
            return status[0].allow;
        },

        async getSelectiveData(name, evt) {
            if (name == "route") {
                if (evt.target.value == "0") {
                    this.stepTwoAddSchedule = false;
                } else {
                    this.stepTwoAddSchedule = true;
                    const resRoute = await this.callApi("post", "schedule/getCity", {
                        id: this.data.route,
                    });
                    this.cities = resRoute.data;
                }
            }
            if (name == "routeEdit") {
                if (evt.target.value == "0") {
                    this.stepTwoAddSchedule = false;
                } else {
                    this.stepTwoAddSchedule = true;
                    const resRouteEdit = await this.callApi("post", "schedule/getCity", {
                        id: this.dataEdit.route_id,
                    });
                    this.cities = resRouteEdit.dataEdit;
                    this.terminals = resRouteEdit.dataEdit;
                }
            }
        },

        clearForm() {
            this.data = {
                addTerminalsOnClick: [],
            };
            this.data.route = 0;
            this.data.busClass = 0;
            this.data.discount = 0;
            this.data.surcharge = 0;
            this.stepTwoAddSchedule = false;
            this.activeSection = 0;
        },

        editNextSection(nextBtn) {
            //Step 1
            if (nextBtn == 'step1') {
                if (this.dataEdit.schedules.name == "" || typeof this.dataEdit.schedules.name == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Name Field is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.start_date == "" || typeof this.dataEdit.schedules.start_date == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Start Date is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.end_date == "" || typeof this.dataEdit.schedules.end_date == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "End Date is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.time == "" || typeof this.dataEdit.schedules.time == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Time Field is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.name && this.dataEdit.schedules.start_date && this.dataEdit.schedules.end_date && this.dataEdit.schedules.time) {
                    this.editActiveSection = nextBtn;
                }
            }
            //Step 2
            if (nextBtn == 'step2') {
                if (this.dataEdit.schedules.route_id == 0)
                    return swal({
                        title: "Required!",
                        text: "Please Select Route",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.bus_class_id == 0)
                    return swal({
                        title: "Required!",
                        text: "Please Select Bus Class",
                        icon: "error",
                        timer: 2000
                    });
                if (this.dataEdit.schedules.route_id != 0 && this.dataEdit.schedules.bus_class_id != 0) {
                    this.editActiveSection = nextBtn;
                }
            }
            //Step3
            if (nextBtn == 'step3') {
                this.editActiveSection = nextBtn;
            }
            // this.editActiveSection = nextBtn;
        },

        previousSection(prvBtn) {
            this.activeSection = prvBtn;
        },

        editPreviousSection(prvBtn) {
            this.editActiveSection = prvBtn;
        },

        validateStep(nextBtnValue) {
            //Step 1
            if (nextBtnValue == 'step1') {
                // if (this.data.name == "" || typeof this.data.name == 'undefined')
                //     return swal({
                //         title: "Required!",
                //         text: "Name Field is Required ",
                //         icon: "error",
                //         timer: 2000
                //     });
                // if (this.data.StartDate == "" || typeof this.data.StartDate == 'undefined')
                //     return swal({
                //         title: "Required!",
                //         text: "Start Date is Required ",
                //         icon: "error",
                //         timer: 2000
                //     });
                // if (this.data.EndDate == "" || typeof this.data.EndDate == 'undefined')
                //     return swal({
                //         title: "Required!",
                //         text: "End Date is Required ",
                //         icon: "error",
                //         timer: 2000
                //     });
                // if (this.data.time == "" || typeof this.data.time == 'undefined')
                //     return swal({
                //         title: "Required!",
                //         text: "Time Field is Required ",
                //         icon: "error",
                //         timer: 2000
                //     });
                // if (this.data.name && this.data.StartDate && this.data.EndDate && this.data.time) {
                this.activeSection = nextBtnValue;
                // }
            }
            //Step 2
            if (nextBtnValue == 'step2') {

                // if (this.data.route == 0)
                //     return swal({
                //         title: "Required!",
                //         text: "Please Select Route",
                //         icon: "error",
                //         timer: 2000
                //     });
                //
                // if (this.data.addTerminalsOnClick.length == 0) {
                //     return swal({
                //         title: "Required!",
                //         text: "Please Select Terminals of Selected Route",
                //         icon: "error",
                //         timer: 2000
                //     });
                // }
                //
                // if (this.data.addTerminalsOnClick.length > 0 && this.data.addTerminalsOnClick.length < 2) {
                //     return swal({
                //         title: "Required!",
                //         text: "Please Select at Least 2 Terminals of Selected Route",
                //         icon: "error",
                //         timer: 2000
                //     });
                // }
                //
                // if (this.data.busClass == 0)
                //     return swal({
                //         title: "Required!",
                //         text: "Please Select Bus Class",
                //         icon: "error",
                //         timer: 2000
                //     });
                // // if (this.data.fareClass == 0)
                // //     return swal({
                // //         title: "Required!",
                // //         text: "Please Select Fare Class",
                // //         icon: "error",
                // //         timer: 2000
                // //     });
                // if (this.data.route != 0 && this.data.busClass != 0 /*&& this.data.fareClass != 0*/) {
                this.activeSection = nextBtnValue;
                // }
            }
            //Step3
            // if (nextBtnValue == 'step3') {
            this.activeSection = nextBtnValue;
            // }
        },

        async addSchedule() {
            this.validationErrors = [];
            if (this.data.name == "")
                return swal({
                    title: "Required!",
                    text: "Via Field is Required ",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.StartDate == "")
                return swal({
                    title: "Required!",
                    text: "Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.EndDate == "")
                return swal({
                    title: "Required!",
                    text: "End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.time == "")
                return swal({
                    title: "Required!",
                    text: "Schedule Time is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.busClass == "")
                return swal({
                    title: "Required!",
                    text: "Bus Class is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.route == "")
                return swal({
                    title: "Required!",
                    text: "Route is Required",
                    icon: "error",
                    timer: 2000
                });
            this.data.cities = this.cities;
            this.loading = true;
            const res = await this.callApi("post", "schedule/store", this.data);
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Schedule Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $('#schedule_table').DataTable().destroy();
                this.loading = false;
                await this.fetchSchedule();
            } else {
                if (res.status == 422) {
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
                            timer: 2000
                        });

                    }
                }
            }
        },

        async updateSchedule() {
            if (this.dataEdit.schedules.name == "" || typeof this.dataEdit.schedules.name == "undefined")
                return swal({
                    title: "Required!",
                    text: "name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.schedules.start_date == "" || typeof this.dataEdit.schedules.start_date == "undefined")
                return swal({
                    title: "Required!",
                    text: "Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.schedules.end_date == "" || typeof this.dataEdit.schedules.end_date == "undefined")
                return swal({
                    title: "Required!",
                    text: "End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.schedules.time == "" || typeof this.dataEdit.schedules.time == "undefined")
                return swal({
                    title: "Required!",
                    text: "Schedule Time is Required",
                    icon: "error",
                    timer: 2000
                });
            // if (this.dataEdit.schedules.bus_class_id == "0")
            //     swal({
            //         title: "Required!",
            //         text: "Bus Class is Required",
            //         icon: "error",
            //         timer: 2000
            //     });
            // if (this.dataEdit.schedules.route_id == "0")
            //     return swal({
            //         title: "Required!",
            //         text: "Route is Required",
            //         icon: "error",
            //         timer: 2000
            //     });
            this.loading = true;
            const resEdit = await this.callApi(
                "post",
                "schedule/update",
                this.dataEdit
            );
            if (resEdit.status == 200) {
                swal({
                    title: "Success",
                    text: "Schedule Updated Successfully \n Go to Fare Table Page, Click Update Schedule Button to Update all Schedules & Schedule Time",
                    icon: "success",
                    timer: 4000
                });
                $("#schedule_table").DataTable().destroy();
                this.loading = false;
                await this.fetchSchedule();
            } else {
                if (resEdit.status == 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async edit(schedule) {
            this.dataEdit.schedules = schedule;
            // const resEditSchedule = await this.callApi("post", "schedule/edit", schedule_id);
            // this.dataEdit.compare_array = resEditSchedule.data.compare_array;
            // this.dataEdit.cities = resEditSchedule.data.cities;
        },

        async genericData() {
            const resCommon = await this.callApi("post", "schedule/genericCommon");
            this.editDiscounts = resCommon.data.discount;
            this.editSurcharges = resCommon.data.surcharge;
            this.editRoutes = resCommon.data.route;
        },

        async deleteSchedule(schVal, i) {
            const deletingObj = {
                url: "schedule/delete",
                data: schVal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },

    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1);
                $('#schedule_table').DataTable().destroy();
                this.fetchSchedule();
            }
        },
    },
};
</script>
<!--<style scoped>-->
<!--#tableSchedulePreview, th, td{-->
<!--    border: 3px solid;-->
<!--}-->
<!--</style>-->
