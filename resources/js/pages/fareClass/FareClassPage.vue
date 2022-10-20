<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Fare Class</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                >
                                    Add Fare Class
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
                                                    id="edit_dis"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(fareClass, i) in fareClasses" :key="i">
                                                        <td>{{ i+1 }}</td>
                                                        <td>{{ fareClass.name }}</td>
                                                        <td>{{ fareClass.is_active == 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ fareClass.added_by.name }}</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(fareClass)" class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a href="#delete-modal" data-toggle="modal"
                                                               @click="deleteModal(fareClass,i)" class="btn btn-danger">
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
                :heading="'Add Fare Class'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="name">Name<span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Fare Class Name"
                            id="name"
                            v-model="data.FareClassName"
                        />
                    </div>
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" value="1" checked class="colorinput-input"
                                       @change="checkBox($event)"/>
                                <span class="colorinput-color bg-success"></span>
                            </span>
                            </label>
                        </div>
                    </div>
<!--                    <div class="form-group col-md-3">-->
<!--                        <label for="name">No. of Rows<span class="text-danger">*</span></label>-->
<!--                        <input-->
<!--                            type="text"-->
<!--                            class="form-control"-->
<!--                            placeholder="Enter No. of Rows"-->
<!--                            v-model="data.noOfRows"-->
<!--                            @keypress="isNumber($event)"-->
<!--                        />-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-3">-->
<!--                        <label for="name">No. of Cols<span class="text-danger">*</span></label>-->
<!--                        <input-->
<!--                            type="text"-->
<!--                            class="form-control"-->
<!--                            placeholder="Enter No. of Cols"-->
<!--                            v-model="data.noOfCols"-->
<!--                            @keypress="isNumber($event)"-->
<!--                        />-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-3 my-4 pt-2">-->
<!--                        <button-->
<!--                            type="button"-->
<!--                            class="btn btn-block btn-warning"-->
<!--                            @click="generateMap"-->
<!--                        >-->
<!--                            Generate Seat Map-->
<!--                        </button>-->
<!--                    </div>-->
                </div>
<!--                <div class="row mx-3 mainRow" v-if="isShowDiv">-->
<!--                    <div class="form-group col-md-5 border py-3">-->
<!--                        <tr-->
<!--                            class="seat-img p-0 m-0 s"-->
<!--                            v-for="(record, rowIndex) in data.seatMap"-->
<!--                            :key="rowIndex"-->
<!--                        >-->
<!--                            <td-->
<!--                                v-for="(col, colIndex) in record"-->
<!--                                :key="colIndex"-->
<!--                                :class="col.reserved ? 'selected-row border' : ''"-->
<!--                            >-->
<!--                                <img @click="changeStatus(rowIndex, colIndex)"-->
<!--                                     :src=" $store.state.app_url + 'assets/img/buses/available_seat_img.gif' "-->
<!--                                     alt=""-->
<!--                                />-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-5 border py-3">-->
<!--                        <tr-->
<!--                            class="seat-img p-0 m-0"-->
<!--                            v-for="(record, rowIndex) in data.seatMap"-->
<!--                            :key="rowIndex"-->
<!--                        >-->
<!--                            <td v-for="(col, colIndex) in record" :key="colIndex">-->
<!--                                <img v-if="col.reserved"-->
<!--                                     :src=" $store.state.app_url + 'assets/img/buses/booked_seat_img.gif' "-->
<!--                                     alt=""/>-->
<!--                                <span v-else></span>-->
<!--                            </td>-->
<!--                        </tr>-->
<!--                    </div>-->
<!--                    <div class="col-md-2 form-group  ">-->
<!--                        <div class="row">-->
<!--                            <div class="col-md-6">-->
<!--                                <ul style="list-style: none;" class="m-0 p-0 ">-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="selected-row mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Selected</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline;">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="booked_Seat mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Booked</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="notForSale mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Not For Sale</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="anyElseClass pr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Other Class</span>-->

<!--                                    </li>-->
<!--                                    <br>-->
<!--                                </ul>-->
<!--                            </div>-->
<!--                            <div class="col-md-6">-->
<!--                                <ul style="list-style: none;" class="m-0 p-0">-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="economy mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Economy</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="exective mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Executive</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="business mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Business</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="reservedForFemale mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-wrap">Reserved For Female</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                </ul>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="addFareClass"
                    >
                        Add Fare Class
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Surcharge"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="SurchargeName">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                               <input type="checkbox"  class="colorinput-input" id="editCheckBox"
                                      @change="editCheckBox($event)" v-bind:checked="dataEdit.is_active == 1"/>
                                <span class="colorinput-color bg-success"></span>
                            </span>
                            </label>
                        </div>
                    </div>
