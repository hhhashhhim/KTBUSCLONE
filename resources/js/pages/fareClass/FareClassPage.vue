<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Fare Class</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#' + formID" class="btn btn-primary"
                                   @click="clearForm()" v-if="checkForSubmenuButtons('add-class')">
                                    Add Fare Class
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
                                        <div class="card-header">
                                            <h4></h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="fare_class_table">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Name</th>
                                                        <th>Color</th>
                                                        <th>Status</th>
                                                        <th>Added By</th>
                                                        <th v-if="checkForSubmenuButtons('edit-class') || checkForSubmenuButtons('delete-class')">
                                                            Action
                                                        </th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(fareClass, i) in fareClasses" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ fareClass.name }}</td>
                                                        <td>
                                                            <div
                                                                style="
                                      border-radius: 50%;
                                      height: 50px;
                                      width: 50px;
                                    "
                                                                :style="{ backgroundColor: fareClass.color }"
                                                            ></div>
                                                        </td>
                                                        <td>{{ fareClass.is_active == 1 ? 'Active' : 'InActive' }}</td>
                                                        <td>{{ fareClass.added_by.name }}</td>
                                                        <td v-if="checkForSubmenuButtons('edit-class') || checkForSubmenuButtons('delete-class')">
                                                            <button title="Edit Fare Class"
                                                                    v-if="checkForSubmenuButtons('edit-class')"
                                                                    :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(fareClass)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" title="Delete Fare Class"
                                                                    v-if="checkForSubmenuButtons('delete-class')"
                                                                    class="btn btn-danger">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
                                                            <!--                                                            :data-target="'#' + deleteFormID "-->
                                                            <!--                                                            data-toggle="modal"-->
                                                            <!--                                                            @click="deleteModal(fareClass,i)"-->
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
                :heading="'Add Fare Class'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name">Name<span class="text-danger ml-1">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Fare Class Name"
                            id="name"
                            v-model="data.FareClassName"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="name">Color<span class="text-danger ml-1">*</span></label>
                        <input
                            type="color"
                            class="form-control"
                            v-model="data.FareClassColor"
                        />
                    </div>
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                                <input type="checkbox" value="1" checked class="colorinput-input"
                                       @change="checkBox($event)"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="addFareClass" :disabled="loading">{{ loading ? 'Loading...' : 'Add Fare Class' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Fare Class"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="SurchargeName">Name <span class="text-danger ml-1">*</span></label>
                        <input type="text" class="form-control" v-model="dataEdit.name"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="SurchargeName">Color <span class="text-danger ml-1">*</span></label>
                        <input type="color" class="form-control" v-model="dataEdit.color"/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center ">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                            <span>
                               <input type="checkbox" class="colorinput-input" id="editCheckBox"
                                      @change="editCheckBox($event)" v-bind:checked="dataEdit.is_active == 1"/>
                                <span class="colorinput-color bg-primary"></span>
                            </span>
                            </label>
                        </div>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateFareClass" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Fare Class' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit MOdel End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Fare Class ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import {mapGetters} from "vuex";
import showRouteDetails from "../route/popup/showRouteDetail";

export default {
    name: "FareClassPage",
    components: {
        Add,
        Edit,
        Delete,
    },
    data() {
        return {
            loading: false,
            fareClasses: [],
            formID: "fareClass_form",
            editFormID: "edit_fareClass_form",
            deleteFormID: "delete_fareClass_form",
            validationErrors: [],
            success: false,
            error: false,
            FareClassName: '',
            permissions: [],

            delId: "",
            data: {
                isActive: 1,
                FareClassName: "",
                FareClassColor: "#000000",
            },
            dataEdit: {
                FareClassName: '',
            },
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
        await this.fetchFareClasses();
        // window.removeEventListener('keydown', this.enter);
        // window.removeEventListener('keydown', this.altM);
        this.permissions = this.$store.state.permissions;
    },

    methods: {
        clearForm: function () {
            this.data = {};
            this.data.FareClassColor = "#000000";
            this.data.isActive = 1
        },
        async fetchFareClasses() {
            const res = await this.callApi("post", 'fare-class');
            if (res.status == 200) {
                this.fareClasses = res.data
            }
            setTimeout(function () {
                $("#fare_class_table").DataTable();
            }, 300);

        },

        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },

        checkBox: function (e) {
            if (e.target.checked) {
                this.data.isActive = 1;
            } else {
                this.data.isActive = 0;
            }
        },
        editCheckBox: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_active = 1;
            } else {
                this.dataEdit.is_active = 0;
            }
        },

        async addFareClass() {
            this.validationErrors = [];
            if (this.data.FareClassName === "")
                return swal({
                    title: "Required!",
                    text: "Fare Class Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.data.FareClassColor === "")
                return swal({
                    title: "Required!",
                    text: "Fare Class Color is Required",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const res = await this.callApi("post", "fare-class/store", this.data);
            if (res.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success!",
                    text: "Fare Class Added Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#fare_class_table").DataTable().destroy();
                this.loading = false
                this.data = {
                    isActive: 1,
                    FareClassName: "",
                    FareClassColor: "#000000",
                };
                await this.fetchFareClasses();
                window.scrollTo(0, 0);

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


        async updateFareClass() {
            this.validationErrors = [];
            if (this.dataEdit.FareClassName === "")
                return swal({
                    title: "Required!",
                    text: "Fare Class Name is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.FareClassColor === "")
                return swal({
                    title: "Required!",
                    text: "Fare Class Color is Required",
                    icon: "error",
                    timer: 2000
                });

            this.loading = true;
            const res = await this.callApi("post", 'fare-class/update', this.dataEdit);
            if (res.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success!",
                    text: "Fare Class Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#fare_class_table").DataTable().destroy();
                this.loading = false;
                await this.fetchFareClasses();

            } else {
                if (res.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
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


        async deleteModal(fare_class, i) {
            const deletingObj = {
                url: "fare-class/delete",
                data: fare_class,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        edit(fare_clases) {
            this.dataEdit = {...fare_clases, busClassColor: fare_clases.color};
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.fareClasses.splice(obj.index, 1)
                $("#fare_class_table").DataTable().destroy();
                this.fetchFareClasses();
            }
        }
    }
};
</script>
<style scoped>
.selected-row {
    background-color: yellow !important;
}

.booked_Seat {
    background-color: rgb(255, 0, 0) !important;
}

.notForSale {
    background-color: rgb(140, 109, 109) !important;
}

.reservedForFemale {
    background-color: rgb(250, 185, 250) !important;
}


.economy {
    background-color: rgb(250, 97, 64) !important;
}

.exective {
    background-color: rgb(64, 250, 81) !important;
}

.business {
    background-color: rgb(31, 126, 91) !important;
}


.anyElseClass {
    background-color: rgb(131, 163, 199) !important;
}

.seat-img {
    height: 40px;
}

.seat-img img,
.seat-img span {
    height: 40px;
    width: 40px;
    display: inline-block;
    cursor: pointer;
}
</style>
