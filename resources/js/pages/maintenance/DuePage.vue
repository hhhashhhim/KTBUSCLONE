<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Due Maintenance</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('update-meter-reading')" href="#" data-toggle="modal"
                                    :data-target="'#' + readingFormID" class="btn btn-warning" @click="clearForm()">
                                    Update Meter Reading
                                </a>

                                <a v-if="checkForSubmenuButtons('add-irregular-maintenance')" href="#"
                                    data-target="#maintenance_add" data-toggle="modal" class="btn btn-info mx-1"
                                    @click="dueMaintenanceFrom(data = null, 1)">
                                    Irregular Maintenance
                                </a>

                                <a v-if="checkForSubmenuButtons('link-maintenance')" href="#" data-toggle="modal"
                                    :data-target="'#' + linkFormID" class="btn btn-primary" @click="clearForm()">
                                    Link New Maintenance
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- <div class="row">
                                <div class="col-6 col-md-6 col-lg-6">
                                    <h5>Total Bus Chart</h5>
                                    <div class="recent-report__chart">
                                        <div id="busChart"></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-6 col-lg-6">
                                    <h5>Total Part Chart</h5>
                                    <div class="recent-report__chart">
                                        <div id="partChart"></div>
                                    </div>
                                </div>
                            </div> -->
                            <transition name="fade">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="error">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        @click="error = !error">
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
                                                <table class="table table-striped table-hover" id="maintenance_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Fleet Name/Number</th>
                                                            <th>Current Reading</th>
                                                            <th>Total Parts</th>
                                                            <th>Due Parts</th>
                                                            <th v-if="checkForSubmenuButtons('add-maintenance')">Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(data, i) in mainData" :key="i">
                                                            <td
                                                                :class="{ 'border border-danger border-right-0': data.due_parts > 0 }">
                                                                {{ data.bus_number }}</td>
                                                            <td
                                                                :class="{ 'border border-danger border-right-0 border-left-0': data.due_parts > 0 }">
                                                                {{ data.current_reading }} (km)</td>
                                                            <td
                                                                :class="{ 'border border-danger border-right-0 border-left-0': data.due_parts > 0 }">
                                                                {{ data.total_parts }}</td>
                                                            <td
                                                                :class="{ 'border border-danger border-right-0 border-left-0': data.due_parts > 0 }">
                                                                {{ data.due_parts }}</td>
                                                            <td
                                                                :class="{ 'border border-danger border-left-0': data.due_parts > 0 }">
                                                                <button
                                                                    v-if="checkForSubmenuButtons('edit-link-maintenance')"
                                                                    class="btn btn-primary ml-1"
                                                                    data-target="#editLinking_form" data-toggle="modal"
                                                                    @click="editFleetDetails(data.bus_id)"
                                                                    title="Edit Link Part">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-info mx-1" data-toggle="modal"
                                                                    data-target="#showDetails"
                                                                    @click="fetchDueFleetDetail(data.bus_id)"
                                                                    title="View Link Part">
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
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal Maintenance-->
           <Add :heading="'Due Maintenance Add'" :errors="this.validationErrors" :success="success" :formID="formID">
    <div class="row">
        <!-- Fleet -->
        <div class="form-group col-md-6">
            <label>Fleet <span class="text-danger ml-1">*</span></label>
            <select class="form-control" v-model="postData.fleetId" :disabled="checkDisable">
                <option value="">Select Fleet</option>
                <option v-for="(fleet, i) in fleets" :key="i" :value="fleet.id">
                    {{ fleet.bus_number }}
                </option>
            </select>
        </div>

        <!-- Part -->
        <div class="form-group col-md-6">
            <label>Part <span class="text-danger ml-1">*</span></label>
            <select class="form-control" v-model="postData.partId" :disabled="checkDisable">
                <option value="">Select Part</option>
                <option v-for="(part, i) in parts" :key="i" :value="part.id">
                    {{ part.name }}
                </option>
            </select>
        </div>

        <!-- Days (Conditional) -->
        <div class="form-group col-md-6" v-if="showDaysInput">
            <label>Maintenance Days <span class="text-danger ml-1">*</span></label>
            <input type="number" class="form-control" v-model="postData.days" placeholder="Enter days">
        </div>

        <!-- Date (Conditional) -->
        <div class="form-group col-md-6" v-if="showDateInput">
            <label>Maintenance Date <span class="text-danger ml-1">*</span></label>
            <input type="date" class="form-control" v-model="postData.date">
        </div>

        <!-- Current Reading -->
        <div class="form-group col-md-6">
            <label>Current Reading <span class="text-danger ml-1">*</span></label>
            <input type="number" class="form-control" placeholder="Meter Reading" v-model="postData.currentReading" />
        </div>

        <!-- Amount -->
        <div class="form-group col-md-6">
            <label>Total Amount <span class="text-danger ml-1">*</span></label>
            <input type="number" class="form-control" v-model="postData.amount" />
        </div>

        <!-- Company Paid -->
        <div class="form-group col-md-6">
            <label>Paid By Company <span class="text-danger ml-1">*</span></label>
            <input type="number" class="form-control" v-model="postData.companyPaid" />
        </div>

        <!-- Evidence -->
        <div class="form-group col-md-6">
            <label>Evidence <span class="text-danger ml-1">*</span></label>
            <input type="file" class="form-control" @change="evidenceImage($event)" id="imageField" />
        </div>

        <!-- Detail -->
        <div class="form-group col-md-12">
            <label>Detail <span class="text-danger ml-1">*</span></label>
            <textarea class="form-control" v-model="postData.detail"></textarea>
        </div>
    </div>

    <template v-slot:button>
        <button type="button" class="btn btn-primary" @click="dueMaintenanceAdd" :disabled="loading">
            {{ loading ? 'Loading...' : 'Add' }}
        </button>
    </template>
