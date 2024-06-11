<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Close Trip Summary Report</h4>
                            <div class="card-header-action">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <form :action="$store.state.api_url + 'api/web/v1/reports/reportExport'"
                                                  target="_blank"
                                                  method="POST" ref="refDailySummeryReport">
                                                <input type="hidden" name="token" :value="$store.state.token">
                                                <input type="hidden" name="language" id="languageReport">
                                                <div class="row">
                                                    <div class="col-md-4">
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
                                                    <!-- <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="scheduleRoute">Route</label>
                                                            <select class="form-control" id="scheduleRoute"
                                                                    name="route">
                                                                <option value="0" selected>Select Route</option>
                                                                <option class="text-uppercase"
                                                                        v-for="(route, i) in routes"
                                                                        :value="route.id"
                                                                        :key="i"
                                                                >{{ route.name }}  ({{ route.via??'n/a' }})
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div> -->
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="fromDate">From Date</label>
                                                            <input type="date" id="fromDate" class="form-control"
                                                                   name="fromDate"
                                                                   :max="maxDateFilterReport()">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="toDate">To Date</label>
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
                                                    <button class="btn btn-outline-danger mr-2"
                                                            @click="getSummeryReport('urdu')">Final Closing Report
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
            routes: [],
            buses: [],
            success: false,
            errors: false,
        }
    },
    async created() {
        $('.modal').remove();
        this.fetchDailySummaryReport();
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
        async fetchDailySummaryReport() {
            const resGetSchedule = await this.callApi("post", 'reports/getRoutes');
            const resGetBuses = await this.callApi("post", 'reports/getBuses');
            if (resGetSchedule.status == 200 && resGetBuses.status == 200) {
                this.routes = resGetSchedule.data;
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
            if ($("#fromDate").val() == "" || $("#toDate").val() == "")
                return swal({
                    title: "Required",
                    text: "Enter Date Range",
                    icon: "error",
                    timer: 2000
                });
            $("#languageReport").val(value);
            this.$refs.refDailySummeryReport.submit();

        }
    },
}
</script>
