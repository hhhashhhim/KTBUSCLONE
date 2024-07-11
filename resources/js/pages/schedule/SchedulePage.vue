<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Schedule</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-schedule')"
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
                            <!-- <transition name="fade">
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
                            </transition> -->
                            <!-- Table -->
                            <div class="row px-2 mb-4">
                                <div class="col-md-4">
                                    <label for="terminalFilter">Bus Class</label>
                                    <select id="terminalFilter" class="form-control"
                                            v-model="filterData.bus_class"
                                            @change="fetchSchedule()">
                                        <option value="">Select Bus Class</option>
                                        <option v-for="(item, i) in busClasses" :key="i"
                                                :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="routeFilter">Routes</label>
                                    <select id="routeFilter" class="form-control"
                                            v-model="filterData.route"
                                            @change="fetchSchedule()">
                                        <option value="">Select Route</option>
                                        <option v-for="(route, i) in routes" :key="i"
                                                :value="route.id">
                                            {{ route.name }}  ({{ route.via??'n/a' }})
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="fromDate">Departure Date</label>
                                    <input id="fromDate" type="date" class="form-control"
                                            v-model="filterData.departure_date" @change="fetchSchedule()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <div v-if="tableLoading">
                                                    <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                </div>
                                                <table v-else class="table table-striped table-hover" id="schedule_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Via</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Time</th>
                                                        <th>Route</th>
                                                        <th>Bus Class</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-schedule') || checkForSubmenuButtons('extend-schedule') || checkForSubmenuButtons('delete-schedule')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(schedule, i) in schedules" :key="i">
                                                        <td>{{ schedule.schedule.name }}</td>
                                                        <td>{{ schedule.schedule.start_date }}</td>
                                                        <td>{{ schedule.schedule.end_date }}</td>
                                                        <td>{{tConvert(schedule.departure_time)}}</td>
                                                        <td> {{ schedule.schedule.route ? schedule.schedule.route.name : "N/A" }}</td>
                                                        <td> {{
                                                                schedule.bus_class.name
                                                            }}
                                                        </td>
                                                        <td> {{
                                                                "N/A"
                                                            }}
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-info btn-sm mr-1"
                                                                    v-if="checkForSubmenuButtons('extend-schedule')"
                                                                    @click="addDays(schedule.schedule), this.extendDate.extended_days == ''"
                                                                    data-target="#addDaysModal" data-toggle="modal"
                                                                    title="Extend Schedule Range"><i
                                                                class="fas fa-plus"></i></button>
                                                            <button title="Edit Schedule"
                                                                    v-if="checkForSubmenuButtons('edit-schedule')"
                                                                    @click=" edit(schedule.schedule); genericData(); "
                                                                    class="btn btn-primary mr-1 btn-sm"><i
                                                                class="far fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-success btn-sm mr-1"
                                                                    v-if="checkForSubmenuButtons('update-time')"
                                                                    @click="editTime(schedule.schedule)"
                                                                    data-target="#editTimeModal" data-toggle="modal"
                                                                    title="Edit Time"><i
                                                                class="fas fa-clock"></i></button>
                                                            <button title="Delete Terminal"
                                                                    v-if="checkForSubmenuButtons('delete-schedule')"
                                                                    :data-target="'#' + hideFormID" @click="delId = schedule.schedule.id" data-toggle="modal"
                                                                    class="btn btn-danger btn-sm mr-1"
                                                            >
                                                                <i class="far fas fa-trash"></i>
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
                            <button type="button" class="close" @click="close()" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>No. of Days (e.g: DD) <span class="text-danger mr-1">*</span></label>
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
                            <button type="button" @click="close()" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- edit time -->
            <div class="modal fade" id="editTimeModal" tabindex="-1" aria-labelledby="addDaysModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content ">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDaysModalLabel">Edit Time</h5>
                            <button type="button" class="close" @click="close()" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 class form-group">
                                    <label for="start">Start Date <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="date"
                                        id="start"
                                        class="form-control"
                                        v-model="dataEditTime.start_date"
                                        
                                    />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 class form-group">
                                    <label for="end">End Date <span class="text-danger ml-1">*</span></label>
                                    <input
                                        type="date"
                                        id="end"
                                        class="form-control"
                                        v-model="dataEditTime.end_date"
                                        
                                    />
                                </div>
                                <div class="col-md-12 class form-group">
                                    <label for="time">Time<span class="text-danger ml-1">*</span></label>
                                    <input type="number" id="time" class="form-control" v-model="dataEditTime.time"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="updateScheduleTime()" :disabled="loading">
                                {{ loading ? 'Loading... ' : 'Update Time' }}
                            </button>
                            <button type="button" @click="close()" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
                                <span class="mr-2">Next</span><i class="fas fa-arrow-right pr-1"></i>
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    class="section2"
                    :class="activeSection != 'step1' ? 'd-none' : ''"
                >
                    <div class="row">
                        <div class="col-md-6 class form-group">
                            <label for="route">Routes <span class="text-danger ml-1">*</span></label>
                            <select
                                class="form-control"
                                id="route"
                                v-model="data.route"
                            >
                                <option value="0" selected>Select Route</option>
                                <option v-for="(route, i) in routes" :value="route.id" :key="i">
                                    {{ route.name }} {{route.via ? '(via  '+route.via+' )' : ''}}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 class form-group">
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
                    <div class="row">
                        <div class="col-md-12 class form-group">
                            <label for="route">Terminals </label>
                            <button class="btn btn-success btn-sm m-1" @click="selectAllTerminals">Select All</button>
                            <button class="btn btn-danger btn-sm " @click="deselectAllTerminals">Deselect All</button>
                            <select
                                class="form-control"
                                id="terminal" multiple
                            >
                                <option v-for="(terminal, i) in allTerminals" :value="terminal.id" :key="i">
                                    {{ terminal.name }}
                                </option>
                            </select>
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
                                <span class="mr-2">Next</span><i class="fas fa-arrow-right pr-1"></i>
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
                        <div class="col-md-12 class form-group">
                            <label for="route">Select Terminal For Discount </label>
                            <button class="btn btn-success btn-sm m-1" @click="selectAllDiscountTerminals">Select All</button>
                            <button class="btn btn-danger btn-sm " @click="deselectAllDiscountTerminals">Deselect All</button>
                            <select
                                class="form-control"
                                id="terminal-discount" multiple
                            >
                                <option v-for="(terminal, i) in allTerminals" :value="terminal.id" :key="i">
                                    {{ terminal.name }}
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
                                <span class="mr-2">Next</span><i class="fas fa-arrow-right pr-1"></i>
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
                    <div class="row justify-content-center mb-2">
                        <div class="col-md-6 border py-4">
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">Name</div>
                                <div>{{ this.dataPreview.Name ?? "N/A" }}</div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">Start Date</div>
                                <div>{{ this.dataPreview.start_date ?? "N/A" }}</div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">End Date</div>
                                <div>{{ this.dataPreview.end_date ?? "N/A" }}</div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto">
                                <div class="font-weight-bold">Time</div>
                                <div>{{ this.dataPreview.time ?? "N/A" }}</div>
                            </div>
                        </div>

                        <div class="col-md-6 border py-4">
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">Route</div>
                                <div>{{ this.dataPreview.route ?? "N/A" }}</div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">Selected Bus Class</div>
                                <div>{{ this.dataPreview.busClass ?? "N/A" }}</div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto mb-4">
                                <div class="font-weight-bold">Discount</div>
                                <div>
                                    {{
                                        this.dataPreview.discount != null ? (this.dataPreview.discount.type == "percentage" ? (this.dataPreview.discount.percentage != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.percentage + "%" : "N/A") : (this.dataPreview.discount.flat != null ? this.dataPreview.discount.name + "-" + this.dataPreview.discount.flat : "N/A")) : "N/A"
                                    }}
                                </div>
                            </div>
                            <div class="d-flex justify-content-between w-75 mx-auto">
                                <div class="font-weight-bold">Surcharge</div>
                                <div> {{
                                        this.dataPreview.surcharge != null ? (this.dataPreview.surcharge.type == "percentage" ? (this.dataPreview.surcharge.percentage != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.percentage + "%" : "N/A") : (this.dataPreview.surcharge.flat != null ? this.dataPreview.surcharge.name + "-" + this.dataPreview.surcharge.flat : "N/A")) : "N/A"
                                    }}
                                </div>
                            </div>
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
                    <!-- <div class="col-md-6 class form-group">
                        <label for="start">Start Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            id="start"
                            class="form-control"
                            v-model="dataEdit.schedules.start_date"
                            disabled
                        />
                    </div> -->
                </div>
                <!-- <div class="row">
                    <div class="col-md-6 class form-group">
                        <label for="end">End Date <span class="text-danger ml-1">*</span></label>
                        <input
                            type="date"
                            id="end"
                            class="form-control"
                            v-model="dataEdit.schedules.end_date"
                            disabled
                        />
                    </div>
                    <div class="col-md-6 class form-group">
                        <label for="time">Time<span class="text-danger ml-1">*</span></label>
                        <input type="time" id="time" class="form-control" v-model="dataEdit.schedules.time"
                        />
                    </div>
                </div> -->
                <div class="row">
                    <div class="col-md-6 class form-group">
                        <label for="surcharge">Surcharge</label>
                        <select
                            class="form-control"
                            id="surcharge"
                            v-model="dataEdit.schedules.surcharge_id"
                        >
                            <option value="0">-----None------</option>
                            <option
                                v-for="(surcharge, i) in editSurcharges"
                                :value="surcharge.id"
                                :key="i"
                            > {{ surcharge.name }} -
                                {{ surcharge.percentage != null ? surcharge.percentage + '%' : surcharge.flat }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6 class form-group">
                        <label for="discount">Discount</label>
                        <select
                            class="form-control"
                            id="discount"
                            v-model="dataEdit.schedules.discount_id"
                        >
                            <option value="0">------None-----</option>
                            <option
                                v-for="(discount, i) in editDiscounts"
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
                    <div class="col-md-12 class form-group">
                        <label for="route">Select Terminal For Discount </label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllEditDiscountTerminals">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllEditDiscountTerminals">Deselect All</button>
                        <select
                            class="form-control"
                            id="edit-terminal-discount" multiple
                            v-model="dataEdit.discountTerminals"
                        >
                            <option v-for="(terminal, i) in allTerminals" :value="terminal.id" :key="i">
                                {{ terminal.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 class form-group">
                        <label for="DiscountName">Routes <span class="text-danger ml-1">*</span></label>
                        <select
                            class="form-control"
                            id="route"
                            v-model="dataEdit.schedules.route_id">
                            <option value="0" selected>Select Route</option>
                            <option
                                v-for="(route, i) in editRoutes"
                                :value="route.id"
                                :key="i"
                            >
                                {{ route.name }}  ({{ route.via??'n/a' }})
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6 class form-group">
                        <label for="busClassEdit">Bus Class <span class="text-danger ml-1">*</span></label>
                        <select
                            class="form-control"
                            id="busClassEdit"
                            v-model="dataEdit.schedules.bus_class_id"
                            disabled
                        >
                            <option value="0" selected>Select Bus Class</option>
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
                <div class="row">
                    <div class="col-md-12 class form-group">
                        <label for="route">Terminal Visibilities </label>
                        <button class="btn btn-success btn-sm m-1" @click="selectAllEditTerminals">Select All</button>
                        <button class="btn btn-danger btn-sm " @click="deselectAllEditTerminals">Deselect All</button>
                        <select
                            class="form-control"
                            id="editTerminal" multiple
                            v-model="dataEdit.terminals"
                        >
                            <option v-for="(terminal, i) in allTerminals" :value="terminal.id" :key="i">
                                {{ terminal.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <template v-slot:button>
                    <button id="submitFormButton" class="btn btn-success" @click="updateSchedule"
                            :disabled="loading"> {{ loading ? 'Loading...' : 'Update Schedule' }}
                    </button>
                </template>
            </Edit>
            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This City ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideSchedule"
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
import vueMask from 'vue-jquery-mask';
import {mapGetters} from "vuex";

export default {
    name: "SchedulePage",
    components: {
        Add,
        Edit,
        Hide,
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
            permissions: [],
            allTerminals: [],
            formID: "schedule_form",
            editFormID: "edit_schedule_form",
            hideFormID: "hide_schedule_form",
            validationErrors: [],
            value: [],
            editDiscounts: [],
            editSurcharges: [],
            tableLoading: true,
            editRoutes: [],
            success: false,
            error: false,
            routes: "",
            cities: "",
            terminals: "",
            classes: "",
            isShowEditDiv: false,
            showTableDiv: true,
            stepTwoAddSchedule: false,
            buses: "",
            addTerminalId: "",
            TripDuration: "",
            extendDate: "",
            activeSection: 0,
            editActiveSection: 0,
            filterData:{
                bus_class: "",
                route: "",
                departure_date: ""
            },
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
                terminals: [],
                discountTerminals: [],
            },
            dataEdit: {
                schedules: [],
                cities: [],
                terminals: [],
                discountTerminals: [],
            },
            dataEditTime: {
                start_date: "",
                end_date: "",
                time: "",
                schedule_id: "",
            },
            dataPreview: {},
            delId: "",
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

        this.fetchSchedule();
        this.permissions = this.$store.state.permissions;
    },
    mounted() {
        setTimeout(() => {
            const terminal = $('#terminal');
            const terminalDiscount = $('#terminal-discount');
            const editTerminal = $('#editTerminal');
            const editTerminalDiscount = $('#edit-terminal-discount');

            // Initialize Select2
            terminal.select2({
                closeOnSelect: false
            });
            terminalDiscount.select2({
                closeOnSelect: false
            });
            editTerminal.select2({
                closeOnSelect: false
            });
            editTerminalDiscount.select2({
                closeOnSelect: false
            });

            // Handle Select2 change event
            const self = this;

            terminal.on('change', function() {
                const selectedValues = $(this).val();
                self.data.terminals = selectedValues;
            });
            terminalDiscount.on('change', function() {
                const selectedValues = $(this).val();
                self.data.discountTerminals = selectedValues;
            });
            editTerminal.on('change', function() {
                const selectedValues = $(this).val();
                self.dataEdit.terminals = selectedValues;
            });
            editTerminalDiscount.on('change', function() {
                const selectedValues = $(this).val();
                self.dataEdit.discountTerminals = selectedValues;
            });
        }, 1000);
    },
    methods: {
        close() {
            $(".modal").click();
        },
        selectAllTerminals() {
            $("#terminal > option").prop("selected", true);
            $("#terminal").trigger("change"); 
        },

        deselectAllTerminals() {
            $("#terminal > option").prop("selected", false);
            $("#terminal").trigger("change");
        },
        
        selectAllDiscountTerminals() {
            $("#terminal-discount > option").prop("selected", true);
            $("#terminal-discount").trigger("change"); 
        },

        deselectAllDiscountTerminals() {
            $("#terminal-discount > option").prop("selected", false);
            $("#terminal-discount").trigger("change");
        },
        
        selectAllEditDiscountTerminals() {
            $("#edit-terminal-discount > option").prop("selected", true);
            $("#edit-terminal-discount").trigger("change"); 
        },

        deselectAllEditDiscountTerminals() {
            $("#edit-terminal-discount > option").prop("selected", false);
            $("#edit-terminal-discount").trigger("change");
        },
        
        selectAllEditTerminals() {
            $("#editTerminal > option").prop("selected", true);
            $("#editTerminal").trigger("change"); 
        },

        deselectAllEditTerminals() {
            $("#editTerminal > option").prop("selected", false);
            $("#editTerminal").trigger("change");
        },
        async addDays(schedule) {
            this.extendDate = schedule;
        },
        async extendedDate() {
            if (this.extendDate.extended_days == '' || typeof this.extendDate.extended_days == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "No of Days is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const resExtend = await this.callApi("post", "schedule/extend", this.extendDate);
            if (resExtend.status == 200) {
                this.extendDate = "";
                this.close();
                swal({
                    title: "Success",
                    text: "Schedule Extended successfully",
                    icon: "success",
                    timer: 2000
                });
                setTimeout(() => {
                    this.loading = false;
                }, 500);
                this.fetchSchedule();
            }
            if (resExtend.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resExtend.data.errors) {
                        resExtend.data.errors[key].forEach((element) => {
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
        },
        async fetchSchedule() {
            this.tableLoading = true;
            const res = await this.callApi("post", "schedule",this.filterData);
            if (res.status == 200) {
                this.tableLoading = false;
                this.schedules = res.data;
            } else {
                console.log(res);
            }
            // setTimeout(() => {
            //     $('#schedule_table').DataTable({
            //         language: {
            //             info: '' // Set the 'info' language option to an empty string to hide the line
            //         }
            //     });
            // }, 300);
            const resGetAllRoutes = await this.callApi("post", "schedule/getRoute");
            this.routes = resGetAllRoutes.data;
            
            const resGetAllTerminals = await this.callApi("post", "schedule/getTerminals");
            this.allTerminals = resGetAllTerminals.data;

            const resGetAllClasses = await this.callApi("post", "schedule/fare-class");
            this.fareClasses = resGetAllClasses.data;

            const resGetBusClasses = await this.callApi("post", "schedule/bus_classes");
            this.busClasses = resGetBusClasses.data;

            const resSurcharge = await this.callApi("post", "schedule/surcharge/getSelective");
            this.surcharges = resSurcharge.data;

            const resDiscount = await this.callApi("post", "schedule/discount/getSelective");
            this.discounts = resDiscount.data;
        },

        async getEntireForm() {
            const resEntire = await this.callApi("post", "schedule/getEntire", this.data);
            this.dataPreview = resEntire.data;
            this.dataPreview.start_date = this.data.StartDate;
            this.dataPreview.end_date = this.data.EndDate;
            this.dataPreview.Name = this.data.name;
            this.dataPreview.time = this.data.time
        },

        tConvert: function (time) {
            time = time.toString().match(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/) || [time];
            if (time.length > 1) {
                time = time.slice(1);
                var hour = +time[0] % 12 || 12;
                var minute = time[1];
                var period = +time[0] < 12 ? 'AM' : 'PM';
                return hour + ':' + minute + ' ' + period;
            }
            return time.join(':');
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
            } else {
                const removeIndex = this.data.addTerminalsOnClick.findIndex(t => t.terminal_id == parseInt(value));
                if (removeIndex !== -1) {
                    this.data.addTerminalsOnClick.splice(removeIndex, 1);
                }
            }
        },


        clearForm() {
            this.data = {
                addTerminalsOnClick: [],
            };
            this.terminalNames = [];
            this.data.route = 0;
            this.data.busClass = 0;
            this.data.discount = 0;
            this.data.surcharge = 0;
            this.stepTwoAddSchedule = false;
            this.activeSection = 0;
        },

        previousSection(prvBtn) {
            this.activeSection = prvBtn;
        },


        validateStep(nextBtnValue) {
            //Step 1
            if (nextBtnValue == 'step1') {
                if (this.data.name == "" || typeof this.data.name == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Name Field is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.StartDate == "" || typeof this.data.StartDate == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Start Date is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.EndDate == "" || typeof this.data.EndDate == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "End Date is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.time == "" || typeof this.data.time == 'undefined')
                    return swal({
                        title: "Required!",
                        text: "Time Field is Required ",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.name && this.data.StartDate && this.data.EndDate && this.data.time) {
                    this.activeSection = nextBtnValue;
                }
            }
            //Step 2
            if (nextBtnValue == 'step2') {

                if (this.data.route == 0)
                    return swal({
                        title: "Required!",
                        text: "Please Select Route",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.busClass == 0)
                    return swal({
                        title: "Required!",
                        text: "Please Select Bus Class",
                        icon: "error",
                        timer: 2000
                    });
                if (this.data.route != 0 && this.data.busClass != 0) {
                    this.activeSection = nextBtnValue;
                }
            }
            //Step3
            if (nextBtnValue == 'step3') {
                this.activeSection = nextBtnValue;
            }
        },

        async addSchedule() {
            this.validationErrors = [];
            if (this.data.name == "") {
                return swal({
                    title: "Required!",
                    text: "Via Field is Required ",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.StartDate == "") {
                return swal({
                    title: "Required!",
                    text: "Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.EndDate == "") {
                return swal({
                    title: "Required!",
                    text: "End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.time == "") {
                return swal({
                    title: "Required!",
                    text: "Schedule Time is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.busClass == "") {
                return swal({
                    title: "Required!",
                    text: "Bus Class is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.route == "") {
                return swal({
                    title: "Required!",
                    text: "Route is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.data.cities = this.cities;
            this.loading = true;
            const res = await this.callApi("post", "schedule/store", this.data);
            if (res.status == 201) {
                this.close();
                swal({
                    title: "Success",
                    text: "Schedule Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $('#schedule_table').DataTable().destroy();
                this.loading = false;
                this.fetchSchedule();
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
            this.loading = true;
            const resEdit = await this.callApi("post", "schedule/update", this.dataEdit);
            if (resEdit.status == 200) {
                $(`#${this.editFormID}`).modal('hide');
                swal({
                    title: "Success",
                    text: "Schedule Updated Successfully",
                    icon: "success",
                    timer: 4000
                });
                $("#schedule_table").DataTable().destroy();
                this.loading = false;
                this.fetchSchedule();
            } else {
                if (resEdit.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach((element) => {
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
        
        async updateScheduleTime() {
            if (this.dataEditTime.start_date == "" || typeof this.dataEditTime.start_date == "undefined")
                return swal({
                    title: "Required!",
                    text: "Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEditTime.end_date == "" || typeof this.dataEditTime.end_date == "undefined")
                return swal({
                    title: "Required!",
                    text: "End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEditTime.time == "" || typeof this.dataEditTime.time == "undefined")
                return swal({
                    title: "Required!",
                    text: "Schedule Time is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resEdit = await this.callApi("post", "schedule/time/update", this.dataEditTime);
            if (resEdit.status == 200) {
                $(`#${this.editFormID}`).modal('hide');
                swal({
                    title: "Success",
                    text: "Schedule Updated Successfully",
                    icon: "success",
                    timer: 4000
                });
                $("#schedule_table").DataTable().destroy();
                this.loading = false;
                this.fetchSchedule();
            } else {
                if (resEdit.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach((element) => {
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

        async edit(schedule) {
            const resEditSchedule = await this.callApi("post", "schedule/edit", {id: schedule.id});
            if (resEditSchedule.status == 200) {
                this.dataEdit.schedules = resEditSchedule.data.schedules;
                this.dataEdit.terminals = resEditSchedule.data.visibilities;
                this.dataEdit.discountTerminals = resEditSchedule.data.discountTerminals;
                setTimeout(() => {
                    $("#editTerminal").select2({
                        closeOnSelect: false
                    });
                    $("#edit-terminal-discount").select2({
                        closeOnSelect: false
                    });
                }, 200);
            }
            $(`#${this.editFormID}`).modal('show');
        },
        
        async editTime(schedule) {
            this.dataEditTime.schedule_id = schedule.id
        },

        async genericData() {
            const resCommon = await this.callApi("post", "schedule/genericCommon");
            this.editDiscounts = resCommon.data.discount;
            this.editSurcharges = resCommon.data.surcharge;
            this.editRoutes = resCommon.data.route;
        },

        async hideSchedule() {
            this.loading = true;
            const resHide = await this.callApi("post", 'schedule/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Schedule Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $('#schedule_table').DataTable().destroy();
                this.fetchSchedule();
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
    watch: {
        'data.addTerminalsOnClick': {
            handler() {
                this.terminalNames = this.data.addTerminalsOnClick.map(item => {
                    const terminalObject = this.cities.find(terminal => {
                        return terminal.terminal.some(t => t.id == item.terminal_id);
                    });
                    return terminalObject ? terminalObject.terminal.find(t => t.id == item.terminal_id).name : '';
                });
            },
            deep: true
        },

        'dataEdit.schedules.route_city_terminal': {
            handler() {
                this.dataEdit.editSequence = this.dataEdit.schedules.route_city_terminal.map(item => {
                    const terminalObject = this.dataEdit.cities.find(terminal => {
                        return terminal.terminal.some(t => t.id == item.terminal_id);
                    });
                    return terminalObject ? terminalObject.terminal.find(t => t.id == item.terminal_id).name : '';
                });
            },
            deep: true
        }
    },
};
</script>
<style scoped>
.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>