</Add>


            <!-- Model for update metere reading -->
            <Add :heading="'Meter Reading Update'" :errors="this.validationErrors" :success="success"
                :formID="readingFormID">
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="city_id">Fleet <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="readingData.fleetId">
                            <option value="">Select Fleet</option>
                            <option v-for="(fleet, i) in fleets" :key="i" :value="fleet.id">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Current Reading <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="Meter Reading"
                            v-model="readingData.currentReading" />
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="readingUpdate" :disabled="loading">{{ loading
                        ?
                        'Loading...' : 'Update' }}
                    </button>
                </template>
            </Add>

            <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Fleet Detail</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="partChartInModal" style="max-width: 360px; margin: 0 auto 20px;"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="d-flex">
                                        <p class="mb-0"><b>Bus Number: </b></p>
                                        <p class="pl-2 mb-0"> {{ due_bus.bus_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex">
                                        <p class="mb-0"><b>Current Reading: </b></p>
                                        <p class="pl-2 mb-0"> {{ due_bus.current_reading ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div></div>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>

                                        <th>Fleet Part</th>
                                        <th>Maintenance Required After</th>
                                        <th>Last Maintenance At</th>
                                        <th>Last Maintenance Date</th>
                                        <th>Due Maintenance At</th>
                                        <th width="300px">Health Status</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(single, i) in due_bus.sortedPartLink" :key="i"
                                        :class="{ 'border border-danger': single.due === true }">

                                        <td> {{ single.maintenance_part.name }}</td>
                                        <td>
                                            <p v-if="single.maintenance_days">
                                                {{ single.maintenance_days }} (Days)
                                            </p>
                                            <p v-else>
                                                {{ single.maintenance_after }} (km)
                                            </p>
                                        </td>
                                        <td>
                                            <p v-if="single.maintenance_days_date">
                                                {{ single.maintenance_days_date }}
                                            </p>
                                            <p v-else>
                                                {{ single.maintenance_at }} (km)
                                            </p>
                                        </td>
                                        <td> {{ single.maintenance_date ?? 'N/A' }}</td>
                                        <td>
                                            <p v-if="single.next_maintenance_date">
                                                {{ single.next_maintenance_date }}
                                            </p>
                                            <p v-else>
                                                {{ parseInt(single.maintenance_after) + parseInt(single.maintenance_at)
                                                }} (km)
                                            </p>
                                        </td>



                                        <td class="align-middle">
                                            <div class="progress-text">
                                                {{ single.percentage + '%' }}
                                            </div>
                                            <div class="progress" data-height="6">
                                                <div class="progress-bar" :class="getProgressColor(single.percentage)"
                                                    :style="{ width: single.percentage + '%' }">
                                                </div>
                                            </div>

                                            <div v-if="single.next_maintenance_date" class="text-muted small mt-1">
                                                Next Maintenance: {{ single.next_maintenance_date }}
                                            </div>
                                        </td>

                                        <td>
                                            <span v-if="single.due == true" class="badge badge-danger">Due</span>
                                            <span v-else class="badge badge-success">Up To Date</span>
                                        </td>
                                        <td>
                                            <button
                                                v-if="checkForSubmenuButtons('add-maintenance') && single.due == true"
                                                class="btn btn-primary mx-1" data-target="#maintenance_add"
                                                data-toggle="modal"
                                                @click="dueMaintenanceFrom({ bus_id: single.bus_id, part_id: single.part_id }, 0)"
                                                title="Add Maintenance">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            
                           <div>
                             <h5 class="">History</h5>
<div v-for="(his, i) in history" :key="i" class="card mb-3 shadow">
  <div class="card-header p-3 d-flex justify-content-between">
    <h6 class="mb-0">Part Name: {{ his.part_name.name }}</h6>
    <h6 class="mb-0">Maintenance Date: {{ formatDate(his.time) }}
</h6>
  </div>
  <div class="card-body p-3 m-0">

    <p class="mb-0">{{ his.detail }}</p>
  </div>
</div>
                           </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary mx-1" data-toggle="modal" data-target="#maintenanceRecord"
                                @click="maintenanceRecord(due_bus.id)" title="View Link Part">
                                Maintenance Record
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                @click="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="maintenanceRecord" tabindex="-1" role="dialog"
                aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Fleet Detail</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                @click="closeModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="maintenance_table">
                                    <thead>
                                        <tr>
                                            <th>Evidence</th>
                                            <th>Part</th>
                                            <th>Company Paid</th>
                                            <th>Maintenance Type</th>
                                            <th>Detail</th>
                                            <th>Date</th>
                                            <th v-if="checkForSubmenuButtons('edit-maintenance')">Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(data, i) in record" :key="i">
                                            <td>
                                                <a :href="($store.state.app_url + 'uploads/maintenance/' + data.evidence)"
                                                    target="_blank">
                                                    <img :src="($store.state.app_url + 'uploads/maintenance/' + data.evidence)"
                                                        style="width:70px;height:70px;" alt="">
                                                </a>
                                            </td>
                                            <td>{{ data.part_name.name }} </td>
                                            <td>{{ data.company_paid }} </td>
                                            <td>{{ data.maintenance_type == 1 ? 'Irregular' : 'Due' }} </td>
                                            <td>{{ data.detail }} </td>
                                            <td>{{ data.time }} </td>
                                            <td v-if="checkForSubmenuButtons('edit-maintenance')">
                                                <button v-if="checkForSubmenuButtons('edit-maintenance')"
                                                    class="btn btn-primary mx-1" data-target="#maintenance_udpate"
                                                    data-toggle="modal" @click="updateMaintenanceFrom(data)"
                                                    title="Edit Maintenance">
                                                    <i class="far fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                @click="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <Edit :heading="'Due Maintenance Update'" :errors="this.validationErrors" :success="success"
                :editForm="'maintenance_udpate'">
                <div class="row">
                    <div class=" form-group col-md-6">
                        <label for="city_id">Fleet <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editData.fleetId" disabled>
                            <option value="">Select Fleet</option>
                            <option v-for="(fleet, i) in fleets" :key="i" :value="fleet.id">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>
                    <div class=" form-group col-md-6">
                        <label for="city_id">Part <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="editData.partId" disabled>
                            <option value="">Select Part</option>
                            <option v-for="(part, i) in parts" :key="i" :value="part.id">
                                {{ part.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Total Amount <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="Total Amount"
                            v-model="editData.amount" />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Paid By Company <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" placeholder="" v-model="editData.companyPaid" />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Evidence</label>
                        <input type="file" class="form-control" placeholder="" @change="editEvidenceImage($event)" />
                    </div>
                    <div class="form-group col-md-6">
                        <a :href="($store.state.app_url + 'uploads/maintenance/' + editData.evidence)" target="_blank">
                            <img :src="($store.state.app_url + 'uploads/maintenance/' + editData.evidence)"
                                style="width:70px;height:70px;border: 1px solid grey;" alt="">
                            Click to Prview
                        </a>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="refOfHiring">Detail <span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" v-model="editData.detail">
            </textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="dueMaintenanceUpdate" :disabled="loading">{{
                        loading ? 'Loading...' : 'Update' }}
                    </button>
                </template>
            </Edit>

            <Add :heading="'Link Part With Bus'" :errors="this.validationErrors" :success="success"
                :formID="linkFormID">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Select Bus <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" v-model="fleetId">
                            <option value="" selected>Select Bus</option>
                            <option v-for="(fleet, i) in fleets" :value="fleet.id" :key="i">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Current Reading (km) <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" min="0" v-model="currentReading" />
                    </div>
                    <div class="col-md-12 d-flex align-items-center">
                        <div class="col-md-6">
                            <h5>Select Part For Maintenance</h5>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th>Use Days</th>
                                    <th>Maintenance Required After</th>
                                    <th>Last Maintenance At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="index in loop" :key="index">
                                    <td>
                                        <select class="form-control rounded-0" @change="saveRow($event, 'rowPart')">
                                            <option value="" selected>Select Part </option>
                                            <option v-for="(part, i) in parts" :value="part.id" :key="i">
                                                {{ part.name }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- ✅ Use Days Checkbox -->
                                    <td>
                                        <input type="checkbox" v-model="useDaysPerRow[index - 1]">
                                    </td>

                                    <!-- ✅ KM-based Inputs -->
                                    <td v-if="!useDaysPerRow[index - 1]">
                                        <label class="form-label mb-1 small text-muted">Required After (km)</label>
                                        <input type="number" class="form-control" min="0"
                                            @keyup="saveRow($event, 'rowAfter')" />
                                    </td>

                                    <td v-if="!useDaysPerRow[index - 1]">
                                        <label class="form-label mb-1 small text-muted">Last Maintenance At (km)</label>
                                        <input type="number" class="form-control" min="0"
                                            @keyup="saveRow($event, 'rowLast')" />
                                    </td>

                                    <!-- ✅ Days-based Inputs -->
                                    <td v-if="useDaysPerRow[index - 1]">
                                        <label class="form-label mb-1 small text-muted">Required After (Days)</label>
                                        <input type="number" class="form-control" min="0"
                                            @keyup="saveRow($event, 'rowDays')" />
                                    </td>

                                    <!-- ✅ New Maintenance Date (Days) -->
                                    <td v-if="useDaysPerRow[index - 1]">
                                        <label class="form-label mb-1 small text-muted">Maintenance Date (Days)</label>
                                        <input type="date" class="form-control"
                                            @change="saveRow($event, 'rowDateDays')" />
                                    </td>

                                    <!-- ✅ Actions -->
                                    <td>
                                        <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                        <button class="btn btn-outline-danger" v-if="index != 1"
                                            @click="removeRow($event)">Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="linkMaintenance" :disabled="loading">{{
                        loading ?
                            'Loading...' : 'Link' }}
                    </button>
                </template>
            </Add>

            <Edit heading="Edit Maintenance" :errors="this.validationErrors" :success="success"
                :editForm="editLinkFormID">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Select Bus <span class="text-danger ml-1">*</span></label>
                        <select class="form-control rounded-0" v-model="edit.fleetId">
                            <option value="" selected>Select Bus</option>
                            <option v-for="(fleet, i) in fleets" :value="fleet.id" :key="i">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Current Reading (km) <span class="text-danger ml-1">*</span></label>
                        <input type="number" class="form-control" min="0" v-model="edit.currentReading" />
                    </div>
                    <div class="col-md-12 d-flex align-items-center">
                        <div class="col-md-6">
                            <h5>Select Part For Maintenance</h5>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th>Use Days</th>
                                    <th>Maintenance Required After</th>
                                    <th>Last Maintenance At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="index in edit.loop" :key="index"
                                    v-if="edit.fleetDetails.maintenance_part_link">

                                    <!-- Part Dropdown -->
                                    <td>
                                        <select class="form-control rounded-0" @change="editSaveRow($event, 'rowPart')"
                                            :value="edit.fleetPart[index - 1] || ''">
                                            <option value="" selected>Select Part</option>
                                            <option v-for="(part, i) in parts" :value="part.id" :key="i">{{ part.name }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- ✅ Checkbox -->
                                    <td>
                                        <input type="checkbox" v-model="edit.useDaysPerRow[index - 1]">
                                    </td>

                                    <!-- ✅ Required After (km or days) -->
                                    <td>
                                        <template v-if="edit.useDaysPerRow[index - 1]">
                                            <label class="form-label small text-muted">Required After (Days)</label>
                                            <input type="number" class="form-control" min="0"
                                                @keyup="editSaveRow($event, 'rowDays')"
                                                :value="edit.fleetDetails.maintenance_part_link[index - 1]?.maintenance_days || ''" />
                                        </template>
                                        <template v-else>
                                            <label class="form-label small text-muted">Required After (km)</label>
                                            <input type="number" class="form-control" min="0"
                                                @keyup="editSaveRow($event, 'rowAfter')"
                                                :value="edit.maintenanceAfter[index - 1] || ''" />
                                        </template>
                                    </td>

                                    <!-- ✅ Last Maintenance At (km) - only if not using days -->
                                    <td v-if="!edit.useDaysPerRow[index - 1]">
                                        <label class="form-label small text-muted">Last Maintenance At (km)</label>
                                        <input type="number" class="form-control" min="0"
                                            @keyup="editSaveRow($event, 'rowLast')"
                                            :value="edit.maintenanceAt[index - 1] || ''" />
                                    </td>
                                    <td v-if="edit.useDaysPerRow[index - 1]">
                                        <label class="form-label small text-muted">Last Maintenance Date</label>
                                        <input type="date" class="form-control" @change="editSaveRow($event, 'rowDate')"
                                            :value="edit.maintenanceDateDays[index - 1] || ''" />

                                    </td>

                                    <!-- Empty cell to keep table aligned when using days -->


                                    <!-- ✅ Maintenance Date -->

                                    <!-- Add/Remove Buttons -->
                                    <td>
                                        <button class="btn btn-outline-primary mx-2" @click="editAddRow">Add</button>
                                        <button class="btn btn-outline-danger" v-if="index !== 1"
                                            @click="editRemoveRow($event)">Remove</button>
                                    </td>
                                </tr>

                            </tbody>


                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateLinkMaintenance" :disabled="loading">{{
                        loading ? 'Loading...' : 'Link' }}
                    </button>
                </template>
            </Edit>

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import { mapGetters } from "vuex";

export default {
    name: "DuePage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            linkFormID: "link_add",
            editLinkFormID: "editLinking_form",
            formID: "maintenance_add",
            readingFormID: "reading_update",
            loading: false,
            checkDisable: false,
            validationErrors: [],
            due_bus: [],
            record: [],
            fleetId: "",
            currentReading: "",
            fleetPart: [],
            maintenanceAfter: [],
            maintenanceAt: [],
            fleetDetails: [],
            fleetParts: [],
            mainData: [],
            fleets: [],
            parts: [],
            permissions: [],
            postData: {
                fleetId: '',
                partId: '',
                currentReading: '',
                amount: '',
                companyPaid: '',
                evidence: '',
                detail: '',
                maintenanceType: '',
                days: "",
                date: ""
            },
            editData: {
                maintenanceId: '',
                fleetId: '',
                partId: '',
                currentReading: '',
                amount: '',
                companyPaid: '',
                evidence: '',
                detail: '',
                maintenanceType: '',
            },
            edit: {
                fleetDetails: [],
                fleetId: "",
                currentReading: "",
                fleetPart: [],
                maintenanceAfter: [],
                maintenanceAt: [],
                maintenanceDays: [],
                maintenanceDateDays: [],
                useDaysPerRow: [],
                loop: 1,
            },
            readingData: {
                fleetId: '',
                currentReading: '',
            },
            loop: 1,
            chartData: null,
            partData: null,
            singleBusChart: null,
            useDays: false,
            maintenanceDays: [],
            maintenanceDateDays: [],
            useDaysPerRow: [],
            history:[],
        };
    },
    created() {
        $('.modal').remove();
        this.fetchData();
        this.permissions = this.$store.state.permissions;
    },
    methods: {

        addDays(dateStr, days) {
            const date = new Date(dateStr);
            date.setDate(date.getDate() + parseInt(days));
            return date;
        },
        formatDate(date) {
            const d = new Date(date);
            return d.toISOString().split('T')[0]; // Format as 'YYYY-MM-DD'
        },
        clearForm: function () {
            this.data = {};
            this.reverseRoute = 1;
        },
        closeModal() {
            $(".modal").click();
        },
        async fetchData() {
            const fleetRes = await this.callApi("post", "fleet/maintenance/due");
            if (fleetRes.status === 200) {

                this.mainData = fleetRes.data.mainData;
                this.fleets = fleetRes.data.busDrop;
                this.parts = fleetRes.data.partDrop;
                this.chartData = fleetRes.data.busChart;
                this.partData = fleetRes.data.partChart;
            }

            setTimeout(() => {
                $('#maintenance_table').DataTable({
                    ordering: false
                });
            }, 300);
        },
        async dueMaintenanceFrom(data, type) {
            this.postData.maintenanceType = type;
            this.postData.fleetId = data ? data.bus_id : '';
            this.postData.partId = data ? data.part_id : '';
            this.postData.currentReading = "";
            this.postData.amount = "";
            this.postData.companyPaid = "";
            this.postData.evidence = "";
            this.postData.detail = "";
            $("#imageField").val('');
            this.checkDisable = data ? true : false;
        },
        async fetchDueFleetDetail(id) {
            const fleetDetailRes = await this.callApi("post", "fleet/single/due/detail", { id: id });
            if (fleetDetailRes.status === 200) {
                this.due_bus = fleetDetailRes.data.due_bus;
                this.singleBusChart = fleetDetailRes.data.singleBusChart;
                this.history = fleetDetailRes.data.maintenancesHistory;
                this.renderModalPartChart();
            }
        },
        getProgressColor(percentage) {
            if (percentage >= 75) {
                return 'bg-success'; // new / fresh
            } else if (percentage >= 50) {
                return 'bg-grey'; // moderate
            } else if (percentage >= 25) {
                return 'bg-orange'; // warning
            } else {
                return 'bg-danger'; // due / expired
            }
        },
        async maintenanceRecord(id) {
            const maintenanceRes = await this.callApi("post", "fleet/maintenance/record", {
                bus_id: id
            });
            if (maintenanceRes.status === 200) {

                this.record = maintenanceRes.data.mainData;
            }

            setTimeout(() => {
                $('#maintenance_table').DataTable({
                    ordering: false
                });
            }, 300);
        },
        async evidenceImage(e) {
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png|pdf|docx|doc)$/i)) {

                const eviImage = e.target.files[0];
                this.postData.evidence = eviImage;


            } else {
                e.target.value = '';
                this.postData.evidence = '';
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png, .pdf, .docx, .doc",
                    icon: "error",
                    timer: 2000
                });
            }
        },
        async updateMaintenanceFrom(data) {
            this.editData.maintenanceType = data.maintenance_type;
            this.editData.maintenanceId = data.id;
            this.editData.fleetId = data.bus_id;
            this.editData.partId = data.part_id;
            this.editData.currentReading = data.bus_name.current_reading;
            this.editData.amount = data.amount;
            this.editData.companyPaid = data.company_paid;
            this.editData.evidence = data.evidence;
            this.editData.detail = data.detail;
        },
        async dueMaintenanceUpdate() {
            // validation for empty data
            if (!this.editData.fleetId || !this.editData.partId || !this.editData.currentReading || !this.editData.amount ||
                !this.editData.companyPaid || !this.editData.detail) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            const config = {
                headers: { 'content-type': 'multipart/form-data' }
            }

            let formData = new FormData();
            formData.append('maintenanceId', this.editData.maintenanceId);
            formData.append('fleetId', this.editData.fleetId);
            formData.append('partId', this.editData.partId);
            formData.append('currentReading', this.editData.currentReading);
            formData.append('amount', this.editData.amount);
            formData.append('companyPaid', this.editData.companyPaid);
            formData.append('evidence', this.editData.evidence);
            formData.append('detail', this.editData.detail);
            formData.append('maintenanceType', this.editData.maintenanceType);


            const res = await this.callApi("post", "fleet/maintenance/due/update", formData, config);
            if (res.status === 200) {
                this.loading = false;
                $('#maintenance_table').DataTable().destroy();
                $('#maintenance_udpate').click();
                this.editData.maintenanceId = "";
                this.editData.maintenanceType = "";
                this.editData.fleetId = "";
                this.editData.partId = "";
                this.editData.currentReading = "";
                this.editData.amount = "";
                this.editData.companyPaid = "";
                this.editData.evidence = "";
                this.editData.detail = "";
                swal({
                    title: "Success",
                    text: "Maintenance Update",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchData();
                this.loading = false;
            }
            else {
                this.loading = false;
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
                            timer: 2000
                        });

                    }
                }
            }
        },
        async editEvidenceImage(e) {
            if (e.target.files[0].name.match(/\.(jpg|jpeg|png|pdf|docx|doc)$/i)) {

                const eviImage = e.target.files[0];
                this.editData.evidence = eviImage;


            } else {
                e.target.value = '';
                this.editData.evidence = '';
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png, .pdf, .docx, .doc",
                    icon: "error",
                    timer: 2000
                });
            }
        },
         async dueMaintenanceAdd() {
        // Basic validation
        if (!this.postData.fleetId || !this.postData.partId || !this.postData.currentReading ||
            !this.postData.amount || !this.postData.companyPaid || !this.postData.evidence || !this.postData.detail ||
            (this.showDaysInput && !this.postData.days) ||
            (this.showDateInput && !this.postData.date)
        ) {
            return swal({ title: "Error", text: "Please Fill All Required Fields", icon: "error", timer: 2000 });
        }

        this.loading = true;
        const config = { headers: { 'content-type': 'multipart/form-data' } };
        let formData = new FormData();
        formData.append('fleetId', this.postData.fleetId);
        formData.append('partId', this.postData.partId);
        formData.append('currentReading', this.postData.currentReading);
        formData.append('amount', this.postData.amount);
        formData.append('companyPaid', this.postData.companyPaid);
        formData.append('evidence', this.postData.evidence);
        formData.append('detail', this.postData.detail);
        formData.append('maintenanceType', this.postData.maintenanceType);
        formData.append('days', this.showDaysInput ? this.postData.days : '');
        formData.append('date', this.showDateInput ? this.postData.date : '');

        const res = await this.callApi("post", "fleet/maintenance/due/add", formData, config);

        if (res.status === 201) {
            $(".modal").click();
            $('#maintenance_table').DataTable().destroy();
            this.resetForm();
            swal({ title: "Success", text: "Maintenance Added", icon: "success", timer: 2000 });
            await this.fetchData();
        } else {
            if (res.status === 422) {
                let errorContent = "";
                let count = 0;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
                        errorContent += (++count) + " - " + element + "\n";
                    });
                }
                swal({ title: "Error", text: errorContent, icon: "error", timer: 2000 });
            }
        }
        this.loading = false;
    },
        async readingUpdate() {
            // validation for empty data
            if (!this.readingData.fleetId || !this.readingData.currentReading) {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;
            const res = await this.callApi("post", "fleet/meter/reading/update", this.readingData);
            if (res.status === 200) {
                $(".modal").click();
                this.loading = false;
                $('#maintenance_table').DataTable().destroy();
                this.readingData.fleetId = "";
                this.readingData.currentReading = "";
                swal({
                    title: "Success",
                    text: "Meter Reading Updated",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchData();
                this.loading = false;
            }
            else {
                this.loading = false;
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
                            timer: 2000
                        });

                    }
                }
            }
        },
        clearForm: function () {
            this.data = {};
            this.reverseRoute = 1;
        },
        saveRow(event, fieldName) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;

            if (fieldName == "rowPart") {
                this.fleetPart[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowAfter") {
                this.maintenanceAfter[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowLast") {
                this.maintenanceAt[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowDays") {
                this.maintenanceDays[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowDateDays") {
                this.maintenanceDateDays[getRowNumber - 1] = event.target.value;
            }
        },

        editSaveRow(event, fieldName) {
            const getRowNumber = event.target.closest('tr').rowIndex - 1;

            if (fieldName === "rowPart") {
                this.edit.fleetPart[getRowNumber] = event.target.value;
            }

            if (fieldName === "rowDays") {
                const value = event.target.value;
                this.edit.maintenanceDays[getRowNumber] = value;
                this.edit.maintenanceDateDays[getRowNumber] = value;

                if (!this.edit.fleetDetails.maintenance_part_link[getRowNumber]) {
                    this.edit.fleetDetails.maintenance_part_link[getRowNumber] = {};
                }
                this.edit.fleetDetails.maintenance_part_link[getRowNumber].maintenance_days = value;
                this.edit.fleetDetails.maintenance_part_link[getRowNumber].maintenance_days_date = value;
            }
            if (fieldName === "rowDate") {
                this.edit.maintenanceDateDays[getRowNumber] = event.target.value;

                if (!this.edit.fleetDetails.maintenance_part_link[getRowNumber]) {
                    this.edit.fleetDetails.maintenance_part_link[getRowNumber] = {};
                }
                this.edit.fleetDetails.maintenance_part_link[getRowNumber].maintenance_days_date = event.target.value;
            }

            if (fieldName === "rowAfter") {
                this.edit.maintenanceAfter[getRowNumber] = event.target.value;
            }

            if (fieldName === "rowLast") {
                this.edit.maintenanceAt[getRowNumber] = event.target.value;
            }
        },

       async linkMaintenance() {
    if (!this.fleetId || !this.currentReading || this.fleetPart.length === 0) {
        return swal({
            title: "Error",
            text: "Please fill all required fields.",
            icon: "error",
            timer: 2000
        });
    }

    for (let i = 0; i < this.fleetPart.length; i++) {
        const part = this.fleetPart[i];
        const isUsingDays = this.useDaysPerRow[i];
        const after = this.maintenanceAfter[i];
        const at = this.maintenanceAt[i];
        const days = this.maintenanceDays[i];
        const dates = this.maintenanceDateDays[i];

        if (!part) {
            return swal({
                title: "Error",
                text: `Please select a part for row ${i + 1}.`,
                icon: "error",
                timer: 2000
            });
        }

        if (isUsingDays) {
            if (!days || !dates) {
                return swal({
                    title: "Error",
                    text: `Please enter maintenance days for row ${i + 1}.`,
                    icon: "error",
                    timer: 2000
                });
            }
        } else {
            if (!after || !at) {
                return swal({
                    title: "Error",
                    text: `Please enter both KM fields for row ${i + 1} or switch to Days.`,
                    icon: "error",
                    timer: 2000
                });
            }
        }
    }

    // ✅ Clean data before sending (convert "" → null, cast to int where needed)
    const data = {
        fleetId: this.fleetId,
        currentReading: this.currentReading,
        fleetPart: this.fleetPart,
        maintenanceAfter: this.maintenanceAfter.map(v => v === "" ? null : (v !== null ? parseInt(v) : null)),
        maintenanceAt: this.maintenanceAt.map(v => v === "" ? null : (v !== null ? parseInt(v) : null)),
        maintenanceDays: this.maintenanceDays.map(v => v === "" ? null : (v !== null ? parseInt(v) : null)),
        maintenanceDateDays: this.maintenanceDateDays.map(v => v === "" ? null : v), // keep as string (date)
        useDaysPerRow: this.useDaysPerRow
    };

    this.loading = true;
    const res = await this.callApi("post", "fleet/part/link", data);

    if (res.status === 200) {
        $(".modal").click();
        this.loading = false;
        $('#maintenance_table').DataTable().destroy();

        // ✅ Reset fields
        this.fleetId = "";
        this.currentReading = "";
        this.loop = 0;
        this.fleetPart = [];
        this.maintenanceAfter = [];
        this.maintenanceAt = [];
        this.maintenanceDays = [];
        this.maintenanceDateDays = [];
        this.useDaysPerRow = [];

        swal({
            title: "Success",
            text: "Maintenance Added",
            icon: "success",
            timer: 2000
        });

        setTimeout(() => { this.loop = 1; }, 2000);
        await this.fetchData();
    } else {
        this.loading = false;
        if (res.status == 422) {
            let errorContent = "";
            let count = 0;
            for (const key in res.data.errors) {
                res.data.errors[key].forEach((element) => {
                    errorContent += (++count) + " - " + element + "\n";
                });
            }
            swal({ title: "Error", text: errorContent, icon: "error", timer: 2000 });
        }
    }
},

        async updateLinkMaintenance() {
            if (!this.edit.fleetId || !this.edit.currentReading || this.edit.fleetPart.length === 0) {
                return swal({ title: "Error", text: "Please fill all required fields.", icon: "error", timer: 2000 });
            }

            for (let i = 0; i < this.edit.fleetPart.length; i++) {
                const partId = this.edit.fleetPart[i];
                const useDays = this.edit.useDaysPerRow[i];
                const linkData = this.edit.fleetDetails.maintenance_part_link[i] || {};

                const afterKm = this.edit.maintenanceAfter[i];
                const atKm = this.edit.maintenanceAt[i];
                const afterDays = this.edit.maintenanceDays[i];
                const afterDateDays = this.edit.maintenanceDateDays[i];

                const savedAfterKm = linkData.maintenance_after;
                const savedAtKm = linkData.maintenance_at;
                const savedAfterDays = linkData.maintenance_days;

                if (!partId) {
                    return swal({ title: "Error", text: `Row ${i + 1}: Part is missing.`, icon: "error", timer: 2000 });
                }

                if (useDays) {
                    const hasDays = afterDays !== null && afterDays !== undefined && afterDays !== '';
                    const isNewRow = !linkData || Object.keys(linkData).length === 0;

                    if (!hasDays && isNewRow) {
                        return swal({ title: "Error", text: `Row ${i + 1}: Required After (Days) is missing.`, icon: "error", timer: 2000 });
                    }

                    this.edit.maintenanceAfter[i] = null;
                    this.edit.maintenanceAt[i] = null;
                    this.edit.maintenanceDays[i] = hasDays ? afterDays : savedAfterDays;
                } else {
                    const finalAfterKm = afterKm ?? savedAfterKm;
                    const finalAtKm = atKm ?? savedAtKm;
                    if (!finalAfterKm || !finalAtKm) {
                        return swal({ title: "Error", text: `Row ${i + 1}: Required After (km) or Last Maintenance At (km) is missing.`, icon: "error", timer: 2000 });
                    }

                    this.edit.maintenanceAfter[i] = finalAfterKm;
                    this.edit.maintenanceAt[i] = finalAtKm;
                    this.edit.maintenanceDays[i] = null;
                }
            }

            const data = {
                fleetId: this.edit.fleetId,
                currentReading: this.edit.currentReading,
                fleetPart: this.edit.fleetPart,
                maintenanceAfter: this.edit.maintenanceAfter,
                maintenanceAt: this.edit.maintenanceAt,
                maintenanceDays: this.edit.maintenanceDays,
                maintenanceDateDays: this.edit.maintenanceDateDays
            };

            this.loading = true;
            const res = await this.callApi("post", "fleet/part/link/update", data);

            if (res.status === 200) {
                $(".modal").click();
                $('#maintenance_table').DataTable().destroy();

                this.edit.fleetId = "";
                this.edit.currentReading = "";
                this.edit.loop = 0;
                this.edit.fleetPart = [];
                this.edit.maintenanceAfter = [];
                this.edit.maintenanceAt = [];
                this.edit.maintenanceDays = [];
                this.edit.maintenanceDateDays = [];
                this.edit.useDaysPerRow = [];

                swal({ title: "Success", text: "Maintenance Updated", icon: "success", timer: 2000 });
                await this.fetchData();
            } else {
                if (res.status === 422 && res.data.errors) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (++count) + " - " + element + "\n";
                        });
                    }
                    swal({ title: "Error", text: errorContent, icon: "error", timer: 2000 });
                }
            }

            this.loading = false;
        },
        addRow() {
            this.loop++;
            this.useDaysPerRow.push(false);
        },
        removeRow(event) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.fleetPart.splice((getRowNumber - 1), 1);
            this.maintenanceAfter.splice((getRowNumber - 1), 1);
            this.maintenanceAt.splice((getRowNumber - 1), 1);
            this.edit.maintenanceDays.splice((getRowNumber - 1), 1);
            this.edit.maintenanceDateDays.splice(getRowNumber - 1, 1);
            this.edit.useDaysPerRow.splice((getRowNumber - 1), 1);
            event.target.parentElement.parentElement.remove();
            // this.loop--;
        },
        editAddRow() {
            this.edit.loop++;
            this.edit.fleetPart.push("");
            this.edit.maintenanceAfter.push("");
            this.edit.maintenanceAt.push("");
            this.edit.maintenanceDays.push("");
            this.edit.maintenanceDateDays.push("");
            this.edit.useDaysPerRow.push(false);
        },

        editRemoveRow(event) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.edit.fleetPart.splice(getRowNumber - 1, 1);
            this.edit.maintenanceAfter.splice(getRowNumber - 1, 1);
            this.edit.maintenanceAt.splice(getRowNumber - 1, 1);
            this.edit.maintenanceDays.splice(getRowNumber - 1, 1);
            this.edit.maintenanceDateDays.splice(getRowNumber - 1, 1);
            this.edit.useDaysPerRow.splice(getRowNumber - 1, 1);
            event.target.parentElement.parentElement.remove();
        },

        async editFleetDetails(id) {
            const fleetDetailRes = await this.callApi("post", "fleet/single/part/link", { id });

            if (fleetDetailRes.status === 200) {
                this.edit.loop = 0;
                this.edit.fleetPart = [];
                this.edit.maintenanceAfter = [];
                this.edit.maintenanceAt = [];
                this.edit.maintenanceDays = [];
                this.edit.maintenanceDateDays = [];
                this.edit.useDaysPerRow = [];

                const links = fleetDetailRes.data.maintenance_part_link;
                this.edit.loop = links.length;
                this.edit.fleetDetails = fleetDetailRes.data;
                this.edit.fleetId = fleetDetailRes.data.id;
                this.edit.currentReading = fleetDetailRes.data.current_reading;

                for (let i = 0; i < links.length; i++) {
                    const link = links[i];
                    this.edit.fleetPart.push(link.part_id);
                    this.edit.maintenanceAfter.push(link.maintenance_after);
                    this.edit.maintenanceAt.push(link.maintenance_at);
                    this.edit.maintenanceDays.push(link.maintenance_days);
                    this.edit.maintenanceDateDays.push(link.maintenance_days_date);
                    this.edit.useDaysPerRow.push(!!link.maintenance_days);
                }
            }
        },
        renderModalPartChart() {
            if (!this.singleBusChart || !this.singleBusChart.labels || !this.singleBusChart.series) {
                return; // ⛔ don't render if data is incomplete
            }
            // Destroy previous chart if needed (optional for repeated openings)
            if (this.modalPartChartInstance) {
                this.modalPartChartInstance.destroy();
            }

            const options = {
                chart: {
                    type: 'pie',
                    width: 360,
                    height: 220
                },
                labels: this.singleBusChart.labels,
                series: this.singleBusChart.series, // static sample values
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    itemMargin: {
                        vertical: 2
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: { width: 200, height: 200 },
                        legend: { position: 'bottom' }
                    }
                }]
            };

            this.modalPartChartInstance = new ApexCharts(
                document.querySelector("#partChartInModal"),
                options
            );

            this.modalPartChartInstance.render();
        }

    },
    beforeUnmount() {
        if (this.busChartInstance) {
            this.busChartInstance.destroy();
        }
        if (this.partChartInstance) {
            this.partChartInstance.destroy();
        }
    },
    mounted() {
        this.$watch('chartData', (newVal) => {
            if (newVal) {

                const options = {
                    chart: { width: 360, height: 220, type: 'pie' },
                    labels: newVal.labels,
                    series: newVal.series,
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: { width: 200, height: 200 },
                            legend: { position: 'bottom' }
                        }
                    }],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        itemMargin: { vertical: 2 }
                    }
                };

                this.busChartInstance = new ApexCharts(document.querySelector("#busChart"), options);
                this.busChartInstance.render();
            }
        });

        this.$watch('partData', (newVal) => {
            if (newVal) {
                const options = {
                    chart: { width: 360, height: 220, type: 'pie' },
                    labels: newVal.labels,
                    series: newVal.series,
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: { width: 200, height: 200 },
                            legend: { position: 'bottom' }
                        }
                    }],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        itemMargin: { vertical: 2 }
                    }
                };

                this.partChartInstance = new ApexCharts(document.querySelector("#partChart"), options);
                this.partChartInstance.render();
            }
        });

        this.renderModalPartChart();
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
        heading: function () {
            return from.name + "<i class='fa fa-user'></i>" + to.name;
        },
    },
    watch: {
    'postData.partId'(newVal) {
        if (!newVal) {
            this.showDaysInput = false;
            this.showDateInput = false;
            return;
        }
        const selectedPart = this.parts.find(p => p.id === newVal);
        if (selectedPart) {
            this.showDaysInput = !!selectedPart.maintenance_days;
            this.showDateInput = !!selectedPart.maintenance_days_date;
        }
    }
},
};
</script>
<style scoped>
table,
table * {
    font-size: 10px;
}

.modal-cell {
    padding: 0 !important;
    position: relative;
}

.modal-cell .modal-btn {
    height: 100%;
    transition: 0.5s transform;
}

.modal-cell:hover .modal-btn {
    position: absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}

.header-select {
    width: 35%;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s;
}

.fade-enter,
.fade-leave-to

/* .fade-leave-active below version 2.1.8 */
    {
    opacity: 0;
}

table,
tr,
th,
td,
option,
select,
label,
button,
a,
div,
p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
