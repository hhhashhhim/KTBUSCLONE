<template>
    <section class="section dashboard-page">
        <header class="dashboard-header">
            <div>
                <div class="eyebrow">Booking Performance</div>
                <h2>{{ isToday ? 'Today Overview' : 'Dashboard Overview' }}</h2>
                <p>{{ periodLabel }} · Data is calculated only for the selected period</p>
            </div>
            <form class="date-toolbar" @submit.prevent="fetchData">
                <div class="date-field">
                    <label for="dashboard-from">From</label>
                    <input id="dashboard-from" v-model="filters.from_date" type="date"
                        class="form-control" :max="filters.to_date" required>
                </div>
                <div class="date-field">
                    <label for="dashboard-to">To</label>
                    <input id="dashboard-to" v-model="filters.to_date" type="date"
                        class="form-control" :min="filters.from_date" required>
                </div>
                <button type="submit" class="btn btn-success filter-button"
                    :class="{ 'btn-progress': loading }" :disabled="loading">
                    <i class="fas fa-filter mr-1"></i> Apply
                </button>
            </form>
        </header>

        <div v-if="error" class="alert alert-danger dashboard-alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ error }}
        </div>

        <div class="kpi-grid" :class="{ 'is-loading': loading }">
            <article v-for="card in cards" :key="card.key" class="kpi-card" :class="`tone-${card.tone}`">
                <div class="kpi-icon"><i :class="card.icon"></i></div>
                <div class="kpi-body">
                    <span class="kpi-label">{{ card.label }}</span>
                    <strong class="kpi-value">{{ formatValue(card) }}</strong>
                    <div class="kpi-period"><i class="far fa-calendar-alt"></i> {{ periodLabel }}</div>
                </div>
            </article>
        </div>

        <div class="schedule-panel">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Operations</span>
                    <h3>Schedules for {{ displayDate(filters.to_date) }}</h3>
                </div>
                <div class="schedule-count">
                    <strong>{{ schedules.length }}</strong>
                    <span>Schedules</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table dashboard-table">
                    <thead>
                        <tr>
                            <th>Schedule</th>
                            <th>Booked Seats</th>
                            <th>Date</th>
                            <th>Departure</th>
                            <th>Occupancy</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="table-state">
                                <i class="fas fa-circle-notch fa-spin"></i> Loading dashboard data…
                            </td>
                        </tr>
                        <tr v-for="schedule in schedules" :key="schedule.id">
                            <td><strong>{{ schedule.name }}</strong></td>
                            <td>{{ number(schedule.booked_seats) }} / {{ number(schedule.total_seat) }}</td>
                            <td>{{ schedule.schedule_date }}</td>
                            <td>{{ schedule.departure_time }}</td>
                            <td class="occupancy-cell">
                                <div class="progress-meta"><span>{{ schedule.progress }}%</span></div>
                                <div class="progress dashboard-progress">
                                    <div class="progress-bar" :class="progressTone(schedule.progress)"
                                        :style="{ width: `${Math.min(100, schedule.progress)}%` }"></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill" :class="schedule.drop ? 'status-dropped' : 'status-active'">
                                    {{ schedule.drop ? 'Dropped' : 'Active' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!loading && !schedules.length">
                            <td colspan="6" class="table-state">
                                <i class="far fa-calendar-times"></i> No schedules found for this date.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script>
let activeDashboardRequest = null;
let activeDashboardRequestKey = "";

export default {
    name: "Dashboard",
    data() {
        const today = this.localDate();
        return {
            loading: false,
            error: "",
            cart: {},
            schedules: [],
            filters: { from_date: today, to_date: today },
            range: {},
        };
    },
    computed: {
        isToday() {
            const today = this.localDate();
            return this.filters.from_date === today && this.filters.to_date === today;
        },
        periodLabel() {
            return this.filters.from_date === this.filters.to_date
                ? this.displayDate(this.filters.from_date)
                : `${this.displayDate(this.filters.from_date)} – ${this.displayDate(this.filters.to_date)}`;
        },
        cards() {
            return [
                { key: "confirm", label: "Confirmed Bookings", icon: "fas fa-check-circle", tone: "green" },
                { key: "reserve", label: "Reserved Bookings", icon: "fas fa-clock", tone: "blue" },
                { key: "cancel", label: "Canceled Bookings", icon: "fas fa-times-circle", tone: "red" },
                { key: "overissue", label: "Overissued Bookings", icon: "fas fa-exclamation-triangle", tone: "orange" },
                { key: "new_customers", label: "New Customers", icon: "fas fa-user-plus", tone: "cyan" },
                { key: "old_customers", label: "Repeat Customers", icon: "fas fa-users", tone: "purple" },
                { key: "discount", label: "Total Discount", icon: "fas fa-tags", tone: "pink", money: true },
                { key: "sale", label: "Net Sales", icon: "fas fa-chart-line", tone: "indigo", money: true },
            ];
        },
    },
    created() {
        this.fetchData();
    },
    methods: {
        localDate(date = new Date()) {
            const offset = date.getTimezoneOffset() * 60000;
            return new Date(date.getTime() - offset).toISOString().slice(0, 10);
        },
        displayDate(value) {
            if (!value) return "—";
            const [year, month, day] = value.split("-").map(Number);
            return new Intl.DateTimeFormat("en-GB", { day: "2-digit", month: "short", year: "numeric" })
                .format(new Date(year, month - 1, day));
        },
        number(value) {
            return this.$insertComma(Number(value || 0));
        },
        formatValue(card) {
            const value = this.cart[`today_${card.key}`] || 0;
            return card.money ? `PKR ${this.number(Math.round(value))}` : this.number(value);
        },
        progressTone(progress) {
            if (progress >= 80) return "bg-danger";
            if (progress >= 50) return "bg-warning";
            return "bg-success";
        },
        async fetchData() {
            this.error = "";
            if (!this.filters.from_date || !this.filters.to_date) {
                this.error = "Please select both From and To dates.";
                return;
            }
            if (this.filters.from_date > this.filters.to_date) {
                this.error = "To date must be the same as or later than From date.";
                return;
            }

            this.loading = true;
            try {
                const requestKey = `${this.filters.from_date}:${this.filters.to_date}`;
                if (!activeDashboardRequest || activeDashboardRequestKey !== requestKey) {
                    activeDashboardRequestKey = requestKey;
                    activeDashboardRequest = this.callApi("post", "terminals/dashboard/data", { ...this.filters });
                }

                const currentRequest = activeDashboardRequest;
                const res = await currentRequest;
                if (activeDashboardRequest === currentRequest) {
                    activeDashboardRequest = null;
                    activeDashboardRequestKey = "";
                }
                if (res.status === 200) {
                    this.cart = res.data.cart || {};
                    this.schedules = res.data.schedules || [];
                    this.range = res.data.range || {};
                } else if (res.status === 403) {
                    this.error = "You do not have permission to view dashboard data.";
                } else {
                    this.error = "Dashboard data could not be loaded.";
                }
            } catch (error) {
                this.error = "Dashboard data could not be loaded. Please try again.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.dashboard-page { padding: 8px 0 28px; }
.dashboard-header { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; padding:20px 22px; margin-bottom:18px; border:1px solid #dfe7f1; border-radius:12px; background:linear-gradient(135deg,#fff 0%,#eef8f2 100%); box-shadow:0 10px 25px rgba(31,41,55,.07); }
.eyebrow,.panel-kicker { color:#28a745; font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
.dashboard-header h2 { margin:3px 0 4px; color:#172033; font-size:27px; font-weight:800; }
.dashboard-header p { margin:0; color:#697386; font-size:13px; }
.date-toolbar { display:flex; align-items:flex-end; gap:10px; }
.date-field label { display:block; margin-bottom:5px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; }
.date-field .form-control { min-width:145px; border-color:#d7e0ea; }
.filter-button { min-width:92px; height:42px; }
.dashboard-alert { border-radius:9px; }
.kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:15px; margin-bottom:18px; transition:opacity .2s; }
.kpi-grid.is-loading { opacity:.58; }
.kpi-card { display:flex; gap:14px; min-width:0; padding:18px; border:1px solid #e1e8f0; border-radius:12px; background:#fff; box-shadow:0 8px 20px rgba(15,23,42,.06); }
.kpi-icon { display:flex; align-items:center; justify-content:center; flex:0 0 46px; width:46px; height:46px; border-radius:12px; font-size:19px; }
.kpi-body { min-width:0; flex:1; }
.kpi-label { display:block; overflow:hidden; color:#667085; font-size:12px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
.kpi-value { display:block; margin:3px 0 9px; color:#172033; font-size:22px; line-height:1.2; }
.kpi-period { padding-top:8px; border-top:1px solid #edf1f5; color:#8a94a6; font-size:11px; white-space:nowrap; }
.tone-green .kpi-icon { background:#e8f8ee; color:#20a050; }.tone-blue .kpi-icon { background:#eaf2ff; color:#3478db; }.tone-red .kpi-icon { background:#fff0f0; color:#e24b4b; }.tone-orange .kpi-icon { background:#fff5e7; color:#e58a16; }
.tone-cyan .kpi-icon { background:#e8f9fb; color:#1596a6; }.tone-purple .kpi-icon { background:#f3edff; color:#7c55c7; }.tone-pink .kpi-icon { background:#fff0f6; color:#d94d87; }.tone-indigo .kpi-icon { background:#eef0ff; color:#5661d8; }
.schedule-panel { overflow:hidden; border:1px solid #e1e8f0; border-radius:12px; background:#fff; box-shadow:0 8px 20px rgba(15,23,42,.06); }
.panel-head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #e8edf3; }
.panel-head h3 { margin:2px 0 0; color:#172033; font-size:18px; font-weight:800; }
.schedule-count { display:flex; align-items:center; gap:8px; padding:7px 11px; border-radius:9px; background:#f2f8f4; color:#697386; font-size:11px; }
.schedule-count strong { color:#28a745; font-size:18px; }
.dashboard-table { margin:0; }
.dashboard-table thead th { border-top:0; border-bottom:1px solid #e5eaf0; background:#f7f9fc; color:#667085; font-size:11px; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
.dashboard-table td { vertical-align:middle; color:#4b5563; font-size:13px; }
.occupancy-cell { min-width:150px; }.progress-meta { margin-bottom:4px; color:#667085; font-size:11px; text-align:right; }.dashboard-progress { height:6px; border-radius:10px; background:#edf1f5; }
.status-pill { display:inline-block; min-width:68px; padding:5px 9px; border-radius:999px; font-size:11px; font-weight:800; text-align:center; }.status-active { background:#e7f8ed; color:#198b42; }.status-dropped { background:#ffeded; color:#d43e3e; }
.table-state { padding:36px !important; color:#8a94a6 !important; text-align:center; }.table-state i { margin-right:6px; }
@media (max-width:1199.98px) { .kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .dashboard-header { align-items:flex-start; flex-direction:column; }.date-toolbar { width:100%; }.date-field { flex:1; }.date-field .form-control { width:100%; } }
@media (max-width:767.98px) { .kpi-grid { grid-template-columns:1fr; }.date-toolbar { align-items:stretch; flex-direction:column; }.filter-button { width:100%; }.dashboard-header { padding:17px; }.panel-head { align-items:flex-start; gap:12px; } }
</style>
