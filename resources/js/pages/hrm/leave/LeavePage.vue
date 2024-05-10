<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Leaves</h4>
                            <div class="card-header-action" v-if="checkForSubmenuButtons('apply-leave')">
                                <a
                                    
                                    href="#"
                                    v-if="$store.state.user.role.name == 'admin'"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Apply For Leave
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
                                                       id="leave_table">
                                                    <thead class="text-center">
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Form</th>
                                                        <th>To</th>
                                                        <th>Reason</th>
                                                        <th>Days</th>
                                                        <th>Decision Maker</th>
                                                        <th>Status</th>
                                                        <th>Applied By</th>
                                                        <th v-if="$store.state.user.role.name == 'admin'">Approval</th>
                                                        <th v-if="checkForSubmenuButtons('edit-leave')">Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(leave, i) in leaves" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ leave.from }}</td>
                                                        <td>{{ leave.to }}</td>
                                                        <td>{{ leave.reason }}</td>
                                                        <td>{{ leave.days }}</td>
                                                        <td v-if="leave.status == 'P' || leave.decision == null"
                                                            class="text-center">
                                                            <div class="badge badge-warning"> Pending</div>
                                                        </td>
                                                        <td class="text-center"
                                                            v-else-if="leave.status == 'A' || leave.decision != null">
                                                            {{ leave.decision.name }}
                                                        </td>
                                                        <td class="text-center" v-else>{{ leave.decision.name }}</td>
                                                        <td class="text-center">
                                                            <div :class="getStatusClass(leave.status)">
                                                                {{ getStatusName(leave.status) }}
                                                            </div>
                                                        </td>
                                                        <td class="text-center">{{ leave.added_by.name }}</td>
                                                        <td v-if="$store.state.user.role.name == 'admin'">
                                                            <div v-if="leave.status == 'P'">
                                                                <button @click="approvalLeave('A', leave.id)"
                                                                        class=" btn btn-sm mr-1 btn-success"><i
                                                                    class="fas fa-check"></i></button>
                                                                <button @click="approvalLeave('R', leave.id)"
                                                                        class=" btn btn-sm btn-danger"><i
                                                                    class="fas fa-times"></i></button>
                                                            </div>
                                                            <div v-else-if="leave.status == 'A'">
                                                                <div class="badge badge-success">Approved</div>
                                                            </div>
                                                            <div v-else="leave.status == 'R'">
                                                                <div class="badge badge-danger">Rejected</div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center" v-if="leave.status == 'P'  ||  $store.state.user.role.name != 'admin'">
                                                            <button v-if="checkForSubmenuButtons('edit-leave')" title="Edit Leave" :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="editLeave(leave)"
                                                                    class="btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
                                                            </button>
                                                            <button style="display:none;" title="Delete Leave"
                                                                    class="btn btn-danger text-light">
                                                                <i class="far fa-trash-alt"></i>
                                                            </button>