<!--                    <div class="form-group col-md-3">-->
<!--                        <label for="name">No. of Rows <span class="text-danger">*</span></label>-->
<!--                        <input-->
<!--                            type="text"-->
<!--                            class="form-control"-->
<!--                            placeholder="Enter No. of Rows"-->
<!--                            v-model="dataEdit.no_of_rows"-->
<!--                            @keypress="isNumber($event)"-->
<!--                        />-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-3">-->
<!--                        <label for="name">No. of Cols <span class="text-danger">*</span></label>-->
<!--                        <input-->
<!--                            type="text"-->
<!--                            class="form-control"-->
<!--                            placeholder="Enter No. of Cols"-->
<!--                            v-model="dataEdit.no_of_cols"-->
<!--                            @keypress="isNumber($event)"-->
<!--                        />-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-3 my-4 pt-2">-->
<!--                        <button-->
<!--                            type="button"-->
<!--                            class="btn btn-block btn-warning"-->
<!--                            @click="editGenerateMap"-->
<!--                        >-->
<!--                            Generate Seat Map-->
<!--                        </button>-->
<!--                    </div>-->
                </div>
<!--                <div class="row mx-1 mainRow">  &lt;!&ndash;v-if="isShowEditDiv&ndash;&gt;-->
<!--                    <div class="form-group col-md-6 border py-3">-->
<!--                            <tr-->
<!--                                class="seat-img p-0 m-0"-->
<!--                                v-for="(record, rowIndex) in dataEdit.seat_map"-->
<!--                                :key="rowIndex"-->
<!--                            >-->
<!--                                <td-->
<!--                                    v-for="(col, colIndex) in record"-->
<!--                                    :key="colIndex"-->
<!--                                    :class="col.reserved ? 'selected-row border' : ''"-->
<!--                                >-->
<!--                                    <img-->
<!--                                        @click="changeEditStatus(rowIndex, colIndex)"-->
<!--                                        :src="$store.state.app_url + 'assets/img/buses/available_seat_img.gif'" alt=""/>-->
<!--                                </td>-->
<!--                            </tr>-->
<!--                        </div>-->
<!--                    <div class="col-md-4 form-group  ">-->
<!--                        <div class="row">-->
<!--                            <div class="col-md-6">-->
<!--                                <ul style="list-style: none;" class="m-0 p-0 ">-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="selected-row mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Selected</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline;">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="booked_Seat mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Booked</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="notForSale mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Not For Sale</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="anyElseClass pr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Other Class</span>-->

<!--                                    </li>-->
<!--                                    <br>-->
<!--                                </ul>-->
<!--                            </div>-->
<!--                            <div class="col-md-6">-->
<!--                                <ul style="list-style: none;" class="m-0 p-0">-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="economy mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Economy</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="exective mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Executive</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="business mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-nowrap">Business</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                    <li style="display:inline; ">-->
<!--                                        <div-->
<!--                                            style="width: 30px; height: 30px; -moz-border-radius: 25px;	-webkit-border-radius: 25px; border-radius: 50px;"-->
<!--                                            class="reservedForFemale mr-1 border">-->
<!--                                        </div>-->
<!--                                        <span class="text-wrap">Reserved For Female</span>-->
<!--                                    </li>-->
<!--                                    <br>-->
<!--                                </ul>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    </div>-->
                <template v-slot:button>
                        <button type="button" class="btn btn-primary" @click="updateFareClass">Update
                            Surcharge
                        </button>
                </template>
            </Edit>
            <!--            Edit MOdel End-->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This Fare Class ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import showRouteDetails from "../route/popup/showRouteDetail";

