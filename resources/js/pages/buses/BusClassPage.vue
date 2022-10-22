<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Bus Class</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add Bus Class
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
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="edit_dis"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Color</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(busClass, i) in busClasses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ busClass.name }}</td>
                                                        <td>
                                                            <div
                                                                style="
                                      border-radius: 50%;
                                      height: 50px;
                                      width: 50px;
                                    "
                                                                :style="{ backgroundColor: busClass.color }"
                                                            ></div>
                                                        </td>
                                                        <td>
                                                            {{
                                                                busClass.is_active == 1
                                                                    ? "Active"
                                                                    : "InActive"
                                                            }}
                                                        </td>
                                                        <td>{{ busClass.added_by.name }}</td>

                                                        <td>
                                                            <a
                                                                href="#edit-modal"
                                                                data-toggle="modal"
                                                                @click="edit(busClass)"
                                                                class="btn btn-primary mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteModal(busClass, i)"
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
                :heading="'Add Bus Class'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Bus Class Name"
                            id="name"
                            v-model="data.BusClassName"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="color">Color<span class="text-danger">*</span></label>
                        <input
                            type="color"
                            class="form-control"
                            id="color"
                            v-model="data.BusClassColor"
                        />
                    </div>
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                  <span>
                    <input
                        type="checkbox"
                        value="1"
                        checked
                        class="colorinput-input"
                        @change="checkBox($event)"
                    />
                    <span class="colorinput-color bg-success"></span>
                  </span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name"
                        >No. of Rows<span class="text-danger">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Rows"
                            v-model="data.noOfRows"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name"
                        >No. of Cols<span class="text-danger">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Cols"
                            v-model="data.noOfCols"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3 my-4 pt-2">
                        <button
                            type="button"
                            class="btn btn-block btn-warning"
                            @click="addFormGenerateMap"
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
                                <img
                                    v-if="col.reserved"
                                    data-toggle="modal"
                                    data-target="#setSeatClass"
                                    @click="modifySeatData(rowIndex, colIndex)"
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
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addBusClass" :class="loading?'disabled':''">
                        {{loading ? 'Loading...' : 'Add Bus Class' }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal End -->
            <!--                    Modal for modify bus class-->
            <div
                class="modal fade"
                id="setSeatClass"
                tabindex="-1"
                aria-labelledby="staticBackdropLabel"
                aria-hidden="true"

            >
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card card-success">
                                <div class="card-header d-flex justify-content-between">
                                    <h4 class="modal-title">Seat Detail</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="seat_class">Seat Class</label>
                                            <div class="float-right badge badge-primary mx-0 mb-1"
                                                 style="cursor: pointer;" data-toggle="modal"
                                                 data-target="#addFareClass" @click="clearFareClassForm()">Add Fare Class
                                            </div>
                                            <select class="form-control" v-model="seatModify.class">
                                                <option value="0" selected>Select Class</option>
                                                <option
                                                    v-for="(fareClass, i) in fareClasses"
                                                    :key="i"
                                                    :value="fareClass.id"
                                                >
                                                    {{ fareClass.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="seat_type">Seat Type</label>
                                            <select class="form-control" v-model="seatModify.type">
                                                <option value="0" selected>Select Type</option>
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
                                                data-dismiss="modal" :class="loading?'disabled':''" > {{loading ? 'Loading...' : 'Add Seat Detail'}} </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--End Modal-->

            <div class="modal fade" id="addFareClass" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Fare Class</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="name">Name<span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Fare Class Name"
                                        id="name"
                                        v-model="addData.FareClassName"
                                    />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="saveFareClass()" :class="loading?'disabled':''" > {{ loading ? 'Loading...' : 'Save Fare Class' }}</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!--            Edit Model-->
        <Edit
            heading="Edit Bus Class"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
        >
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="SurchargeName"
                    >Name <span class="text-danger">*</span></label
                    >
                    <input type="text" class="form-control" v-model="dataEdit.name"/>
                </div>
                <div class="form-group col-md-6">
                    <label for="color">Color<span class="text-danger">*</span></label>
                    <input
                        type="color"
                        class="form-control"
                        id="color"
                        v-model="dataEdit.busClassColor"
                    />
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-2">
                    <h5>Status</h5>
                    <div class="form-group d-flex align-items-center">
                        <label class="mt-4" for="active">Is Active</label>
                        <label class="colorinput mx-3 mt-3">
                  <span>
                    <input
                        type="checkbox"
                        class="colorinput-input"
                        id="editCheckBox"
                        @change="editCheckBox($event)"
                        v-bind:checked="dataEdit.is_active == 1"
                    />
                    <span class="colorinput-color bg-success"></span>
                  </span>
                        </label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <label for="name"
                    >No. of Rows <span class="text-danger">*</span></label
                    >
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Enter No. of Rows"
                        v-model="dataEdit.no_of_rows"
                        @keypress="isNumber($event)"
                    />
                </div>
                <div class="form-group col-md-3">
                    <label for="name"
                    >No. of Cols <span class="text-danger">*</span></label
                    >
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Enter No. of Cols"
                        v-model="dataEdit.no_of_cols"
                        @keypress="isNumber($event)"
                    />
                </div>
                <div class="form-group col-md-3 my-4 pt-2">
                    <button
                        type="button"
                        class="btn btn-block btn-warning"
                        @click="editGenerateMap"
                    >
                        Generate Seat Map
                    </button>
                </div>
            </div>
            <div class="row mx-1 mainRow">
                <!--v-if="isShowEditDiv-->
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
                                :src="$store.state.app_url + 'assets/img/buses/available_seat_img.gif' " alt=""
                            />
                        </td>
                    </tr>
                </div>
                <div class="form-group col-md-6 border py-3">
                    <tr
                        class="seat-img p-0 m-0"
                        v-for="(record, rowIndex) in dataEdit.seat_map"
                        :key="rowIndex"
                    >

                        <td v-for="(col, colIndex) in record"

                            :key="colIndex"
                            :class="col.reserved ? 'selected-row border' : ''"
                        >
                            <img
                                data-toggle="modal"
                                data-target="#setEditSeatClass"
                                @click="getSeatDetails(rowIndex, colIndex)"
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
            </div>
            <template v-slot:button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="updateFareClass" :class="loading?'disabled':''" >{{ loading ? 'Loading...' : 'Update Bus Class' }}
                </button>
            </template>
        </Edit>

        <!--                    Modal for modify bus class-->
        <div
            class="modal fade"
            id="setEditSeatClass"
            tabindex="-1"
            aria-labelledby="staticBackdropLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="card card-success">
                            <div class="card-header d-flex justify-content-between">
                                <h4 class="modal-title">Edit Seat Detail</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="seat_class">Seat Class</label>
                                        <select class="form-control" v-model="editSeatModify.class">
                                            <option value="0" selected>Select Class</option>
                                            <option
                                                v-for="(fareClass, i) in fareClasses"
                                                :key="i"
                                                :value="fareClass.id"
                                            >
                                                {{ fareClass.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="seat_type">Seat Type</label>
                                        <select class="form-control" v-model="editSeatModify.type">
                                            <option value="0" selected>Select Type</option>
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
                                            @click=" updateSeatDetail(editSingleSeat.rowId, editSingleSeat.colId)"
                                            data-dismiss="modal"> Update Seat Data </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--End Modal-->
        <!--            Edit MOdel End-->
        <Delete
            confirmationMessage="Are You Sure You want To Delete This Bus Class ???"
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
    name: "BusClassPage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            busClasses: [],
            fareClasses: [],
            formID: "busClass_form",
            validationErrors: [],
            seatModify: {
                class: 0,
                type: 0,
            },
            editSeatModify: {
                class: 0,
                type: 0,
            },
            success: false,
            loading: false,
            error: false,
            isShowDiv: false,
            isShowEditDiv: false,
            BusClassName: "",
            updateSeatValue: [],
            editSingleSeat: [],
            delId: "",
            addData: {},
            data: {
                noOfRows: "",
                noOfCols: "",
                seatMap: [],
                isActive: 1,
                BusClassName: "",
                BusClassColor: "#00000",
            },
            dataEdit: {
                BusClassName: "",
                is_Active: "",
                noOfRows: "",
                no_of_cols: "",
                seatMap: [],
            },
        };
    },
    async created() {
        await this.fetchBussClasses();
    },
    methods: {
        clearForm: function () {
            this.data = {};
            this.isShowDiv = false;
        },
        clearFareClassForm: function () {
            this.addData = {};
        },
        async fetchBussClasses() {
            const resBusClass = await this.callApi("post", "bus_classes");
            if (resBusClass.status === 200) {
                this.busClasses = resBusClass.data;
            } else {
                console.log(resBusClass);
            }
            const resFareClass = await this.callApi("post", "fare-class");
            if (resFareClass.status === 200) {
                this.fareClasses = resFareClass.data;
            } else {
                console.log(res);
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
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },
        async saveFareClass(){
            this.loading = true;
            const resSaveFareClass = await this.callApi("post", "buses/storeFareClass", this.addData);
            if (resSaveFareClass.status === 201) {
                swal({
                    title: "Success",
                    text: "Fare Class Added Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.fareClasses.push(resSaveFareClass.data);
            } else {
                console.log(resSaveFareClass);
            }
        },
        addSeatData: function (col, row) {

            if (this.seatModify.class || this.seatModify.type) {

                const seatDetails = this.data.seatMap[row][col];
                this.data.seatMap[row][col] = {
                    reserved: seatDetails.reserved,
                    seatNo: seatDetails.seatNo,
                    class: this.seatModify.class??0,
                    type: this.seatModify.type??0,
                };

                this.success = "Seat Class Customize Successfully to Seat Number " + seatDetails.seatNo;

            }
            else{

                swal({
                    title: "Required",
                    text: "Please Select Any Field For Seat Modification !!!!",
                   icon: "error",
                   timer: 2000
                });

            }

        },
        modifySeatData: function (rowId, colId) {
            this.seatModify = {
                class: this.data.seatMap[rowId][colId].class ?? 0,
                type: this.data.seatMap[rowId][colId].type ?? 0,
            };
            this.updateSeatValue = {
                modalRowId: rowId,
                modalColId: colId,
            };
            console.log(this.updateSeatValue);
        },
        getSeatDetails: function (rowId, colId) {

            this.editSeatModify = {
                class: this.dataEdit.seat_map[rowId][colId].class ?? 0,
                type: this.dataEdit.seat_map[rowId][colId].type ?? 0,
            };
            this.editSingleSeat = {
                rowId: rowId,
                colId: colId,
            };
        },

        updateSeatDetail: function (rowId, colId) {

            console.log(rowId, colId);
            if (this.editSeatModify.class == 0) {
                // swal('required', 'Please Select Seat class', 'error');
                swal({
                    title: "required",
                    text: "Please Select Seat Class",
                   icon: "error",
                   timer: 2000
                });
            } else {
                const singleSeatDetails = this.dataEdit.seat_map[rowId][colId];
                this.dataEdit.seat_map[rowId][colId] = {
                    reserved: singleSeatDetails.reserved,
                    seatNo: singleSeatDetails.seatNo,
                    class: this.editSeatModify.class,
                    type: this.editSeatModify.type,
                };
                this.success = "Seat Class Update Successfully to Seat Number " + singleSeatDetails.seatNo;
            }

        },

        changeStatus: function (row, col) {
            if (this.data.seatMap[row][col].reserved) {
                this.data.seatMap[row][col] = {
                    reserved: false,
                };
            } else {
                this.data.seatMap[row][col] = {
                    reserved: true,
                };
            }
        },
        changeEditStatus: function (row, col) {
            if (this.dataEdit.seat_map[row][col].reserved) {
                this.dataEdit.seat_map[row][col] = {
                    reserved: false,
                };
            } else {
                this.dataEdit.seat_map[row][col] = {
                    reserved: true,
                };
            }
        },
        addFormGenerateMap: function () {
            this.validationErrors = [];
            let vm = this;
            console.log(vm.data.noOfRows, vm.data.noOfCols)
            if ( vm.data.noOfRows == "")
                swal('Required', 'No of Rows Field is Required!', 'error')
            if ( vm.data.noOfCols == "" )
                swal('Required', 'No of Cols Field is Required!', 'error')
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
                            };
                        }
                    }
                    this.isShowDiv = true;
                    return (this.data.seatMap = map);
                } else {
                    // swal('Limited', 'No of Cols must be less then or equal to 7', 'error')
                    swal({
                        title: "Limited",
                        text: "No of Cols must be less then or equal to 7",
                        icon: "error",
                       timer: 2000
                    });
                }
            } else {
                // swal('Limited', 'No of Rows must be less then or equal to 15', 'error')
                swal({
                    title: "Limited",
                    text: "No of Rows must be less then or equal to 15",
                    icon: "error",
                   timer: 2000
                });
            }
        },
        editGenerateMap: function () {
            this.isShowEditDiv = true;
        },

        // async
        checkBox: function (e) {
            if (e.target.checked) {
                this.data.isActive = 1;
            } else {
                this.data.isActive = 0;
            }
        },
        editCheckBox: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_Active = 1;
            } else {
                this.dataEdit.is_Active = 0;
            }
        },

        async addBusClass() {
            this.validationErrors = [];
            let seatNo=0;
            this.data.seatMap = this.data.seatMap.map( (seat)=>{
                for (let i = (seat.length-1); i >= 0; i--) {
                    if (seat[i].reserved) {
                        seat[i]['seatNo']=++seatNo;
                    }
                }
                return seat;
            });

            if (this.data.BusClassName === "")
                // swal('Required', 'Bus Class Name is Required', 'error')
                swal({
                    title: "Required",
                    text: "Bus Class Name is Required",
                    icon: "error",
                   timer: 2000
                });
            if (this.data.noOfRows === "0")
                // swal('Required', 'Row Field is Required', 'error')
                swal({
                    title: "Required ",
                    text: "Row Field is Required",
                    icon: "error",
                   timer: 2000
                });
            if (this.data.noOfCols === "0")
                // swal('Required', 'Col Field is Required', 'error')
                swal({
                    title: "Required",
                    text: "Col Field is required",
                    icon: "error",
                   timer: 2000
                });
            this.loading = true;
            const res = await this.callApi("post", "bus_classes/store", this.data);
            if (res.status === 201) {
                // swal('Success', 'Bus Class Added Successfully', 'success');
                swal({
                    title: "Success",
                    text: "Bus Class Added Successfully",
                    icon: "success",
                   timer: 2000
                });
                this.loading = false;
                await this.fetchBussClasses();
                this.data = "";
                this.isShowDiv = false;
                window.scrollTo(0, 0);
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

        async updateFareClass() {
            this.validationErrors = [];
            if (this.dataEdit.BusClassName === "")

                swal({
                    title: "Required",
                    text: "Bus Class name is required",
                    icon: "error",
                   timer: 2000
                });
            if (this.dataEdit.noOfRows === "0")

                swal({
                    title: "Required",
                    text: "row Field is required",
                    icon: "error",
                   timer: 2000
                });
            if (this.dataEdit.noOfCols === "0")

                swal({
                    title: "Required",
                    text: "Col Field is Required",
                    icon: "error",
                   timer: 2000
                });
                this.loading = true;

            let seatNo=0;

            this.dataEdit.seat_map = this.dataEdit.seat_map.map( (seat)=>{
                for (let i = (seat.length-1); i >= 0; i--) {
                    if (seat[i].reserved) {
                        seat[i]['seatNo']=++seatNo;
                    }
                }
                return seat;
            });

            const res = await this.callApi(
                "post",
                "bus_classes/update",
                this.dataEdit
            );
            if (res.status === 200 && res.statusText === "OK") {
                swal({
                    title: "Success",
                    text: "Bus Class Updated Successfully",
                    icon: "success",
                   timer: 2000
                });
                this.loading = false;
                await this.fetchBussClasses();

            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors.percentage.forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async deleteModal(fare_class, i) {
            const deletingObj = {
                url: "bus_classes/delete",
                data: fare_class,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(bus_class) {
            this.dataEdit = {...bus_class, busClassColor: bus_class.color};
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.busClasses.splice(obj.index, 1);
                this.fetchBussClasses();
            }
        },
    },
};
</script>
<style scoped>
.selected-row {
    background-color: yellow;
}

.seat-img img,
.seat-img span {
    height: 40px;
    width: 40px;
    display: inline-block;
    cursor: pointer;
}
</style>
