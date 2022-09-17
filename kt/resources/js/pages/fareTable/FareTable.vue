<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Fare Table</h4>
                            <div class="w-50 d-flex align-items-center">
                                <div class="header-select mx-2">
                                    <label for="fare_class" class="font-weight-bold my-0">Fare Class</label>
                                    <select v-model="data.fare_class" class="form-control rounded-0 text-capitalize">
                                        <option value="0" selected>Select Fare Class</option>
                                        <option v-for="(fareClass,i) in fareClasses" :key="i" :value="fareClass.id"
                                                class="text-capitalize"> {{ fareClass.name }}
                                        </option>
                                    </select>
                                </div>
                                <button class="btn btn-success mt-4" type="button" @click="fetchRecord">Fetch
                                    Record
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">

                                <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="error">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                            @click="error=!error">
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
                                            <div class="table-responsive" v-if="cities">
                                                <table
                                                    class="table table-striped table-hover table-bordered"
                                                    id="edit_loc"
                                                >
                                                    <thead>
                                                    <tr v-if="cities.length === 0">
                                                        <th style="font-size:15px;">{{
                                                                msg == 1 ? "Class Not Selected......." : "No Cities Found......."
                                                            }}
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <th></th>
                                                        <th v-for="(city,i) in cities" :key="i"> {{ city.name }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(departureCity,i) in cities" :key="i">

                                                        <template
                                                            v-for="(destinationCity,j) in departureCity.destinationCities"
                                                            :key="j">
                                                            <th v-if="j==0"> {{ cities[i].name }}</th>
                                                            <td :class="destinationCity.id==departureCity.id?'bg-danger':'modal-cell'">
                                                                <a
                                                                    href="#" :data-target="'#'+formID"
                                                                    data-toggle="modal"
                                                                    @click="changeInfo(departureCity,destinationCity)"
                                                                    v-if="departureCity.id!=destinationCity.id"
                                                                    class="btn btn-success btn-block modal-btn">
                                                                    {{ destinationCity.fare }}
                                                                </a>
                                                            </td>
                                                        </template>

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
                :heading="from + icon + to"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="fare">Fare</label>
                        <input type="text" class="form-control" v-model="data.fare" @keypress="isNumber($event)">
                    </div>
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="commission_flat">Commission Flat</label>-->
<!--                        <input type="number" class="form-control" v-model="data.commission_flat">-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="commission_percentage">Commission Percentage</label>-->
<!--                        <input type="number" class="form-control" v-model="data.commission_percentage">-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="terminal_commission">Terminal Commission</label>-->
<!--                        <input type="number" class="form-control" v-model="data.terminal_commission">-->
<!--                    </div>-->
                    <div class="form-group col-md-4">
                        <label for="time_difference">Time Difference ( e.g 1:30 )</label>
                        <input type="text" class="form-control" v-model="data.time_difference">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="distance_in_km">Distance in KiloMeter</label>
                        <input type="text" class="form-control" @keypress="isNumber($event)" maxlength="4" v-model="data.distance_in_km">
                    </div>
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="surcharge">Surcharge</label>-->
<!--                        <input type="number" class="form-control" v-model="data.surcharge">-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="surcharge_start_date">Surcharge Start Date</label>-->
<!--                        <input type="date" class="form-control" v-model="data.surcharge_start_date">-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="surcharge_end_date">Surcharge End Date</label>-->
<!--                        <input type="date" class="form-control" v-model="data.surcharge_end_date">-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-4">-->
<!--                        <label for="advance_booking">Advance Booking Allowed(Days)</label>-->
<!--                        <input type="number" class="form-control" v-model="data.advance_booking">-->
<!--                    </div>-->

                    <div class="form-group col-md-12">
                        <button type="button" class="btn btn-block btn-success" @click="add">
                            Save Fare Details
                        </button>
                    </div>
                </div>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Add Fare Class"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >

            </Edit>

            <!-- Add Modal -->
            <Delete
                confirmationMessage='Are You Sure You want To Delete This "terminal" ???'
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
    name: "FareTable",
    created() {
        this.getClasses();
    },
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            cities: [],
            companies: [],
            fetchedData: [],
            validationErrors: [],
            FareClassName: '',
            msg: 1,
            formID: "fareTablePopup",
            fareClasses: [],
            data: {
                fare_class: '0',
            },
            dataEdit: {},
            from: {},
            to: {},
            success: false,
            error: false,
            icon: ' <i class="fa fa-bus"></i> ',

        };
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
        async add() {
            this.validationErrors = [];
            const res = await this.callApi("post", "/fare-table/store", this.data);
            console.log(res.data)
            if (res.status === 200) {
                this.success = "Fare Table Updated Successfully";
                this.data.fare = {}
                this.cities = res.data
                setTimeout(() => {
                    this.success = "";
                    window.location.reload();
                    // $("#add-modal").modal("hide")
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

        async addFareClass() {
            this.validationErrors = [];
            if (this.FareClassName === "")
                return this.errorsArray("Fare Class Name is Required", "DiscountName");
            const dataFare = {
                name: this.FareClassName,
            }
            const res = await this.callApi("post", "/fare-table/fare_class/store", dataFare);
            if (res.status === 201) {
                this.success = "Fare Class Added Successfully";
                await this.getClasses();
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
            }
        },

        async getClasses() {
            const res = await this.callApi("post", '/fare-table/fare_class/get');
            if (res.status === 200) {
                this.fareClasses = res.data
            } else {
                console.log(res);
            }
        },

        changeInfo(from, to) {
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id
            this.data.to = to.id
        },
        async fetchRecord() {
            if (!this.data.fare_class) {
                this.error = true;
                return
            }
            const res = await this.callApi("post", "/fare-table", {
                company_id: this.data.company_id, fare_class: this.data.fare_class
            });
            if (res.status === 200) {
                this.msg = 2;
                this.cities = res.data
                setTimeout(() => {
                    this.success = "";
                }, 3000);
            } else {
                alert("Something Went Wrong")
            }
        },

        deleteModal(terminal, i) {
            const deletingObj = {
                url: "/terminal/delete",
                data: terminal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        }
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),

        heading: function () {
            return (from.name + "<i class='fa fa-user'></i>" + to.name);
        }
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.terminals.splice(obj.index, 1);
            }
        },
    },
};
</script>
<style scoped>
table, table * {
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
</style>
