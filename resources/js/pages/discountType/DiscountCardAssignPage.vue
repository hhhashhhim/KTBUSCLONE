<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Assign Discount Card</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-assign-discount')" href="#" data-toggle="modal"
                                    :data-target="'#' + formID" class="btn btn-primary" @click="clearForm()">
                                    Assign Card
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="my-2 col-md-3">
                                            <label>RF ID No</label>
                                            <input type="text" class="form-control" placeholder="RF ID No"
                                                v-model="filterAssign.rfId">
                                        </div>
                                        <div class="my-2 col-md-3">
                                            <label>Customer Cnic</label>
                                            <input type="text" class="form-control" placeholder="Customer Cnic"
                                                v-model="filterAssign.cnic">
                                        </div>
                                        <div class="my-2 col-md-3">
                                            <label>Customer Name</label>
                                            <input type="text" class="form-control" placeholder="Customer Name"
                                                v-model="filterAssign.name">
                                        </div>
                                        <div class="my-2 col-md-3">
                                            <label>Customer Phone</label>
                                            <input type="text" class="form-control" placeholder="Customer Phone"
                                                v-model="filterAssign.phone">
                                        </div>
                                        <div class="my-2 col-md-4">
                                            <label>Card Category Name</label>
                                            <select class="form-control" v-model="filterAssign.card_type_id">
                                                <option value="0">All</option>
                                                <option v-for="(single, i) in categories" :key="i" :value="single.id">
                                                    {{ single.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="my-2 col-md-3">
                                            <label>Expiry From</label>
                                            <input type="date" class="form-control" v-model="filterAssign.expiry_from">
                                        </div>
                                        <div class="my-2 col-md-3">
                                            <label>Expiry To</label>
                                            <input type="date" class="form-control" v-model="filterAssign.expiry_to">
                                        </div>
                                        <div class="my-2 col-md-2 d-flex align-items-end">
                                            <button class="btn btn-primary mr-2" type="button"
                                                @click="fetchAssignedCard()" :disabled="loadingTable">
                                                {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button"
                                                @click="resetFilters()" :disabled="loadingTable">
                                                Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover"
                                                    style="overflow-x: auto; white-space: nowrap;" id="cardAssignTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>RF ID No.</th>
                                                            <th>Customer Cnic</th>
                                                            <th>Customer Name</th>
                                                            <th>Customer Phone</th>
                                                            <th>Card Category Name</th>
                                                            <!-- <th>Card Starting Points</th> -->
                                                            <th>Card Expiry Date</th>
                                                            <th>Added By</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(card, i) in cardsAssign" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ card.rfId }}</td>
                                                            <td>{{ card.cnic }}</td>
                                                            <td>{{ card.name }}</td>
                                                            <td>{{ card.phone }}</td>
                                                            <td>{{ card.discount_card_type.name }}</td>
                                                            <!-- <td>{{ card.starting_points }}</td> -->
                                                            <td>{{ card.expiry_date }}</td>
                                                            <td class="text-capitalize">{{ card.added_by.name }}</td>
                                                            <td>
                                                                <button
                                                                    v-if="checkForSubmenuButtons('edit-assign-discount')"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(card)" class="btn btn-primary mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-info mx-1"
                                                                    @click="showDiscountHistory(card)">
                                                                    <i class="fas fa-tag"></i>
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

            <!-- Add Modal -->
            <Add :heading="'Assign Card'" :errors="this.validationErrors" :success="success" :formID="formID">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="CardName">RF-ID<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="addForm.rfId" @keypress="isNumber($event)" />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="CardName">CNIC<span class="text-danger ml-1">*</span></label>
                        <vue-mask v-on:blur="getCustomer('addFormCNIC')" class="form-control"
                            v-model="addForm.customerCNIC" mask="00000-0000000-0" :raw="false" :options="options">
                        </vue-mask>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="CardName">Customer Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="addForm.customerName"
                            @keypress="isAlphabet($event)" />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="CardName">Phone<span class="text-danger ml-1">*</span></label>
                        <vue-mask v-on:blur="getCustomer('addFormContact')" class="form-control"
                            v-model="addForm.contact" mask="0000-0000000" :raw="false" :options="optionsPhone">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CardName">Card Category<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addForm.cardCategory">
                            <option value="0" selected disabled>Select Any Category</option>
                            <option v-for="(single, i) in categories" :key="i" :value="single.id">
                                {{ single.name }}
                            </option>
                        </select>
                    </div>
                    <!-- <div class="form-group col-md-4">
                        <label for="startPoint">Card Starting Points</label>
                        <input type="text" id="startPoint" class="form-control" @keypress="isNumber($event)"
                               v-model="addForm.startingPoints">
                    </div> -->
                    <div class="form-group col-md-4">
                        <label for="expiryDate">Expiry Date <span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" id="expiryDate" v-model="addForm.expiryDate"
                            :min="minDateFilter()">
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="storeAssignCardDetails" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Assign Card' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit heading="Edit Card Category" :errors="this.validationErrors" :success="success"
                :editForm="editFormID">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="CardName">RF-ID<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.rfId" @keypress="isNumber($event)" />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="CardName">CNIC<span class="text-danger ml-1">*</span></label>
                        <vue-mask class="form-control" v-model="dataEdit.cnic" mask="00000-0000000-0" :raw="false"
                            :options="options">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="CardName">Customer Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"
                            @keypress="isAlphabet($event)" />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="CardName">Phone<span class="text-danger ml-1">*</span></label>
                        <vue-mask class="form-control" v-model="dataEdit.phone" mask="0000-0000000" :raw="false"
                            :options="optionsPhone">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CardName">Card Category<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="dataEdit.card_type_id">
                            <option value="0" disabled>Select Any Category</option>
                            <option v-for="(single, i) in categories" :key="i" :value="single.id">
                                {{ single.name }}
                            </option>
                        </select>
                    </div>
                    <!-- <div class="form-group col-md-4">
                        <label for="startPoint">Card Starting Points</label>
                        <input type="text" id="startPoint" class="form-control" @keypress="isNumber($event)"
                               v-model="dataEdit.starting_points">
                    </div> -->
                    <div class="form-group col-md-4">
                        <label for="expiryDate">Expiry Date <span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" id="expiryDate" v-model="dataEdit.expiry_date"
                            :min="minDateFilter()">
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateCard()" :disabled="loading"> {{ loading
                        ? 'Loading...' : 'Update Loyalty Card' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit MOdel End-->
            <div class="modal fade" id="discountHistoryModal" tabindex="-1" role="dialog"
                aria-labelledby="discountHistoryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="discountHistoryModalLabel">
                                Discount History - {{ discountHistoryCustomerName }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div v-if="historyLoading" class="text-center py-4">
                                <img class="loading-spinner"
                                    :src="$store.state.main_url + 'assets/img/loading-spinner.gif'" alt="Loading..."
                                    style="width: 20px; height: 20px" />
                                Loading discount history...
                            </div>
                            <div v-else-if="discountHistory.length === 0" class="text-center py-4">
                                No discount history found
                            </div>
                            <div v-else class="table-responsive">
                                <table id="discountHistoryTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <!-- <th>Booking No</th> -->
                                            <!-- <th>Ticket Customer ID</th> -->
                                            <th>Customer Name</th>
                                            <th>Customer CNIC</th>
                                            <th>Customer Contact</th>
                                            <th>Invoice ID</th>
                                            <!-- <th>Transaction ID</th> -->
                                            <th>Schedule Date</th>
                                            <th>Schedule Time</th>
                                            <th>Seat No</th>
                                            <th>Seat Fare</th>
                                            <th>Discount</th>
                                            <th>Terminal Discount</th>
                                            <th>Schedule Discount</th>
                                            <th>Total Discount</th>
                                            <th>Route</th>
                                            <th>From City</th>
                                            <th>To City</th>
                                            <th>Terminal</th>
                                            <th>Booked Time</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="history in discountHistory" :key="history.id">
                                            <!-- <td>{{ history.booking_no ?? '-' }}</td>
                                        <td>{{ history.customer_id ?? '-' }}</td> -->
                                            <td>{{ history.customer_name ?? '-' }}</td>
                                            <td>{{ history.customer_cnic ?? '-' }}</td>
                                            <td>{{ history.customer_contact ?? '-' }}</td>
                                            <td>{{ history.invoice_id ?? '-' }}</td>
                                            <!-- <td>{{ history.transaction_id ?? '-' }}</td> -->
                                            <td>{{ history.schedule_date ?? '-' }}</td>
                                            <td>{{ history.schedule_time ?? '-' }}</td>
                                            <td>{{ history.seat_no ?? '-' }}</td>
                                            <td>{{ history.seat_fare ?? 0 }}</td>
                                            <td>{{ history.discount ?? 0 }}</td>
                                            <td>{{ history.terminal_discount ?? 0 }}</td>
                                            <td>{{ history.schedule_discount ?? 0 }}</td>
                                            <td>{{ history.total_discount ?? 0 }}</td>
                                            <td>{{ history.route_name ?? '-' }}</td>
                                            <td>{{ history.departure_city_name ?? '-' }}</td>
                                            <td>{{ history.destination_city_name ?? '-' }}</td>
                                            <td>{{ history.terminal_name ?? '-' }}</td>
                                            <td>{{ history.booked_time ?? '-' }}</td>
                                            <td>{{ history.added_by_name ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="7">Totals</td>
                                            <td>{{ discountHistoryTotals.seatFare.toFixed(2) }}</td>
                                            <td>{{ discountHistoryTotals.discount.toFixed(2) }}</td>
                                            <td>{{ discountHistoryTotals.terminalDiscount.toFixed(2) }}</td>
                                            <td>{{ discountHistoryTotals.scheduleDiscount.toFixed(2) }}</td>
                                            <td>{{ discountHistoryTotals.totalDiscount.toFixed(2) }}</td>
                                            <td colspan="6"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary"
                                :disabled="historyLoading || !discountHistoryCustomerId"
                                @click="printDiscountHistory()">
                                Print PDF
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <form :action="$store.state.api_url + 'api/web/v1/print/pdf/discount-card/history'" method="POST"
                ref="refDiscountHistoryPdf" target="_blank">
                <input type="hidden" name="token" :value="$store.state.token">
                <input type="hidden" name="customer_id" :value="discountHistoryCustomerId">
            </form>
            <!--            <Delete :deleteForm="deleteFormID"-->
            <!--                    confirmationMessage='Are You Sure You want To Delete This Surcharge ???'-->
            <!--            />-->

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import { mapGetters } from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "discountCardAssignPage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            options: {
                placeholder: "xxxxx-xxxxxxx-x",
            },
            optionsPhone: {
                placeholder: "03xx-xxxxxxx",
            },
            loading: false,
            loadingTable: false,
            historyLoading: false,
            discountHistory: [],
            discountHistoryCustomerName: "",
            discountHistoryCustomerId: "",
            cardsAssign: [],
            categories: [],
            permissions: [],
            formID: "card_assign",
            editFormID: "edit_card_assign",
            deleteFormID: "delete_card_assign",
            validationErrors: [],
            success: false,
            error: false,
            dataEdit: {},
            filterAssign: {
                rfId: "",
                cnic: "",
                name: "",
                phone: "",
                card_type_id: "0",
                expiry_from: "",
                expiry_to: "",
            },
            addForm: {
                rfId: "",
                contact: "",
                customerName: "",
                customerCNIC: "",
                cardCategory: '0',
                // startingPoints: "0",
                expiryDate: "",
            },
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

        this.fetchFilters();
        this.fetchAssignedCard();
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },

        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
        },
        normalizeAssignedCard(card) {
            const customer = card.customer || {};
            const rawCnic = customer.cnic || card.cnic || "";
            const rawPhone = customer.contact || card.phone || "";
            const cleanCnic = rawCnic.toString().replace(/\D/g, "");
            const cleanPhone = rawPhone.toString().replace(/\D/g, "");

            return {
                ...card,
                cnic: cleanCnic.length === 13 ? this.cnicFormat(cleanCnic) : rawCnic,
                phone: cleanPhone.length === 11 ? this.phoneFormat(cleanPhone) : rawPhone,
                name: customer.name || card.name || "",
            };
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
        numberRange: function (evt) {
            const val = parseInt(evt.target.value + evt.key);
            if (!isNaN(val) && val > 100) {
                evt.preventDefault();
                return swal({
                    title: "Limited!",
                    text: "Percentage is must be less then 100",
                    icon: "error",
                    timer: 2000
                });
            }

        },
        async fetchFilters() {
            const resCategories = await this.callApi("post", 'discountCardAssign/categories');
            if (resCategories.status == 200) {
                $(".modal").click();
                this.categories = resCategories.data
            } else {
                console.log(resCategories);
            }
        },
        destroyCardAssignTable() {
            if (!$.fn.DataTable) {
                return;
            }

            if ($.fn.DataTable.isDataTable("#cardAssignTable")) {
                $("#cardAssignTable").DataTable().destroy();
            }
        },
        initCardAssignTable() {
            setTimeout(() => {
                if (!$.fn.DataTable) {
                    return;
                }

                this.destroyCardAssignTable();
                $("#cardAssignTable").DataTable();
            }, 300);
        },
        async fetchAssignedCard() {
            this.loadingTable = true;
            this.destroyCardAssignTable();
            const res = await this.callApi("post", 'discountCardAssign', this.filterAssign);
            if (res.status == 200) {
                this.cardsAssign = res.data.map((card) => this.normalizeAssignedCard(card))
                this.initCardAssignTable();
            } else {
                console.log(res);
            }
            this.loadingTable = false;
        },
        async showDiscountHistory(card) {
            this.destroyDiscountHistoryTable();
            this.discountHistory = [];
            this.discountHistoryCustomerName = card.name || card.customer?.name || "";
            this.discountHistoryCustomerId = card.customer_id || card.customer?.id || "";
            this.historyLoading = true;
            $("#discountHistoryModal").modal("show");

            const customerId = this.discountHistoryCustomerId;
            if (!customerId) {
                this.historyLoading = false;
                return;
            }

            const res = await this.callApi("get", `discount-card/customer/${customerId}/discount-history`);
            if (res.status === 200) {
                this.discountHistory = res.data.history || [];
            } else {
                console.log(res);
            }
            this.historyLoading = false;
            this.initDiscountHistoryTable();
        },
        destroyDiscountHistoryTable() {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable("#discountHistoryTable")) {
                $("#discountHistoryTable").DataTable().destroy();
            }
        },
        initDiscountHistoryTable() {
            this.$nextTick(() => {
                if (!$.fn.DataTable || this.discountHistory.length === 0) {
                    return;
                }

                this.destroyDiscountHistoryTable();
                $("#discountHistoryTable").DataTable({
                    order: [],
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    pageLength: 10,
                    scrollX: true,
                    responsive: false,
                });
            });
        },
        printDiscountHistory() {
            this.$refs.refDiscountHistoryPdf.submit();
        },
        resetFilters() {
            this.filterAssign = {
                rfId: "",
                cnic: "",
                name: "",
                phone: "",
                card_type_id: "0",
                expiry_from: "",
                expiry_to: "",
            };
            this.fetchAssignedCard();
        },
        clearForm: function () {
            this.addForm.cardCategory = "0";
            this.addForm.contact = "";
            this.addForm.customerName = "";
            this.addForm.customerCNIC = "";
            // this.addForm.startingPoints = "0";
            this.addForm.expiryDate = "";
        },
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },

        async getCustomer(flag) {
            if (flag == 'addFormCNIC') {
                if (this.addForm.customerCNIC != '' && this.addForm.customerCNIC != 'undefined') {
                    const resCnic = await this.callApi("post", "discountCardAssign/getCNIC", {
                        cnicNumber: this.addForm.customerCNIC,
                        status: flag,

                    });
                    if (resCnic.data) {
                        this.addForm.contact = resCnic.data.contact || "";
                        this.addForm.customerName = resCnic.data.name || "";
                    } else {
                        this.addForm.contact = "";
                        this.addForm.customerName = "";
                    }
                }
            }
            if (flag == 'addFormContact') {
                if (this.addForm.contact != '' && this.addForm.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "discountCardAssign/getCNIC", {
                        phoneNumber: this.addForm.contact,
                        status: flag,
                    });
                    if (resCnic.data) {
                        this.addForm.customerCNIC = resCnic.data.cnic || "";
                        this.addForm.customerName = resCnic.data.name || "";
                    } else {
                        this.addForm.customerCNIC = "";
                        this.addForm.customerName = "";
                    }
                }
            }
        },
        minDateFilter: function () {
            const dtToday = new Date();
            let month = dtToday.getMonth() + 1;
            let day = dtToday.getDate();
            const year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();
            return year + '-' + month + '-' + day;
        },
        async storeAssignCardDetails() {
            this.validationErrors = [];
            if (this.addForm.rfId == "" || typeof this.addForm.rfId == "undefined") {
                return swal({
                    title: "Required!",
                    text: "RF ID Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.customerCNIC == "" || typeof this.addForm.customerCNIC == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Cnic Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.customerName == "" || typeof this.addForm.customerName == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.contact == "" || typeof this.addForm.contact == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Contact Number Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.cardCategory == "0") {
                return swal({
                    title: "Required!",
                    text: "Please Select Any Card Category",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.expiryDate == "" || typeof this.addForm.expiryDate == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "PLease Add Expiry Date",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const resCardAssign = await this.callApi("post", "discountCardAssign/store", this.addForm);
            if (resCardAssign.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Card Assigned To Customer Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#cardAssignTable").DataTable().destroy();
                this.loading = false;
                this.fetchAssignedCard();
            } else {
                if (resCardAssign.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCardAssign.data.errors) {
                        resCardAssign.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
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

        async updateCard() {
            this.validationErrors = [];
            if (this.dataEdit.rfId == "" || typeof this.dataEdit.rfId == "undefined") {
                return swal({
                    title: "Required!",
                    text: "RF ID Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.cnic == "" || typeof this.dataEdit.cnic == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Cnic Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.phone == "" || typeof this.dataEdit.phone == "undefined") {
                return swal({
                    title: "Required!",
                    text: "Contact Number Field is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.card_type_id == "0") {
                return swal({
                    title: "Required!",
                    text: "Please Select Any Card Category",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.dataEdit.expiry_date == "" || typeof this.dataEdit.expiry_date == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "PLease Add Expiry Date",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;
            const resUpdateCard = await this.callApi("post", 'discountCardAssign/update', this.dataEdit);
            if (resUpdateCard.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Loyalty Card Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#cardAssignTable").DataTable().destroy();
                this.loading = false;
                this.fetchAssignedCard();
            } else {
                if (resUpdateCard.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resUpdateCard.data.errors) {
                        resUpdateCard.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " +
                                element +
                                "\n"
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


        edit(cardAssign) {
            this.dataEdit = {
                ...this.normalizeAssignedCard(cardAssign),
            };
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj']),
        discountHistoryTotals() {
            return this.discountHistory.reduce((totals, history) => {
                totals.seatFare += Number(history.seat_fare) || 0;
                totals.discount += Number(history.discount) || 0;
                totals.terminalDiscount += Number(history.terminal_discount) || 0;
                totals.scheduleDiscount += Number(history.schedule_discount) || 0;
                totals.totalDiscount += Number(history.total_discount) || 0;

                return totals;
            }, {
                seatFare: 0,
                discount: 0,
                terminalDiscount: 0,
                scheduleDiscount: 0,
                totalDiscount: 0,
            });
        },
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cardsAssign.splice(obj.index, 1)
                $("#cardAssignTable").DataTable().destroy();
                this.fetchCardCategories();
            }
        }
    }
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
