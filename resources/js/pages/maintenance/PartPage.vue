<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Maintenance Parts</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-part')"
                                   href="#"
                                   data-toggle="modal"
                                   :data-target="'#' + formID"
                                   class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Part
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                        @click="error = !error"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table dataTables table-striped table-hover"
                                                       id="part_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-part') || checkForSubmenuButtons('delete-part')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(part, i) in parts" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ part.name }}</td>
                                                        <td>{{ part.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-part') || checkForSubmenuButtons('delete-part')">
                                                            <button v-if="checkForSubmenuButtons('edit-part')"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editpart(part)"
                                                                    class="btn btn-primary mx-1" title="Edit Part">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" v-if="checkForSubmenuButtons('delete-part')"
                                                                    class="btn btn-danger mx-1" title="Delete Part">
                                                                <i class="far fa-trash-alt"></i>
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
                :heading="'Add Part'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="addForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addPart"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Add Part' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Part"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="editName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" id="editName" class="form-control" v-model="dataEdit.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updatePart"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Part' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->


        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "PartPage",
    components: {
        Add,
        Edit,
        vueMask,
    },
    data() {
        return {
            addForm: {},
            permissions: [],
            parts: [],
            loading: false,
            formID: "part_form",
            editFormID: "edit_department_form",
            validationErrors: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
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
        await this.fetchParts();
        this.permissions = this.$store.state.permissions;

    },

    methods: {

        async fetchParts() {
            const resPart = await this.callApi("post", 'fleet/maintenance/part');
            if (resPart.status == 200) {
                this.parts = resPart.data
            } else {
                console.log(resPart);
            }
            setTimeout(function () {
                $("#part_table").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm = {};
        },

        async addPart() {
            this.validationErrors = [];
            if (this.addForm.name == "" || typeof this.addForm.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resPartAdd = await this.callApi("post", "fleet/maintenance/part/store", this.addForm);
            if (resPartAdd.status == 201) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Part Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#part_table").DataTable().destroy();
                await this.fetchParts();
            } else {
                if (resPartAdd.status == 422) {
                    this.loading = false;
                    for (const key in resPartAdd.data.errors) {
                        resPartAdd.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updatePart() {
            this.validationErrors = [];
            if (this.dataEdit.name == "" || typeof this.dataEdit.name == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resPartEdit = await this.callApi("post", 'fleet/maintenance/part/update', this.dataEdit);
            if (resPartEdit.status == 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Part Name Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#part_table").DataTable().destroy();
                await this.fetchParts();
            } else {
                if (resPartEdit.status == 422) {
                    this.loading = false;
                    for (const key in resPartEdit.data.errors) {
                        resPartEdit.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        editpart(partEdit) {
            this.dataEdit = partEdit
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.departments.splice(obj.index, 1)
                $("#department_table").DataTable().destroy();
                this.fetchParts();
            }
        }
    }
};
</script>
<style scoped>
</style>
