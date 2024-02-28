<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Schedule Drop Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="schedule_drop">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Schedule</th>
                                                        <th>Schedule Date</th>
                                                        <th>Route</th>
                                                        <th>Drop By</th>
                                                        <th>Drop Time</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(drop,i) in dropSchedules" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td>{{ drop.schedule.name }}</td>
                                                        <td>{{ drop.schedule_date }}</td>
                                                        <td>{{ drop.schedule.route.name }}</td>
                                                        <td>{{ drop.added_by.name }}</td>
                                                        <td>{{ drop.time }}</td>
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

            

        </div>
    </section>


</template>

<script>
import {mapGetters} from 'vuex';

export default {
    name: "ScheduleDropReportPage",
    components: {
    },
    data() {
        return {
            validationErrors: [],
            dropSchedules: [],
            loading: false,
            formID: 'schedule_drop',
            editFormID: 'edit_schedule_drop',
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
            const resReport = await this.callApi("post", 'report/schedules/drop');
            if (resReport.status == 200) {
                this.dropSchedules = resReport.data;
            }
            setTimeout(function () {
                $("#schedule_drop").DataTable();
            }, 300);
        },
    },
}
</script>
