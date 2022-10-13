<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Discount</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
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
                                                        <th>Percentage</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(discount, i) in discounts" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ discount.name }}</td>
                                                        <td>{{ discount.percentage }}%</td>
                                                        <td>{{ discount.is_active === 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ discount.added_by.name }}</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(discount)" class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a href="#delete-modal" data-toggle="modal"
                                                               @click="deleteModal(discount,i)" class="btn btn-danger">
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
                :heading="'ADD NEW DISCOUNT'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="DiscountName">Name</label>
                        <input type="text" class="form-control" v-model="DiscountName" @keypress="isAlphabet($event)"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">Percentage</label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="PercentageName"
                                   @keypress="isNumber($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-12">
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


                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-primary"
                            @click="addDiscount"
                        >
                            Save Discount Details
                        </button>
                    </div>
                </div>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Discount"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="DiscountName">Name</label>
                        <input type="text" class="form-control" v-model="dataEdit.name" @keypress="isAlphabet($event)"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="PercentageName">Percentage</label>
                        <div class="input-group">
                            <input type="text" class="form-control" maxlength="3" v-model="dataEdit.percentage"
                                   @keypress="isNumber($event)">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" class="colorinput-input" id="editCheckBox"
                                       @change="editCheckBox($event)" v-bind:checked="dataEdit.is_active === 1"/>
                                <span class="colorinput-color bg-success"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="form-group col-md-5">
                        <button type="button" class="btn btn-block btn-success" @click="updateDiscount">Update
                            Discount
                        </button>
                    </div>
                </div>
            </Edit>
            <!--            Edit MOdel End-->
            <Delete
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
            discounts: [],
            isActive: 1,
            formID: "addNewDiscount",
            validationErrors: [],
            success: false,
            error: false,
            DiscountName: '',
            delId: "",
            PercentageName: '',
            dataEdit: {
                id: "",
                name: "",
                percentage: "",
                is_Active: "",
            },
        };
    },
    async created() {
        const res = await this.callApi("post", 'discount');
        if (res.status == 200) {
            this.discounts = res.data
        } else {
            console.log(res);
        }
    },
    methods: {
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
                this.dataEdit.is_Active = 1;
            } else {
                this.dataEdit.is_Active = 0;
            }
        },

        async addDiscount() {
            this.validationErrors = [];
            if (this.DiscountName === "")
                return this.errorsArray("Name is Required", "DiscountName");
            if (this.PercentageName === "")
                return this.errorsArray("Percentage is Required", "PercentageName");

            const data = {
                name: this.DiscountName,
                percentage: this.PercentageName,
                active: this.isActive,
            }

            const res = await this.callApi("post", "discount/store", data);
            if (res.status === 201 && res.statusText === "Created") {
                this.success = "Discount Created Successfully";
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        },

        async updateDiscount() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return this.errorsArray("Name is Required", "DiscountName");
            if (this.dataEdit.percentage === "")
                return this.errorsArray("Percentage is Required", "PercentageName");

            const res = await this.callApi("post", 'discount/update', this.dataEdit);
            if (res.status === 200 && res.statusText === "OK") {
                this.success = "Discount Updated Successfully";
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
        },


        async deleteModal(discount, i) {
            const deletingObj = {
                url: "/discount/delete",
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
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
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
