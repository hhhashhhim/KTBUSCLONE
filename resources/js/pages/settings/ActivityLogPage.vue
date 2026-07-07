<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Activity Log</h4>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="fetchLogs">
                                <div class="row px-2 mb-4 align-items-end">
                                    <div class="col-md-4">
                                        <label>Activity By</label>
                                        <input type="text" class="form-control" v-model="filters.activity_by"
                                            placeholder="Name or email">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Message</label>
                                        <input type="text" class="form-control" v-model="filters.message"
                                            placeholder="Search message">
                                    </div>
                                    <!-- <div class="col-md-2">
                                        <label>Host/IP</label>
                                        <input type="text" class="form-control" v-model="filters.requested_host"
                                            placeholder="Host/IP">
                                    </div>
                                    <div class="col-md-2">
                                        <label>From Date</label>
                                        <input type="date" class="form-control" v-model="filters.from_date">
                                    </div>
                                    <div class="col-md-2">
                                        <label>To Date</label>
                                        <input type="date" class="form-control" v-model="filters.to_date">
                                    </div> -->
                                    <div class="col-md-4 mt-3 d-flex justify-content-end">
                                        <button type="submit" class="w-100 btn btn-primary mr-2" :disabled="tableLoading">
                                            {{ tableLoading ? 'Loading...' : 'Search' }}
                                        </button>
                                        <button type="button" class="w-100 btn btn-secondary" @click="resetFilters"
                                            :disabled="tableLoading">
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table dataTables table-striped table-hover"
                                                    id="ticket_templates">
                                                    <thead>
                                                        <tr>
                                                            <th style="min-width:200px">Activity By</th>
                                                            <th style="min-width:200px">Time</th>
                                                            <th style="min-width:140px">Host/IP</th>
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(log, i) in logs" :key="i">
                                                            <td>{{ log.activity ? log.activity.email : 'N/A' }}</td>
                                                            <td>{{ log.formatted_created_at }}</td>
                                                            <td>{{ log.requested_host || 'N/A' }}</td>
                                                           <td>{{ log.message }}</td>
                                                        </tr>
                                                        <tr v-if="tableLoading">
                                                            <td class="text-center" colspan="4">
                                                                <img class="loading-spinner" src="http://www.digitisingascent.com/cpadmin/assets/admin/layout/img/loading-spinner-blue.gif" />
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

           
            
        </div>
    </section>
</template>

<script>
import { mapGetters } from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "activityLog",
    components: {
    },
    data() {
        return {
            permissions: [],
            logs: [],
            loading: false,
            tableLoading: false,
            filters: {
                activity_by: '',
                message: '',
                requested_host: '',
                from_date: '',
                to_date: '',
            },
            data: {
                start_from : 0,
            },
            loadingEdit: false,
            validationErrors: [],
        };
    },
    async created() {
        $('.modal').remove();
        this.fetchLogs();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }
        this.permissions = this.$store.state.permissions;
    },
    methods: {
        async fetchLogs() {
            this.tableLoading = true;
            const resLogs = await this.callApi("post", 'settings/activity/logs', this.filters);
            if (resLogs.status == 200) {
                this.logs = resLogs.data;
                this.tableLoading = false;
            }
            if (resLogs.status == 422) {
                this.tableLoading = false;
                console.log(resTicketTemplate)
            }

            // setTimeout(function () {
            //     $("#ticket_templates").DataTable();
            // }, 300);
        },
        resetFilters() {
            this.filters = {
                activity_by: '',
                message: '',
                requested_host: '',
                from_date: '',
                to_date: '',
            };
            this.fetchLogs();
        },
    },
};
</script>
<style scoped></style>
