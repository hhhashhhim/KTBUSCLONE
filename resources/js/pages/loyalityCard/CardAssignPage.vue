<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Assign Loyalty Card</h4>
                            <div class="card-header-action">
                                <!--                                v-if="checkForSubmenuButtons('add-card-category')"-->
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Assign Card
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
                                                <table class="table table-striped table-hover"
                                                       style="overflow-x: auto; white-space: nowrap;"
                                                       id="cardAssignTable"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Customer Cnic</th>
                                                        <th>Customer Name</th>
                                                        <th>Customer Phone</th>
                                                        <th>Card Category Name</th>
                                                        <th>Card Starting Points </th>
                                                        <th>Card Expiry Date</th>
                                                        <th>Added By</th>
                                                        <!--                                                        <th v-if="checkForSubmenuButtons('edit-card-category')">Action-->
                                                        <!--                                                        </th>-->
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(card, i) in cardsAssign" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ card.cnic }}</td>
                                                        <td>{{ card.name }}</td>
                                                        <td>{{ card.phone }}</td>
                                                        <td>{{ card.card_category.name }}</td>
                                                        <td>{{ card.starting_points }}</td>
                                                        <td>{{ card.expiry_date }}</td>
                                                        <td>{{ card.added_by.name }}</td>
                                                        <!--                                                        v-if="checkForSubmenuButtons('edit-surcharge') || checkForSubmenuButtons('delete-surcharge')"-->
                                                        <!--                                                        <td v-if="checkForSubmenuButtons('edit-card-category')">-->
                                                        <!--                                                            <button v-if="checkForSubmenuButtons('edit-card-category')"-->
                                                        <!--                                                                    :data-target="'#' + editFormID" data-toggle="modal"-->
                                                        <!--                                                                    @click="edit(card)"-->
                                                        <!--                                                                    class="btn btn-primary mx-1">-->
                                                        <!--                                                                <i class="far fa-edit"></i>-->
                                                        <!--                                                            </button>-->
                                                        <!--                                                            &lt;!&ndash;                                                            <button&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                                                class="btn btn-danger d-none">&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                                                <i class="far fa-trash-alt"></i>&ndash;&gt;-->
                                                        <!--                                                            &lt;!&ndash;                                                            </button>&ndash;&gt;-->
                                                        <!--                                                        </td>-->
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
                :heading="'Assign Card'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="CardName">CNIC<span class="text-danger ml-1">*</span></label>
                        <vue-mask
                            v-on:blur="getCustomer('addFormCNIC')"
                            class="form-control"
                            v-model="addForm.customerCNIC"
                            mask="00000-0000000-0"
                            :raw="false"
                            :options="options"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CardName">Customer Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="addForm.customerName"
                               @keypress="isAlphabet($event)"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="CardName">Phone<span class="text-danger ml-1">*</span></label>
                        <vue-mask
                            v-on:blur="getCustomer('addFormContact')"
                            class="form-control"
                            v-model="addForm.contact"
                            mask="0000-0000000"
                            :raw="false"
                            :options="optionsPhone"
                        >
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
                    <div class="form-group col-md-4">
                        <label for="startPoint">Card Starting Points</label>
                        <input type="text" id="startPoint" class="form-control" @keypress="isNumber($event)"
                               v-model="addForm.startingPoints">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="expiryDate">Expiry Date <span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" id="expiryDate" v-model="addForm.expiryDate">
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
            <!--            <Edit-->
            <!--                heading="Edit Card Category"-->
            <!--                :errors="this.validationErrors"-->
            <!--                :success="success"-->
            <!--                :editForm="editFormID"-->
            <!--            >-->
            <!--                <div class="row">-->
            <!--                    <div class="form-group col-md-4">-->
            <!--                        <label for="CardName">Name <span class="text-danger ml-1">*</span></label>-->
            <!--                        <input type="text" class="form-control" v-model="dataEdit.name"/>-->
            <!--                    </div>-->
            <!--                    &lt;!&ndash;                    Discount&ndash;&gt;-->
            <!--                    <div class="form-group col-md-3 mt-4 pt-2">-->
            <!--                        <div class="custom-control custom-radio custom-control-inline">-->
            <!--                            <input type="radio" id="Editpercentage" name="percentageAmount" class="custom-control-input"-->
            <!--                                   :checked="dataEdit.discount_type == 'percentage'" value="percentage"-->
            <!--                                   v-model="dataEdit.discount_type"-->
            <!--                                   @click="ChangeRadioValue('Editpercentage')">-->
            <!--                            <label class="custom-control-label" for="Editpercentage">Percentage</label>-->
            <!--                        </div>-->
            <!--                        <div class="custom-control custom-radio custom-control-inline">-->
            <!--                            <input type="radio" id="Editflat" name="flatAmount" class="custom-control-input"-->
            <!--                                   value="flat" :checked="dataEdit.discount_type == 'flat'"-->
            <!--                                   v-model="dataEdit.discount_type" @click="ChangeRadioValue('Editflat')">-->
            <!--                            <label class="custom-control-label" for="Editflat">Flat Amount</label>-->
            <!--                        </div>-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--                <div class="row">-->
            <!--                    <div class="form-group col-md-6">-->
            <!--                        <label for="SurchargePercentage">Discount In Percentage <span class="text-danger ml-1">*</span></label>-->
            <!--                        <div class="input-group">-->
            <!--                            <input type="text" class="form-control" maxlength="3" v-model="dataEdit.percentage_discount"-->
            <!--                                   placeholder="Enter Percentage Applied Per Point"-->
            <!--                                   @keypress="isNumber($event); numberRange($event)">-->
            <!--                            <div class="input-group-append">-->
            <!--                                <span class="input-group-text">%</span>-->
            <!--                            </div>-->
            <!--                        </div>-->
            <!--                    </div>-->
            <!--                    <div class="form-group col-md-6">-->
            <!--                        <label for="SurchargePercentage">Discount In Flat Amount <span class="text-danger ml-1">*</span>-->
            <!--                            <span-->
            <!--                                class="text-muted">max: 10K</span> </label>-->
            <!--                        <input type="text" class="form-control" maxlength="5" v-model="dataEdit.flat_discount"-->
            <!--                               placeholder="Enter Flat Amount Applied Per Point"-->
            <!--                               @keypress="isNumber($event)">-->
            <!--                    </div>-->

            <!--                    <div class="col-md-12">-->
            <!--                        <h5>Addition of Points Via Type</h5>-->
            <!--                        &lt;!&ndash;points&ndash;&gt;-->
            <!--                        <div class="row">-->
            <!--                            <div class="form-group col-md-4 mt-4 pt-2">-->
            <!--                                <div class="custom-control custom-radio custom-control-inline">-->
            <!--                                    <input type="radio" id="EditdistancePoints" name="percentageAmountPoints"-->
            <!--                                           class="custom-control-input"-->
            <!--                                           :checked="dataEdit.point_type == 'distancePoints'" value="distancePoints"-->
            <!--                                           v-model="dataEdit.point_type"-->
            <!--                                           @click="ChangeRadioValue('distancePoints')">-->
            <!--                                    <label class="custom-control-label" for="EditdistancePoints">Distance</label>-->
            <!--                                </div>-->
            <!--                                <div class="custom-control custom-radio custom-control-inline">-->
            <!--                                    <input type="radio" id="EditflatPoints" name="flatAmountPoints"-->
            <!--                                           class="custom-control-input"-->
            <!--                                           value="flatPoints"-->
            <!--                                           :checked="dataEdit.point_type == 'flatPoints'"-->
            <!--                                           v-model="dataEdit.point_type" @click="ChangeRadioValue('flatPoints')">-->
            <!--                                    <label class="custom-control-label" for="EditflatPoints">Flat</label>-->
            <!--                                </div>-->
            <!--                            </div>-->
            <!--                            <div class="form-group col-md-4">-->
            <!--                                <label for="SurchargePercentage">Distance <span-->
            <!--                                    class="text-danger ml-1">*</span></label>-->
            <!--                                <div class="input-group">-->
            <!--                                    <input type="text" class="form-control" maxlength="3"-->
            <!--                                           v-model="dataEdit.point_distance"-->
            <!--                                           placeholder="How Many Points Set after 1 KiloMeter?"-->
            <!--                                           @keypress="isNumber($event)">-->
            <!--                                </div>-->
            <!--                            </div>-->
            <!--                            <div class="form-group col-md-4">-->
            <!--                                <label for="SurchargePercentage">Flat<span class="text-danger mx-1">*</span>-->
            <!--                                    <span-->
            <!--                                        class="text-muted">max: 10K</span> </label>-->
            <!--                                <input type="text" class="form-control" maxlength="5" v-model="dataEdit.point_flat"-->
            <!--                                       placeholder="How many Points Set of Amount?"-->
            <!--                                       @keypress="isNumber($event)">-->
            <!--                            </div>-->
            <!--                        </div>-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--                <template v-slot:button>-->
            <!--                    <button type="button" class="btn btn-primary" @click="updateCard()"-->
            <!--                            :disabled="loading"> {{ loading ? 'Loading...' : 'Update Card Category' }}-->
            <!--                    </button>-->
            <!--                </template>-->
            <!--            </Edit>-->
            <!--            Edit MOdel End-->
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
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "loyaltyCardAssignPage",
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
            addForm: {
                cardCategory: '0',
                startingPoints: "0",
            },
        };
    },
    async created() {
        window.removeEventListener('keydown', this.enter);
        window.removeEventListener('keydown', this.altM);
        await this.fetchAssignedCard();
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },

        cnicFormat: function (string) {
            return string.replace(/(\d{5})(\d{7})(\d{1})/, "$1-$2-$3");
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
        async fetchAssignedCard() {
            const resCategories = await this.callApi("post", 'loyaltyCardAssign/categories');
            if (resCategories.status == 200) {
                this.categories = resCategories.data
            } else {
                console.log(resCategories);
            }

            const res = await this.callApi("post", 'loyaltyCardAssign');
            if (res.status == 200) {
                this.cardsAssign = res.data
            } else {
                console.log(res);
            }


            setTimeout(() => {
                $("#cardAssignTable").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm.cardCategory = "0";
            this.addForm.contact = "";
            this.addForm.customerName = "";
            this.addForm.customerCNIC = "";
            this.addForm.startingPoints = "0";
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
                    const resCnic = await this.callApi("post", "loyaltyCardAssign/getCNIC", {
                        cnicNumber: this.addForm.customerCNIC,
                        status: flag,

                    });
                    if ((this.addForm.contact == '' || typeof this.addForm.contact == 'undefined') && (this.addForm.customerName == '' || typeof this.addForm.customerName == 'undefined')) {
                        this.addForm.contact = resCnic.data.contact;
                        this.addForm.customerName = resCnic.data.name;
                    }
                }
            }
            if (flag == 'addFormContact' && this.addForm.customerCNIC == '' && this.addForm.customerName == '') {
                if (this.addForm.contact != '' && this.addForm.contact != 'undefined') {
                    const resCnic = await this.callApi("post", "loyaltyCardAssign/getCNIC", {
                        phoneNumber: this.addForm.contact,
                        status: flag,
                    });
                    if ((this.addForm.customerName == '' || typeof this.addForm.customerName == 'undefined') && (this.addForm.customerCNIC == '' || typeof this.addForm.customerCNIC == 'undefined')) {
                        this.addForm.customerCNIC = resCnic.data.cnic;
                        this.addForm.customerName = resCnic.data.name;
                    }
                }
            }
        },

        async storeAssignCardDetails() {
            this.validationErrors = [];
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
            if (this.addForm.expiryDate == "" || typeof  this.addForm.expiryDate == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "PLease Add Expiry Date",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const resCardAssign = await this.callApi("post", "loyaltyCardAssign/store", this.addForm);
            if (resCardAssign.status == 201) {
                swal({
                    title: "Success",
                    text: "Card Assigned To Customer Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#cardAssignTable").DataTable().destroy();
                this.loading = false;
                this.fetchCardCategories();
            } else {
                if (res.status === 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateCard() {
            this.validationErrors = [];
            // if (this.dataEdit.name === ""|| typeof this.dataEdit.name == 'undefined')
            //     return swal({
            //         title: "Required!",
            //         text: "Name Field is Required ",
            //         icon: "error",
            //         timer: 2000
            //     });
            //
            // if (this.dataEdit.discount_type == "percentage" || this.dataEdit.percentageRadio == 'percentage') {
            //     if (this.dataEdit.percentage == "" || this.dataEdit.percentage == null || typeof this.dataEdit.percentage == "undefined") {
            //         return swal({
            //             title: "Required!",
            //             text: "Percentage Field is Required",
            //             icon: "error",
            //             timer: 2000
            //         });
            //     }
            // }
            // if (this.dataEdit.discount_type == 'flat' || this.dataEdit.percentageRadio == "flat") {
            //     if (this.dataEdit.flat == "" || this.dataEdit.flat == null || typeof this.dataEdit.flat == "undefined") {
            //         return swal({
            //             title: "Required!",
            //             text: "Flat Amount Field is Required",
            //             icon: "error",
            //             timer: 2000
            //         });
            //     }
            // }


            this.loading = true;
            const res = await this.callApi("post", 'loyaltyCardAssign/update', this.dataEdit);
            if (res.status === 200) {
                swal({
                    title: "Success",
                    text: "Card Category Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#cardAssignTable").DataTable().destroy();
                this.loading = false;
                await this.fetchCardCategories();
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }

                }
            }
        },


        edit(cardAssign) {
            this.dataEdit = cardAssign;
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
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

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
    opacity: 0;
}

table, tr, th, td, option, select, label, button, a, div, p {
    font-size: 14px !important;
}

.checkbox-inputs {
    position: relative;
    bottom: 10px;
}
</style>
