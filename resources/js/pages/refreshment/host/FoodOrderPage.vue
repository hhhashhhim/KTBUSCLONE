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
                                    v-if="checkForSubmenuButtons('add-order')"
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
                                                    class="table table-striped table-hover text-capitalize"

                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Order</th>
                                                        <th>Quantity</th>
                                                        <th>Amount</th>
                                                        <th>Seat No</th>
                                                        <th>Hotel</th>
                                                        <th>Bus No</th>
                                                        <th>Status</th>
                                                       <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <template v-for="(data, i) in mainData" :key="i">
                                                        <tr v-for="(order, j) in data" :key="j">

                                                            <td v-if="order.item_type==1" class="font-weight-bold">{{ order.food_record.name}}</td>
                                                            <td v-else-if="order.item_type==2" class="font-weight-bold">
                                                                {{ order.food_record.name }}
                                                                <div class="d-flex" v-for="(dealFood, k) in order.food_record.deal_details" :key="k">
                                                                    <p class="mb-0">{{ dealFood.food.name }} :</p>
                                                                    <p class="mb-0">{{ dealFood.quantity + " (" + dealFood.food.unit + ")"}}</p>
                                                                </div>
                                                            </td>

                                                            <td v-if="order.item_type==1">{{  order.quantity + " (" + order.food_record.unit + ")" }}</td>
                                                            <td v-else-if="order.item_type==2">{{ order.quantity }} (deal)</td>

                                                            <td>{{ order.amount }}</td>
                                                            <td>{{ order.seat_no }}</td>
                                                            <td>{{ order.hotel.name }}</td>
                                                            <td>{{ order.bus.bus_number }}</td>

                                                            <td>
                                                                <span v-if="order.status=='pending'" class="badge badge-warning">{{ order.status }}</span>
                                                                <span v-else-if="order.status=='received'" class="badge badge-success">{{ order.status }}</span>
                                                                <span v-else-if="order.status=='ready'" class="badge badge-danger">{{ order.status }}</span>
                                                                <span v-else-if="order.status=='delivered'" class="badge badge-info">{{ order.status }}</span>
