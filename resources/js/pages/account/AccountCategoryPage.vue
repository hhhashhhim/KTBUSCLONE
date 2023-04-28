<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Account Categories</h4>
                            <div class="card-header-action">
                                <a v-if="checkForSubmenuButtons('add-category')"
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Category
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
                                                       id="category_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Tier 2</th>
                                                        <th>Tier 1</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(category, i) in categories" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ category.name}}</td>
                                                        <td>{{ category.second_level.name}}</td>
                                                        <td>{{ category.first_level.name }}</td>
                                                        <td>
                                                            N/A
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
                :heading="'Add Account Category'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="terminals">Tier 1 <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" @change="getSecondLevel(addForm.firstLevel)" v-model="addForm.firstLevel">
                            <option value="" selected>Select Tier 1</option>
                            <option value="1">Assets</option>
                            <option value="2">Liabilities</option>
                            <option value="3">Equity</option>
                            <option value="4">Revenue</option>
                            <option value="5">Expenses</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="departmentName">Tier 2<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="addForm.secondLevel">
                            <option value="" selected>Select Tier 2</option>
                            <option
                                v-for="(second, i) in secondLevels"
                                :key="i"
                                :value="second.id"
                            >
                                {{ second.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="addForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addCategory"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Add Category' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Category"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="terminals">Tier 1 <span class="text-danger ml-1">*</span></label>
                        <select class="form-control" @change="getSecondLevel(updatedForm.firstLevel)" v-model="updatedForm.firstLevel">
                            <option value="" selected>Select Tier 1</option>
                            <option value="1">Assets</option>
                            <option value="2">Liabilities</option>
                            <option value="3">Equity</option>
                            <option value="4">Revenue</option>
                            <option value="5">Expenses</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="departmentName">Tier 2<span class="text-danger ml-1">*</span></label>
                        <select class="form-control" v-model="updatedForm.secondLevel">
                            <option value="" selected>Select Tier 2</option>
                            <option
                                v-for="(second, i) in secondLevels"
                                :key="i"
                                :value="second.id"
                            >
                                {{ second.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input type="text" id="name" class="form-control" v-model="updatedForm.name"/>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateDesignation"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Category' }}
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
    name: "AccountCategoryPage",
    components: {
        Add,
        Edit,
        vueMask,
    },
    data() {
        return {
            addForm: {
                firstLevel: "",
                secondLevel: "",
                name: "",
            },
            updatedForm: {
                categoryId: "",
                firstLevel: "",
                secondLevel: "",
                name: "",
            },
            permissions: [],
            categories: [],
            secondLevels: [],
            loading: false,
            formID: "category_account",
            editFormID: "edit_category_account",
            validationErrors: [],
            success: false,
            error: false,
        };
    },
    async created() {
        await this.fetchCategories();

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

        async fetchCategories() {

            const resCategories = await this.callApi("post", 'accounts/coa/categories');
            console.log(resCategories.data);
            if (resCategories.status == 200) {
                this.categories = resCategories.data
            } else {
                console.log(resCategories);
            }

            setTimeout(function () {
                $("#category_table").DataTable();
            }, 300);
        },
        clearForm: function () {
            this.addForm = {
                firstLevel: "",
                secondLevel: "",
                name: "",
            };
        },
        async getSecondLevel(id) {
            const resSecondLevel = await this.callApi("post", 'accounts/coa/getSecondLevel', {'id': id});

            if (resSecondLevel.status == 200 && resSecondLevel.data.length > 0) {
                this.secondLevels = resSecondLevel.data;
                this.addForm.secondLevel = "";
                this.updatedForm.secondLevel = "";
            }else{
                this.addForm.secondLevel = "";
                this.updatedForm.secondLevel = "";
            }
            if (resSecondLevel.status == 422) {

                let errorContent = "";
                let count = 0;
                for (const key in resSecondLevel.data.errors) {
                    resSecondLevel.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " + //creating serial no.
                            element + // main error
                            "\n" // creating new line
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 2000
                    });

                }
            }
        },
        async addCategory() {
            this.validationErrors = [];
            if (this.addForm.firstLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 1",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.secondLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 2",
                    icon: "error",
                    timer: 2000
                });
            if (!this.addForm.name)
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });

            const resCategory = await this.callApi("post", "accounts/coa/category/store", this.addForm);
            if (resCategory.status == 201) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Category Added Successfully!",
                    icon: "success",
                    timer: 2000
                });
                this.clearForm();
                $("#category_table").DataTable().destroy();
                await this.fetchCategories();
            } else {
                if (resCategory.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCategory.data.errors) {
                        resCategory.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                    timer: 2000
                        });
                    }
                }
            }
        },

        async updateDesignation() {
            this.validationErrors = [];
            if (this.updatedForm.firstLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 1",
                    icon: "error",
                    timer: 2000
                });
            if (this.updatedForm.secondLevel == "")
                return swal({
                    title: "Required!",
                    text: "Please Select Tier 2",
                    icon: "error",
                    timer: 2000
                });
            if (!this.updatedForm.name)
                return swal({
                    title: "Required!",
                    text: "Name is Required",
                    icon: "error",
                    timer: 2000
                });

            const resCategory = await this.callApi("post", "accounts/coa/category/update", this.updatedForm);
            if (resCategory.status == 200) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Category Updated Successfully!",
                    icon: "success",
                    timer: 2000
                });
                $("#category_table").DataTable().destroy();
                await this.fetchCategories();
            } else {
                if (resCategory.status == 422) {
                    this.loading = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resCategory.data.errors) {
                        resCategory.data.errors[key].forEach((element) => {
                            errorContent += (
                                (++count) + " - " + //creating serial no.
                                element + // main error
                                "\n" // creating new line
                            );
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                    timer: 2000
                        });
                    }
                }
            }
        },

        editCategory(category) {
            this.updatedForm.categoryId = category.id;
            this.updatedForm.firstLevel = category.first_level_id;
            this.updatedForm.name = category.name;
            this.getSecondLevel(category.first_level_id);
            setTimeout(() => {
                this.updatedForm.secondLevel = category.second_level_id;
            },500);
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.departmentsDetails.splice(obj.index, 1)
                $("#category_table").DataTable().destroy();
                this.fetchCategories();
            }
        }
    }
};
</script>
<style scoped>
</style>