export default {
    name: "FareClassPage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            fareClasses: [],
            formID: "addNewFareClass",
            validationErrors: [],
            success: false,
            error: false,
            // isShowDiv: false,
            // isShowEditDiv: false,
            FareClassName:'',
            delId:"",
            // seatNo: 0,
            data:{
                // noOfRows: "",
                // noOfCols: "",
                // seatMap: [],
                isActive:1,
                FareClassName:"",
            },
            dataEdit: {
                FareClassName: '',
                is_Active: '',
                // noOfRows: "",
                // no_of_cols: "",
                // seatMap: [],
            },
        };
    },
    async created() {
        await this.fetchFareClasses();

    },
    methods: {
        async fetchFareClasses(){
            const res = await this.callApi("post", 'fare-class');
            if (res.status === 200) {
                this.fareClasses = res.data
            }
        },
        // isNumber: function (evt) {
        //     evt = (evt) ? evt : window.event;
        //     var charCode = (evt.which) ? evt.which : evt.keyCode;
        //     if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
        //         evt.preventDefault();
        //     } else {
        //         return true;
        //     }
        // },
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },


        // addSeatData: function (col, row) {
        //     if (this.seatType === 0) {
        //         return this.errorsArray("Please Select Seat Type", "Seat Type");
        //     }
        //     if (this.seatClass === 0) {
        //         return this.errorsArray("Please Select Seat Class", "Seat Class");
        //     }
        //
        //     const seatDetails = this.data.seatMap[row][col];
        //     this.data.seatMap[row][col] = {
        //         reserved: seatDetails.reserved,
        //         seatNo: seatDetails.seatNo,
        //         class: this.seatClass,
        //         type: this.seatType,
        //     };
        //
        //     this.success = "Seat Classes Added Successfully ";
        // },
        // updateSeatData: function (rowId, colId) {
        //     (this.seatClass = "0"),
        //         (this.seatType = "0"),
        //         (this.updateSeatValue = {
        //             modalRowId: rowId,
        //             modalColId: colId,
        //         });
        //     console.log(this.updateSeatValue);
        // },
        // getSeatFareClass: function () {
        //     $("#setSeatClass").appendTo("body");
        // },
        // changeStatus: function (row, col) {
        //     if (this.data.seatMap[row][col].reserved) {
        //         this.seatNo--;
        //         this.data.seatMap[row][col] = {
        //             reserved: false,
        //             seatNo: 0,
        //         };
        //     } else {
        //         this.seatNo++;
        //         this.data.seatMap[row][col] = {
        //             reserved: true,
        //             seatNo: this.seatNo,
        //         };
        //     }
        // },
        // changeEditStatus: function (row, col) {
        //     if (this.dataEdit.seat_map[row][col].reserved) {
        //         this.seatNo--;
        //         this.dataEdit.seat_map[row][col] = {
        //             reserved: false,
        //             seatNo: 0,
        //         };
        //     } else {
        //         this.seatNo++;
        //         this.dataEdit.seat_map[row][col] = {
        //             reserved: true,
        //             seatNo: this.seatNo,
        //         };
        //     }
        // },
        // generateMap: function () {
        //     this.validationErrors = [];
        //     let vm = this;
        //     if (vm.data.noOfRows <= 15) {
        //         if (vm.data.noOfCols <= 7) {
        //             let arr,
        //                 count = 0;
        //             var map = new Array(parseInt(vm.data.noOfRows)); // creating rows
        //             for (var i = 0; i < map.length; i++) {
        //                 map[i] = new Array(vm.data.noOfCols); // creating columns
        //             }
        //
        //             for (var i = 0; i < vm.data.noOfRows; i++) {
        //                 for (var j = 0; j < vm.data.noOfCols; j++) {
        //                     count++;
        //                     map[i][j] = {
        //                         reserved: false,
        //                         seatNo: 0,
        //                     };
        //                 }
        //             }
        //             this.isShowDiv = true;
        //             return (this.data.seatMap = map);
        //         } else {
        //             return this.errorsArray("No of Cols must be less then or equal to 7", "No Of Cols");
        //         }
        //     } else {
        //         return this.errorsArray("No of Rows must be less then or equal to 15", "No Of Rows");
        //
        //     }
        // },
        // editGenerateMap: function () {
        //     this.isShowEditDiv = true;
        // },


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
        // async getClasses() {
        //     const res = await this.callApi("post", 'fare-table/fare_class/get');
        //     if (res.status === 200) {
        //         this.fareClasses = res.data
        //     } else {
        //         console.log(res);
        //     }
        // },

        async addFareClass() {
            this.validationErrors = [];
            if (this.data.FareClassName === "")
                return this.errorsArray("Fare Class Name is Required", "FareClassName");
            // if (this.data.noOfRows === "0")
            //     return this.errorsArray("Row Field is Required", "noOfRows");
            // if (this.data.noOfCols === "0")
            //     return this.errorsArray("Col Field is Required", "noOfCols");

            const res = await this.callApi("post", "fare-class/store", this.data);
            if (res.status === 201) {
                this.success = "Fare Class Added Successfully";
                window.scrollTo(0, 0);
                this.data.FareClassName = "";
                // await this.getClasses();

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
            if (this.dataEdit.FareClassName === "")
                return this.errorsArray("Fare Class Name is Required", "FareClassName");

            const res = await this.callApi("post", 'fare-class/update', this.dataEdit);
            if (res.status === 200 && res.statusText === "OK") {
                this.fetchFareClasses();
                this.success = "Fare Class Updated Successfully";

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


        async deleteModal( fare_class,i ){
            const deletingObj = {
                url:"fare-class/delete",
                data:fare_class,
                index:i,
            }
            this.$store.commit("setDeleteObj",deletingObj);
        },

        edit(fare_class) {
            console.log(fare_class);
            this.dataEdit = fare_class;
        },
    },
    computed:{
        ...mapGetters(['getDeletingObj'])
    },
    watch:{
        getDeletingObj(obj){
            if (obj.isDeleted) {
                this.fareClasses.splice(obj.index,1)

            }
        }
    }
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
