<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Food Order Page</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Order
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
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
                                                    id="order_table"
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
                                                        <td>{{ data.current_reading??'N/A' }} </td>
                                                        <td>{{ data.reading_date??'N/A' }} </td>
                                                        <td>
                                                            <button class="btn btn-primary mx-1"
                                                                    data-target="#editLinking_form"
                                                                    data-toggle="modal"
                                                                    @click="editFleetDetails( data.id )">
                                                                    <i class="far fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#showDetails"
                                                                    @click="fetchFleetDetails( data.id )">
                                                                    <i class="far fa-eye"></i>
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
                :heading="'Food Order'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Select Bus <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0" v-model="busId" @change="getSchedule(busId)">
                            <option value="" selected>Select Bus</option>
                            <option v-for="(bus, i) in buses" :value="bus.id" :key="i">
                                {{ bus.bus_number }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Schedule <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" min="0" disabled v-model="schedule"/>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Select Hotel <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0" v-model="postData.hotelId" @change="getFoods(postData.hotelId)">
                            <option value="" selected>Select Hotel</option>
                            <option v-for="(hotel, i) in hotels" :value="hotel.id" :key="i">
                                {{ hotel.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="time_difference">Travel Time ( e.g HH:MM ) <span class="text-danger ml-1">*</span></label>
                        <vue-mask
                            class="form-control"
                            v-model="postData.estimatedTime"
                            mask="00:00"
                            :raw="false"
                            :options="options">
                        </vue-mask>
                    </div>

                    <div class="col-md-12 d-flex align-items-center">
                        <div class="col-md-6">
                            <h5>Select Food/Deal</h5>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Food/Deal</th>
                                <th>Quantity</th>
                                <!-- <th>Amount</th> -->
                                <th>Seat No</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="index in loop" :key="index">
                                <td>
                                    <select class="form-control rounded-0" @change="saveRow($event,'first')">
                                        <option value="" selected>Select Food </option>
                                        <option v-for="(item, i) in items" :value="item.cid" :key="i">
                                            {{parseInt(item.price)}} | {{ item.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,'second')" />
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,'third')" />
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                    <button class="btn btn-outline-danger" v-if="index != 0" @click="removeRow($event)">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="orderBook" :disabled="loading" >{{loading ? 'Loading...' : 'Link' }}
                    </button>
                </template>
            </Add>

            <!-- <Edit
                heading="Edit"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Select Bus <span class="text-danger">*</span></label>
                        <select class="form-control rounded-0" v-model="edit.fleetId">
                            <option value="" selected>Select Bus</option>
                            <option v-for="(fleet, i) in fleets" :value="fleet.id" :key="i">
                                {{ fleet.bus_number }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="name">Current Reading (km) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" min="0" v-model="edit.currentReading"/>
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

                            <tr v-for="index in edit.loop" :key="index" v-if="edit.fleetDetails.maintenance_part_link">
                                <td>
                                    <select class="form-control rounded-0" @change="editSaveRow($event,'rowPart')"
                                    :value="edit.fleetDetails.maintenance_part_link[index - 1] ? edit.fleetDetails.maintenance_part_link[index - 1].part_id : '' " >
                                        <option value="" selected>Select Part </option>
                                        <option v-for="(part, i) in parts" :value="part.id" :key="i">
                                            {{ part.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>

                                    <input type="number" class="form-control" min="0" @keyup="editSaveRow($event,'rowAfter')"
                                    :value="edit.fleetDetails.maintenance_part_link[index - 1] ? edit.fleetDetails.maintenance_part_link[index - 1].maintenance_after : ''" />
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="editSaveRow($event,'rowLast')"
                                    :value="edit.fleetDetails.maintenance_part_link[index - 1] ? edit.fleetDetails.maintenance_part_link[index - 1].maintenance_at : '' " />
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="editAddRow">Add</button>
                                    <button class="btn btn-outline-danger" @click="editRemoveRow($event)">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateLinkMaintenance" :disabled="loading" >{{loading ? 'Loading...' : 'Link' }}
                    </button>
                </template>
            </Edit> -->

            <!--            Details Model-->
            <!-- <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
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
                                        <td> {{ single.maintenance_after }} (km)</td>
                                        <td> {{ single.maintenance_at }} (km)</td>
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
            </div> -->



        </div>
    </section>
</template>

<script>
import Add from "../../../components/Add.vue";
// import Edit from "../../../components/Edit.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "RoutePage",
    components: {
        Add,
        // Edit,
        vueMask,
    },
    data() {
        return {
            loading : false,
            validationErrors: [],
            buses: [],
            busId: "",
            hotels: [],
            items: [],
            schedule: "",
            postData : {
                ticketClosingId: "",
                estimatedTime: "",
                hotelId: "",
                item: [],
                quantity: [],
                // amount: [],
                seat: [],
            },
            options: {
                placeholder: 'HH:MM',
            },
            // mainData: [],
            // currentReading: "",
            // fleetPart: [],
            // maintenanceAfter: [],
            // maintenanceAt: [],
            // fleetDetails: [],
            // edit: {
            //     fleetDetails: [],
            //     fleetId: "",
            //     currentReading: "",
            //     fleetPart: [],
            //     maintenanceAfter: [],
            //     maintenanceAt: [],
            //     loop: 1,
            // },
            formID: "food_order",
            // editFormID: "editLinking_form",
            loop: 1,
        };
    },
    created() {
        $('.modal').remove();
        this.fetchData();
    },
    methods: {
        clearForm: function () {
          this.data = {};
          this.reverseRoute = 1;
        },
        saveRow(event,fieldName) {

            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if(fieldName == "first")
            {
                this.postData.item[getRowNumber-1] = event.target.value;
            }
            if(fieldName == "second")
            {
                this.postData.quantity[getRowNumber-1] = event.target.value;
            }
            // if(fieldName == "fourth")
            // {
            //     this.postData.amount[getRowNumber-1] = event.target.value;
            // }
            if(fieldName == "third")
            {
                this.postData.seat[getRowNumber-1] = event.target.value;
            }
        },
        // editSaveRow(event,fieldName) {

        //     const getRowNumber = event.target.parentElement.parentElement.rowIndex;

        //     if(fieldName == "rowPart")
        //     {
        //         this.edit.fleetPart[getRowNumber-1] = event.target.value;
        //     }
        //     if(fieldName == "rowAfter")
        //     {
        //         this.edit.maintenanceAfter[getRowNumber-1] = event.target.value;
        //     }
        //     if(fieldName == "rowLast")
        //     {
        //         this.edit.maintenanceAt[getRowNumber-1] = event.target.value;
        //     }
        // },
        async getSchedule(id) {

            const schedule = await this.callApi("post", "buses/single/schedule/latest", {
                id: id
            });
            if (schedule.status === 200 && schedule.data) {
                this.schedule = schedule.data.schedule_date + " " + schedule.data.schedule_time;
                this.postData.ticketClosingId = schedule.data.id;
            }
        },
        async getFoods(id) {

            this.items = [];
            const items = await this.callApi("post", "refreshments/hotels/specific/foods/items", {
                id: id
            });
            if (items.status === 200) {
                this.items = items.data;
            }
        },
        async orderBook() {

            // validation for empty data
            if(!this.postData.ticketClosingId || !this.postData.hotelId || !this.postData.estimatedTime ||
                this.postData.item.length == 0 || this.postData.quantity.length == 0 || this.postData.seat.length == 0)
            {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for(var i = 0; i < this.postData.item.length; i++)
            {
                if(!this.postData.item[i] || !this.postData.quantity[i] || !this.postData.seat[i])
                {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }
            }


            this.loading = true;
            const res = await this.callApi("post", "refreshments/hotels/orders/book", this.postData);
            if (res.status === 200) {
                this.loading = false;
                $('#order_table').DataTable().destroy();
                this.postData.item = [];
                this.postData.quantity = [];
                this.postData.seat = [];
                this.loop = 0;
               swal({
                    title: "Success",
                    text: "Order Added",
                    icon: "success",
                    timer: 2000
                });
                setTimeout(() => {
                    this.loop = 1;
                }, 2000);
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
                    timer: 2000
                        });

                    }
                }
            }
        },
        // async updateLinkMaintenance() {

        //     // validation for empty data
        //     if(!this.edit.fleetId || !this.edit.currentReading || this.edit.fleetPart.length == 0 ||
        //         this.edit.maintenanceAfter.length == 0 || this.edit.maintenanceAt.length == 0)
        //     {
        //         return swal({
        //             title: "Error",
        //             text: "Please Fill All Field",
        //             icon: "error",
        //             timer: 2000
        //         });
        //     }

        //     // check if any index is empty or null in object
        //     for(var i = 0; i < this.edit.fleetPart.length; i++)
        //     {
        //         if(!this.edit.fleetPart[i] || !this.edit.maintenanceAfter[i] || !this.edit.maintenanceAt[i])
        //         {
        //             return swal({
        //                 title: "Error",
        //                 text: "Please Fill All Field Or Remove Extra",
        //                 icon: "error",
        //                 timer: 2000
        //             });
        //         }
        //     }

        //     // post data
        //     const data = {
        //         fleetId: this.edit.fleetId,
        //         currentReading: this.edit.currentReading,
        //         fleetPart: this.edit.fleetPart,
        //         maintenanceAfter: this.edit.maintenanceAfter,
        //         maintenanceAt: this.edit.maintenanceAt,
        //     }
        //     this.loading = true;
        //     const res = await this.callApi("post", "fleet/part/link/update", data);
        //     if (res.status === 200) {
        //         this.loading = false;
        //         $('#order_table').DataTable().destroy();
        //         this.edit.fleetId = "";
        //         this.edit.currentReading = "";
        //         this.edit.loop = 0;
        //         this.edit.fleetPart =  [];
        //         this.edit.maintenanceAfter =  [];
        //         this.edit.maintenanceAt =  [];
        //        swal({
        //             title: "Success",
        //             text: "Maintenance Added",
        //             icon: "success",
        //             timer: 2000
        //         });
        //         await this.fetchData();
        //         this.loading = false;
        //     }
        //     else {
        //         this.loading = false;
        //         if (res.status == 422) {
        //             let errorContent = "";
        //             let count = 0;
        //             for (const key in res.data.errors) {
        //                 res.data.errors[key].forEach((element) => {
        //                     errorContent += (
        //                         (++count) + " - " + //creating serial no.
        //                         element + // main error
        //                         "\n" // creating new line
        //                     );
        //                 });
        //                 swal({
        //                     title: "Error",
        //                     text: errorContent,
        //                     icon: "error",
        //                     timer: 2000
        //                 });

        //             }
        //         }
        //     }
        // },
        addRow() {
            this.loop++;
        },
        removeRow(event) {
            // Array.from(element.parentNode.children).indexOf(element)
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.postData.item.splice((getRowNumber-1), 1);
            this.postData.quantity.splice((getRowNumber-1), 1);
            // this.postData.amount.splice((getRowNumber-1), 1);
            this.postData.seat.splice((getRowNumber-1), 1);
            event.target.parentElement.parentElement.remove();
            // this.loop--;
        },
        // editAddRow() {
        //     this.edit.loop++;
        // },
        // editRemoveRow(event) {
        //     const getRowNumber = event.target.parentElement.parentElement.rowIndex;
        //     this.edit.fleetPart.splice((getRowNumber-1), 1);
        //     this.edit.maintenanceAfter.splice((getRowNumber-1), 1);
        //     this.edit.maintenanceAt.splice((getRowNumber-1), 1);
        //     event.target.parentElement.parentElement.remove();
        // },
        async fetchData() {
            const fleetRes = await this.callApi("post", "refreshments/hotels/orders/food");
            if (fleetRes.status === 200) {

                this.mainData = fleetRes.data.mainData;
                this.buses = fleetRes.data.busDrop;
                this.hotels = fleetRes.data.hotelDrop;
            }

            setTimeout(() => {
                $('#order_table').DataTable();
            }, 300);
        },
        // async fetchFleetDetails(id) {

        //     const fleetDetailRes = await this.callApi("post", "fleet/single/part/link", {
        //         id: id
        //     });
        //     if (fleetDetailRes.status === 200) {
        //         this.fleetDetails = fleetDetailRes.data;
        //         this.edit.loop = fleetDetailRes.data.maintenance_part_link.length;
        //         this.edit.fleetId = fleetDetailRes.data.id;
        //         this.edit.currentReading = fleetDetailRes.data.current_reading;
        //     }
        // },
        // async editFleetDetails(id) {

        //     const fleetDetailRes = await this.callApi("post", "fleet/single/part/link", {
        //         id: id
        //     });
        //     if (fleetDetailRes.status === 200) {

        //         // Array Empty
        //         this.edit.loop = 0;
        //         this.edit.fleetPart = [];
        //         this.edit.maintenanceAfter = [];
        //         this.edit.maintenanceAt = [];

        //         this.edit.loop = fleetDetailRes.data.maintenance_part_link.length;
        //         this.edit.fleetDetails = fleetDetailRes.data;
        //         this.edit.fleetId = fleetDetailRes.data.id;
        //         this.edit.currentReading = fleetDetailRes.data.current_reading;

        //         for(var i = 0; i < fleetDetailRes.data.maintenance_part_link.length; i++)
        //         {
        //             this.edit.fleetPart.push(fleetDetailRes.data.maintenance_part_link[i].part_id);
        //             this.edit.maintenanceAfter.push(fleetDetailRes.data.maintenance_part_link[i].maintenance_after);
        //             this.edit.maintenanceAt.push(fleetDetailRes.data.maintenance_part_link[i].maintenance_at);
        //         }

        //     }
        // },
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
