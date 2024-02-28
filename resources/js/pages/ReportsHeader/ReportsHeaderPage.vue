<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Report Headers</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()"
                                   class="btn btn-primary">
                                    Add New Header
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
                                                <table class="table table-striped table-hover" id="report_header_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Added By</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(header,i) in headers" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ header.name }}</td>
                                                        <td>{{ header.added_by.name }}</td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(header)"
                                                                    class=" text-light btn btn-primary mx-1"
                                                                    title="Edit Header">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" title="Delete Header"
                                                                    class=" text-light btn btn-danger">
                                                                <i class="far fa-trash-alt"></i>
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
                heading="Add New Header"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Header Name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                        {{ loading ? 'Loading...' : 'Save Header' }}
                    </button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit
                heading="Edit Header Name"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Header Name" v-model="dataEdit.name">
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update Header' }}
                    </button>
                </template>
            </Edit>

            <!-- Add Modal -->
            <!-- <Delete :deleteForm="deleteFormID" confirmationMessage="Are You Sure You want To Delete This City ???" /> -->

        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "category",
    components: {
        Add,
        Edit,
        // Delete,
    },
    data() {
        return {
            validationErrors: [],
            headers: [],
            loading: false,
            formID: 'reports_header',
            editFormID: 'edit_reports_header',
            data: {
                name: "",
            },
            dataEdit: {
                id: "",
                name: "",
            },
            success: false,
            errors: false,
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchHeadersData();
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
        async fetchHeadersData() {
            const resHeaders = await this.callApi("post", 'reportsHeader');
            if (resHeaders.status == 200) {
                this.headers = resHeaders.data;
            }
            setTimeout(function () {
                $("#report_header_table").DataTable();
            }, 300);
        },
        async add() {
            this.validationErrors = []
            if (!this.data.name)
                return swal({
                    title: "Required",
                    text: "Header Name is required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true
            const res = await this.callApi("post", 'reportsHeader/store', this.data);
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Header Created Successfully Named as  " + res.data.name,
                    icon: "success",
                    timer: 2000
                });
                $("#report_header_table").DataTable().destroy();
                this.loading = false;
                 this.fetchHeadersData();
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
        edit(category) {
            this.dataEdit = category;
        },
        async update() {
            this.validationErrors = []
            if (this.dataEdit.name == "")
                return swal({
                    title: "Required",
                    text: "Header Name is required ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resEdit = await this.callApi("post", 'reportsHeader/update', this.dataEdit);
            if (resEdit.status == 200) {
                swal({
                    title: "Success",
                    text: "Header Name updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#report_header_table").DataTable().destroy();
                 this.fetchHeadersData();
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
        // async deleteModal( city,i ){
        //     const deletingObj = {
        //         url:"cities/delete",
        //         data:city,
        //         index:i,
        //     }
        //     this.$store.commit("setDeleteObj",deletingObj);
        // },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#report_header_table").DataTable().destroy();
                this.fetchHeadersData();
            }
        }
    }
}
</script>
