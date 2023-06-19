<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Routes</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-routes')"
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
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
                                                    id="route_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Via</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-routes') || checkForSubmenuButtons('details-routes') || checkForSubmenuButtons('delete-routes')">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(route, i) in routes" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ route.name }}</td>
                                                        <td>{{ route.via??'N/A' }}</td>
                                                        <td>{{ route.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-routes') || checkForSubmenuButtons('details-routes') || checkForSubmenuButtons('delete-routes')">
                                                            <button title="Show Route Details" v-if="checkForSubmenuButtons('details-routes')"
                                                                    class="btn btn-outline-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#showDetails"
                                                                    @click="fetchRouteDetails( route.id )">See Details
                                                            </button>
                                                            <button class="btn btn-primary mx-1" title="Edit Routes" v-if="checkForSubmenuButtons('edit-routes')"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(route)"
                                                            ><i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" title="Delete Route" v-if="checkForSubmenuButtons('delete-routes')"
                                                                    class="btn btn-danger">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#' + deleteFormID "-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(fareClass,i)"-->
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
                    <div class="form-group col-md-4">
                        <label for="name">Route Start Point Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="routeStartName"/>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="name">Route End Point Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="routeEndName"/>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Via</label>
                        <input type="text" class="form-control" v-model="routeVia"/>
                    </div>
                    <div class="col-md-12 d-flex align-items-center">
                        <div class="col-md-6">
                            <h5>Select Cities</h5>
                        </div>
                        <div class="col-md-6">
                            <label for="reverseSeats" class="text-dark mr-3">Reverse Route</label>
                            <label class="colorinput">
                                <input name="color" type="checkbox" id="reverseSeats" class="colorinput-input"
                                       :checked="this.reverseRoute == 1" @change="checkBox($event)">
                                <span class="colorinput-color bg-primary"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>City From</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="index in loop" :key="index">
                                <td>
                                    <select class="form-control rounded-0" id="selectCities"
                                            @change="fetchTerminals($event , index)">
                                        <option value="0" selected>Select City</option>
                                        <option v-for="(city, i) in cities" :value="city.id" :key="i">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                    <button class="btn btn-outline-danger" @click="removeRow">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addRoute" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Save Route' }}
                    </button>
                </template>
            </Add>

            <!--            Details Model-->
            <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Route Fare Chart</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeModal()">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
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
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <Edit
                heading="Edit Route Name"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="name">Route Start Point Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.routeStartName"/>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="name">Route End Point Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.routeEndName"/>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label for="name">Via</label>
                        <input type="text" class="form-control" v-model="dataEdit.routeVia"/>
                    </div>
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="editLoading" @click="updateRoute()">
                        {{ editLoading ? 'Loading...' : 'Update Route' }}
                    </button>
                </template>
            </Edit>

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
            loading: false,
            editLoading: false,
            cities: [],
            validationErrors: [],
            city: 0,
            addCities: [],
            companies: [],
            terminals: [],
            fetchedData: [],
            addTerminalsOnClick: [],
            routes: [],
            formID: "route_form",
            editFormID: 'edit_route_form',
            data: {},
            dataEdit: {},
            from: {},
            to: {},
            success: false,
            error: false,
            icon: ' <i class="fa fa-bus"></i> ',
            loop: 1,
            routeStartName: '',
            routeVia: '',
            routeEndName: '',
            reverseRoute: 1,
            routeDetails: [],
            th: [],
            permissions: [],
            classFareName: ''
        };
    },
    created() {
        this.fetchCities();
        this.permissions = this.$store.state.permissions;

    },
    methods: {
        closeModal(){
            $(".modal").click();
        },
        clearForm: function () {
            route
            this.data = {};
            this.reverseRoute = 1;
            this.loop = 1;
            this.addCities = [];
            $("select#selectCities").prop('selectedIndex', 0);
        },
        fareClassValue(data, className) {
            const dataTwo = data;
            const converted = Object.keys(dataTwo)
            let new_name = '';
            converted.forEach((element, i) => {
                if (className + '_fare' == element) {
                    new_name = dataTwo[element];
                }
            });
            return new_name ? new_name + ' PKR' : 'N/A';
        },
        edit(route) {
            this.dataEdit = {
                id: route.id,
                routeStartName: route.name.split('-')[0],
                routeEndName: route.name.split('-')[1],
                routeVia: route.via,
            }
        },
        async addRoute() {
            if (this.routeStartName == '' || typeof this.routeStartName == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Route Start Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.routeEndName == '' || typeof this.routeEndName == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Route End Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            const data = {
                routeStart: this.routeStartName,
                routeEnd: this.routeEndName,
                routeVia: this.routeVia,
                cities: this.addCities,
                revereRoute: this.reverseRoute,
                terminals: this.addTerminalsOnClick
            }
            this.loading = true;
            const res = await this.callApi("post", "routes/store", data);
            if (res.status === 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Route Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                $('#route_table').DataTable().destroy();
                this.routeStartName = "";
                this.routeEndName = "";
                this.routeVia = "";
                this.loop = 1;
                this.addCities = 0;
                this.cities = 0;
                this.routeDetails = [];
                await this.fetchCities();
            } else {
                this.loading = false;
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
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

        async updateRoute() {

            if (this.dataEdit.routeStartName == '' || typeof this.dataEdit.routeStartName == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Route Start Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }

            if (this.dataEdit.routeEndName == '' || typeof this.dataEdit.routeEndName == 'undefined') {
                return swal({
                    title: "Required!!",
                    text: "Route End Name is Required",
                    icon: "error",
                    timer: 2000
                });
            }
            this.editLoading = true;
            const res = await this.callApi("post", "routes/update", this.dataEdit);
            if (res.status == 200) {
                $(".modal").click();
                this.editLoading = false;
                $('#route_table').DataTable().destroy();
                swal({
                    title: "Success",
                    text: "Route Name Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchCities();
            } else {
                this.editLoading = false;
                if (res.status == 422) {
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
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
        checkBox: function (e) {
            if (e.target.checked) {
                this.reverseRoute = 1;
            } else {
                this.reverseRoute = 0;
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
                if (index == -1) {
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
            if (indexI == -1) {
                this.addCities.push(value);
            }
        },
        async fetchCities() {
            const cityRes = await this.callApi("post", "routes/list");
            if (cityRes.status === 200) {
                this.cities = cityRes.data.cities;
                this.routes = cityRes.data.routes;
            }

            setTimeout(() => {
                $('#route_table').DataTable();
            }, 300);
        },
        changeInfo(from, to) {
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id;
            this.data.to = to.id;
        },
        async fetchRouteDetails(id) {

            const routeDetailRes = await this.callApi("post", "routes/details", {
                id: id
            });
            if (routeDetailRes.status === 200) {
                this.routeDetails = routeDetailRes.data.data;
                this.th = routeDetailRes.data.th;
            }
        },

        deleteModal(terminal, i) {
            const deletingObj = {
                url: "terminal/delete",
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