<!--                                                            :data-target="'#' + deleteFormID"-->
<!--                                                            data-toggle="modal"-->
<!--                                                            @click="deleteModal(leave,i)"-->
                                                        </td>
                                                        <td v-else class="text-center">
                                                            <div class="badge badge-info text-wrap text-break"> You can not
                                                                Edit/Delete Application
                                                            </div>
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
                :heading="'Apply For Leave'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="fromDate">From <span class="text-danger ml-1">*</span></label>
                        <input type="date" id="fromDate" class="form-control" v-model="addForm.from"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="toDate">To <span class="text-danger ml-1">*</span></label>
                        <input type="date" id="toDate" :min="minDateFilter()" class="form-control"
                               v-model="addForm.to"/>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="reason">Reason<span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" name="" id="reason" cols="50" rows="60"
                                  v-model="addForm.reason"></textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addLeave"
                            :class=" loading ? 'disabled' : '' ">
                        {{ loading ? 'Loading...' : 'Apply For Leave' }}
                    </button>
                </template>
            </Add>


            <!-- Add Modal End -->
            <!--            Edit Model-->
            <Edit
                heading="Edit Leave Application"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-6">
                        <label for="fromDate">From <span class="text-danger ml-1">*</span></label>
                        <input type="date" id="fromDate" class="form-control" v-model="dataEdit.from"/>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="toDate">To <span class="text-danger ml-1">*</span></label>
                        <input type="date" id="toDateEdit" :min="minDateFilter()" class="form-control"
                               v-model="dataEdit.to"/>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="reason">Reason<span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" name="" id="reason" cols="50" rows="60"
                                  v-model="dataEdit.reason"></textarea>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateLeaves"
                            :disabled="loading">
                        {{ loading ? 'Loading...' : 'Update Leave Record' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->
            <Delete :deleteForm="deleteFormID"
                    confirmationMessage='Are You Sure You want To Delete This Leave Record ???'
            />

        </div>
    </section>
</template>

<script>
import Add from "../../../components/Add.vue";
import Edit from "../../../components/Edit.vue";
import Delete from "../../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "LeavePage",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            addForm: {},
            leaves: [],
            loading: false,
            formID: "leaves_form",
            editFormID: "edit_leaves_form",
            deleteFormID: "delete_leaves_form",
            validationErrors: [],
            permissions: [],
            success: false,
            error: false,
            delId: "",
            dataEdit: {},
        };
    },
    async created() {
        $('.modal').remove();
        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
         this.fetchLeaves();

    },

    methods: {

        async fetchLeaves() {
            const resLeave = await this.callApi("post", 'hrm/leave');
            console.log(resLeave);
            if (resLeave.status == 200) {
                this.leaves = resLeave.data
            } else {
                console.log(resLeave);
            }
            setTimeout(function () {
                $("#leave_table").DataTable();
            }, 300);
        },
        async approvalLeave(value, id) {
            const approvalRes = await this.callApi("post", 'hrm/leave/approval', {status: value, id: id});
            console.log(approvalRes);

            if (approvalRes.status == 200) {
                if (approvalRes.data[0].status == 'A') {
                    swal({
                        title: "Success!",
                        text: "Leave Application Approved Successfully",
                        icon: "success",
                        timer: 2000
                    });
                } else {
                    swal({
                        title: "Success!",
                        text: "Leave Application Rejected Successfully",
                        icon: "success",
                        timer: 2000
                    });
                }
                $("#leave_table").DataTable().destroy();
                await this.fetchLeaves();
            } else {
                if (approvalRes.status == 422) {
                    $("#" + formID).scrollTop(0, 0);
                    this.loading = false;
                    for (const key in approvalRes.data.errors) {
                        approvalRes.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }

        },
        minDateFilter: function () {
            var dtToday = new Date();
            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();
            return year + '-' + month + '-' + day;
        },

        getStatusName: function (value) {
            if (value == 'P') {
                return 'Pending';
            }
            if (value == 'A') {
                return 'Approved';
            }
            if (value == 'R') {
                return 'Rejected';
            }
        },

        getStatusClass: function (value) {
            if (value == 'P') {
                return 'badge badge-warning';
            }
            if (value == 'R') {
                return 'badge badge-danger';
            }
            if (value == 'A') {
                return 'badge badge-success';
            }
        },

        clearForm: function () {
            this.addForm = {};
        },
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
            } else {
                return true;
            }
        },

        async addLeave() {
            this.validationErrors = [];
            if (this.addForm.from == "" || typeof this.addForm.from == 'undefined')
                return swal({
                    title: "Required!",
                    text: "From/Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.to == "" || typeof this.addForm.to == 'undefined')
                return swal({
                    title: "Required!",
                    text: "To/End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.addForm.reason == "" || typeof this.addForm.reason == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Please Enter Leave Reason ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resLeaveAdd = await this.callApi("post", "hrm/leave/store", this.addForm);
            console.log(resLeaveAdd)
            if (resLeaveAdd.status == 201) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Leave Application Successfully Submitted!",
                    icon: "success",
                    timer: 2000
                });
                $("#leave_table").DataTable().destroy();
                await this.fetchLeaves();
                this.clearForm();

            } else {
                if (resLeaveAdd.status == 422) {
                    this.loading = false;
                    for (const key in resLeaveAdd.data.errors) {
                        resLeaveAdd.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateLeaves() {
            this.validationErrors = [];
            if (this.dataEdit.from == "" || typeof this.dataEdit.from == 'undefined')
                return swal({
                    title: "Required!",
                    text: "From/Start Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.to == "" || typeof this.dataEdit.to == 'undefined')
                return swal({
                    title: "Required!",
                    text: "To/End Date is Required",
                    icon: "error",
                    timer: 2000
                });
            if (this.dataEdit.reason == "" || typeof this.dataEdit.reason == 'undefined')
                return swal({
                    title: "Required!",
                    text: "Please Enter Leave Reason ",
                    icon: "error",
                    timer: 2000
                });
            this.loading = true;
            const resLeaveUpdate = await this.callApi("post", 'hrm/leave/update', this.dataEdit);
            console.log(resLeaveUpdate);
            if (resLeaveUpdate.status == 200) {
                $(".modal").click();
                this.loading = false;
                swal({
                    title: "Success!",
                    text: "Leave Application Updated Successfully",
                    icon: "success",
                    timer: 2000
                });
                $("#leave_table").DataTable().destroy();
                await this.fetchLeaves();
            } else {
                if (resLeaveUpdate.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in resLeaveUpdate.data.errors) {
                        resLeaveUpdate.data.errors[key].forEach((element) => {
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

        async deleteModal(emp, i) {
            const deletingObj = {
                url: "hrm/leave/delete",
                data: emp,
                index: i,
            }
            this.$store.commit("setDeleteObj", deletingObj);
        },

        editLeave(leaveEdit) {
            this.dataEdit = leaveEdit
        },
    },
    computed: {
        ...mapGetters(['getDeletingObj'])
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.leaves.splice(obj.index, 1)
                $("#leave_table").DataTable().destroy();
                this.fetchLeaves();
            }
        }
    }
};
</script>
<style scoped>
</style>
