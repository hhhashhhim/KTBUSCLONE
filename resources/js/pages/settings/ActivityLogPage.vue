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
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(log, i) in logs" :key="i">
                                                            <td>{{ log.activity ? log.activity.email : 'N/A' }}</td>
                                                            <td>{{ log.formatted_created_at }}</td>
                                                           <td>{{ log.message }}</td>
                                                        </tr>
                                                        <tr v-if="tableLoading">
                                                            <td class="text-center" colspan="3">
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
            const resLogs = await this.callApi("post", 'settings/activity/logs');
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
    },
};
</script>
<style scoped></style>
