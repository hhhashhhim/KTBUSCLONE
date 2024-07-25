<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Sub Routes</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()"
                                   class="btn btn-primary" v-if="checkForSubmenuButtons('add-sub-route')">
                                    Add
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
                                                <table class="table table-striped table-hover" id="sub_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>From</th>
                                                        <th>To</th>
                                                        <th>Message</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-sub-route')  || checkForSubmenuButtons('delete-sub-route')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(single,i) in subroutes" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ single.from_city_data.name }}</td>
                                                        <td>{{ single.to_city_data.name }}</td>
                                                        <td>{{ single.cancel_message }}</td>
                                                        <td>{{ single.added_by_data.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-sub-route')  || checkForSubmenuButtons('delete-sub-route')">
                                                            <button v-if="checkForSubmenuButtons('edit-sub-route')"
                                                                    title="Edit City" :data-target="'#' + editFormID"
                                                                    data-toggle="modal" @click="edit(single)"
                                                                    class=" text-light btn btn-primary mx-1">
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
            <Add
                heading="Add New"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="department">From City</label>
                    <select class="form-control" v-model="addData.fromCity">
                        <option value="0">Select From City</option>
                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                city.name
                            }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="department">To City</label>
                    <select class="form-control" v-model="addData.toCity">
                        <option value="0">Select To City</option>
                        <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                city.name
                            }}
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="department">Cancellation Message</label>
                    <input type="text" class="form-control" v-model="addData.message">
                </div>
            </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                        {{ loading ? 'Loading...' : 'Add' }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit Message"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="department">From City</label>
                        <select class="form-control" v-model="dataEdit.from_city">
                            <option value="0">Select From City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                    city.name
                                }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="department">To City</label>
                        <select class="form-control" v-model="dataEdit.to_city">
                            <option value="0">Select To City</option>
                            <option v-for="(city,i) in cities" :key="i" :value="city.id"> {{
                                    city.name
                                }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="department">Cancellation Message</label>
                        <input type="text" class="form-control" v-model="dataEdit.cancel_message">
                    </div>
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update City' }}
                    </button>
                </template>
            </Edit>

        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';

export default {
    name: "subroute",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            validationErrors: [],
            cities: [],
            subroutes: [],
            permissions: [],
            loading: false,
            formID: 'route_form',
            editFormID: 'edit_route_form',
            addData: {
                fromCity: 0,
                toCity: 0,
                message: "",
            },
            dataEdit: {
            },
            delId: "",
            success: false,
            errors: false,
        }
    },
    async created() {
        $('.modal').remove();
        await this.fetchSubroutes();
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
            this.addData = {
                fromCity: 0,
                toCity: 0,
                message: "",
            };
        },

        async fetchSubroutes() {
            const resCity = await this.callApi("post", 'routes/sub-routes');
            if (resCity.status == 200) {
                this.subroutes = resCity.data.subRoutes;
                this.cities = resCity.data.cities;
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
                $("#sub_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = []
            if (!this.addData.fromCity)
                return swal({
                    title: "Required",
                    text: "From City Name is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addData.toCity)
                return swal({
                    title: "Required",
                    text: "To City Name is required",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addData.message)
                return swal({
                    title: "Required",
                    text: "Message is required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const res = await this.callApi("post", 'routes/sub-routes/store', this.addData);
            if (res.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Added",
                    icon: "success",
                    timer: 2000
                });
                $("#sub_table").DataTable().destroy();
                this.loading = false;
                await this.fetchSubroutes();
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
        edit(subroute) {
            this.dataEdit = subroute;
        },
        async update() {
            this.validationErrors = []
            if (this.dataEdit.from_city == "")
                return swal({
                    title: "Required",
                    text: "From City Name is required ",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.to_city == "")
                return swal({
                    title: "Required",
                    text: "To City Name is required ",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.cancel_message == "")
                return swal({
                    title: "Required",
                    text: "Message is required ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resEdit = await this.callApi("post", 'routes/sub-routes/update', this.dataEdit);
            if (resEdit.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#sub_table").DataTable().destroy();
                await this.fetchSubroutes();
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
    }
}
</script>
