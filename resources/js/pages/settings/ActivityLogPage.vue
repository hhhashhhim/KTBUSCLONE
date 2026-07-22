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
                                    <div class="col-md-3">
                                        <label>Activity By</label>
                                        <input type="text" class="form-control" v-model="filters.activity_by"
                                            placeholder="Name or email">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Message</label>
                                        <input type="text" class="form-control" v-model="filters.message"
                                            placeholder="Search message">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="activity-from-date">From Date <span class="text-danger">*</span></label>
                                        <input id="activity-from-date" type="date" class="form-control"
                                            v-model="filters.from_date" :max="filters.to_date || undefined" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="activity-to-date">To Date <span class="text-danger">*</span></label>
                                        <input id="activity-to-date" type="date" class="form-control"
                                            v-model="filters.to_date" :min="filters.from_date || undefined" required>
                                    </div>
                                    <div class="col-md-6 mt-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mr-2 px-4"
                                            :class="{ 'btn-progress': tableLoading }" :disabled="tableLoading">
                                            Search
                                        </button>
                                        <button type="button" class="btn btn-secondary px-4" @click="resetFilters"
                                            :disabled="tableLoading">
                                            Reset
                                        </button>
                                    </div>
                                    <div class="col-md-6 mt-3 text-md-right" v-if="searchPerformed">
                                        <label class="mb-0">
                                            Rows per page
                                            <select class="form-control d-inline-block ml-2 per-page-select"
                                                v-model.number="filters.per_page" @change="fetchLogs(1)"
                                                :disabled="tableLoading">
                                                <option :value="10">10</option>
                                                <option :value="25">25</option>
                                                <option :value="50">50</option>
                                                <option :value="100">100</option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                            </form>
                            <div v-if="dateError" class="alert alert-danger">{{ dateError }}</div>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="min-width:200px">Activity By</th>
                                                            <th style="min-width:200px">Time</th>
                                                            <th style="min-width:140px">Host/IP</th>
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="log in logs" :key="log.id">
                                                            <td>{{ log.activity ? log.activity.email : 'N/A' }}</td>
                                                            <td>{{ log.formatted_created_at }}</td>
                                                            <td>{{ log.requested_host || 'N/A' }}</td>
                                                           <td>{{ log.message }}</td>
                                                        </tr>
                                                        <tr v-if="tableLoading">
                                                            <td class="text-center" colspan="4">
                                                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                                            </td>
                                                        </tr>
                                                        <tr v-else-if="!searchPerformed">
                                                            <td class="text-center text-muted py-4" colspan="4">
                                                                Select a From Date and To Date, then press Search.
                                                            </td>
                                                        </tr>
                                                        <tr v-else-if="logs.length === 0">
                                                            <td class="text-center text-muted py-4" colspan="4">
                                                                No activity logs found for the selected dates.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div v-if="searchPerformed && pagination.total" class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                                                <div class="text-muted mb-2">
                                                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                                                </div>
                                                <nav aria-label="Activity log pagination" class="mb-2">
                                                    <ul class="pagination mb-0">
                                                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 || tableLoading }">
                                                            <button type="button" class="page-link" @click="fetchLogs(pagination.current_page - 1)">Previous</button>
                                                        </li>
                                                        <li v-for="page in visiblePages" :key="page" class="page-item"
                                                            :class="{ active: page === pagination.current_page }">
                                                            <button type="button" class="page-link" @click="fetchLogs(page)">{{ page }}</button>
                                                        </li>
                                                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page || tableLoading }">
                                                            <button type="button" class="page-link" @click="fetchLogs(pagination.current_page + 1)">Next</button>
                                                        </li>
                                                    </ul>
                                                </nav>
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
            searchPerformed: false,
            dateError: "",
            filters: {
                activity_by: '',
                message: '',
                requested_host: '',
                from_date: '',
                to_date: '',
                per_page: 25,
            },
            pagination: {
                current_page: 1,
                last_page: 1,
                from: 0,
                to: 0,
                total: 0,
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
    computed: {
        visiblePages() {
            const start = Math.max(1, this.pagination.current_page - 2);
            const end = Math.min(this.pagination.last_page, start + 4);
            const adjustedStart = Math.max(1, end - 4);
            return Array.from({ length: end - adjustedStart + 1 }, (_, index) => adjustedStart + index);
        },
    },
    methods: {
        async fetchLogs(page = 1) {
            if (page < 1 || page > this.pagination.last_page && this.searchPerformed) return;
            this.dateError = "";
            if (!this.filters.from_date || !this.filters.to_date) {
                this.dateError = "Please select both From Date and To Date.";
                return;
            }
            if (this.filters.from_date > this.filters.to_date) {
                this.dateError = "To Date must be the same as or later than From Date.";
                return;
            }

            this.tableLoading = true;
            const resLogs = await this.callApi("post", 'settings/activity/logs', {
                ...this.filters,
                page,
            });
            if (resLogs.status == 200) {
                this.logs = resLogs.data.data;
                this.pagination = {
                    current_page: resLogs.data.current_page,
                    last_page: resLogs.data.last_page,
                    from: resLogs.data.from || 0,
                    to: resLogs.data.to || 0,
                    total: resLogs.data.total,
                };
                this.searchPerformed = true;
            }
            if (resLogs.status == 422) {
                this.dateError = "Please check the selected date range.";
            }
            this.tableLoading = false;
        },
        resetFilters() {
            this.filters = {
                activity_by: '',
                message: '',
                requested_host: '',
                from_date: '',
                to_date: '',
                per_page: 25,
            };
            this.logs = [];
            this.searchPerformed = false;
            this.dateError = "";
            this.pagination = { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
        },
    },
};
</script>
<style scoped>
.per-page-select {
    width: 80px;
}

th {
    white-space: nowrap;
}
</style>
