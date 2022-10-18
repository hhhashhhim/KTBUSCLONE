<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Booking</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                >
                                    Add Booking
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
                                                    id="edit_dis"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Customer Name</th>
                                                        <th>CNIC Number</th>
                                                        <th>Cell Number</th>
                                                        <th>Ticket Booked By</th>
                                                        <th>No. of Tickets</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(customer, i) in customers" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ customer.name}}</td>
                                                        <td>{{ cnicFormat(customer.cnic) }}</td>
                                                        <td>{{ customer.contact }}</td>
                                                        <td>{{ customer.added_by.name }}</td>
                                                        <td>{{ customer.tickets_count }}</td>
                                                        <td>
                                                            <a
                                                                href="#detail-modal"
                                                                data-toggle="modal"
                                                                @click="viewDetail(customer)"
                                                                class="btn btn-primary mx-1"
                                                            >
                                                                <i class="far fa-eye"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteModal(customer, i)"
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
                :heading="'Create Booking'"
                :errors="this.validationErrors"
                :formID="formID"
            >
                <div class="row">

                    <div class="col-md-5 class form-group">
                        <label for="DiscountName">Schedule Name <span class="text-danger">*</span></label>
                        <select
                            class="form-control"
                            id="route"
                            v-model="addForm.schedule"
                        >
                            <option value="0" selected>Select Schedule</option>
                            <option
                                v-for="(schedule, i) in allSchedules"
                                :value="schedule.id"
                                :key="i"
                            >
                                {{ schedule.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-5 class form-group">
                        <label for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" v-model="addForm.date">
                    </div>
                    <div class="col-md-2">
                        <label>Action</label>
                        <button @click="fetchScheduleData" class="btn btn-block btn-primary">Get Record</button>
                    </div>

                    <h1 v-if="loading">Loading.........</h1>

                    <div class="col-md-12 row" v-if="showBookingDiv">
                        <div class="col-md-6">
                            <div class="card p-4">
                                <div class="form-group row">
                                    <label
                                        class="col-md-3 pt-3 font-weight-bold"
                                        for="customer-cnic"
                                    >CNIC <span class="text-danger">*</span> </label
                                    >
                                    <vue-mask v-on:keyup.enter="getCustomer"
                                              class="form-control col-md-9"
                                              v-model="addForm.customerCNIC"
                                              mask="00000-0000000-0"
                                              :raw="false"
                                              :options="options">
                                    </vue-mask>

                                    <!--                  <input-->
                                    <!--                    type="text"-->
                                    <!--                    class="form-control col-md-9"-->
                                    <!--                    id="customer-cnic"-->
                                    <!--                    v-model="addForm.customerCNIC"-->
                                    <!--                  />-->
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="fullName"
                                    >Full Name</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control col-md-9"
                                        id="fullName"
                                        v-model="addForm.customerName"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                                    >Contact</label
                                    >
                                    <input
                                        type="text" @keypress="isNumber($event)"
                                        class="form-control col-md-9"
                                        id="contact"
                                        v-model="addForm.contact"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="remarks"
                                    >Remarks</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control col-md-9"
                                        id="remarks"
                                        v-model="addForm.remarks"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                                    >Gender</label
                                    >
                                    <div class="col-md-9 pt-3">
                                        <input type="radio" v-model="addForm.gender" value="0"/>
                                        <label class="mx-3">Female</label>
                                        <input type="radio" v-model="addForm.gender" value="1"/>
                                        <label class="mx-3">Male</label>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="contact"
                                    >Issue Or Book</label
                                    >
                                    <div class="col-md-9 pt-3">
                                        <input type="radio" v-model="addForm.type" value="booked"/>
                                        <label class="mx-3">Issue</label>
                                        <input
                                            type="radio"
                                            v-model="addForm.type"
                                            value="advance booking"
                                        />
                                        <label class="mx-3">Book</label>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="seatNo"
                                    >Seat No.</label
                                    >
                                    <input
                                        type="text"
                                        readonly
                                        class="form-control col-md-9"
                                        id="seatNo"
                                        v-model="addForm.selectedSeats"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="totalFare"
                                    >Total Seats</label
                                    >
                                    <input
                                        type="text"
                                        readonly
                                        class="form-control col-md-9"
                                        id="totalNoSeats"
                                        v-model="selectedSeats.length"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="totalFare"
                                    >Total Fare</label
                                    >
                                    <input
                                        type="text"
                                        readonly
                                        class="form-control col-md-9"
                                        id="totalFare"
                                        v-model="addForm.totalFare"
                                    />
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 pt-3 font-weight-bold" for="discount"
                                    >Discount ( % )</label
                                    >
                                    <input
                                        type="text"
                                        readonly
                                        class="form-control col-md-9"
                                        id="discount"
                                        v-model="addForm.discount"
                                    />
                                </div>

                                <div class="form-group text-right">
                                    <button class="btn btn-primary mx-1" @click="add">
                                        Save
                                    </button>
                                    <button class="btn btn-secondary mx-1">Reset</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card p-4">
                                <div class="col-md-12 mb-2 d-flex flex-wrap">
                                    <div class="my-2">
                                        <div class="selected circles mr-1 border"></div>
                                        <span class="text-wrap">Selected</span>
                                    </div>
                                    <div class="my-2">
                                        <div class="for-female circles mr-1 border"></div>
                                        <span class="text-wrap">For Female</span>
                                    </div>
                                    <div class="my-2">
                                        <div class="for-male circles mr-1 border"></div>
                                        <span class="text-wrap">For Male</span>
                                    </div>
                                    <div class="my-2">
                                        <div class="not-for-sale circles mr-1 border"></div>
                                        <span class="text-wrap">Not For Sale</span>
                                    </div>
                                    <div class="my-3">
                                        <div class="circles icons-legend mr-1 border">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <span class="text-wrap">Booked</span>
                                    </div>
                                    <div class="my-3">
                                        <div class="fas fa-check-double circles icons-legend mr-1 border"></div>
                                        <span class="text-wrap">Issued</span>
                                    </div>
                                </div>
                                <div
                                    class="d-flex justify-content-center seat-img p-0 m-0"
                                    v-for="(record, rowIndex) in schedule.selective_bus.seat_map"
                                    :key="rowIndex"
                                >
                                    <div v-for="(col, colIndex) in record" :key="colIndex">
                                        <!-- <div v-if="colIndex==0">
                                          {{ col }}
                                        </div> -->
                                        <div
                                            v-if="col.reserved"
                                            class="image-span d-block text-center text-white"
                                            @click="selectSeat(rowIndex, colIndex, col.seatNo)"
                                            data-toggle="modal"
                                            :data-target="col.type?'#booking-options-popup':''"
                                            :class="getClasses(col)"
                                        >
                                            <small>{{ col.seatNo }}</small>
                                            <br>
                                            <small v-if="col.type">
                                                <i class="fas"
                                                   :class="col.type=='booked'?'fa-check-double':'fa-check'"></i>
                                            </small>
                                        </div>
                                        <span v-else></span>
                                    </div>
                                </div>
                                <tr></tr>
                                <!-- schedule -->
                            </div>
                        </div>
                    </div>
                </div>
            </Add>
            <!--View Details Model-->
            <div class="modal fade" id="detail-modal" tabindex="-1" aria-labelledby="detailModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Ticket Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body m-1 p-1">
                            <div class="card-body my-0 py-0">
                                <!-- Table -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover dataTable no-footer">
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
                                                        <tr v-for="(single, i) in ticketDetails" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td v-if="single.name">{{ single.name }}</td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="single.address">{{ single.address }}</td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="single.contact">{{ single.contact }}</td>
                                                            <td v-else>N/A</td>
                                                            <td v-if="single.added_by">{{ single.added_by.name }}</td>
                                                            <td v-else>N/A</td>
                                                            <td><a
                                                                href="#edit-modal"
                                                                data-toggle="modal"
                                                                @click="editTerminal(single)"
                                                                class="btn btn-warning mx-2"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                                <a
                                                                    href="#delete-modal"
                                                                    data-toggle="modal"
                                                                    @click="deleteModal(single, i)"
                                                                    class="btn btn-danger"
                                                                >
                                                                    <i class="far fa-trash-alt"></i>
                                                                </a></td>
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
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

<!--            DELETE MODAL-->
            <Delete
                confirmationMessage="Are You Sure You want To Delete This Booking ???"
            />

            <BookingOptionsPopup
                formID="booking-options-popup"
            />

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import BookingOptionsPopup from "./popup/BookingOptionsPopup.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from 'vue-jquery-mask';

export default {
    name: "SurchargePage",
    components: {
        Add,
        Edit,
        Delete,
        BookingOptionsPopup,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: 'xxxxx-xxxxxxx-x',
                // http://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
            },
            customers: [],
            ticketDetails: [],
            isActive: 1,
            formID: "addBooking",
            validationErrors: [],
            success: false,
            error: false,
            SurchargeName: "",
            delId: "",
            SurchargePercentage: "",
            allSchedules: [],
            schedule: "",
            loading: false,
            showBookingDiv: false,
            selectedSeats: [],
            // seatMap:[],
            addForm: {
                type: "booked",
                gender: "1",
                customerCNIC: "",
                schedule: 0,
            },
            dataEdit: {
                id: "",
                name: "",
                percentage: "",
                is_Active: "",
            },
        };
    },
    async created() {

        const res = await this.callApi("post", "booking");
        console.log(res.data);
        if (res.status == 200) {
            this.customers = res.data;
        } else {
            console.log(res);
        }
    },

    methods: {
        cnicFormat:function(string){
            return (string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3"));
        },

        async getCustomer() {
            const resCnic = await this.callApi("post", "booking/getCNIC", {cnicNumber: this.addForm.customerCNIC});
            this.addForm.contact = resCnic.data.contact;
            this.addForm.customerName = resCnic.data.name;
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
        async fetchScheduleData() {
            this.validationErrors = [];
            if (!this.addForm.schedule)
                return this.errorsArray("Schedule Name is Required", "Schedule");
            if (!this.addForm.date)
                return this.errorsArray("Date is Required", "Date");
            this.validationErrors = [];

            this.loading = true;
            const res = await this.callApi("post", "schedule/selected", {
                id: this.addForm.schedule,
                date: this.addForm.date,
            });
            if (res.status == 200) {
                this.loading = false;
                this.showBookingDiv = true;
                this.schedule = res.data;
            } else {
                console.log(res);
            }
        },
        selectSeat(row, col, seatNo) {
            let index = this.selectedSeats.indexOf(seatNo);
            if (index != -1) {
                this.schedule.selective_bus.seat_map[row][col].selected = false;
                this.selectedSeats.splice(index, 1);
            } else {
                this.schedule.selective_bus.seat_map[row][col].selected = true;
                this.selectedSeats.push(seatNo);
            }

            this.addForm.selectedSeats = this.selectedSeats;
        },
        getClasses(col) {
            let gender = col.gender != undefined && col.gender == 0 ? 'for-female' : col.gender && col.gender == 1 ? 'for-male' : ''
            let selected = col.selected ? "selected" : "";
            return gender + " " + selected;
        },
        async add() {
            this.validationErrors = [];
            if (!this.addForm.schedule) {
                this.doScroll()
                return this.errorsArray("Schedule Name is Required", "Schedule");
            }
            if (!this.addForm.date) {
                this.doScroll()
                return this.errorsArray("Date is Required", "Date");
            }
            if (!this.addForm.customerCNIC || this.addForm.customerCNIC.length != 15) {
                this.doScroll()
                return this.errorsArray("CNIC is Required and Should Contain 15 Digits", "CNIC");
            }
            if (this.selectedSeats.length == 0)
                return this.errorsArray("Please Select At Least One Seat", "Seat");


            this.validationErrors = [];

            const res = await this.callApi("post", "booking/store", this.addForm);
            if (res.status === 201 && res.statusText === "Created") {
                this.success = "Booking Created Successfully";
                this.addForm = "";
                window.scrollTo(0, 0);
            } else {
                if (res.status === 422) {
                    for (const key in res.addForm.errors) {
                        res.addForm.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        doScroll: function () {
            $('#addBooking').scrollTop(10);
        },
        async viewDetail(customer){
            const resDetailTicket = await this.callApi("post", "booking/detail", {id: customer.id});
            this.ticketDetails = resDetailTicket.data;



        },
        async deleteModal(ticket, i) {
            const deletingObj = {
                url: "/booking/delete",
                data: ticket,
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
                this.surcharges.splice(obj.index, 1);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        },
    },
};
</script>
