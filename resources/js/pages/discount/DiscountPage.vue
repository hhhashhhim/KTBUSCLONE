<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Discount</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-discount')"
                                   href="#"
                                   data-toggle="modal"
                                   :data-target="'#' + formID"
                                   class="btn btn-primary" @click="clearForm()"
                                >
                                    Add Discount
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
                                                    id="discount_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Percentage</th>
                                                        <th>Flat Amount</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-discount') || checkForSubmenuButtons('delete-discount')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(discount, i) in discounts" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ discount.name }}</td>
                                                        <td v-if="discount.percentage">{{
                                                                discount.percentage
                                                            }}{{ discount.type == 'percentage' ? '%' : '' }}
                                                        </td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="discount.flat">{{ discount.flat }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ discount.is_active == 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ discount.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-discount') || checkForSubmenuButtons('delete-discount')">
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(discount)"
                                                                    v-if="checkForSubmenuButtons('edit-discount')"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" class="btn btn-danger text-light"
                                                                    v-if="checkForSubmenuButtons('delete-discount')">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#' + deleteFormID"-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(discount,i)"-->
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
                :heading="'ADD NEW DISCOUNT'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="DiscountName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="DiscountName"/>
                    </div>
                    <div class="form-group col-md-3 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="discountPercentage" name="percentageAmount"
                                   class="custom-control-input" checked="" value="percentage"
                                   v-model="discountPercentageRadio" @click="discountApply('percentage')">
                            <label class="custom-control-label" for="discountPercentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="discountFlat" name="flatAmount" class="custom-control-input"
                                   value="flat" v-model="discountPercentageRadio" @click="discountApply('flat')">
                            <label class="custom-control-label" for="discountFlat">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="showDiscountDivPercentage">
                        <label for="SurchargePercentage">Percentage <span class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="DiscountPercentage"
                                   placeholder="Enter Percentage"
                                   @keypress="isNumber($event);  numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="showDiscountDivFlat">
                        <label for="SurchargePercentage">Flat Amount <span class="text-danger ml-1">*</span> <span
                            class="text-muted">max: 10K</span> </label>
                        <input type="text" class="form-control" maxlength="5" v-model="DiscountFlat"
                               placeholder="Enter Flat Amount"
                               @keypress="isNumber($event)">

                    </div>
                    <div class="col-md-12">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" value="1" checked class="colorinput-input"
                                       @change="checkBox($event)"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addDiscount" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Save Discount' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Discount"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="DiscountName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                    <div class="form-group col-md-3 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editPercentage" name="editPercentageAmount"
                                   class="custom-control-input" :checked="dataEdit.type == 'percentage'"
                                   value="percentage" v-model="dataEdit.discountPercentageRadio"
                                   @click="discountApply('editPercentage')">
                            <label class="custom-control-label" for="editPercentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editFlat" name="editFlatAmount" class="custom-control-input"
                                   :checked="dataEdit.type == 'flat'" value="flat"
                                   v-model="dataEdit.discountPercentageRadio" @click="discountApply('editFlat')">
                            <label class="custom-control-label" for="editFlat">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="dataEdit.type == 'percentage'">
                        <label for="SurchargePercentage">Percentage <span class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="dataEdit.percentage"
                                   placeholder="Enter Percentage"
                                   @keypress="isNumber($event); numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="dataEdit.type == 'flat'">
                        <label for="SurchargePercentage">Flat Amount <span class="text-danger ml-1">*</span><span
                            class="text-muted">max: 10K</span></label>
                        <input type="text" class="form-control" maxlength="5" v-model="dataEdit.flat"
                               placeholder="Enter Flat Amount"
                               @keypress="isNumber($event)">
                    </div>
                    <div class="col-md-12">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" id="editCheckBox"
                                       @change="editCheckBox($event)" v-bind:checked="dataEdit.is_active == 1"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDiscount" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Discount' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Discount ???'
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
    name: "DiscountPage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            loading: false,
            discounts: [],
            isActive: 1,
            formID: "discount_form",
            editFormID: "edit_discount_form",
            deleteFormID: "delete_discount_form",
            validationErrors: [],
            permissions: [],
            success: false,
            showDiscountDivPercentage: true,
            showDiscountDivFlat: false,
            error: false,
            DiscountName: '',
            DiscountPercentage: '',
            DiscountFlat: '',
            delId: "",
            PercentageName: '',
            discountPercentageRadio: 'percentage',
            dataEdit: {
                id: "",
                name: "",
                percentage: "",
                flat: "",
            },
        };
    },
    async created() {
        $('.modal').remove();
        await this.fetchDiscount();

        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
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

        discountApply(value) {
            if (value == "percentage") {
                this.showDiscountDivPercentage = true;
                this.showDiscountDivFlat = false;
            }
            if (value == "flat") {
                this.showDiscountDivPercentage = false;
                this.showDiscountDivFlat = true;
            }
            if (value == "editPercentage") {
                this.dataEdit.type = 'percentage';
            }
            if (value == "editFlat") {
                this.dataEdit.type = 'flat';

            }
        },
        async fetchDiscount() {
            const res = await this.callApi("post", 'discount');
            if (res.status == 200) {
                this.discounts = res.data
            } else {
                console.log(res);
            }

            setTimeout(() => {
                $('#discount_table').DataTable();
            }, 300);
        },
        clearForm: function () {
            this.DiscountName = '';
            this.DiscountPercentage = '';
            this.DiscountFlat = '';
            this.discountPercentageRadio = "percentage";
            this.isActive = 1;
            this.showDiscountDivPercentage = true;
            this.showDiscountDivFlat = false;
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

        async addDiscount() {
            this.validationErrors = [];
            if (this.DiscountName == "")
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.discountPercentageRadio == "percentage") {
                if (this.DiscountPercentage == "" || typeof this.DiscountPercentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.discountPercentageRadio == "flat") {
                if (this.DiscountFlat == "" || typeof this.DiscountFlat == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Amount Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            this.loading = true;
            const data = {
                name: this.DiscountName,
                type: this.discountPercentageRadio,
                percentage: this.DiscountPercentage,
                flat: this.DiscountFlat,
                active: this.isActive,
            }

            const res = await this.callApi("post", "discount/store", data);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Discount Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.DiscountName = '';
                this.discountPercentageRadio = 'percentage';
                this.DiscountFlat = '';
                this.DiscountPercentage = '';
                this.isActive = 1;
                this.showDiscountDivPercentage = true;
                this.showDiscountDivFlat = false;

                $('#discount_table').DataTable().destroy();
                await this.fetchDiscount();
                window.scrollTo(0, 0);

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

        async updateDiscount() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return swal({
                    title: "Required!",
                    text: "Name Field is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.type == "percentage" || this.dataEdit.discountPercentageRadio == 'percentage') {
                if (this.dataEdit.percentage == "" || this.dataEdit.percentage == null || typeof this.dataEdit.percentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.dataEdit.type == 'flat' || this.dataEdit.discountPercentageRadio == "flat") {
                if (this.dataEdit.flat == "" || this.dataEdit.flat == null || typeof this.dataEdit.flat == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Flat Amount Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            this.loading = true;
            const res = await this.callApi("post", 'discount/update', this.dataEdit);
            if (res.status === 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Discount Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#discount_table').DataTable().destroy();
                await this.fetchDiscount();
            } else {
                if (res.status === 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                    // window.location.reload();
                }, 2000);
            }
        },


        async deleteModal(discount, i) {
            const deletingObj = {
                url: "discount/delete",
                data: discount,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(dis) {

            this.dataEdit = dis;
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.discounts.splice(obj.index, 1)
                $('#discount_table').DataTable().destroy();
                this.fetchDiscount();
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
