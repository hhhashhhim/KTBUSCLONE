<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Loyalty Card Categories</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-card-category')"
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add Card Category
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
                                                       id="cardCategory_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Discount Type</th>
                                                        <th>Percentage Discount</th>
                                                        <th>Flat Discount</th>
                                                        <th>Points Type</th>
                                                        <th>Points after KM's</th>
                                                        <th>Points In Flat</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-card-category')">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(card, i) in cards" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ card.name }}</td>
                                                        <td class="text-capitalize">{{ card.discount_type }}</td>
                                                        <td>{{ card.percentage_discount }}</td>
                                                        <td>{{ card.flat_discount }}</td>
                                                        <td class="text-capitalize">{{ card.point_type }}</td>
                                                        <td>{{ card.point_distance }}</td>
                                                        <td>{{ card.point_flat }}</td>
                                                        <td>{{ card.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-card-category')">
                                                            <button v-if="checkForSubmenuButtons('edit-card-category')" :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(card)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
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
            <Add
                :heading="'Add Card Category'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="CardName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="CardName"/>
                    </div>
                </div>
                <h5>Define per point discount</h5>
                <div class="row">
                    <!--                    Discount-->
                    <div class="form-group col-md-4 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="percentage" name="percentageAmount" class="custom-control-input"
                                   value="percentage" v-model="percentageRadio"
                                   @click="ChangeRadioValue('percentage')">
                            <label class="custom-control-label" for="percentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="flat" name="flatAmount" class="custom-control-input" value="flat"
                                   v-model="percentageRadio" @click="ChangeRadioValue('flat')">
                            <label class="custom-control-label" for="flat">Flat Amount</label>
                        </div>
                    </div>



                    <div class="form-group col-md-8" v-if="showDivPercentage">
                        <label for="SurchargePercentage">Discount In Percentage <span class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="DiscountPercentage"
                                   placeholder="Enter Percentage Applied Per Point"
                                   @keypress="isNumber($event); numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-8" v-if="showDivFlat">
                        <label for="SurchargePercentage">Discount In Flat Amount <span class="text-danger ml-1">*</span>
                            <span
                                class="text-muted">max: 10K</span> </label>
                        <input type="text" class="form-control" maxlength="5" v-model="DiscountFlat"
                               placeholder="Enter Flat Amount Applied Per Point"
                               @keypress="isNumber($event)">
                    </div>
                </div>
                        <!--points-->
                <h5>Define value to add one point</h5>
                <div class="row">
                    <div class="form-group col-md-4 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="distancePoints" name="percentageAmountPoints"
                                    class="custom-control-input"
                                    checked="" value="distancePoints" v-model="pointsRadio"
                                    @click="ChangeRadioValue('distancePoints')">
                            <label class="custom-control-label" for="distancePoints">Distance</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="flatPoints" name="flatAmountPoints"
                                    class="custom-control-input"
                                    value="flatPoints"
                                    v-model="pointsRadio" @click="ChangeRadioValue('flatPoints')">
                            <label class="custom-control-label" for="flatPoints">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-8" v-if="showDivDistancePoints">
                        <label for="SurchargePercentage">Distance <span
                            class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="DistancePoints"
                                    placeholder="Enter km for 1 point"
                                    @keypress="isNumber($event)">
                        </div>
                    </div>
                    <div class="form-group col-md-8" v-if="showDivFlatPoints">
                        <label for="SurchargePercentage">Flat<span class="text-danger mx-1">*</span>
                            <span
                                class="text-muted">max: 10K</span> </label>
                        <input type="text" class="form-control" maxlength="5" v-model="FlatPoints"
                                placeholder="Enter amount for 1 point"
                                @keypress="isNumber($event)">
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="storeCardDetails" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Save Card Category' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Card Category"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="CardName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <h5>Discount Usage</h5>
                <div class="row">
                    <div class="form-group col-md-4 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="Editpercentage" name="percentageAmount" class="custom-control-input"
                                   :checked="dataEdit.discount_type == 'percentage'" value="percentage"
                                   v-model="dataEdit.discount_type"
                                   @click="ChangeRadioValue('Editpercentage')">
                            <label class="custom-control-label" for="Editpercentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="Editflat" name="flatAmount" class="custom-control-input"
                                   value="flat" :checked="dataEdit.discount_type == 'flat'"
                                   v-model="dataEdit.discount_type" @click="ChangeRadioValue('Editflat')">
                            <label class="custom-control-label" for="Editflat">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-8" v-if="showDivPercentageEdit">
                        <label for="SurchargePercentage">Discount In Percentage <span class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="dataEdit.percentage_discount"
                                   placeholder="Enter Percentage Applied Per Point"
                                   @keypress="isNumber($event); numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-8" v-if="showDivFlatEdit">
                        <label for="SurchargePercentage">Discount In Flat Amount <span class="text-danger ml-1">*</span>
                            <span
                                class="text-muted">max: 10K</span> </label>
                        <input type="text" class="form-control" maxlength="5" v-model="dataEdit.flat_discount"
                               placeholder="Enter Flat Amount Applied Per Point"
                               @keypress="isNumber($event)">
                    </div>

                    <div class="col-md-12">
                        <h5>Point Addition</h5>
                        <!--points-->
                        <div class="row">
                            <div class="form-group col-md-4 mt-4 pt-2">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="EditdistancePoints" name="percentageAmountPoints"
                                           class="custom-control-input"
                                           :checked="dataEdit.point_type == 'distancePoints'" value="distancePoints"
                                           v-model="dataEdit.point_type"
                                           @click="ChangeRadioValue('distancePointsEdit')">
                                    <label class="custom-control-label" for="EditdistancePoints">Distance</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="EditflatPoints" name="flatAmountPoints"
                                           class="custom-control-input"
                                           value="flatPoints"
                                           :checked="dataEdit.point_type == 'flatPoints'"
                                           v-model="dataEdit.point_type" @click="ChangeRadioValue('flatPointsEdit')">
                                    <label class="custom-control-label" for="EditflatPoints">Flat</label>
                                </div>
                            </div>
                            <div class="form-group col-md-8" v-if="showDivDistancePointsEdit">
                                <label for="SurchargePercentage">Distance <span
                                    class="text-danger ml-1">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" maxlength="3"
                                           v-model="dataEdit.point_distance"
                                           placeholder="Enter km for 1 point"
                                           @keypress="isNumber($event)">
                                </div>
                            </div>
                            <div class="form-group col-md-8" v-if="showDivFlatPointsEdit">
                                <label for="SurchargePercentage">Flat<span class="text-danger mx-1">*</span>
                                    <span
                                        class="text-muted">max: 10K</span> </label>
                                <input type="text" class="form-control" maxlength="5" v-model="dataEdit.point_flat"
                                       placeholder="Enter amount for per point"
                                       @keypress="isNumber($event)">
                            </div>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateCard()"
                            :disabled="loading"> {{ loading ? 'Loading...' : 'Update Card Category' }}
                    </button>
                </template>
            </Edit>
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
import showRouteDetails from "../route/popup/showRouteDetail";

export default {
    name: "loyaltyCardPage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            loading: false,
            cards: [],
            permissions: [],
            showDivPercentage: true,
            showDivFlat: false,
            showDivFlatPoints: true,
            showDivDistancePoints: false,
            showDivPercentageEdit: true,
            showDivFlatEdit: false,
            showDivDistancePointsEdit: true,
            showDivFlatPointsEdit: false,
            formID: "card_category",
            editFormID: "edit_card_category",
            deleteFormID: "delete_card_category",
            validationErrors: [],
            success: false,
            error: false,
            CardName: '',
            delId: "",
            DiscountPercentage: '',
            FlatPoints: '',
            DistancePoints: '',
            DiscountFlat: '',
            percentageRadio: 'percentage',
            pointsRadio: 'distancePoints',
            dataEdit: {},
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

         this.fetchCardCategories();
        this.permissions = this.$store.state.permissions;
    },

    methods: {
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
        ChangeRadioValue(value) {

            // add form point addition row
            if (value == "percentage") {
                this.showDivPercentage = true;
                this.showDivFlat = false;
            }
            if (value == "flat") {
                this.showDivPercentage = false;
                this.showDivFlat = true;
            }

            // add form point usage row
            if (value == "distancePoints") {
                this.showDivDistancePoints = true;
                this.showDivFlatPoints = false;
            }
            if (value == "flatPoints") {
                this.showDivDistancePoints = false;
                this.showDivFlatPoints = true;
            }

            // edit form point addintion row
            if (value == "Editpercentage") {
                this.showDivPercentageEdit = true;
                this.showDivFlatEdit = false  
            }
            if (value == "Editflat") {
                this.showDivPercentageEdit = false;
                this.showDivFlatEdit = true;
            }

            // add form point usage row
            if (value == "distancePointsEdit") {
                this.showDivDistancePointsEdit = true;
                this.showDivFlatPointsEdit = false;
            }
            if (value == "flatPointsEdit") {
                this.showDivDistancePointsEdit = false;
                this.showDivFlatPointsEdit = true;
            }
            
            
        },
        async fetchCardCategories() {
            const res = await this.callApi("post", 'loyaltyCard');
            if (res.status == 200) {
                this.cards = res.data
            } else {
                console.log(res);
            }

            setTimeout(() => {
                $("#cardCategory_table").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.CardName = "";
            this.DiscountPercentage = "";
            this.DistancePoints = "";
            this.FlatPoints = "";
            this.percentageRadio = 'percentage';
            this.pointsRadio = 'distancePoints';
            this.DiscountFlat = "";
            this.showDivFlat = false;
            this.showDivPercentage = true;
            this.showDivDistancePoints = true;
            this.showDivFlatPoints = false;
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
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },
        checkBox: function (e) {
            if (e.target.checked) {
                this.isActive = 1;
            } else {
                this.isActive = 0;
            }
        },
        editCheckBox: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_active = 1;
            } else {
                this.dataEdit.is_active = 0;
            }
        },

        async storeCardDetails() {
            this.validationErrors = [];
            if (this.CardName == "" || typeof this.CardName == "undefined")
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.percentageRadio == "percentage") {
                if (this.DiscountPercentage == "" || typeof this.DiscountPercentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.percentageRadio == "flat") {
                if (this.DiscountFlat == "" || typeof this.DiscountFlat == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Amount Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.pointsRadio == "distancePoints") {
                if (this.DistancePoints == "" || typeof this.DistancePoints == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Distance Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.pointsRadio == "flatPoints") {
                if (this.FlatPoints == "" || typeof this.FlatPoints == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            this.loading = true;
            const data = {
                name: this.CardName,
                discountType: this.percentageRadio,
                discountPercentage: this.DiscountPercentage,
                discountFlat: this.DiscountFlat,
                pointsType: this.pointsRadio,
                pointsDistance: this.DistancePoints,
                pointsFlat: this.FlatPoints,
            }

            const resCard = await this.callApi("post", "loyaltyCard/store", data);
            if (resCard.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Card Category Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.CardName = '';
                this.percentageRadio = 'percentage';
                this.DiscountPercentage = '';
                this.DiscountFlat = '';
                this.showDivFlat = false;
                this.showDivPercentage = true;
                $("#cardCategory_table").DataTable().destroy();
                this.loading = false;
                this.fetchCardCategories();
            } else {
                if (resCard.status == 422) {
                    this.loading = false;
                    for (const key in resCard.data.errors) {
                        resCard.data.errors[key].forEach((element) => {
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
            const resUpdateCard = await this.callApi("post", 'loyaltyCard/update', this.dataEdit);
            if (resUpdateCard.status === 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Card Category Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#cardCategory_table").DataTable().destroy();
                this.loading = false;
                 this.fetchCardCategories();
            } else {
                if (resUpdateCard.status == 422) {
                    this.loading = false;
                    for (const key in resUpdateCard.data.errors) {
                        resUpdateCard.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }

                }
            }
        },


        async deleteModal(surcharge, i) {
            const deletingObj = {
                url: "surcharge/delete",
                data: surcharge,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(cardList) {
            this.dataEdit = cardList;
            if(cardList.discount_type == "flat")
            {
                this.showDivPercentageEdit = false;
                this.showDivFlatEdit = true;
            }
            else
            {
                this.showDivPercentageEdit = true;
                this.showDivFlatEdit = false;
            }
            
            if(cardList.point_type == "flatPoints")
            {
                this.showDivDistancePointsEdit = false;
                this.showDivFlatPointsEdit = true;
            }
            else
            {
                this.showDivDistancePointsEdit = true;
                this.showDivFlatPointsEdit = false;
            }


        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cards.splice(obj.index, 1)
                $("#cardCategory_table").DataTable().destroy();
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
