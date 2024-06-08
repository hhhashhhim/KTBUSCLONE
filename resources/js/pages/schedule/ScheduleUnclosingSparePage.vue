<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Spare Unclosing Detail</h4>
                        </div>
                        <div class="card-body">
                            
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- <div class="d-flex justify-content-end">
                                                <button class="btn btn-primary" :disabled="loading" @click="mergeSchedule()">
                                                    Merge Schedule
                                                </button>
                                            </div> -->
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="closing_table"
                                                    style="border-collapse: separate;
                                                    border-spacing: 0 10px;"
                                                >
                                                    <thead>
                                                    <tr>
                                                        <th>Bus Number</th>
                                                        <th>Schedule</th>
                                                        <th>Route Name</th>
                                                        <th>Schedule Date</th>
                                                        <th>Schedule Time</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(close, j) in closings" :key="j">
                                                        <td>
                                                            {{ close.bus.bus_number }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule.name }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule.route.name }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule_date }}
                                                        </td>
                                                        <td>
                                                            {{ close.schedule_time }}
                                                        </td>
                                                        <td>
                                                            <button title="Revert Unclosing"
                                                                    :data-target="'#' + hideFormID" @click="delId = close.id" data-toggle="modal"
                                                                    class="btn btn-primary btn-sm mx-2"
                                                            >
                                                            Revert
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="closings.length==0">
                                                        <td class="text-center" colspan="6">No data found</td>
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
            <Hide :hideForm="hideFormID" confirmationMessage="Are You Sure You want To Revert This Closing ???">
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                       :disabled="loading" @click="revertUnclosing"
                    >
                    {{ loading ? 'Loading...' : 'Yes, I want to Revert' }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>

<script>

import {mapGetters} from "vuex";
import Hide from "../../components/Hide.vue";
export default {
    name: "ScheduleUnclosingSparePage",
    components: {
        Hide
    },
    data() {
        return {
            loading: false,
            closings: [],
            permissions: [],
            validationErrors: "",
            formID: "schedule_closing_form",
            hideFormID: "hide_schedule_form",
            delId: "",
            seatNo: 0,
            addData: {
                mergeIds: [],
                busIds: [],
            },
            success: false,
            errors: false,
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

        this.fetchData();
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        clearForm: function () {
            this.data = {};
        },
        async fetchData() {
            const res = await this.callApi("post", "booking/close/schedule/unclosing/spare");
            if (res.status == 200) {
                this.closings = res.data.closings;
            } else {
                console.log(res);
            }
        },

        async revertUnclosing() {
            
            this.loading = true;
            const resHide = await this.callApi("post", 'booking/close/schedule/unclosing/revert', {id:this.delId});
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Unclosing Deleted Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.loading = false;
                this.fetchData();
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
    },
    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.buses.splice(obj.index, 1);
                $('#closing_table').DataTable().destroy();
                this.fetchBuses();
            }
        },
    },
};
</script>
<style src="@vueform/multiselect/themes/default.css"></style>

