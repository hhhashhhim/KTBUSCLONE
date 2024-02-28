<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Cities</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()"
                                   class="btn btn-primary" v-if="checkForSubmenuButtons('add-city')">
                                    Add New City
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
                                                <table class="table table-striped table-hover" id="city_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-city')  || checkForSubmenuButtons('delete-city')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(city,i) in cities" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ city.name }}</td>
                                                        <td>{{ city.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-city')  || checkForSubmenuButtons('delete-city')">
                                                            <button v-if="checkForSubmenuButtons('edit-city')"
                                                                    title="Edit City" :data-target="'#' + editFormID"
                                                                    data-toggle="modal" @click="edit(city)"
                                                                    class=" text-light btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button title="Delete City" v-if="checkForSubmenuButtons('delete-city')"
                                                                    class=" text-light btn btn-danger" :data-target="'#' + hideFormID" @click="delId = city.id"
                                                                    data-toggle="modal">
                                                                <i class="far fas fa-trash"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#'+ deleteFormID" data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(city,i)"-->
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
                heading="Add New City"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter City Name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                        {{ loading ? 'Loading...' : 'Add New City' }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit City Name"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter City Name" v-model="dataEdit.name">
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update City' }}
                    </button>
                </template>
            </Edit>

            <!-- Hide Modal -->
            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Delete This City ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="hideCity"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Delete' }}
                    </button>
                </template>
            </Hide>

        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
import Hide from '../../components/Hide.vue';
import {mapGetters} from 'vuex';

export default {
    name: "city",
    components: {
        Add,
        Edit,
        Hide,
    },
    data() {
        return {
            validationErrors: [],
            cities: [],
            permissions: [],
            loading: false,
            formID: 'city_form',
            editFormID: 'edit_city_form',
            hideFormID: 'hide_city_form',
            data: {
                name: "",
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
        $('.modal').remove();
        await this.fetchCities();
        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
    },

    methods: {
        clearForm: function () {
            this.data = {};
        },
        
        // async test() {
        //     const resCity = await this.callApi("post", 'api/booking/schedules/available',{
        //         departure_city: "multan",
        //         destination_city: "karachi",
        //         date: "2023-05-12",
        //     });
        // },

        async fetchCities() {
            const resCity = await this.callApi("post", 'cities');
            if (resCity.status == 200) {
                this.cities = resCity.data;
            }
            if (resCity.status == 403) {
                window.history.back();
                return swal({
                    title: "OOPS!!!!!",
                    text: "ACCESS DENIED",
                    icon: "error",
                    timer: 2000
                });
            }
            setTimeout(function () {
                $("#city_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = []
            if (!this.data.name)
                return swal({
                    title: "Required",
                    text: "City Name is required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const res = await this.callApi("post", 'cities/store', this.data);
            if (res.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "City Created Succesfuly Named as  " + res.data.name,
                    icon: "success",
                    timer: 2000
                });
                $("#city_table").DataTable().destroy();
                this.loading = false;
                await this.fetchCities();
                this.data.name = "";
                setTimeout(function () {
                    this.success = "";
                    this.data = "";
                }, 300)
            } else {
                if (res.status == 422) {
                    this.loading = false;
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
            this.validationErrors = []
            if (this.dataEdit.name == "")
                return swal({
                    title: "Required",
                    text: "city Name is required ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resEdit = await this.callApi("post", 'cities/update', this.dataEdit);
            if (resEdit.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "City updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#city_table").DataTable().destroy();
                await this.fetchCities();
                setTimeout(() => {
                    this.success = ""
                    $('#edit-modal').modal('hide')
                }, 3000);
            } else {
                if (resEdit.status == 422) {
                    this.loading = false;
                    for (const key in resEdit.data.errors) {
                        resEdit.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },
        async hideCity() {
            this.loading = true;
            const resHide = await this.callApi("post", 'cities/hide', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "City Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#city_table").DataTable().destroy();
                await this.fetchCities();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false
                }, 3000);
            }
        },
    }
}
</script>
