<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Routes Details</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                >
                                    Add New Route
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
                                                    id="edit_loc"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(route, i) in routes" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ route.name }}</td>
                                                        <td>
                                                            <button class="btn btn-outline-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#showDetails"
                                                                    @click="fetchRouteDetails( route.id )">See Details
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
                :heading="'ADD NEW ROUTE'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" v-model="routeName"/>
                    </div>

                    <div class="col-md-12">
                        <h5>Select Terminals</h5>
                        <br/>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">

                    </div>

                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>City From</th>
                                <!--                                <th>Select Terminal</th>-->
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="index in loop" :key="index">
                                <td>
                                    <select class="form-control rounded-0" @change="fetchTerminals($event , index)" >
                                        <option value="0" selected>Select City</option>
                                        <option v-for="(city, i) in cities" :value="city.id" :key="i">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </td>
                                <!--                                <td>-->
                                <!--                   <span class="mx-2" v-for="(item) in terminals[index]" :key="item.id">-->
                                <!--                    <label class="mt-4 checkbox-inputs" for="sms">{{ item.name }}</label>-->
                                <!--                    <label class="colorinput mx-3 mt-3">-->
                                <!--                      <span>-->
                                <!--                        <input-->
                                <!--                            type="checkbox"-->
                                <!--                            class="colorinput-input"-->
                                <!--                            @click="addTerminal($event)"-->
                                <!--                            id="sms"-->
                                <!--                            :value="item.id"-->
                                <!--                        />-->
                                <!--                        <span class="colorinput-color bg-success"></span>-->
                                <!--                      </span>-->
                                <!--                    </label>-->
                                <!--                  </span>-->
                                <!--                                </td>-->
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                    <button class="btn btn-outline-danger" @click="removeRow">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>


                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-primary"
                            @click="addRoute"
                        >
                            Save Route Details
                        </button>
                    </div>
                </div>
            </Add>

            <!--            Details Model-->
            <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Route Fare Chart</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="card">
                                <div class="card-body" style="font-size:14px;">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                            <tr>

                                                <th>City From</th>
                                                <th>City To</th>
                                                <th v-for="(heading,i) in th" :key="i">
                                                    {{ heading.name }}
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <template v-for="(item,j) in routeDetails" :key="j">
                                                <tr v-for="(single, i) in item" :key="i">

                                                    <td> {{ single.departure_city }}</td>
                                                    <td> {{ single.destination_city }}</td>
                                                    <td v-for="(row, k) in th" :key="k">
                                                        {{
                                                            fareClassValue(single, row.name)
                                                        }}

<!--                                                        <span v-if="single.includes(row.name +'_fare')">N/A</span>-->
<!--                                                        {{single.includes(row.name +'_fare')}}-->
                                                    </td>
<!--                                                    <td v-if="single.include(this,th[0]+'_fare')">{{single.Economy_fare}}</td>-->
<!--                                                    <td v-if="single.fare_one"> {{ single.fare_one }}</td>-->
<!--                                                    <td v-else>N/A</td>-->
<!--                                                    <td v-if="single.fare_two"> {{ single.fare_two }}</td>-->
<!--                                                    <td v-else>N/A</td>-->
<!--                                                    <td v-if="single.fare_three"> {{ single.fare_three }}</td>-->
<!--                                                    <td v-else>N/A</td>-->

                                                </tr>
                                            </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Add Modal -->
            <Delete confirmationMessage='Are You Sure You want To Delete This "Route" ???'
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
    name: "RoutePage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            cities: [],
            validationErrors: [],
            city: 0,
            addCities: [],
            companies: [],
            terminals: [],
            fetchedData: [],
            addTerminalsOnClick: [],
            routes: [],
            formID: "addNewRoute",
            data: {
                fare_class: "0",
            },
            dataEdit: {},
            from: {},
            to: {},
            success: false,
            error: false,
            icon: ' <i class="fa fa-bus"></i> ',
            loop: 1,
            routeName: '',
            routeDetails: [],
            th : [],
            classFareName : ''
        };
    },
    created() {
        this.fetchCities();
    },
    methods: {
        fareClassValue( data , className){
            const dataTwo = data;
            const converted = Object.keys(dataTwo)
            let new_name = '';
            converted.forEach((element , i) => {
                if( className + '_fare' == element ){
                    new_name = dataTwo[element];
                }
            });
            return new_name ? new_name + ' PKR' : 'N/A';
        },
        async addRoute() {
            const data = {
                route: this.routeName,
                cities: this.addCities,
                terminals: this.addTerminalsOnClick
            }
            const res = await this.callApi("post", "cities/routes", data);
            if (res.status === 200) {
                this.success = "Route Created Successfully";
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        },
        async add() {
            this.validationErrors = [];
            const res = await this.callApi("post", "fare-table/store", this.data);
            if (res.status === 200) {
                this.success = "Fare Table Updated Created Successfully";
                // Object.keys(obj).forEach((i) => obj[i] = null);
                this.data = {};
                this.cities = res.data;
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                }, 3000);
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
        addRow() {
            this.loop++;
        },
        removeRow() {
            this.loop--;
        },
        addTerminal(event) {
            const value = event.target.value
            if (event.target.checked) {
                const index = this.addTerminalsOnClick.indexOf(value);
                if (index === -1) {
                    this.addTerminalsOnClick.push(value);
                }
            } else {
                const index = this.addTerminalsOnClick.indexOf(value);
                this.addTerminalsOnClick.splice(index, 1);
            }
        },
        async fetchTerminals(event, index) {
            const value = event.target.value;

            const indexI = this.addCities.indexOf(value);
            if (indexI === -1) {
                this.addCities.push(value);
            }

            const terminalRes = await this.callApi("post", "cities/terminals", {
                id: value
            });
            if (terminalRes.status === 200) {
                this.terminals[index] = terminalRes.data;

            }
        },
        async fetchCities() {
            const cityRes = await this.callApi("post", "cities/routes/list");
            if (cityRes.status === 200) {
                this.cities = cityRes.data.cities;
                this.routes = cityRes.data.routes;
            }
        },
        changeInfo(from, to) {
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id;
            this.data.to = to.id;
        },
        async fetchRouteDetails(id) {

            const routeDetailRes = await this.callApi("post", "cities/routes/details", {
                id: id
            });
            if (routeDetailRes.status === 200) {
                this.routeDetails = routeDetailRes.data.data;
                this.th = routeDetailRes.data.th;
            }
        },
        async fetchRecord() {
            if (!this.data.fare_class) {
                this.error = true;
                return;
            }
            const res = await this.callApi("post", "fare-table", {
                company_id: this.data.company_id,
                fare_class: this.data.fare_class,
            });
            if (res.status === 200) {
                this.cities = res.data;
                setTimeout(() => {
                    this.success = "";
                }, 3000);
            } else {
                alert("Something Went Wrong");
            }
        },

        deleteModal(terminal, i) {
            const deletingObj = {
                url: "/terminal/delete",
                data: terminal,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
        heading: function () {
            return from.name + "<i class='fa fa-user'></i>" + to.name;
        },
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
