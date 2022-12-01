<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Maintenance Linking</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Link New Maintenance
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
                                                    id="maintenance_table"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Fleet Name/Number</th>
                                                        <th>Current Reading</th>
                                                        <th>Reading Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(data, i) in mainData" :key="i">
                                                        <td>{{ data.bus_number }}</td>
                                                        <td>{{ data.current_reading }} </td>
                                                        <td>{{ data.reading_date??'N/A' }} </td>
                                                        <td>
                                                            <button class="btn btn-outline-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#showDetails"
                                                                    @click="fetchFleetDetails( data.id )">See Details
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
                    <div class="form-group col-md-6">
                        <label for="name">Select Bus <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0" v-model="fleetId">
                            <option value="" selected>Select Bus</option>
                            <option v-for="(fleet, i) in fleets" :value="fleet.id" :key="i">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Current Reading (km) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" min="0" v-model="currentReading"/>
                    </div>
                    <div class="col-md-12 d-flex align-items-center">
                        <div class="col-md-6">
                            <h5>Select Part For Maintenance</h5>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Part</th>
                                <th>Maintenance Required After (km)</th>
                                <th>Last Maintenance At (km)</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="index in loop" :key="index">
                                <td>
                                    <select class="form-control rounded-0" @change="saveRow($event,index,'rowPart')">
                                        <option value="" selected>Select Part </option>
                                        <option v-for="(part, i) in parts" :value="part.id" :key="i">
                                            {{ part.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,index,'rowAfter')" />
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,index,'rowLast')" />
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                    <button class="btn btn-outline-danger" @click="removeRow($event,index)">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="linkMaintenance" :disabled="loading" >{{loading ? 'Loading...' : 'Save Route' }}
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
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped">
                                            <thead>
                                            <tr>

                                                <th>Fleet Part</th>
                                                <th>Maintenance Required After</th>
                                                <th>Last Maintenance At</th>
                                                <th>Last Maintenance Date</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(single, i) in fleetDetails.maintenance_part_link" :key="i">

                                                    <td> {{ single.maintenance_part.name }}</td>
                                                    <td> {{ single.maintenance_after }}</td>
                                                    <td> {{ single.maintenance_at }}</td>
                                                    <td> {{ single.maintenance_date??'N/A' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>


           
        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import {mapGetters} from "vuex";

export default {
    name: "RoutePage",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            loading : false,
            mainData: [],
            fleets: [],
            parts: [],
            validationErrors: [],
            fleetId: "",
            currentReading: "",
            fleetPart: [],
            maintenanceAfter: [],
            maintenanceAt: [],
            fleetDetails: [],
            companies: [],
            terminals: [],
            fetchedData: [],
            addTerminalsOnClick: [],
            routes: [],
            formID: "route_form",
            data: {},
            dataEdit: {},
            from: {},
            to: {},
            success: false,
            error: false,
            icon: ' <i class="fa fa-bus"></i> ',
            loop: 1,
            routeStartName: '',
            routeEndName: '',
            reverseRoute: 1,
            th: [],
            classFareName: ''
        };
    },
    created() {
        this.fetchData();
    },
    methods: {
        clearForm: function () {
          this.data = {};
          this.reverseRoute = 1;
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
        saveRow(event,index,fieldName) {
           
            if(fieldName == "rowPart")
            {
                this.fleetPart[index-1] = event.target.value;
            }
            if(fieldName == "rowAfter")
            {
                this.maintenanceAfter[index-1] = event.target.value;
            }
            if(fieldName == "rowLast")
            {
                this.maintenanceAt[index-1] = event.target.value;
            }

        },
        async linkMaintenance() {
            
            // validation for empty data
            if(!this.fleetId || !this.currentReading || this.fleetPart.length == 0 || 
                this.maintenanceAfter.length == 0 || this.maintenanceAt.length == 0)
            {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 4000
                });
            }

            // if(this.fleetPart.length != this.maintenanceAfter.length || this.maintenanceAfter.length != this.maintenanceAt.length)
            // {
            //     return swal({
            //         title: "Error",
            //         text: "Please Fill All Field Or Remove Extra",
            //         icon: "error",
            //         timer: 4000
            //     }); 
            // }
            
            // check if any index is empty or null in object
            for(var i = 0; i < this.fleetPart.length; i++)
            {
                if(!this.fleetPart[i] || !this.maintenanceAfter[i] || !this.maintenanceAt[i])
                {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 4000
                    }); 
                }
            }

            // post data
            const data = {
                fleetId: this.fleetId,
                currentReading: this.currentReading,
                fleetPart: this.fleetPart,
                maintenanceAfter: this.maintenanceAfter,
                maintenanceAt: this.maintenanceAt,
            }

            this.loading = true;
            const res = await this.callApi("post", "fleet/part/link", data);
            if (res.status === 200) {
                this.loading = false;
                $('#maintenance_table').DataTable().destroy();
                this.fleetId = "";
                this.currentReading = "";
                this.loop = 1;
                this.fleetPart =  [];
                this.maintenanceAfter =  [];
                this.maintenanceAt =  [];
               swal({
                    title: "Success",
                    text: "Maintenance Added",
                    icon: "success",
                    timer: 2000
                });
                await this.fetchData();
                this.loading = false;
            }
            else {
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
                            timer: 4000
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
        async add() {
            this.validationErrors = [];
            this.loading = true;

            const res = await this.callApi("post", "fare-table/store", this.data);
            if (res.status === 200) {
                this.loading = false;

                // this.success = "Fare Table Updated Created Successfully";
               swal({
                    title: "Success",
                    text: "Fare Table Created Successfully",
                    icon: "success",
                    timer: 2000
                });
                // Object.keys(obj).forEach((i) => obj[i] = null);
                this.data = {};

                this.cities = res.data;
                window.scrollTo(0, 0);
                this.
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                }, 3000);
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
        addRow() {
            this.loop++;
        },
        removeRow(event,index) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.fleetPart.splice((getRowNumber-1), 1);
            this.maintenanceAfter.splice((getRowNumber-1), 1);
            this.maintenanceAt.splice((getRowNumber-1), 1);
            event.target.parentElement.parentElement.remove();
            // this.loop--;
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
        async fetchData() {
            const fleetRes = await this.callApi("post", "fleet");
            if (fleetRes.status === 200) {
                
                this.mainData = fleetRes.data.mainData;
                this.fleets = fleetRes.data.busDrop;
                this.parts = fleetRes.data.partDrop;
            }

            setTimeout(() => {
                $('#maintenance_table').DataTable();
            }, 300);
        },
        changeInfo(from, to) {
            this.from = from.name;
            this.to = to.name;
            this.data.from = from.id;
            this.data.to = to.id;
        },
        async fetchFleetDetails(id) {

            const fleetDetailRes = await this.callApi("post", "fleet/single/part/link", {
                id: id
            });
            if (fleetDetailRes.status === 200) {
                this.fleetDetails = fleetDetailRes.data;
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
