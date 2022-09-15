<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" class="btn btn-success">
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
                                                        <th>Name</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(city,i) in cities" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ city.name }}</td>
                                                        <td>
                                                            <a href="#edit-modal" data-toggle="modal"
                                                               @click="edit(city)" class="btn btn-warning mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a href="#delete-modal" data-toggle="modal"
                                                               @click="deleteModal(city,i)" class="btn btn-danger">
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
                        <label for="name">Chasis Number</label>
                        <input type="text" class="form-control" placeholder="Enter Chasis Number"
                               v-model="data.chasisNumber" @keypress="isNumber($event)">
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
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">No. of Rows</label>
                        <input type="text" class="form-control" placeholder="Enter No. of Rows"
                               v-model="data.noOfRows" @keypress="isNumber($event)">
                    </div>
                    <!--                    <div class="form-group col-md-3">-->
                    <!--                        <label for="name">No. of Columns</label>-->
                    <!--                        <input type="text" class="form-control" placeholder="Enter No. of Columns"-->
                    <!--                               v-model="data.noOfColumns" @keypress="isNumber($event)">-->
                    <!--                    </div>-->
                    <div class="form-group col-md-4 my-4">
                        <button type="button" class="btn btn-block btn-warning" @click="generateMap">Generate Seat Map
                        </button>

                    </div>
                    <div v-if="isShowDiv" class="col-md-6 border py-5 mb-5">
                        <label class="text-dark " for="">Create Here</label>
                        <div id="seat-map">
                            <div class="front-indicator">Front</div>
                        </div>
<!--                        <tr class="my-5 py-5" v-for="(record, indexRecord) in parseInt(data.noOfRows)" :key="record">-->
<!--                            <td v-for="(col, index) in parseInt(5)" :key="col">-->
<!--                                <p>{{ index + 1 }}</p>-->
<!--                                <img alt="image" src="/assets/img/buses/available_seat_img.gif"-->
<!--                                     class="mr-3 user-img-radious-style user-list-img">-->
<!--                            </td>-->
<!--                        </tr>-->
                        <!--                    </div>-->
                        <!--                    <div v-if="isShowDiv" class="col-md-6 border py-5 mb-5">-->
                        <!--                        <label class="text-dark " for="">Generated Map</label>-->
                        <!--                        <tr class="my-5 py-5" v-for="(record, indexRecord) in parseInt(data.noOfColumns)" :key="record">-->
                        <!--                            <td v-for="(col, index) in parseInt(data.noOfRows)" :key="col">-->
                        <!--                                <p>{{ indexRecord+1 }}</p>-->
                        <!--                                <img alt="image" src="http://127.0.0.1:8000/assets/img/buses/available_seat_img.gif" class="mr-3 user-img-radious-style user-list-img">-->
                        <!--                            </td>-->
                        <!--                        </tr>-->
                    </div>
                    <div class="form-group col-md-12">
                        <button type="button" class="btn btn-block btn-success" @click="addBuses">Add Bus</button>
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
            cities: [],
            validationErrors: '',
            records: '',
            columns: '',
            details: '',
            formID: 'newBuses',
            isShowDiv: false,
            data: {
                busNumber: "",
                chasisNumber: "",
                insuranceNumber: "",
                noOfSeats: "",
                routePermit: "",
                noOfColumns: "0",
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
            this.cities = res.data
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
        generateMap: function () {
            this.validationErrors = []
            let vm = this;
            if (vm.data.noOfRows === "0") {
                return this.errorsArray("Please Enter No. of Rows", "No.of Rows");
            } else {
                this.isShowDiv = true;

                var firstSeatLabel = 1;

                    var $cart = $('#selected-seats'),
                        $counter = $('#counter'),
                        $total = $('#total'),
                        sc = $('#seat-map').seatCharts({
                            map: [
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
                            ],
                            seats: {
                                f: {
                                    price   : 100,
                                    classes : 'first-class', //your custom CSS class
                                    category: 'First Class'
                                },
                                e: {
                                    price   : 40,
                                    classes : 'economy-class', //your custom CSS class
                                    category: 'Economy Class'
                                }

                            },
                            naming : {
                                top : false,
                                getLabel : function (character, row, column) {
                                    return firstSeatLabel++;
                                },
                            },
                            legend : {
                                node : $('#legend'),
                                items : [
                                    [ 'f', 'available',   'First Class' ],
                                    [ 'e', 'available',   'Economy Class'],
                                    [ 'f', 'unavailable', 'Already Booked']
                                ]
                            },
                            click: function () {
                                if (this.status() == 'available') {
                                    //let's create a new <li> which we'll add to the cart items
                                    $('<li>'+this.data().category+' Seat # '+this.settings.label+': <b>$'+this.data().price+'</b> <a href="#" class="cancel-cart-item">[cancel]</a></li>')
                                        .attr('id', 'cart-item-'+this.settings.id)
                                        .data('seatId', this.settings.id)
                                        .appendTo($cart);

                                    /*
                                     * Lets update the counter and total
                                     *
                                     * .find function will not find the current seat, because it will change its stauts only after return
                                     * 'selected'. This is why we have to add 1 to the length and the current seat price to the total.
                                     */
                                    $counter.text(sc.find('selected').length+1);
                                    $total.text(recalculateTotal(sc)+this.data().price);

                                    return 'selected';
                                } else if (this.status() == 'selected') {
                                    //update the counter
                                    $counter.text(sc.find('selected').length-1);
                                    //and total
                                    $total.text(recalculateTotal(sc)-this.data().price);

                                    //remove the item from our cart
                                    $('#cart-item-'+this.settings.id).remove();

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
            if (this.data.name === "") return this.errorsArray("City Name is Required", "Name");
            const res = await this.callApi("post", '/city/store', this.data);
            if (res.status === 201) {
                this.success = "City Created Successfully";
                this.cities.unshift(res.data);
                this.data.name = "";
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
    margin-top:150px;
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
