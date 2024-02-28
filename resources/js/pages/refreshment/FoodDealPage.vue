<template>
    <div>
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card card-primary p-4">
                            <h3>Hotel Name : {{ hotelData.name }}</h3>
                            <div class="row">
                                <div class="col-md-4 pt-4 border-right">
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Name</p>
                                        <p class="mb-2">{{ hotelData.user.name }}</p>
                                    </div>
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Balance</p>
                                        <p class="mb-2">{{ hotelData.balance }}</p>
                                    </div>
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Commisssion</p>
                                        <p class="mb-2">{{ hotelData.commission }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4 pt-4 border-right">
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Email</p>
                                        <p class="mb-2">{{ hotelData.user.email }}</p>
                                    </div>
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Contact</p>
                                        <p class="mb-2">{{ hotelData.contact }}</p>
                                    </div>
                                    <div class="d-flex">
                                        <p class="font-weight-bold w-50 mb-2">Location</p>
                                        <p class="mb-2">{{ hotelData.location }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div>
                                        <a :href="$store.state.app_url + 'uploads/refreshment/hotel/' + (hotelData.logo)"
                                            target="_blank">
                                            <img :src="$store.state.app_url + 'uploads/refreshment/hotel/' + (hotelData.logo)"
                                                style="width:180px;height:180px;" alt="">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Food Deals</h4>
                                <div class="card-header-action">
                                    <a v-if="checkForSubmenuButtons('deal-add-deal')" href="#" data-toggle="modal"
                                        :data-target="'#' + formID" class="btn btn-primary">
                                        Add Deal
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Table -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover text-capitalize"
                                                        id="deal_table">
                                                        <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Name</th>
                                                                <th>Price</th>
                                                                <th>Description</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(deal, i) in hotelData.deals" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>
                                                                    <h6 class="mb-0">{{ deal.name }}</h6>
                                                                    <div class="d-flex"
                                                                        v-for="(detail, i) in deal.deal_details" :key="i">
                                                                        <p class="mb-0">{{ detail.food.name }} :</p>
                                                                        <p class="mb-0">{{ detail.quantity }}
                                                                            ({{ detail.food.unit }})</p>
                                                                    </div>
                                                                </td>
                                                                <td>{{ deal.price }}</td>
                                                                <td>{{ deal.description ?? 'N/A' }}</td>
                                                                <td>
                                                                    <button v-if="checkForSubmenuButtons('deal-edit-deal')"
                                                                        :data-target="'#' + editFormID" data-toggle="modal"
                                                                        @click="edit(deal)" class="btn btn-primary mx-1"
                                                                        title="Edit Deal">
                                                                        <i class="far fa-edit"></i>
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
                <Add heading="Add Deal" :errors="this.validationErrors" :success="success" :formID="formID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Deal Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Deal Name" id="name"
                                v-model="postData.name" />
                        </div>

                        <div class="form-group col-md-6">
                            <label for="userName">Price <span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Price" id="userName"
                                v-model="postData.price" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Description</label>
                            <textarea class="form-control" placeholder="Enter Description" id="location"
                                v-model="postData.description" cols="30" rows="10"></textarea>
                        </div>

                        <div class="col-md-12 d-flex align-items-center">
                            <div class="col-md-12">
                                <h5>Select Food For Deal <small> (Duplicate food will be remove automatically)</small></h5>
                            </div>
                        </div>
                        <div class="form-group col-md-12 d-flex align-items-center">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Food</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="index in loop" :key="index">
                                        <td>
                                            <select class="form-control rounded-0" @change="saveRow($event, 'rowFood')">
                                                <option value="" selected>Select Food </option>
                                                <option v-for="(food, i) in allFoods" :value="food.id" :key="i">
                                                    {{ food.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" min="1"
                                                @keyup="saveRow($event, 'rowQty')" />
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                            <button class="btn btn-outline-danger" v-if="index != 1"
                                                @click="removeRow($event)">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                            {{ loading ? "Loading...." : "Add Deal" }}
                        </button>
                    </template>
                </Add>

                <!-- Add Modal -->
                <Edit heading="Edit Deal" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Deal Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Deal Name" id="name"
                                v-model="editData.name" />
                        </div>

                        <div class="form-group col-md-6">
                            <label for="userName">Price <span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Price" id="userName"
                                v-model="editData.price" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Description</label>
                            <textarea class="form-control" placeholder="Enter Description" id="location"
                                v-model="editData.description" cols="30" rows="10"></textarea>
                        </div>

                        <div class="col-md-12 d-flex align-items-center">
                            <div class="col-md-12">
                                <h5>Select Food For Deal <small> (Duplicate food will be remove autometically)</small></h5>
                            </div>
                        </div>
                        <div class="form-group col-md-12 d-flex align-items-center">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Food</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="index in editLoop" :key="index">
                                        <td>
                                            <select class="form-control rounded-0" @change="editSaveRow($event, 'rowFood')"
                                                :value="editData.foods[index - 1] ? editData.foods[index - 1] : ''">
                                                <option value="" selected>Select Part </option>
                                                <option v-for="(food, i) in allFoods" :value="food.id" :key="i">
                                                    {{ food.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" min="1"
                                                @keyup="editSaveRow($event, 'rowQty')"
                                                :value="editData.qtys[index - 1] ? editData.qtys[index - 1] : ''" />
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary mx-2" @click="editAddRow">Add</button>
                                            <button class="btn btn-outline-danger" v-if="index != 1"
                                                @click="editRemoveRow($event)">Remove </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                            {{ loading ? "Loading...." : "Update Deal" }}
                        </button>
                    </template>
                </Edit>
            </div>
        </section>
    </div>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import vueMask from 'vue-jquery-mask';

import { mapGetters } from "vuex";

export default {
    name: "HotelPage",
    components: {
        Add,
        Edit,
        vueMask,
    },
    data() {
        return {
            date: null,
            options: {
                placeholder: '0300-0000000',
                // http://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
            },
            formID: "newDeal",
            editFormID: 'edit_deal_form',
            loading: false,
            loop: 1,
            editLoop: 1,
            hotelId: "",
            allFoods: [],
            hotelData: [],
            permissions: [],
            postData: {
                hotelId: "",
                name: "",
                price: "",
                description: "",
                foods: [],
                qtys: [],
            },
            editData: {
                dealId: "",
                hotelId: "",
                name: "",
                price: "",
                description: "",
                foods: [],
                qtys: [],
            },
            success: false,
        };
    },
    async created() {
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        await this.setData();
        await this.fetchData();
        await this.fetchFoods();
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        async fetchData() {
            const data = {
                hotelId: this.hotelId
            }

            const hotelRes = await this.callApi("post", "refreshments/hotels/specific/foods/deals", data);
            if (hotelRes.status == 200) {
                this.hotelData = hotelRes.data;
                setTimeout(() => {
                    $("#deal_table").DataTable();
                }, 300);
            }
        },
        async fetchFoods() {
            const data = {
                hotelId: this.hotelId
            }

            const hotelRes = await this.callApi("post", "refreshments/hotels/specific/foods", data);
            if (hotelRes.status == 200) {
                this.allFoods = hotelRes.data.foods;
                setTimeout(() => {
                    $("#deal_table").DataTable();
                }, 300);
            }
        },
        async setData() {
            this.hotelId = localStorage.getItem("hotel-id");
            this.postData.hotelId = localStorage.getItem("hotel-id");
            this.editData.hotelId = localStorage.getItem("hotel-id");
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        async add() {

            // validation for empty data
            if (!this.postData.hotelId || !this.postData.name || !this.postData.price ||
                this.postData.foods == 0 || this.postData.qtys == 0) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for (var i = 0; i < this.postData.foods.length; i++) {
                if (!this.postData.foods[i] || !this.postData.qtys[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }
            }

            this.loading = true;

            const res = await this.callApi("post", "refreshments/hotels/specific/foods/deals/store", this.postData);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false
                $("#deal_table").DataTable().destroy();
                this.success = "Deal Added Successfully";

                this.postData.name = "";
                this.postData.price = "";
                this.postData.description = "";
                this.postData.foods = [];
                this.postData.qtys = [];
                this.loop = 0;

                await this.fetchData();
                window.scrollTo(0, 0);
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                    this.loop = 1;
                }, 2000);
            } else {
                if (res.status == 422) {
                    this.loading = false
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        async edit(deal) {
            this.editData.dealId = "";
            this.editData.hotelId = "";
            this.editData.name = "";
            this.editData.price = "";
            this.editData.description = "";
            this.editData.foods = [];
            this.editData.qtys = [];
            if (deal) {
                this.editLoop = deal.deal_details.length;
                this.editData.dealId = deal.id;
                this.editData.hotelId = deal.hotel_id;
                this.editData.name = deal.name;
                this.editData.price = deal.price;
                this.editData.description = deal.description;

                for (var i = 0; i < deal.deal_details.length; i++) {
                    this.editData.foods.push(deal.deal_details[i].food_id);
                    this.editData.qtys.push(deal.deal_details[i].quantity);
                }

            } else {
                return alert("Something Went Wrong !!!");
            }
        },
        async update() {

            // validation for empty data
            if (!this.editData.dealId || !this.editData.name || !this.editData.price ||
                this.editData.foods == 0 || this.editData.qtys == 0) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            // check if any index is empty or null in object
            for (var i = 0; i < this.editData.foods.length; i++) {
                if (!this.editData.foods[i] || !this.editData.qtys[i]) {
                    return swal({
                        title: "Error",
                        text: "Please Fill All Field Or Remove Extra",
                        icon: "error",
                        timer: 2000
                    });
                }
            }

            this.loading = true;

            const res = await this.callApi("post", "refreshments/hotels/specific/foods/deals/update", this.editData);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false
                $("#deal_table").DataTable().destroy();
                this.success = "Deal Updated Successfully";

                this.editData.dealId = "";
                this.editData.hotelId = "";
                this.editData.name = "";
                this.editData.price = "";
                this.editData.description = "";
                this.editData.foods = [];
                this.editData.qtys = [];
                this.editLoop = 0;

                await this.fetchData();
                window.scrollTo(0, 0);
                setTimeout(() => {
                    this.success = "";
                    $("#add-modal").modal("hide");
                }, 2000);
            } else {
                if (res.status == 422) {
                    this.loading = false
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },
        saveRow(event, fieldName) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if (fieldName == "rowFood") {
                this.postData.foods[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowQty") {
                this.postData.qtys[getRowNumber - 1] = event.target.value;
            }
        },
        addRow() {
            this.loop++;
        },
        removeRow(event) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.postData.foods.splice((getRowNumber - 1), 1);
            this.postData.qtys.splice((getRowNumber - 1), 1);
            event.target.parentElement.parentElement.remove();
        },
        // this is for update
        editSaveRow(event, fieldName) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if (fieldName == "rowFood") {
                this.editData.foods[getRowNumber - 1] = event.target.value;
            }
            if (fieldName == "rowQty") {
                this.editData.qtys[getRowNumber - 1] = event.target.value;
            }
        },
        editAddRow() {
            this.editLoop++;
        },
        editRemoveRow(event) {
            const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            this.editData.foods.splice((getRowNumber - 1), 1);
            this.editData.qtys.splice((getRowNumber - 1), 1);
            // event.target.parentElement.parentElement.remove();
            this.editLoop--;
        },
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.companies.splice(obj.index, 1);
            }
        },
    },
};
</script>
<style scoped>
div.dataTables_length select {
    width: 90px !important;
    display: inline-block;
}
</style>
