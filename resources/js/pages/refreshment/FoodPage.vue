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
                                <h4>Foods</h4>
                                <div class="card-header-action">
                                    <a v-if="checkForSubmenuButtons('food-add-food')" href="#" data-toggle="modal"
                                        :data-target="'#' + formID" class="btn btn-primary">
                                        Add Food
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
                                                    <table class="table table-striped table-hover" id="food_table">
                                                        <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Name</th>
                                                                <th>Price</th>
                                                                <th>Unit</th>
                                                                <th>Description</th>
                                                                <th v-if="checkForSubmenuButtons('food-edit-food')">Action
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(food, i) in hotelData.foods" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ food.name }}</td>
                                                                <td>{{ food.price }}</td>
                                                                <td>{{ food.unit }}</td>
                                                                <td>{{ food.description ?? 'N/A' }}</td>
                                                                <td v-if="checkForSubmenuButtons('food-edit-food')">
                                                                    <button v-if="checkForSubmenuButtons('food-edit-food')"
                                                                        :data-target="'#' + editFormID" data-toggle="modal"
                                                                        @click="edit(food)" class="btn btn-primary mx-1"
                                                                        title="Edit Food">
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
                <Add heading="Add Food" :errors="this.validationErrors" :success="success" :formID="formID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Food Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Food Name" id="name"
                                v-model="postData.name" />
                        </div>

                        <div class="col-md-6">

                        </div>

                        <div class="form-group col-md-6">
                            <label for="userName">Price <span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Price" id="userName"
                                v-model="postData.price" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Unit <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Unit" id="email"
                                v-model="postData.unit" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Description</label>
                            <textarea class="form-control" placeholder="Enter Description" id="location"
                                v-model="postData.description" cols="30" rows="10"></textarea>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                            {{ loading ? "Loading...." : "Add Food" }}
                        </button>
                    </template>
                </Add>

                <!-- Add Modal -->
                <Edit heading="Edit Food" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Food Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Hotel Name" id="name"
                                v-model="editData.name" />
                        </div>

                        <div class="col-md-6">

                        </div>

                        <div class="form-group col-md-6">
                            <label for="userName">Price <span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Price" id="userName"
                                v-model="editData.price" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Unit <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Email" id="email"
                                v-model="editData.unit" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Description</label>
                            <textarea class="form-control" placeholder="Enter Location" id="location"
                                v-model="editData.description" cols="30" rows="10"></textarea>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                            {{ loading ? "Loading...." : "Update Food" }}
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
            formID: "newFood",
            editFormID: 'edit_food_form',
            loading: false,
            hotelId: "",
            hotelData: [],
            postData: {
                name: "",
                price: "",
                unit: "",
                description: "",
                hotelId: "",
            },
            editData: {
                foodId: "",
                name: "",
                price: "",
                unit: "",
                description: "",
                hotelId: "",
            },
            permissions: [],
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
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        async fetchData() {
            const data = {
                hotelId: this.hotelId
            }

            const hotelRes = await this.callApi("post", "refreshments/hotels/specific/foods", data);
            if (hotelRes.status == 200) {
                this.hotelData = hotelRes.data;
                setTimeout(() => {
                    $("#food_table").DataTable();
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
                !this.postData.unit) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;


            const res = await this.callApi("post", "refreshments/hotels/specific/foods/store", this.postData);
            if (res.status == 201) {
                $(".modal").click();
                this.loading = false
                $("#food_table").DataTable().destroy();
                this.success = "Food Added Successfully";

                this.postData.name = "";
                this.postData.price = "";
                this.postData.unit = "";
                this.postData.description = "";

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
        async edit(food) {

            if (food) {
                this.editData.foodId = food.id;
                this.editData.name = food.name;
                this.editData.price = food.price;
                this.editData.unit = food.unit;
                this.editData.description = food.description;
            } else {
                return alert("Something Went Wrong !!!");
            }

        },
        async update() {

            // validation for empty data
            if (!this.editData.foodId || !this.editData.hotelId || !this.editData.name ||
                !this.editData.price || !this.editData.unit) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            const res = await this.callApi("post", "refreshments/hotels/specific/foods/update", this.editData);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false
                $("#food_table").DataTable().destroy();
                this.success = "Food Updated Successfully";

                this.postData.name = "";
                this.postData.price = "";
                this.postData.unit = "";
                this.postData.description = "";

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
