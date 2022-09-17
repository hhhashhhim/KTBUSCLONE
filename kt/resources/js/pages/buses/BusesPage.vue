<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" class="btn btn-success"  @click="getFareClass()">
                                    Add New Bus
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
                                                <table class="table table-striped table-hover" id="edit_loc">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Bus Number</th>
                                                        <th>Chassis Number</th>
                                                        <th>Insurance Number</th>
                                                        <th>No. of Seats</th>
                                                        <th>Route Permit</th>
                                                        <th>Added By</th>
                                                        <th>Updated By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(bus,i) in buses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ bus.bus_number }}</td>
                                                        <td>{{ bus.chassis_number }}</td>
                                                        <td>{{ bus.insurance_number }}</td>
                                                        <td>{{ bus.no_of_seats }}</td>
                                                        <td>{{ bus.route_permit_number }}</td>
                                                        <td v-if="bus.added_by">{{ bus.added_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="bus.updated_by">{{ bus.updated_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(bus)" class="btn btn-warning mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a href="#delete-modal" data-toggle="modal"
                                                               @click="deleteModal(bus,i)" class="btn btn-danger">
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
                heading="New Bus"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Bus Number</label>
                        <input type="text" class="form-control" placeholder="Enter Bus Name" v-model="data.busNumber">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Chassis Number</label>
                        <input type="text" class="form-control" placeholder="Enter Chasis Number"
                               v-model="data.chassisNumber" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Insurance Number</label>
                        <input type="text" class="form-control" placeholder="Enter Insurance Number"
                               v-model="data.insuranceNumber" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">No. of Seats</label>
                        <input type="text" class="form-control" placeholder="Enter No. of Seats"
                               v-model="data.noOfSeats" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Route Permit Number</label>
                        <input type="text" class="form-control" placeholder="Enter Route Permit Number"
                               v-model="data.routePermit" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="city_id">Fare Classes</label>
                        <select class="form-control" v-model="data.fare_class">
                            <option value="">Select Fare Class</option>
                            <option v-for="(fareClass,i) in fareClasses" :key="i" :value="fareClass.id"> {{ fareClass.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">No. of Rows</label>
                        <input type="text" class="form-control" placeholder="Enter No. of Rows"
                               v-model="data.noOfRows" @keypress="isNumber($event)">
                    </div>
                    <div class="form-group col-md-4 my-4">
                        <button type="button" class="btn btn-block btn-warning" @click="generateMap">Generate Seat Map
                        </button>

                    </div>
                    <div v-if="isShowDiv" class="col-md-6 border py-5 mb-5">
                        <div id="seat-map">
                            <label class="text-dark " for="">Create Here</label>
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <button type="button" class="btn btn-block btn-success" @click="addBuses">Add Bus
                        </button>
                    </div>
                </div>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit City"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" placeholder="Enter City Name" v-model="dataEdit.name">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-block btn-success" @click="update">Update City</button>
                </div>
            </Edit>
            <!-- Add Modal -->
            <Delete confirmationMessage="Are You Sure You want To Delete This City ???"/>
        </div>
    </section>
</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
import Delete from '../../components/Delete.vue';
import seatCharts from '/assets/js/buses/jquery.seat-charts';
import seatChartsMin from '/assets/js/buses/jquery.seat-charts.min';

import {mapGetters} from 'vuex';

export default {
    name: "buses",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            buses: [],
            fareClasses: [],
            validationErrors: '',
            records: '',
            columns: '',
            details: '',
            formID: 'newBuses',
            isShowDiv: false,
            data: {
                busNumber: "",
                fare_class: "",
                chassisNumber: "",
                insuranceNumber: "",
                noOfSeats: "",
                routePermit: "",
                noOfRows: "0",
            },
            dataEdit: {
                id: "",
                name: "",
            },
            delId: "",
            success: false,
            errors: false,
        }
    },
    async created() {

        const res = await this.callApi("post", '/buses');

        if (res.status === 200) {
            this.buses = res.data
        } else {
            console.log(res);
        }

    },
    methods: {

        getFareClass: async function () {
            const resFareClass = await this.callApi("post", '/fare-class');
            console.log(resFareClass);
            if (resFareClass.status === 200) {
                this.fareClasses = resFareClass.data
            } else {
                console.log(res);
            }
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
        generateMap: function () {
            this.validationErrors = []
            let vm = this;
            if (vm.data.noOfRows === "0") {
                return this.errorsArray("Please Enter No. of Rows", "No.of Rows");
            } else {
                this.isShowDiv = true;
                let customMap=[];
                for(let r = 0;r < parseInt(this.data.noOfRows);r++){
                    customMap[r] = 'eeeee';
                }
                var firstSeatLabel = 1;

                var $cart = $('#selected-seats'),
                    $counter = $('#counter'),
                    $total = $('#total'),
                    sc = $('#seat-map').seatCharts({
                        map: customMap/*[
                            'fefff',
                            'ff_ff',
                            'ee_ee',
                            'ee_ee',
                            'ee_ee',
                            'ee_ee',
                            'ee_ee',
                            'ee_ee',
                            'eeeee',
                            'eeeee',
                            'eeeee',
                            'eeeee',
                        ]*/,
                        seats: {
                            f: {
                                price: 100,
                                classes: 'first-class', //your custom CSS class
                                category: 'First Class'
                            },
                            e: {
                                price: 40,
                                classes: 'economy-class', //your custom CSS class
                                category: 'Economy Class'
                            }

                        },
                        naming: {
                            top: false,
                            getLabel: function (character, row, column) {
                                return firstSeatLabel++;
                            },
                        },
                        legend: {
                            node: $('#legend'),
                            items: [
                                // ['f', 'available', 'First Class'],
                                // ['e', 'available', 'Economy Class'],
                                // ['f', 'unavailable', 'Already Booked']
                            ]
                        },
                        click: function () {
                            if (this.status() === 'available') {
                                //let's create a new <li> which we'll add to the cart items
                                $('<li>' + this.data().category + ' Seat # ' + this.settings.label + ': <b>$' + this.data().price + '</b> <a href="#" class="cancel-cart-item">[cancel]</a></li>')
                                    .attr('id', 'cart-item-' + this.settings.id)
                                    .data('seatId', this.settings.id)
                                    .appendTo($cart);
                                $counter.text(sc.find('selected').length + 1);
                                $total.text(recalculateTotal(sc) + this.data().price);

                                return 'selected';
                            } else if (this.status() === 'selected') {
                                //update the counter
                                $counter.text(sc.find('selected').length - 1);
                                //and total
                                $total.text(recalculateTotal(sc) - this.data().price);

                                //remove the item from our cart
                                $('#cart-item-' + this.settings.id).remove();

                                //seat has been vacated
                                return 'available';
                            } else if (this.status() == 'unavailable') {
                                //seat has been already booked
                                return 'unavailable';
                            } else {
                                return this.style();
                            }
                        }
                    });

                //this will handle "[cancel]" link clicks
                $('#selected-seats').on('click', '.cancel-cart-item', function () {
                    //let's just trigger Click event on the appropriate seat, so we don't have to repeat the logic here
                    sc.get($(this).parents('li:first').data('seatId')).click();
                });

                //let's pretend some seats have already been booked
                sc.get(['1_2', '4_1', '7_1', '7_2']).status('unavailable');


                function recalculateTotal(sc) {
                    var total = 0;

                    //basically find every selected seat and sum its price
                    sc.find('selected').each(function () {
                        total += this.data().price;
                    });

                    return total;
                }

            }
        },

        async addBuses() {
            this.validationErrors = []
            if (this.data.busNumber === "") return this.errorsArray("Bus Number is Required", "busNumber");
            if (this.data.chassisNumber === "") return this.errorsArray("Chassis Number is Required", "chassisNumber");
            if (this.data.insuranceNumber === "") return this.errorsArray("Insurance Number is Required", "insuranceNumber");
            if (this.data.noOfSeats === "") return this.errorsArray("No. Of Seats is Required", "noOfSeats");
            if (this.data.routePermit === "") return this.errorsArray("Route Permit is Required", "routePermit");
            if (this.data.noOfRows === "0") return this.errorsArray("Bus Seats Rows is Required", "noOfRows");
            if (this.data.fare_class === "") return this.errorsArray("PLease Select Fare Class", "fare_class");
            const res = await this.callApi("post", '/buses/store', this.data);
            if (res.status === 201) {
                this.success = "Bus Created Successfully";
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                if (res.status === 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach(element => {
                            this.errorsArray(element, key)
                        });
                    }
                }
            }
        },
        edit(city) {
            this.dataEdit = city;
        },
        async update() {
            this.validationErrors = []
            if (this.dataEdit.name === "") return this.errorsArray("City Name is Required", "Name");
            const res = await this.callApi("post", '/city/update', this.dataEdit);
            if (res.status === 200) {
                this.success = "City Updated Successfully";
                const res = await this.callApi("post", '/city');
                if (res.status === 200) {
                    this.cities = res.data
                }
                this.dataEdit.name = this.dataEdit.company_id = "";
                setTimeout(() => {
                    this.success = ""
                }, 3000);
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach(element => {
                            this.errorsArray(element, key)
                        });
                    }
                }
            }
        },
        async deleteModal(city, i) {
            const deletingObj = {
                url: "/city/delete",
                data: city,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
            }
        }
    }
}
</script>
<style scoped>
@import 'http://www.jqueryscript.net/css/jquerysctipttop.css';
@import '/assets/css/buses/jquery.seat-charts.css';

.front-indicator {
    width: 145px;
    margin: 5px 32px 15px 32px;
    background-color: #f6f6f6;
    color: #adadad;
    text-align: center;
    padding: 3px;
    border-radius: 5px;
}

.wrapper {
    width: 100%;
    text-align: center;
    margin-top: 150px;
}

.container {
    margin: 0 auto;
    width: 500px;
    text-align: left;
}

.booking-details {
    float: left;
    text-align: left;
    margin-left: 35px;
    font-size: 12px;
    position: relative;
    height: 401px;
}

.booking-details h2 {
    margin: 25px 0 20px 0;
    font-size: 17px;
}

.booking-details h3 {
    margin: 5px 5px 0 0;
    font-size: 14px;
}

div.seatCharts-cell {
    color: #182C4E;
    height: 25px;
    width: 25px;
    line-height: 25px;

}

div.seatCharts-seat {
    color: #FFFFFF;
    cursor: pointer;
}

div.seatCharts-row {
    height: 35px;
}

div.seatCharts-seat.available {
    background-color: #B9DEA0;

}

div.seatCharts-seat.available.first-class {
    /* 	background: url(vip.png); */
    background-color: #3a78c3;
}

div.seatCharts-seat.focused {
    background-color: #76B474;
}

div.seatCharts-seat.selected {
    background-color: #E6CAC4;
}

div.seatCharts-seat.unavailable {
    background-color: #472B34;
}

div.seatCharts-container {
    border-right: 1px dotted #adadad;
    width: 200px;
    padding: 20px;
    float: left;
}

div.seatCharts-legend {
    padding-left: 0px;
    position: absolute;
    bottom: 16px;
}

ul.seatCharts-legendList {
    padding-left: 0px;
}

span.seatCharts-legendDescription {
    margin-left: 5px;
    line-height: 30px;
}

.checkout-button {
    display: block;
    margin: 10px 0;
    font-size: 14px;
}

#selected-seats {
    max-height: 90px;
    overflow-y: scroll;
    overflow-x: none;
    width: 170px;
}
</style>
