<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Fare Table</h4>
                        </div>
                        <!-- <div class="border">
                            <div class="row d-flex justify-content-between mx-3 my-2">
                                <div class="form-group col-md-3">
                                    <label for="department">From City<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="addForm.fromCity">
                                        <option value="0">Select From City</option>
                                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                                city.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="department">To City<span class="text-danger ml-1">*</span></label>
                                    <select class="form-control" v-model="addForm.toCity">
                                        <option value="0">Select To City</option>
                                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
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
                        </div> -->
                        <div class="border">
                            <div class="row d-flex justify-content-between mx-3 my-2">
                                <div class="form-group col-md-3">
                                    <label for="department">Fare Class</label>
                                    <select class="form-control" v-model="entry.class" @change="getFareTable()">
                                        <option value="0">Select Class</option>
                                        <option v-for="(item,i) in classes" :key="i" :value="item.id"> {{
                                                item.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="department">From City</label>
                                    <select class="form-control" v-model="entry.fromCity" @change="getFareTable()">
                                        <option value="0">Select From City</option>
                                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                                city.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="department">To City</label>
                                    <select class="form-control" v-model="entry.toCity" @change="getFareTable()">
                                        <option value="0">Select To City</option>
                                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                                city.name
                                            }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="department">Deleted</label>
                                    <input class="d-block" type="checkbox" v-model="entry.hide" @change="getFareTable()"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                                <div class="col-12 px-2">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <div v-if="tableLoading">
                                                    <img class="loading-spinner" :src="$store.state.main_url + 'assets/img/loading-spinner.gif'">
                                                </div>
                                                <table v-else class="table table-striped table-hover" id=""
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Fare Class</th>
                                                        <th>From City</th>
                                                        <th>To City</th>
                                                        <th>Fare</th>
                                                        <th>Time Difference ( e.g HH:MM )</th>
                                                        <th>Distance (km)</th>
                                                        <th>Reverse</th>
                                                        <th>Show</th>
                                                        <th>
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-if="fareTable.length > 0">
                                                            <td colspan="7"></td>
                                                            <td><input type="checkbox" @change="allReverseSelect($event)" /></td>
                                                            <td><input type="checkbox"  @change="allHideSelect($event)" :checked="entry.hide == 0" /></td>
                                                        </tr>
                                                        <tr v-for="(item, i) in fareTable" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td><input type="text" disabled v-model="fareTable[i].class.name"></td>
                                                            <td><input type="text" disabled v-model="fareTable[i].city_from.name"></td>
                                                            <td><input type="text" disabled v-model="fareTable[i].city_to.name"></td>
                                                            <td><input type="text" v-model="fareTable[i].fare" @keypress="isNumber($event)"></td>
                                                            <td>
                                                                <vue-mask
                                                                    v-model="fareTable[i].time_difference"
                                                                    mask="00:00"
                                                                    :raw="false"
                                                                    :options="options">
                                                                </vue-mask>
                                                            </td>
                                                            <td><input type="text" v-model="fareTable[i].distance_in_km" @keypress="isNumber($event)" maxlength="4"></td>
                                                            <td><input type="checkbox" v-model="fareTable[i].reverse" :true-value="1" :false-value="0"/></td>
                                                            <td><input type="checkbox" v-model="fareTable[i].hide" :true-value="0" :false-value="1"/></td>
                                                            <td>
                                                                <button class="btn btn-primary btn-sm mr-1" @click="updateFareTable(fareTable[i])" title="update Fare">Save
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="fareTable.length > 0">
                                                            <td colspan="3">
                                                                <button class="btn btn-primary btn-sm mr-1" @click="updateFareTable(fareTable)" title="update Fare">Save All
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
        $('.modal').remove();
        this.getCitiesClasses();
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
            tableLoading: true,
            loadingFare: false,
            options: {
                placeholder: 'HH:MM',
            },
            cities: [],
            classes: [],
            fareTable: [],
            companies: [],
            fetchedData: [],
            validationErrors: [],
            formID: "fareTable_form",
            data: {
                fare_class: '0',
            },
            entry: {
                class: '0',
                fromCity: '0',
                toCity: '0',
                hide: '0',
            },
            addForm: {
                fromCity: 0,
                toCity: 0,
                updatedFare: '',
                reverse: true,
            },
            dataEdit: {},
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

        async getCitiesClasses() {
            const res = await this.callApi("post", 'fare-table/cities/classes');
            if (res.status == 200) {
                this.classes = res.data.classes;
                this.cities = res.data.cities;
            } 
            
        },
        
        async getFareTable() {
            this.tableLoading = true;
            const res = await this.callApi("post", 'fare-table',this.entry);
            if (res.status == 200) {
                this.fareTable = res.data.fareTable;
                this.tableLoading = false;
            } 
            
        },
        
        allReverseSelect: function (e) {
            if (e.target.checked) {
                this.fareTable.forEach((single) => {
                    single.reverse = 1;
                });
            } else {
                this.fareTable.forEach((single) => {
                    single.reverse = 0;
                });
            }
           
        },
        
        allHideSelect: function (e) {
            if (e.target.checked) {
                this.fareTable.forEach((single) => {
                    single.hide = 0;
                });
            } else {
                this.fareTable.forEach((single) => {
                    single.hide = 1;
                });
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
        
        async updateFareTable(data) {
            let formData = Array.isArray(data) ? data : [data];
            
            this.loadingFare = true;
            this.addForm.fareClass = this.data.fare_class;
            const resUpdateFare = await this.callApi("post", 'fare-table/fare/update/multiple', {mydata:formData});
            if (resUpdateFare.status == 200) {
                this.loadingFare = false;
                swal({
                    title: "Success",
                    text: "Fare Updated Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.getFareTable();

            }
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

.loading-spinner {
    display: block;
    margin: 0 auto;
    padding: 2em;
  }
</style>
