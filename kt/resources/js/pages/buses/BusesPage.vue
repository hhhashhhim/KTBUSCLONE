<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h4>Buses</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-success"
                                    @click="getFareClass()"
                                >
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
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="edit_loc"
                                                >
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
                                                    <tr v-for="(bus, i) in buses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ bus.bus_number }}</td>
                                                        <td>{{ bus.chassis_number }}</td>
                                                        <td>{{ bus.insurance_number }}</td>
                                                        <td>{{ bus.no_of_seats }}</td>
                                                        <td>{{ bus.route_permit_number }}</td>
                                                        <td v-if="bus.added_by">{{ bus.added_by }}</td>
                                                        <td v-else>N/A</td>
                                                        <td v-if="bus.updated_by">
                                                            {{ bus.updated_by }}
                                                        </td>
                                                        <td v-else>N/A</td>
                                                        <td>
                                                            <a
                                                                href="#edit-modal"
                                                                data-toggle="modal"
                                                                @click="edit(bus)"
                                                                class="btn btn-warning mx-1"
                                                            >
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                            <a
                                                                href="#delete-modal"
                                                                data-toggle="modal"
                                                                @click="deleteModal(bus, i)"
                                                                class="btn btn-danger"
                                                            >
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
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Bus Name"
                            v-model="data.busNumber"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Chassis Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Chasis Number"
                            v-model="data.chassisNumber"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Insurance Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Insurance Number"
                            v-model="data.insuranceNumber"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">No. of Seats</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Seats"
                            v-model="data.noOfSeats"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Route Permit Number</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Route Permit Number"
                            v-model="data.routePermit"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="city_id">Fare Classes</label>
                        <select class="form-control" v-model="data.fare_class">
                            <option value="">Select Fare Class</option>
                            <option
                                v-for="(fareClass, i) in fareClasses"
                                :key="i"
                                :value="fareClass.id"
                            >
                                {{ fareClass.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">No. of Rows</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Rows"
                            v-model="data.noOfRows"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-4 my-4 pt-2">
                        <button
                            type="button"
                            class="btn btn-block btn-warning"
                            @click="generateMap"
                        >
                            Generate Seat Map
                        </button>
                    </div>
                </div>
                <div class="row mx-3 mainRow" v-if="isShowDiv">
                    <div class="form-group col-md-5 border colLeft mx-1 py-3">
                            <ul class="multiple_columns" style=" list-style: none;">
                                <span id="innerSpan" v-html="someArray"></span>
                            </ul>
                    </div>
                    <div class="form-group col-md-5 border mx-1 py-3">
                        <!-- <tr v-for="(record, rowIndex) in parseInt(data.noOfRows)" :key="rowIndex">
                                        <td class="m-5" v-for="(col, colIndex) in parseInt(5)" :key="colIndex">

                                        </td>
                                    </tr> -->
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <button
                            type="button"
                            class="btn btn-block btn-success"
                            @click="addBuses"
                        >
                            Add Bus
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
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Enter City Name"
                        v-model="dataEdit.name"
                    />
                </div>
                <div class="form-group">
                    <button
                        type="button"
                        class="btn btn-block btn-success"
                        @click="update"
                    >
                        Update City
                    </button>
                </div>
            </Edit>
            <!-- Add Modal -->
            <Delete
                confirmationMessage="Are You Sure You want To Delete This City ???"
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
            validationErrors: "",
            records: "",
            columns: "",
            details: "",
            formID: "newBuses",
            isShowDiv: false,
            someArray: "",
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
        };
    },
    async created() {
        const res = await this.callApi("post", "/buses");

        if (res.status === 200) {
            this.buses = res.data;
        } else {
            console.log(res);
        }
    },

    methods: {
        getBoxIndex: function (rIndex) {
            alert(rIndex);
        },
        getFareClass: async function () {
            const resFareClass = await this.callApi("post", "/fare-class");
            if (resFareClass.status === 200) {
                this.fareClasses = resFareClass.data;
            } else {
                console.log(res);
            }
        },

        isNumber: function (evt) {
            evt = evt ? evt : window.event;
            var charCode = evt.which ? evt.which : evt.keyCode;
            if (
                charCode > 31 &&
                (charCode < 48 || charCode > 57) &&
                charCode !== 46
            ) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
        generateMap: function () {
            this.validationErrors = [];
            let vm = this;
            if (vm.data.noOfRows === "0") {
                return this.errorsArray("Please Enter No. of Rows", "No.of Rows");
            } else {
                const count = this.data.noOfRows * 5;
                let someArray = "";
                //   <a @click="getBoxIndex(rowIndex, colIndex)"><span id="counterBox" v-html="someArray"></span><img
                //                             class="p-1"
                //                             :src="$store.state.app_url+'assets/img/buses/available_seat_img.gif'" alt=""></a>
                for (let i = 1; i <= count; i++) {
                    if (i % 5 === 0) {
                        someArray += `<li class=" innerLi p-0 m-0">
                                                    <a onclick="getBoxIndex(${i})" class="innerAnchor" >
                                                        <span class="counterBox"> ${i} </span>
                                                        <img class="p-1 m-0" src="http://127.0.0.1:8000/assets/img/buses/available_seat_img.gif"  alt=""/>
                                                    </a>
                                                </li>`;
                    } else {
                        someArray += `<li class=" innerLi p-0 m-0">
                                                    <a  onclick="getBoxIndex(${i})" class="innerAnchor">
                                                        <span class="counterBox"> ${i} </span>
                                                        <img class="p-1 m-0" src="http://127.0.0.1:8000/assets/img/buses/available_seat_img.gif"  alt=""/>
                                                    </a>
                                                </li>`;
                    }
                }
                this.someArray = someArray;
                this.isShowDiv = true;
            }
        },

        async addBuses() {
            this.validationErrors = [];
            if (this.data.busNumber === "")
                return this.errorsArray("Bus Number is Required", "busNumber");
            if (this.data.chassisNumber === "")
                return this.errorsArray("Chassis Number is Required", "chassisNumber");
            if (this.data.insuranceNumber === "")
                return this.errorsArray(
                    "Insurance Number is Required",
                    "insuranceNumber"
                );
            if (this.data.noOfSeats === "")
                return this.errorsArray("No. Of Seats is Required", "noOfSeats");
            if (this.data.routePermit === "")
                return this.errorsArray("Route Permit is Required", "routePermit");
            if (this.data.noOfRows === "0")
                return this.errorsArray("Bus Seats Rows is Required", "noOfRows");
            if (this.data.fare_class === "")
                return this.errorsArray("PLease Select Fare Class", "fare_class");
            const res = await this.callApi("post", "/buses/store", this.data);
            if (res.status === 201) {
                this.success = "Bus Created Successfully";
                setTimeout(() => {
                    window.location.reload();
                    this.isShowDiv = false;
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
        edit(city) {
            this.dataEdit = city;
        },
        async update() {
            this.validationErrors = [];
            if (this.dataEdit.name === "")
                return this.errorsArray("City Name is Required", "Name");
            const res = await this.callApi("post", "/city/update", this.dataEdit);
            if (res.status === 200) {
                this.success = "City Updated Successfully";
                const res = await this.callApi("post", "/city");
                if (res.status === 200) {
                    this.cities = res.data;
                }
                this.dataEdit.name = this.dataEdit.company_id = "";
                setTimeout(() => {
                    this.success = "";
                }, 3000);
            } else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
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
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1);
            }
        },
    },
};
</script>
<style scoped>
/*.mainRow .colLeft .multiple_columns #innerSpan .innerLi:hover {*/
/*    cursor: pointer;*/
/*    background-color: yellow;*/
/*}*/

ul.multiple_columns, span#innerSpan li.innerLi  a.innerAnchor img:hover {
    cursor: pointer;
    background-color: yellow;
}
</style>
