<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Expense Categories</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-category')" href="#" data-toggle="modal"
                                    :data-target="'#' + formID" @click="clearForm()" class="btn btn-primary">
                                    Add New Category
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
                                                <table class="table table-striped table-hover" id="category_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Added By</th>
                                                            <th v-if="checkForSubmenuButtons('edit-category')">Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(category, i) in categories" :key="i">
                                                            <td>{{ i + 1 }}</td>
                                                            <td>{{ category.name }}</td>
                                                            <td>{{ category.added_by.name }}</td>
                                                            <td>
                                                                <button v-if="checkForSubmenuButtons('edit-category')"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(category)"
                                                                    class=" text-light btn btn-primary mx-1"
                                                                    title="Edit Category">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <button style="display:none;" title="Delete Category"
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
            <Add heading="Add New Category" :errors="validationErrors" :success="success" :formID="formID">
                <!-- Category Name -->
                <div class="form-group">
                    <label for="name">
                        Name <span class="text-danger ml-1">*</span>
                    </label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="data.name">
                </div>

                <!-- Include in Closing Summary -->
                <div class="form-group">
                    <label>
                        Include in Closing Summary
                        <span class="text-danger ml-1">*</span>
                    </label>

                    <div class="d-flex align-items-center mt-2">
                        <div class="form-check mr-4">
                            <input class="form-check-input" type="radio" id="closing_yes" :value="1"
                                v-model.number="data.include_in_closing_summary">
                            <label class="form-check-label" for="closing_yes">
                                Yes
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="closing_no" :value="0"
                                v-model="data.include_in_closing_summary">
                            <label class="form-check-label" for="closing_no">
                                No
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Button Slot -->
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">
                        {{ loading ? 'Loading...' : 'Add New Category' }}
                    </button>
                </template>
            </Add>


            <Edit heading="Edit Category Name" :errors="validationErrors" :success="success" :editForm="editFormID">

                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="dataEdit.name">
                </div>

                <!-- Include in Closing Summary -->
                <div class="form-group">
                    <label>
                        Include in Closing Summary
                        <span class="text-danger ml-1">*</span>
                    </label>

                    <div class="d-flex align-items-center mt-2">
                        <div class="form-check mr-4">
                            <input class="form-check-input" type="radio" id="closing_summary_yes" :value="1"
                                v-model.number="dataEdit.include_in_closing_summary">
                            <label class="form-check-label" for="closing_summary_yes">Yes</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="closing_summary_no" :value="0"
                                v-model.number="dataEdit.include_in_closing_summary">
                            <label class="form-check-label" for="closing_summary_no">No</label>
                        </div>
                    </div>
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">
                        {{ loading ? 'Loading...' : 'Update Category' }}
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
import { mapGetters } from 'vuex';

export default {
    name: "category",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            validationErrors: [],
            categories: [],
            loading: false,
            formID: 'category_form',
            editFormID: 'edit_category_form',
            permissions: [],
            data: {
                name: "",
                include_in_closing_summary: 1, // default Yes
            },
            dataEdit: {
                id: "",
                name: "",
                include_in_closing_summary: 1, // default Yes
            },
            success: false,
            errors: false,
        }
    },
    mounted() {
        // Set default for Add modal
        $(document).on('shown.bs.modal', `#${this.formID}`, () => {
            this.data.include_in_closing_summary = 1;
        });

        // Failsafe default for Edit modal
        $(document).on('shown.bs.modal', `#${this.editFormID}`, () => {
            if (
                this.dataEdit.include_in_closing_summary === undefined ||
                this.dataEdit.include_in_closing_summary === null
            ) {
                this.dataEdit.include_in_closing_summary = 1;
            }
        });
    },
    async created() {
        $('.modal').remove();
        this.permissions = this.$store.state.permissions;
        await this.fetchData();
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
        clearForm() {
            this.data = {
                name: "",
                include_in_closing_summary: 1
            };
        },

        async fetchData() {
            const resCity = await this.callApi("post", 'expenses/categories');
            if (resCity.status == 200) {
                this.categories = resCity.data;
            }
            setTimeout(function () {
                $("#category_table").DataTable();
            }, 300);
        },

        async add() {
            this.validationErrors = []
            if (!this.data.name) return swal({
                title: "Required",
                text: "Category Name is required",
                icon: "error",
                timer: 2000
            });

            this.loading = true
            const res = await this.callApi("post", 'expenses/categories/store', this.data);

            if (res.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Category Created Successfully Named as " + res.data.name,
                    icon: "success",
                    timer: 2000
                });
                $("#category_table").DataTable().destroy();
                this.loading = false;
                await this.fetchData();
                this.clearForm();
            } else if (res.status == 422) {
                this.loading = false;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
                        this.errorsArray(element, key);
                    });
                }
            }
        },

        edit(category) {
            this.dataEdit = {
                id: category.id,
                name: category.name,
                include_in_closing_summary:
                    category.include_in_closing == null || category.include_in_closing == undefined
                        ? 1 // default Yes if NULL
                        : Number(category.include_in_closing) // 0 or 1
            };


            // Open Edit modal after setting data
            $(`#${this.editFormID}`).modal('show');
        },

        async update() {
            this.validationErrors = []
            if (!this.dataEdit.name) return swal({
                title: "Required",
                text: "Category Name is required",
                icon: "error",
                timer: 2000
            });

            this.loading = true;
            const resEdit = await this.callApi("post", 'expenses/categories/update', this.dataEdit);

            if (resEdit.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Category updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                $("#category_table").DataTable().destroy();
                await this.fetchData();
                this.dataEdit = { id: "", name: "", include_in_closing_summary: 1 }; // reset edit form
                $(`#${this.editFormID}`).modal('hide');
            } else if (resEdit.status == 422) {
                this.loading = false;
                for (const key in resEdit.data.errors) {
                    resEdit.data.errors[key].forEach((element) => {
                        this.errorsArray(element, key);
                    });
                }
            }

            setTimeout(() => this.loading = false, 3000);
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.cities.splice(obj.index, 1)
                $("#category_table").DataTable().destroy();
                this.fetchData();
            }
        }
    }
}
</script>
