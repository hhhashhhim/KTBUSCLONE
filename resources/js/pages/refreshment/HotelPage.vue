<template>
    <div>
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Hotels</h4>
                                <div class="card-header-action">
                                    <a v-if="checkForSubmenuButtons('add-hotel')" href="#" data-toggle="modal"
                                        :data-target="'#' + formID" class="btn btn-primary">
                                        Add Hotel
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
                                                    <table class="table table-striped table-hover" id="hotel_table">
                                                        <thead>
                                                            <tr>
                                                                <th>Sr No.</th>
                                                                <th>Name</th>
                                                                <th>Contact</th>
                                                                <th>Balance (Rs)</th>
                                                                <th>Commission (%)</th>
                                                                <th>Location</th>
                                                                <th>Logo</th>
                                                                <th  v-if="checkForSubmenuButtons('edit-hotel') || checkForSubmenuButtons('food') || checkForSubmenuButtons('deal')"
                                                                    width="230px !important">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(hotel, i) in hotels" :key="i">
                                                                <td>{{ i + 1 }}</td>
                                                                <td>{{ hotel.name }}</td>
                                                                <td>{{ phoneFormat(hotel.contact) }}</td>
                                                                <td>{{ hotel.balance ?? 0 }}</td>
                                                                <td>{{ hotel.commission }}</td>
                                                                <td>{{ hotel.location }}</td>
                                                                <td>
                                                                    <a :href="$store.state.app_url + 'uploads/refreshment/hotel/' + (hotel.logo)"
                                                                        target="_blank">
                                                                        <img :src="$store.state.app_url + 'uploads/refreshment/hotel/' + (hotel.logo)"
                                                                            style="width:100px;height:100px;" alt="">
                                                                    </a>
                                                                </td>
                                                                <td
                                                                    v-if="checkForSubmenuButtons('edit-hotel') || checkForSubmenuButtons('food') || checkForSubmenuButtons('deal')">
                                                                    <button v-if="checkForSubmenuButtons('edit-hotel')"
                                                                        :data-target="'#' + editFormID" data-toggle="modal"
                                                                        @click="edit(hotel)" class="btn btn-primary mx-1"
                                                                        title="Edit Hotel">
                                                                        <i class="far fa-edit"></i>
                                                                    </button>
                                                                    <router-link v-if="checkForSubmenuButtons('food')"
                                                                        class="btn btn-success" @click="hotelId(hotel.id)"
                                                                        :to="{ name: 'foods' }">
                                                                        Foods
                                                                    </router-link>
                                                                    <router-link v-if="checkForSubmenuButtons('deal')"
                                                                        class="btn btn-success mx-1"
                                                                        @click="hotelId(hotel.id)"
                                                                        :to="{ name: 'foodDeals' }">
                                                                        Food Deals
                                                                    </router-link>
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
                <Add heading="Add Hotel" :errors="this.validationErrors" :success="success" :formID="formID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Hotel Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Hotel Name" id="name"
                                v-model="postData.hotelName" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="userName">Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Name" id="userName"
                                v-model="postData.name" />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="email">Email <span class="text-danger ml-1">*</span></label>
                            <input type="email" class="form-control" placeholder="Enter Email" id="email"
                                v-model="postData.email" />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password">Password <span class="text-danger ml-1">*</span></label>
                            <input type="password" class="form-control" placeholder="Enter Password" id="password"
                                v-model="postData.password" />
                        </div>
                        <div class="form-group col-md-4">
                            <label for="role">Role<span class="text-danger ml-1">*</span></label>
                            <select
                                type="text"
                                class="form-control"
                                id=""
                                v-model="postData.role"
                            >
                                <option value="">Select Role</option>
                                <option v-for="(role, i) in roles" :value="role.id" :key="i">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                            <vue-mask class="form-control" v-model="postData.contact" mask="0000-0000000" :raw="false"
                                :options="options">
                            </vue-mask>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="Logo">Logo</label>
                            <input type="file" class="form-control" placeholder="" @change="uploadLogo($event, 'add')"
                                id="imageField" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Initial Balance </label>
                            <input type="number" class="form-control" placeholder="Balance" id="balance"
                                v-model="postData.balance" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Company Commission (%)<span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Commission" id="balance"
                                v-model="postData.commission" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location <span class="text-danger ml-1">*</span></label>
                            <textarea class="form-control" placeholder="Enter Location" id="location"
                                v-model="postData.location" cols="30" rows="10"></textarea>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                            {{ loading ? "Loading...." : "Add Hotel" }}
                        </button>
                    </template>
                </Add>

                <!-- Add Modal -->
                <Edit heading="Edit Company" :errors="this.validationErrors" :success="success" :editForm="editFormID">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Hotel Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Hotel Name" id="name"
                                v-model="editData.hotelName" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="userName">Name <span class="text-danger ml-1">*</span></label>
                            <input type="text" class="form-control" placeholder="Enter Name" id="userName"
                                v-model="editData.name" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email <span class="text-danger ml-1">*</span></label>
                            <input type="email" class="form-control" placeholder="Enter Email" id="email"
                                v-model="editData.email" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password">Password <small>(Empty field will save password same)</small></label>
                            <input type="password" class="form-control" placeholder="Enter Password" id="password"
                                v-model="editData.password" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Contact <span class="text-danger ml-1">*</span></label>
                            <vue-mask class="form-control" v-model="editData.contact" mask="0000-0000000" :raw="false"
                                :options="options">
                            </vue-mask>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="Logo">Logo <small>(Empty field will save logo same)</small></label>
                            <input type="file" class="form-control" placeholder="" @change="uploadLogo($event, 'edit')"
                                id="editImageField" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Initial Balance </label>
                            <input type="number" class="form-control" placeholder="Balance" id="balance"
                                v-model="editData.balance" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact">Company Commission (%)<span class="text-danger ml-1">*</span></label>
                            <input type="number" class="form-control" placeholder="Commission" id="balance"
                                v-model="editData.commission" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="location">Location <span class="text-danger ml-1">*</span></label>
                            <textarea class="form-control" placeholder="Enter Location" id="location"
                                v-model="editData.location" cols="30" rows="10"></textarea>
                        </div>
                    </div>


                    <template v-slot:button>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                            {{ loading ? "Loading...." : "Update Hotel" }}
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
            permissions: [],

            options: {
                placeholder: '0300-0000000',
                // http://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
            },
            formID: "newHotel",
            editFormID: 'edit_company_form',
            loading: false,
            hotels: [],
            roles: [],
            postData: {
                hotelName: "",
                name: "",
                role: "",
                email: "",
                password: "",
                contact: "",
                logo: "",
                balance: "",
                commission: "",
                location: "",
            },
            editData: {
                hotelId: "",
                userId: "",
                hotelName: "",
                name: "",
                email: "",
                password: "",
                contact: "",
                logo: "",
                balance: "",
                commission: "",
                location: "",
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

        await this.fetchData();
        this.permissions = this.$store.state.permissions;
        setTimeout(() => {
            $("#hotel_table").DataTable();
        }, 500);
    },

    methods: {
        async fetchData() {
            const hotelRes = await this.callApi("post", "refreshments/hotels");
            if (hotelRes.status == 200) {
                this.hotels = hotelRes.data;
            }
            const roleRes = await this.callApi("post", "role");
            if (roleRes.status == 200) {
                this.roles = roleRes.data;
            } else {
                console.log(roleRes)
            }
        },
        async hotelId(hotelId) {
            localStorage.setItem('hotel-id', hotelId);
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        async add() {

            // validation for empty data
            if (!this.postData.hotelName || !this.postData.role || !this.postData.name || !this.postData.email || !this.postData.password ||
                !this.postData.contact || !this.postData.commission || !this.postData.location) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            const config = {
                headers: { 'content-type': 'multipart/form-data' }
            }

            let formData = new FormData();
            formData.append('hotelName', this.postData.hotelName);
            formData.append('name', this.postData.name);
            formData.append('email', this.postData.email);
            formData.append('role', this.postData.role);
            formData.append('password', this.postData.password);
            formData.append('contact', this.postData.contact);
            formData.append('logo', this.postData.logo);
            formData.append('balance', this.postData.balance);
            formData.append('commission', this.postData.commission);
            formData.append('location', this.postData.location);


            const res = await this.callApi("post", "refreshments/hotels/store", formData, config);
            if (res.status == 201) {
                $(".modal").click();
                this.loading = false
                swal({
                    title: "Success",
                    text: "Created Succesfuly",
                    icon: "success",
                    timer: 2000
                });
                $("#hotel_table").DataTable().destroy();

                this.postData.hotelName = "";
                this.postData.name = "";
                this.postData.email = "";
                this.postData.password = "";
                this.postData.contact = "";
                this.postData.logo = "";
                this.postData.balance = "";
                this.postData.commission = "";
                this.postData.location = "";
                $("#imageField").val('');

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
        async edit(hotel) {

            if (hotel) {
                this.editData.hotelId = hotel.id;
                this.editData.userId = hotel.user_id;
                this.editData.hotelName = hotel.name;
                this.editData.name = hotel.user.name;
                this.editData.email = hotel.user.email;
                this.editData.contact = hotel.contact;
                this.editData.balance = hotel.balance;
                this.editData.commission = hotel.commission;
                this.editData.location = hotel.location;
            } else {
                return alert("Something Went Wrong !!!");
            }

        },
        async update() {

            // validation for empty data
            if (!this.editData.hotelName || !this.editData.name || !this.editData.email ||
                !this.editData.contact || !this.editData.commission || !this.editData.location) {
                return swal({
                    title: "Error",
                    text: "Please Fill Required Field",
                    icon: "error",
                    timer: 2000
                });
            }

            this.loading = true;

            const config = {
                headers: { 'content-type': 'multipart/form-data' }
            }

            let formData = new FormData();
            formData.append('hotelId', this.editData.hotelId);
            formData.append('userId', this.editData.userId);
            formData.append('hotelName', this.editData.hotelName);
            formData.append('name', this.editData.name);
            formData.append('email', this.editData.email);
            formData.append('password', this.editData.password);
            formData.append('contact', this.editData.contact);
            formData.append('logo', this.editData.logo);
            formData.append('balance', this.editData.balance);
            formData.append('commission', this.editData.commission);
            formData.append('location', this.editData.location);


            const res = await this.callApi("post", "refreshments/hotels/update", formData, config);
            if (res.status == 200) {
                $(".modal").click();
                this.loading = false
                $("#hotel_table").DataTable().destroy();
                this.success = "Hotel Updated Successfully";

                this.editData.hotelId = "";
                this.editData.userId = "";
                this.editData.hotelName = "";
                this.editData.name = "";
                this.editData.email = "";
                this.editData.password = "";
                this.editData.contact = "";
                this.editData.logo = "";
                this.editData.balance = "";
                this.editData.commission = "";
                this.editData.location = "";
                $("#editImageField").val('');

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
        uploadLogo(e, name) {
            const imageFile = e.target.files[0];
            if (imageFile.name.match(/\.(jpg|jpeg|png)$/i)) {
                if (name == "add") {
                    this.postData.logo = imageFile;
                }
                if (name == "edit") {
                    this.editData.logo = imageFile;
                }
            } else {
                return swal({
                    title: "Invalid Format",
                    text: "Uploaded File must be in .jpg, .jpeg, .png",
                    icon: "error",
                    timer: 2000
                });
                e.target.value = '';
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
}</style>