<!--                                                            <td>N/A</td>-->
                                                            </td>
                                                            <td>
                                                                <button
                                                                    v-if="order.status!='received' && checkForSubmenuButtons('received-order')"
                                                                    @click="received(order.id)" class="btn btn-success mx-1 btn-sm"
                                                                    title="received Order">
                                                                    Received
                                                                </button>
                                                                <button
                                                                    v-if="order.status!='ready' && checkForSubmenuButtons('ready-order')"
                                                                    @click="ready(order.id)" class="btn btn-danger mx-1 btn-sm"
                                                                    title="ready Order">
                                                                    Ready
                                                                </button>
                                                                <button
                                                                    v-if="order.status!='delivered' && checkForSubmenuButtons('delivered-order')"
                                                                    @click="delivered(order.id)" class="btn btn-info mx-1 btn-sm"
                                                                    title="delivered Order">
                                                                    Delivered
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="border-bottom border-success" colspan="8" style="height:0 !important; "></td>
                                                        </tr>
                                                    </template>
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
                        <select class="form-control rounded-0" v-model="busId" @change="getSchedule(busId)" disabled>
                            <option value="" selected>Not Assigned</option>
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

                    <div class="col-md-12 d-flex align-items-center" v-if="postData.hotelId">
                        <div class="col-md-6 px-0 mb-1">
                            <!-- <h5 class="mb-0 mx-2">Select Food/Deal</h5> -->
                            <button data-target="#showDetails" data-toggle="modal"
                                    @click="hotelItems(postData.hotelId)"
                                    class="btn btn btn-primary m-1">
                                    Show Food/Deal
                            </button>
                        </div>
                    </div>
                    <div class="form-group col-md-12 d-flex align-items-center">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Food/Deal</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Seat No</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(i,index) in loop" :key="index">
                                <td>
                                    <!-- {{ items[0] ? items[0].price : '' }} -->
                                    <select class="form-control rounded-0" @change="saveRow($event,'first',index)" :value="postData.item[index]">
                                        <option price="0" value="" selected>Select Food </option>
                                        <option :price="item.price" v-for="(item, i) in items" :value="item.cid" :key="i" >
                                            {{ parseInt(item.price)}} | {{ item.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,'second',index)" :value="postData.quantity[index]" />
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" readonly :value="postData.quantity[index] * postData.itemPrice[index]" />
                                </td>
                                <td>
                                    <input type="number" class="form-control" min="0" @keyup="saveRow($event,'third',index)" :value="postData.seat[index]"/>
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                    <button class="btn btn-outline-danger" v-if="index != 0" @click="removeRow($event,index)">Remove</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="orderBook" :disabled="loading" >{{loading ? 'Loading...' : 'Order' }}
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
            <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Detail</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Food/Deal</th>
                                        <th>Price</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(food, i) in hotelFoods" :key="i" class="border-bottom">
                                            <td class="font-weight-bold"> {{ food.name }}</td>
                                            <td> {{ food.price }}</td>
                                        </tr>
                                        <tr v-for="(deal, i) in hotelDeals" :key="i" class="border-bottom">
                                            <td class="font-weight-bold"> {{ deal.name }}
                                            <div class="d-flex font-weight-normal" v-for="(dealFood, k) in deal.deal_details" :key="k">
                                                <p class="mb-0">{{ dealFood.food.name }} :</p>
                                                <p class="mb-0">{{ dealFood.quantity + " (" + dealFood.food.unit + ")"}}</p>
                                            </div>
                                            </td>
                                            <td> {{ deal.price }} </td>
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
            permissions: [],
            mainData: [],
            buses: [],
            busId: "",
            hotels: [],
            items: [],
            hotelFoods: [],
            hotelDeals: [],
            schedule: "",
            postData : {
                ticketClosingId: "",
                estimatedTime: "",
                hotelId: "",
                item: [],
                quantity: [],
                itemPrice: [],
                // amount: [],
                seat: [],
            },
            options: {
                placeholder: 'HH:MM',
            },
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
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        clearForm: function () {
          this.data = {};
          this.reverseRoute = 1;
          this.getSchedule(this.busId);
        },
        saveRow(event,fieldName,index) {
            // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if(fieldName == "first")
            {
                const price = event.target.options[event.target.options.selectedIndex].getAttribute('price');
                this.postData.item[index] = event.target.value;
                this.postData.itemPrice[index] = price;
            }
            if(fieldName == "second")
            {
                this.postData.quantity[index] = event.target.value;
            }
            // if(fieldName == "fourth")
            // {
            //     this.postData.amount[index] = event.target.value;
            // }
            if(fieldName == "third")
            {
                this.postData.seat[index] = event.target.value;
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
                this.schedule = schedule.data.schedule_date ? schedule.data.schedule_date + " " + schedule.data.schedule_time : 'Not Assigned';
                this.postData.ticketClosingId = schedule.data.id;
            }
        },
        async received(id) {

            const received = await this.callApi("post", "refreshments/hotels/orders/received", {
                id: id
            });
            if (received.status === 200 && received.data) {
               this.fetchData();
                swal({
                    title: "Success",
                    text: "Order Received",
                    icon: "success",
                    timer: 2000
                });
            }
        },
        async ready(id) {

            const ready = await this.callApi("post", "refreshments/hotels/orders/ready", {
                id: id
            });
            if (ready.status === 200 && ready.data) {
               this.fetchData();
                swal({
                    title: "Success",
                    text: "Order Ready",
                    icon: "success",
                    timer: 2000
                });
            }
        },
        async delivered(id) {

            const delivered = await this.callApi("post", "refreshments/hotels/orders/delivered", {
                id: id
            });
            if (delivered.status === 200 && delivered.data) {
               this.fetchData();
                swal({
                    title: "Success",
                    text: "Order Delivered",
                    icon: "success",
                    timer: 2000
                });
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
        async hotelItems(id)
        {
            this.hotelFoods = [];
            const items = await this.callApi("post", "refreshments/hotels/items", {
                id: id
            });
            if (items.status === 200) {
                this.hotelFoods = items.data.foods;
                this.hotelDeals = items.data.deals;
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
                $(".modal").click();
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
        removeRow(event,index) {
            // Array.from(element.parentNode.children).indexOf(element)
            // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.postData.item.splice(index, 1);
            this.postData.itemPrice.splice(index, 1);
            this.postData.quantity.splice(index, 1);
            // this.postData.amount.splice(index, 1);
            this.postData.seat.splice(index, 1);
            // event.target.parentElement.parentElement.remove();
            this.loop--;
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
                this.busId = fleetRes.data.hostData ? fleetRes.data.hostData.bus_id : '';
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
