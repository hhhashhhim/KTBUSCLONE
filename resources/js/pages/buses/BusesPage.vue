<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a href="#addBus" data-toggle="modal" class="btn btn-primary">
                                    Add New Bus
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
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
                                                    id="edit_loc"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Bus Number</th>
                                                        <th>Chassis Number</th>
                                                        <th>Insurance Number</th>
                                                        <th>No. of Seats</th>
                                                        <th>Route Permit</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(bus, i) in buses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ bus.bus_number }}</td>
                                                        <td>{{ bus.chassis_number }}</td>
                                                        <td>{{ bus.insurance_number }}</td>
                                                        <td>{{ bus.no_of_seats }}</td>
                                                        <td>{{ bus.route_permit_number }}</td>
                                                        <td v-if="bus.added_by">{{ bus.added_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>
                                                            <a
                                                                href="#view-modal"
                                                                data-toggle="modal"
                                                                @click="viewBus(bus)"
                                                                class="btn btn-info mx-1"
                                                            >
                                                                <i class="far fa-eye"></i>
                                                            </a>
                                                            <a
                                                                href="#edit-modal"
                                                                data-toggle="modal"
                                                                @click="editBus(bus)"
                                                                class="btn btn-primary mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteBus(bus, i)"
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
                heading="New Bus"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
            </Add>
            <!--view modal-->
            <div
                class="modal fade"
                id="view-modal"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h4 class="modal-title">View Bus Details</h4>
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="modal"
                                        aria-label="Close"
                                        @click="close"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-striped table-hover"
                                            id="view_bus"
                                        >
                                            <thead>
                                            <tr>
                                                <th>Bus Number</th>
                                                <th>Chassis Number</th>
                                                <th>Insurance Number</th>
                                                <th>No. of Seats</th>
                                                <th>Route Permit</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>{{ dataView.bus_number }}</td>
                                                <td>{{ dataView.chassis_number }}</td>
                                                <td>{{ dataView.insurance_number }}</td>
                                                <td>{{ dataView.no_of_seats }}</td>
                                                <td>{{ dataView.route_permit_number }}</td>
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

            <div class="modal fade" id="addBus" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h4 class="modal-title">Add Bus</h4>
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="modal"
                                        aria-label="Close"
                                        @click="close"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div
                                        class="alert alert-danger alert-dismissible fade show"
                                        role="alert"
                                        v-if="this.validationErrors.length"
                                    >
                                        <button
                                            type="button"
                                            class="close"
                                            data-dismiss="alert"
                                            aria-label="Close"
                                        >
                                            <span aria-hidden="true">&times;</span>
                                            <span class="sr-only">Close</span>
                                        </button>
                                        <!-- {{ this.validationErrors.length }} -->
                                        <ul>
                                            <li v-for="(error, i) in this.validationErrors" :key="i">
                                                {{ error.desc }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div
                                        class="alert alert-success alert-dismissible fade show"
                                        role="alert"
                                        v-if="success"
                                    >
                                        <button
                                            type="button"
                                            class="close"
                                            data-dismiss="alert"
                                            aria-label="Close"
                                        >
                                            <span aria-hidden="true">&times;</span>
                                            <span class="sr-only">Close</span>
                                        </button>
                                        {{ success }}
                                    </div>
                                    <slot></slot>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="name">Bus Number</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter Bus Name"
                                                v-model="data.busNumber"
                                            />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="name">Chassis Number</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter Chasis Number"
                                                v-model="data.chassisNumber"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="name">Insurance Number</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter Insurance Number"
                                                v-model="data.insuranceNumber"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="name">No. of Seats</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter No. of Seats"
                                                v-model="data.noOfSeats"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="name">Route Permit Number</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter Route Permit Number"
                                                v-model="data.routePermit"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="city_id">Fare Classes</label>
                                            <select class="form-control" v-model="data.fare_class">
                                                <option value="">Select Fare Class</option>
                                                <option
                                                    v-for="(fareClass, i) in fareClasses"
                                                    :key="i"
                                                    :value="fareClass.id"
                                                >
                                                    {{ fareClass.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="name">No. of Rows</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter No. of Rows"
                                                v-model="data.noOfRows"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="name">No. of Cols</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="Enter No. of Cols"
                                                v-model="data.noOfCols"
                                                @keypress="isNumber($event)"
                                            />
                                        </div>
                                        <div class="form-group col-md-4 my-4 pt-2">
                                            <button
                                                type="button"
                                                class="btn btn-block btn-warning"
                                                @click="generateMap"
                                            >
                                                Generate Seat Map
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mx-3 mainRow" v-if="isShowDiv">
                                        <div class="form-group col-md-5 border py-3">
                                            <tr
                                                class="seat-img p-0 m-0 s"
                                                v-for="(record, rowIndex) in data.seatMap"
                                                :key="rowIndex"
                                            >
                                                <td
                                                    v-for="(col, colIndex) in record"
                                                    :key="colIndex"
                                                    :class="col.reserved ? 'selected-row border' : ''"
                                                >
                                                    <img
                                                        @click="changeStatus(rowIndex, colIndex)"
                                                        :src="
                              $store.state.app_url +
                              'assets/img/buses/available_seat_img.gif'
                            "
                                                        alt=""
                                                    />
                                                </td>
                                            </tr>
                                        </div>
                                        <div class="form-group col-md-5 border py-3">
                                            <tr
                                                class="seat-img p-0 m-0"
                                                v-for="(record, rowIndex) in data.seatMap"
                                                :key="rowIndex"
                                            >
                                                <td v-for="(col, colIndex) in record" :key="colIndex">
                                                    <!-- data-toggle="modal"
                                                        data-target="#setSeatClass"
                                                        @click="updateSeatData(rowIndex, colIndex)" -->
                                                    <img
                                                        v-if="col.reserved"
                                                        :src="
                              $store.state.app_url +
                              'assets/img/buses/booked_seat_img.gif'
                            "
                                                        alt=""
                                                    />
                                                    <span v-else></span>
                                                </td>
                                            </tr>
                                        </div>
                                        <div class="col-md-2 form-group  ">
                                            <!--                            <div class="btn selected-row border-dark border-2">-->
                                            <!--                                    <span class="text-dark p-1">Selected</span>-->
                                            <!--                            </div>-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ul style="list-style: none;" class="m-0 p-0 ">
                                                        <li style="display:inline; ">
                                                            <div
                                                                style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                                class="selected-row mr-1 border">
                                                            </div>
                                                            <span class="text-nowrap">Selected</span>
                                                        </li>
                                                        <br>
                                                        <li style="display:inline;">
                                                            <div
                                                                style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                                class="booked_Seat mr-1 border">
                                                            </div>
                                                            <span class="text-nowrap">Booked</span>
                                                        </li>
                                                        <br>
                                                        <li style="display:inline; ">
                                                            <div
                                                                style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                                class="notForSale mr-1 border">
                                                            </div>
                                                            <span class="text-nowrap">Not For Sale</span>
                                                        </li>
                                                        <br>
                                                        <li style="display:inline; ">
                                                            <div
                                                                style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                                class="anyElseClass pr-1 border">
                                                            </div>
                                                            <span class="text-nowrap">Other Class</span>

                                                </li>
                                                <br>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul style="list-style: none;" class="m-0 p-0">
                                                <li style="display:inline; ">
                                                    <div
                                                        style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                        class="economy mr-1 border">
                                                    </div>
                                                    <span class="text-nowrap">Economy</span>
                                                </li>
                                                <br>
                                                <li style="display:inline; ">
                                                    <div
                                                        style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                        class="exective mr-1 border">
                                                    </div>
                                                    <span class="text-nowrap">Executive</span>
                                                </li>
                                                <br>
                                                <li style="display:inline; ">
                                                    <div
                                                        style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                        class="business mr-1 border">
                                                    </div>
                                                    <span class="text-nowrap">Business</span>
                                                </li>
                                                <br>
                                                <li style="display:inline; ">
                                                    <div
                                                        style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"
                                                        class="reservedForFemale mr-1 border">
                                                    </div>
                                                    <span class="text-wrap">Reserved For Female</span>
                                                </li>
                                                <br>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="addBuses"> Add Bus </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add Modal -->
            <!--Seat Class-->

            <!--End Seat Class-->

            <!--Edit Modal-->
            <Edit
                heading="Edit Bus"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Bus Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Bus Name"
                            v-model="dataEdit.bus_number"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Chassis Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Chasis Number"
                            v-model="dataEdit.chassis_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Insurance Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Insurance Number"
                            v-model="dataEdit.insurance_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">No. of Seats</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Seats"
                            v-model="dataEdit.no_of_seats"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Route Permit Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Route Permit Number"
                            v-model="dataEdit.route_permit_number"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="city_id">Fare Classes</label>
                        <select class="form-control" v-model="dataEdit.fare_class_id">
                            <option value="">Select Fare Class</option>
                            <option
                                v-for="(fareClass, i) in fareClasses"
                                :key="i"
                                :value="fareClass.id"
                            >
                                {{ fareClass.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="name">No. of Rows</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Rows"
                            v-model="dataEdit.no_of_rows"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name">No. of Cols</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Cols"
                            v-model="dataEdit.no_of_cols"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-4 my-4 pt-2">
                        <button
                            type="button"
                            class="btn btn-block btn-warning"
                            @click="editGenerateMap"
                        >
                            Generate Seat Map
                        </button>
                    </div>
                </div>
                <div class="row mx-1 mainRow" v-if="isShowEditDiv">
                    <div class="form-group col-md-6 border py-3">
                        <tr
                            class="seat-img p-0 m-0"
                            v-for="(record, rowIndex) in dataEdit.seat_map"
                            :key="rowIndex"
                        >
                            <td
                                v-for="(col, colIndex) in record"
                                :key="colIndex"
                                :class="col.reserved ? 'selected-row border' : ''"
                            >
                                <img
                                    @click="changeEditStatus(rowIndex, colIndex)"
                                    :src="$store.state.app_url + 'assets/img/buses/available_seat_img.gif'"
                                    :src="
                    $store.state.app_url +
                    'assets/img/buses/available_seat_img.gif'
                  "
                                    alt=""
                                />
                            </td>
                        </tr>
                    </div>
                </div>
                <template v-slot:button>
                <button
                        type="button"
                        class="btn btn-primary"
                        @click="updateBus"
                    >
                        Update Bus
                    </button>
                </template>
            </Edit>
            <!-- Add Modal -->
            <Delete
                confirmationMessage="Are You Sure You want To Delete This Bus Record ???"
            />

            <!-- Modal -->
            <div
                class="modal fade"
                id="setSeatClass"
                tabindex="-1"
                aria-labelledby="staticBackdropLabel"
                aria-hidden="true"

            >
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h4 class="modal-title">Add Class To Seat</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="seat_class">Seat Class</label>
                                            <select class="form-control" v-model="seatClass">
                                                <option value="0">Select Class</option>
                                                <option
                                                    v-for="(fareClass, i) in fareClasses"
                                                    :key="i"
                                                    :value="fareClass.name"
                                                >
                                                    {{ fareClass.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="seat_type">Seat Type</label>
                                            <select class="form-control" v-model="seatType">
                                                <option value="0">Select Type</option>
                                                <option value="reserved_for_female">
                                                    Reserved for Female
                                                </option>
                                                <option value="not_for_sale">Not for Sale</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button
                                                type="button"
                                                class="btn btn-block btn-success"
                                                @click=" addSeatData(updateSeatValue.modalColId, updateSeatValue.modalRowId)"
                                                data-dismiss="modal"
                                            >
                                                Add Seat Data
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
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";

import {mapGetters} from "vuex";

export default {
    name: "buses",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            buses: [],
            seatClass: "0",
            seatType: "0",
            fareClasses: [],
            updateSeatValue: [],
            validationErrors: "",
            records: "",
            columns: "",
            details: "",
            dataView: {},
            formID: "newBuses",
            isShowDiv: false,
            isShowEditDiv: false,
            seatNo: 0,
            data: {
                busNumber: "",
                fare_class: "",
                chassisNumber: "",
                insuranceNumber: "",
                noOfSeats: "",
                routePermit: "",
                noOfRows: "",
                noOfCols: "",
                seatMap: [],
            },
            dataEdit: {
                busNumber: "",
                fare_class: "",
                chassisNumber: "",
                insuranceNumber: "",
                noOfSeats: "",
                routePermit: "",
                noOfRows: "",
                no_of_cols: "",
                seatMap: [],
            },
            delId: "",
            success: false,
            errors: false,
        };
    },
    async created() {
        const res = await this.callApi("post", "buses");
        if (res.status === 200) {
            this.buses = res.data;
        } else {
            console.log(res);
        }

        const resFareClass = await this.callApi("post", "fare-class");
        if (resFareClass.status === 200) {
            this.fareClasses = resFareClass.data;
        } else {
            console.log(res);
        }
    },

    methods: {
        addSeatData: function (col, row) {
            if (this.seatType === 0) {
                return this.errorsArray("Please Select Seat Type", "Seat Type");
            }
            if (this.seatClass === 0) {
                return this.errorsArray("Please Select Seat Class", "Seat Class");
            }

            const seatDetails = this.data.seatMap[row][col];
            this.data.seatMap[row][col] = {
                reserved: seatDetails.reserved,
                seatNo: seatDetails.seatNo,
                class: this.seatClass,
                type: this.seatType,
            };

            this.success = "Seat Classes Added Successfully ";
        },
        updateSeatData: function (rowId, colId) {
            (this.seatClass = "0"),
                (this.seatType = "0"),
                (this.updateSeatValue = {
                    modalRowId: rowId,
                    modalColId: colId,
                });
            console.log(this.updateSeatValue);
        },
        getSeatFareClass: function () {
            $("#setSeatClass").appendTo("body");
        },
        changeStatus: function (row, col) {
            if (this.data.seatMap[row][col].reserved) {
                this.seatNo--;
                this.data.seatMap[row][col] = {
                    reserved: false,
                    seatNo: 0,
                };
            } else {
                this.seatNo++;
                this.data.seatMap[row][col] = {
                    reserved: true,
                    seatNo: this.seatNo,
                };
            }
        },
        changeEditStatus: function (row, col) {
            if (this.dataEdit.seat_map[row][col].reserved) {
                this.seatNo--;
                this.dataEdit.seat_map[row][col] = {
                    reserved: false,
                    seatNo: 0,
                };
            } else {
                this.seatNo++;
                this.dataEdit.seat_map[row][col] = {
                    reserved: true,
                    seatNo: this.seatNo,
                };
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
        generateMap: function () {
            this.validationErrors = [];
            let vm = this;
            if (vm.data.noOfRows <= 15) {
                if (vm.data.noOfCols <= 7) {
                    let arr,
                        count = 0;
                    var map = new Array(parseInt(vm.data.noOfRows)); // creating rows
                    for (var i = 0; i < map.length; i++) {
                        map[i] = new Array(vm.data.noOfCols); // creating columns
                    }

                    for (var i = 0; i < vm.data.noOfRows; i++) {
                        for (var j = 0; j < vm.data.noOfCols; j++) {
                            count++;
                            map[i][j] = {
                                reserved: false,
                                seatNo: 0,
                            };
                        }
                    }
                    this.isShowDiv = true;
                    return (this.data.seatMap = map);
                } else {
                    return this.errorsArray("No of Cols must be less then or equal to 7", "No Of Cols");
                }
            } else {
                return this.errorsArray("No of Rows must be less then or equal to 15", "No Of Rows");

            }
        },
        editGenerateMap: function () {
            this.isShowEditDiv = true;
        },

        async addBuses() {
            this.validationErrors = [];
            if (this.data.busNumber === "")
                return this.errorsArray("Bus Number is Required", "busNumber");
            if (this.data.chassisNumber === "")
                return this.errorsArray("Chassis Number is Required", "chassisNumber");
            if (this.data.insuranceNumber === "")
                return this.errorsArray("Insurance Number is Required", "insuranceNumber"
                );
            if (this.data.noOfSeats === "")
                return this.errorsArray("No. Of Seats is Required", "noOfSeats");
            if (this.data.routePermit === "")
                return this.errorsArray("Route Permit is Required", "routePermit");
            if (this.data.noOfRows === "0")
                return this.errorsArray("Bus Seats Rows is Required", "noOfRows");
            if (this.data.fare_class === "")
                return this.errorsArray("PLease Select Fare Class", "fare_class");
            const res = await this.callApi("post", "buses/store", this.data);
            console.log(res);
            if (res.status === 201) {
                this.success = "Bus Created Successfully";
                setTimeout(() => {
                    // window.location.reload();
                    this.isShowDiv = false;
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        editBus(val) {
            this.dataEdit = val;
        },
        viewBus(view) {
            this.dataView = view;
            console.log(this.dataViews);
        },
        async updateBus() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return this.errorsArray("City Name is Required", "Name");
            const res = await this.callApi("post", "buses/update", this.dataEdit);
            if (res.status === 200) {
                this.success = "Bus Record Updated Successfully";
                const res = await this.callApi("post", "buses");
                if (res.status === 200) {
                    this.buses = res.data;
                }
                setTimeout(() => {
                    // window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        async deleteBus(busVal, i) {
            const deletingObj = {
                url: "/buses/delete",
                data: busVal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
            setTimeout(() => {
                // window.location.reload();
            }, 3000);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
            }
        },
    },
};
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

.seat-img {
    height: 40px;
}

.seat-img img,
.seat-img span {
    height: 40px;
    width: 40px;
    display: inline-block;
    cursor: pointer;
}
</style>
