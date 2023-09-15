<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Schedule Unclosing Detail</h4>
                        </div>
                        <div class="card-body">
                            
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-end">
                                                <button class="btn btn-primary" @click="mergeSchedule()">
                                                    Merge Schedule
                                                </button>
                                            </div>
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
                                                        <th v-if="checkForSubmenuButtons('edit-close-booking')">Action
                                                        </th>
                                                        <!-- <th>Expense</th> -->
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <template v-for="(data, i) in closings" :key="i">
                                                        <tr v-for="(close, j) in data" :key="j">
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-left border-bottom border-success' : 'border-left border-top border-success' : 'border-left border-bottom border-top border-danger'">
                                                                {{ close.bus.bus_number }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule.route.name }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_date }}
                                                            </td>
                                                            <td class="h5"
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-success' : 'border-top border-success' : 'border-bottom border-top border-danger'">
                                                                {{ close.schedule_time }}
                                                            </td>
                                                            <td
                                                                :class="data.length == 2 ? j == 1 ? 'border-bottom border-right border-success' : 'border-right border-top border-success' : 'border-bottom border-right border-top border-danger'">
                                                                 <input type="checkbox" 
                                                                        id="femaleCheckBox"
                                                                        :checked="addData.mergeIds.includes(close.ticket_merge_id)"
                                                                        @click="changeClosingId(close)"
                                                                        name="">
                                                            </td>
                                                        </tr>
                                                    </template>
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
        </div>
    </section>
</template>

<script>

import {mapGetters} from "vuex";

export default {
    name: "unclosing",
    components: {
    },
    data() {
        return {
            loading: false,
            closings: [],
            permissions: [],
            validationErrors: "",
            formID: "schedule_closing_form",
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
        changeClosingId: function (close) {

            if(this.addData.mergeIds.includes(close.ticket_merge_id))
            {
                this.addData.mergeIds.splice(this.addData.mergeIds.indexOf(close.ticket_merge_id), 1);   
                this.addData.busIds.splice(this.addData.busIds.indexOf(close.bus_id), 1);   
            }
            else
            {
                if(this.addData.mergeIds.length == 2)
                {
                    this.closings = [];
                    this.fetchData();
                    return swal({
                        title: "Required",
                        text: "You can't select more than two",
                        icon: 'error',
                        timer: 2000
                    });
                }
                this.addData.mergeIds.push(close.ticket_merge_id);
                this.addData.busIds.push(close.bus_id);
            }
            console.log(this.addData);
        },
        async fetchData() {
            const res = await this.callApi("post", "booking/close/schedule/unclosing");
            if (res.status == 200) {
                this.closings = res.data.closings;
            } else {
                console.log(res);
            }
        },

        async mergeSchedule() {
            this.validationErrors = [];
            if (this.addData.busIds[0] != this.addData.busIds[1])
                return swal({
                    title: "Required",
                    text: "Please select same buses",
                    icon: 'error',
                    timer: 2000
                });
            if (this.addData.mergeIds.length != 2)
                return swal({
                    title: "Required",
                    text: "Please select two schedule",
                    icon: 'error',
                    timer: 2000
                });
            this.loadig = true;
            const res = await this.callApi("post", "booking/close/schedule/closing/merge", this.addData);
            if (res.status == 200) {
                this.loading = false;
                swal({
                    title: "Success",
                    text: "Schedule Merge Successfully",
                    icon: "success",
                    timer: 2000
                });
                this.addData.mergeIds = [];
                this.fetchData();

            } else {
                if (res.status == 422) {
                    this.loading = false;
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
        editSchedule(schedule) {
            
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

