<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Close Trip Summery Report</h4>
                            <div class="card-header-action">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <form :action="$store.state.app_url + 'reports/reportExport'"
                                                  target="_blank"
                                                  method="POST" ref="refDailySummeryReport">
                                                <input type="hidden" name="_token" v-bind:value="csrf">
                                                <input type="hidden" name="language" id="languageReport">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="busNo">Bus No</label>
                                                            <select class="form-control" id="busNo" name="busNO">
                                                                <option value="0" selected>Select Bus</option>
                                                                <option
                                                                    v-for="(bus, i) in buses"
                                                                    :value="bus.id"
                                                                    :key="i"
                                                                >{{ bus.bus_number }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="scheduleRoute">Route</label>
                                                            <select class="form-control" id="scheduleRoute"
                                                                    name="schedule">
                                                                <option value="0" selected>Select Schedule</option>
                                                                <option class="text-uppercase"
                                                                        v-for="(schedule, i) in schedules"
                                                                        :value="schedule.id"
                                                                        :key="i"
                                                                >{{ schedule.name }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="fromDate">From Date</label>
                                                            <input type="date" id="fromDate" class="form-control"
                                                                   name="fromDate"
                                                                   :max="maxDateFilterReport()">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="toDate">To Date <span
                                                                class="text-danger ml-1">*</span></label>
                                                            <input type="date" id="toDate" class="form-control"
                                                                   name="toDate"
                                                                   :max="maxDateFilterReport()">
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                            <div class="row">
                                                <div class="card-footer">
                                                    <button class="btn btn-primary mr-2"
                                                            @click="getSummeryReport('english')">Export Report
                                                    </button>
                                                    <button class="btn btn-secondary mr-2"
                                                            @click="getSummeryReport('urdu')">Export Report
                                                        (Urdu)
                                                    </button>
                                                </div>
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
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name: "closeSummeryReport",
    components: {
        Add,
        Edit,
    },
    data() {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            validationErrors: [],
            headers: [],
            loading: false,
            formID: 'reports_header',
            editFormID: 'edit_reports_header',
            schedules: [],
            buses: [],
            success: false,
            errors: false,
        }
    },
    async created() {
        this.fetchDailySummaryReport();
    },
    methods: {
        async fetchDailySummaryReport() {
            const resGetSchedule = await this.callApi("post", 'reports/getSchedule');
            const resGetBuses = await this.callApi("post", 'reports/getBuses');
            if (resGetSchedule.status == 200 && resGetBuses.status == 200) {
                this.schedules = resGetSchedule.data;
                this.buses = resGetBuses.data;
            }
        },
        maxDateFilterReport: function () {
            const dtToday = new Date();
            let month = dtToday.getMonth() + 1;
            let day = dtToday.getDate();
            const year = dtToday.getFullYear();
            if (month < 10)
                month = '0' + month.toString();
            if (day < 10)
                day = '0' + day.toString();
            return year + '-' + month + '-' + day;
        },
        async getSummeryReport(value) {
            $("#languageReport").val(value);
            this.$refs.refDailySummeryReport.submit();

        }
    },
}
</script>
