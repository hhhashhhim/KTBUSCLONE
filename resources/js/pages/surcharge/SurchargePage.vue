<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Surcharge</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-surcharge')"
                                   href="#"
                                   data-toggle="modal"
                                   :data-target="'#' + formID"
                                   class="btn btn-primary" @click="clearForm()"
                                >
                                    Add Surcharge
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
                                                <table class="table table-striped table-hover" id="surcharge_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Percentage</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-surcharge') || checkForSubmenuButtons('delete-surcharge')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(surcharge, i) in surcharges" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ surcharge.name }}</td>
                                                        <td v-if="surcharge.percentage">{{ surcharge.percentage }}%</td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="surcharge.flat">{{ surcharge.flat }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ surcharge.is_active == 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ surcharge.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-surcharge') || checkForSubmenuButtons('delete-surcharge')">
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(surcharge)"
                                                                    v-if="checkForSubmenuButtons('edit-surcharge')"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" v-if="checkForSubmenuButtons('delete-surcharge')"
                                                                    class="btn btn-danger">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#' + deleteFormID"-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(surcharge,i)"-->
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
                :heading="'ADD SURCHARGE'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="SurchargeName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="SurchargeName"/>
                    </div>
                    <div class="form-group col-md-3 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="percentage" name="percentageAmount" class="custom-control-input"
                                   checked="" value="percentage" v-model="percentageRadio"
                                   @click="surchargeApply('percentage')">
                            <label class="custom-control-label" for="percentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="flat" name="flatAmount" class="custom-control-input" value="flat"
                                   v-model="percentageRadio" @click="surchargeApply('flat')">
                            <label class="custom-control-label" for="flat">Flat Amount</label>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="showDivPercentage">
                        <label for="SurchargePercentage">Percentage <span class="text-danger ml-1">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="SurchargePercentage"
                                   placeholder="Enter Percentage"
                                   @keypress="isNumber($event); numberRange($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-5" v-if="showDivFlat">
                        <label for="SurchargePercentage">Flat Amount <span class="text-danger ml-1">*</span> <span
                            class="text-muted">max: 10K</span> </label>
                        <input type="text" class="form-control" maxlength="5" v-model="SurchargeFlat"
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
                    <button type="button" class="btn btn-primary" @click="addSurcharge" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Save Surcharge' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Surcharge"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="SurchargeName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                    <div class="form-group col-md-3 mt-4 pt-2">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editPercentage" name="editPercentageAmount"
                                   class="custom-control-input" :checked="dataEdit.type == 'percentage'"
                                   value="percentage" v-model="dataEdit.percentageRadio"
                                   @click="surchargeApply('editPercentage')">
                            <label class="custom-control-label" for="editPercentage">Percentage</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="editFlat" name="editFlatAmount" class="custom-control-input"
                                   :checked="dataEdit.type == 'flat'" value="flat" v-model="dataEdit.percentageRadio"
                                   @click="surchargeApply('editFlat')">
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
                    <button type="button" class="btn btn-primary" @click="updateSurcharge"
                            :disabled="loading"> {{ loading ? 'Loading...' : 'Update Surcharge' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit MOdel End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Surcharge ???'
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
    name: "SurchargePage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            loading: false,
            surcharges: [],
            permissions: [],
            isActive: 1,
            showDivPercentage: true,
            showDivFlat: false,
            formID: "surcharge_form",
            editFormID: "edit_surcharge_form",
            deleteFormID: "delete_surcharge_form",
            validationErrors: [],
            success: false,
            error: false,
            SurchargeName: '',
            delId: "",
            SurchargePercentage: '',
            SurchargeFlat: '',
            percentageRadio: 'percentage',
            dataEdit: {
                id: "",
                name: "",
                percentage: "",
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

        await this.fetchSurcharges();
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
        surchargeApply(value) {
            if (value == "percentage") {
                this.showDivPercentage = true;
                this.showDivFlat = false;
            }
            if (value == "flat") {
                this.showDivPercentage = false;
                this.showDivFlat = true;
            }
            if (value == "editPercentage") {
                this.dataEdit.type = 'percentage';
            }
            if (value == "editFlat") {
                this.dataEdit.type = 'flat';

            }
        },
        async fetchSurcharges() {
            const res = await this.callApi("post", 'surcharge');
            if (res.status == 200) {
                this.surcharges = res.data
            } else {
                console.log(res);
            }

            setTimeout(() => {
                $("#surcharge_table").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.SurchargeName = "";
            this.SurchargePercentage = "";
            this.percentageRadio = 'percentage';
            this.SurchargeFlat = "";
            this.isActive = 1;
            this.showDivFlat = false;
            this.showDivPercentage = true;
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

        async addSurcharge() {
            this.validationErrors = [];
            if (this.SurchargeName == "")
                swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.percentageRadio == "percentage") {
                if (this.SurchargePercentage == "" || typeof this.SurchargePercentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.percentageRadio == "flat") {
                if (this.SurchargeFlat == "" || typeof this.SurchargeFlat == "undefined") {
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
                name: this.SurchargeName,
                type: this.percentageRadio,
                percentage: this.SurchargePercentage,
                flat: this.SurchargeFlat,
                active: this.isActive,
            }

            const res = await this.callApi("post", "surcharge/store", data);
            if (res.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Surcharge Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.SurchargeName = '';
                this.percentageRadio = 'percentage';
                this.SurchargePercentage = '';
                this.SurchargeFlat = '';
                this.showDivFlat = false;
                this.showDivPercentage = true;
                this.isActive = 1;
                $("#surcharge_table").DataTable().destroy();
                this.loading = false;
                await this.fetchSurcharges();
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

        async updateSurcharge() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                swal({
                    title: "Required!",
                    text: "Name Field is Required ",
                    icon: "error",
                    timer: 2000
                });

            if (this.dataEdit.type == "percentage" || this.dataEdit.percentageRadio == 'percentage') {
                if (this.dataEdit.percentage == "" || this.dataEdit.percentage == null || typeof this.dataEdit.percentage == "undefined") {
                    return swal({
                        title: "Required!",
                        text: "Percentage Field is Required",
                        icon: "error",
                        timer: 2000
                    });
                }
            }
            if (this.dataEdit.type == 'flat' || this.dataEdit.percentageRadio == "flat") {
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
            const res = await this.callApi("post", 'surcharge/update', this.dataEdit);
            if (res.status === 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Surcharge Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#surcharge_table").DataTable().destroy();
                this.loading = false;
                await this.fetchSurcharges();
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


        async deleteModal(surcharge, i) {
            const deletingObj = {
                url: "surcharge/delete",
                data: surcharge,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(sur) {
            this.dataEdit = sur;


        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.surcharges.splice(obj.index, 1)
                $("#surcharge_table").DataTable().destroy();
                this.fetchSurcharges();
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
