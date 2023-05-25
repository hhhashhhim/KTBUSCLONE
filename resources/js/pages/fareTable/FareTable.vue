<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
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
                                <button class="btn btn-primary mt-4 ml-2" type="button" @click="fetchRecord()"
                                        :disabled="loadingTable">
                                    {{ loadingTable ? 'Loading...' : 'Fetch Record' }}
                                </button>
                            </div>
                        </div>
                        <div class="bg-secondary mx-4 border rounded" v-if="queueProgress">
                            <div class="bg-success rounded text-center text-white"
                                 :style="{'width':(progressPercent > 1 ? progressPercent : 2) +'%'}">
                                {{ progressPercent > 100 ? Progressing : progressPercent }}%
                            </div>
                        </div>
                        <div class="d-flex justify-content-between px-4 border" v-else>
                            <p>After updating time difference press button this will check and update your schedule.
                                This can take time.</p>
                            <button class="btn btn-danger mt-4 ml-2 mb-1" type="button" @click="updateScheduleTimes()"
                                    :disabled="loadingTable">
                                {{ loadingTable ? 'Loading...' : 'Update Schedule' }}
                            </button>
                        </div>
                        <div class="border">
                            <div class="row d-flex justify-content-between mx-3 my-2">
                                <div class="form-group col-md-3">
                                    <label for="department">From City<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="addForm.fromCity">
                                        <option value="0">Select From City</option>
                                        <option v-for="(city,i) in updateCities" :key="i" :value="city.id"> {{
                                                city.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="department">To City<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="addForm.toCity">
                                        <option value="0">Select To City</option>
                                        <option v-for="(city,i) in updateCities" :key="i" :value="city.id"> {{
                                                city.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="EmployeeName">Updated Fare <span
                                        class="text-danger ml-1">*</span></label>
                                    <input type="text" id="EmployeeName" class="form-control"
                                           @keypress="isNumber($event)" maxlength="5"
                                           v-model="addForm.updatedFare"/>
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="reverseFare">Reverse Fare </label>
                                    <label class="colorinput mx-3 mt-3">
                                        <input type="checkbox" id="reverseFare" class="colorinput-input"
                                               v-model="addForm.reverse"/>
                                        <span class="colorinput-color bg-primary"></span>
                                    </label>
                                </div>
                                <div class="form-group col-md-2">
                                    <button class="btn btn-success mt-4 ml-2 mb-1" type="button" @click="updateFare()"
                                            :disabled="loadingFare">
                                        {{ loadingFare ? 'Loading...' : 'Update Fare' }}
                                    </button>
                                </div>
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
                                <div class="col-12 text-center py-5" v-if="loading">
                                    <div class="spinner-grow text-primary" style="width: 6rem; height: 6rem;"
                                         role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                                <div class="col-12" v-else>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive" v-if="cities.length == 0 || showDivOrHide">
                                                <table class="table table-striped table-hover table-bordered">
                                                    <thead>
                                                    <tr v-if="cities.length == 0">
                                                        <th style="font-size:15px;">{{
                                                                msg == 1 ? "Class Not Selected......." : "No Cities Found......."
                                                            }}
                                                        </th>
                                                    </tr>
                                                    <tr v-else>
                                                        <th></th>
                                                        <th class="text-capitalize" v-for="(city,i) in cities" :key="i">
                                                            {{ city.name }}
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(departureCity,i) in cities" :key="i">
                                                        <template
                                                            v-for="(destinationCity,j) in departureCity.destinationCities"
                                                            :key="j">
                                                            <th class="text-capitalize" v-if="j==0"> {{
                                                                    cities[i].name
                                                                }}
                                                            </th>
                                                            <td :class="destinationCity.id==departureCity.id?'bg-danger':'modal-cell'">
                                                                <a
                                                                    href="#" :data-target="'#'+formID"
                                                                    data-toggle="modal"
                                                                    @click="changeInfo(departureCity,destinationCity)"
                                                                    v-if="departureCity.id!=destinationCity.id"
                                                                    class="btn btn-success btn-block modal-btn d-flex flex-column justify-content-between">
                                                                    <span>Fare : {{ destinationCity.fare }}</span>
                                                                    <span class="text-title">Time : {{
                                                                            destinationCity.time_difference ?? 'Not Added'
                                                                        }}</span>
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
                        <label for="fare">Fare <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="data.fare" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="time_difference">Travel Time ( e.g HH:MM ) <span
                            class="text-danger ml-1">*</span></label>
                        <vue-mask
                            class="form-control"
                            v-model="data.time_difference"
                            mask="00:00"
                            :raw="false"
                            :options="options">
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="distance_in_km">Distance In KM <span class="text-danger ml-1">*</span></label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" @keypress="isNumber($event)" maxlength="4"
                                   v-model="data.distance_in_km">
                            <div class="input-group-append">
                                <span class="input-group-text">km</span>
                            </div>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="add" :disabled="loading">
                        {{ loading ? 'Loading... ' : 'Save Fare Details' }}
                    </button>
                </template>
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
import vueMask from "vue-jquery-mask";

export default {
    name: "FareTable",
    created() {
        this.getClasses();
        this.getScheduleProgress();
        setInterval(() => {
            this.getScheduleProgress();
        }, 2000)
    },
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            loading: false,
            showDivOrHide: false,
            loadingTable: false,
            loadingFare: false,
            date: null,
            options: {
                placeholder: 'HH:MM',
            },
            cities: [],
            companies: [],
            fetchedData: [],
            queueProgress: [],
            progressPercent: "",
            validationErrors: [],
            FareClassName: '',
            msg: 1,
            formID: "fareTable_form",
            fareClasses: [],
            data: {
                fare_class: '0',
            },
            addForm: {
                fromCity: 0,
                toCity: 0,
                updatedFare: '',
                reverse: true,
            },
            dataEdit: {},
            from: {},
            updateCities: [],
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
            if (this.data.fare == '' || typeof this.data.fare == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Fare is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.time_difference == '' || typeof this.data.time_difference == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Travel Time is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            const timeRegex = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timeRegex.test(this.data.time_difference)) {
                return swal({
                    title: "Warning!",
                    text: "Please enter a valid time in HH:MM format",
                    icon: "warning",
                    timer: 2000
                });
            }
            if (this.data.distance_in_km == '' || this.data.distance_in_km == null || typeof this.data.distance_in_km == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Distance Field is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = true;
            const res = await this.callApi("post", "fare-table/store", this.data);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Fare Table Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.cities = res.data
            }
            if (res.status == 422) {
                this.loading = false;
                let errorContent = "";
                let count = 0;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
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

        },

        async getClasses() {
            const res = await this.callApi("post", 'fare-table/fare_class/get');
            const resUpdateCities = await this.callApi("post", 'fare-table/update/cities/get');
            if (res.status == 200 && resUpdateCities.status == 200) {
                this.fareClasses = res.data;
                this.updateCities = resUpdateCities.data;
            } else {
                console.log(res);
                console.log(resUpdateCities);
            }
        },

        async getScheduleProgress() {
            const res = await this.callApi("post", 'fare-table/schedules/times/update/progress');
            if (res.status == 200) {
                this.queueProgress = res.data
                this.progressPercent = parseFloat(parseFloat(res.data.passed_time) / parseFloat(res.data.total_time == 0 ? 1 : res.data.total_time) * 100).toFixed(0);
            } else {
                console.log(res);
            }
        },

        CheckBox: function (e) {
            if (e.target.checked) {
                this.addForm.reverse = 1;
            } else {
                this.addForm.reverse = 0;
            }
        },
        async updateScheduleTimes() {
            this.loadingTable = true;
            const res = await this.callApi("post", 'fare-table/schedules/times/update');
            if (res.status == 200) {
                this.getScheduleProgress();
                swal({
                    title: "Success",
                    text: "Schedule Times Updated",
                    icon: "success",
                    timer: 2000
                });
                setTimeout(() => {
                    this.loadingTable = false;
                }, 500);
            } else {
                console.log(res);
            }
        },
        async updateFare() {
            if (this.addForm.fromCity == '0') {
                return swal({
                    title: "Required!",
                    text: "Please Select From City!",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.toCity == '0') {
                return swal({
                    title: "Required!",
                    text: "Please Select To City!",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.data.fare_class == '0') {
                return swal({
                    title: "Required!",
                    text: "Please Select Fare Class",
                    icon: "error",
                    timer: 2000
                });
            }
            if (this.addForm.updatedFare == '' || typeof this.addForm.updatedFare == 'undefined') {
                return swal({
                    title: "Required!",
                    text: "Updated Fare is Required!",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingFare = true;
            this.addForm.fareClass = this.data.fare_class;
            const resUpdateFare = await this.callApi("post", 'fare-table/fare/update', this.addForm);
            if (resUpdateFare.status == 200) {
                this.loadingFare = false;
                swal({
                    title: "Success",
                    text: "Fare Updated Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.fetchRecord();
                this.addForm.fromCity = 0;
                this.addForm.toCity = 0;
                this.addForm.updatedFare = '';
                this.addForm.reverse = true;

            }
        },

        async changeInfo(from, to) {
            this.data.fare = '';
            this.data.distance_in_km = '';
            this.data.time_difference = '';
            const resGetTerminal = await this.callApi("post", 'fare-table/check', {
                from: from.id,
                to: to.id,
                fare_class: this.data.fare_class,
            });
            if (resGetTerminal.status == 200 && resGetTerminal.data !== '') {
                this.data = resGetTerminal.data;
                this.data.created = 1;
            } else {
                this.data.created = 0;
            }
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id
            this.data.to = to.id;
        },
        async fetchRecord() {
            if (this.data.fare_class == 0) {
                this.showDivOrHide = false;
                this.cities = [];
                return swal({
                    title: "Required!!",
                    text: "Select Any Fare Class",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loadingTable = true;
            this.showDivOrHide = false;
            const res = await this.callApi("post", "fare-table", {
                company_id: this.data.company_id, fare_class: this.data.fare_class
            });
            if (res.status == 200) {
                this.msg = 2;
                this.cities = res.data
                this.showDivOrHide = true;
                setTimeout(() => {
                    this.loadingTable = false;
                }, 500);
            } else {
                console.log("Something Went Wrong");
            }
            // }
        },

        deleteModal(terminal, i) {
            const deletingObj = {
                url: "terminal/delete",
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

/*.modal-cell:hover .modal-btn {
    position: absolute;
    z-index: 20;
    transform: scale(1.3) translateY(-20px);
    box-shadow: 0px 0px 10px black;
}*/

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
