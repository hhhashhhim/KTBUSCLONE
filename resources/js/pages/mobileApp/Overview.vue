<template>
    <div>
        <div class="ma-panel"><div class="ma-panel-head"><div><h2>Overview</h2><p class="ma-muted">Bookings created in the selected period. Financial values come from ERP records.</p></div></div><MobileFilters id="ma-overview" v-model="filters" :options="options" dates environments terminals :busy="loading" @apply="load(true)" /></div>
        <div v-if="error" class="ma-error" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm ml-2" @click="load()">Retry</button></div>
        <div class="ma-kpis" :aria-busy="loading">
            <article class="ma-kpi" v-for="metric in metrics" :key="metric.key"><i :class="metric.icon"></i><span>{{ metric.label }}</span><strong>{{ loading ? '…' : metric.money ? money(data?.metrics?.[metric.key]) : number(data?.metrics?.[metric.key]) }}</strong><small>{{ metric.note }}</small></article>
        </div>
        <LoginActivity :scope="applied" />
        <p v-for="(notice, index) in notices" :key="index" class="ma-note">{{ notice }}</p>
        <div v-if="options.permissions.bookings_view" class="ma-panel"><div class="ma-panel-head"><div><h2>Recent mobile bookings</h2><p class="ma-muted">Latest bookings within your permitted terminal scope.</p></div><router-link v-if="options.permissions.bookings_view" :to="{ name: 'mobile-app-bookings' }" class="btn btn-outline-success btn-sm">View bookings</router-link></div>
            <div class="ma-table-wrap"><table class="ma-table"><thead><tr><th>Booking</th><th>Passenger</th><th>Journey</th><th>Seats</th><th>Status</th><th class="ma-numeric">Fare</th></tr></thead><tbody><tr v-if="loading"><td colspan="6" class="ma-empty" role="status">Loading bookings…</td></tr><template v-else><tr v-for="booking in data?.recent_bookings || []" :key="booking.id"><td><router-link v-if="options.permissions.bookings_view" :to="{name:'mobile-app-bookings',query:{booking:String(booking.invoice_id)}}">{{ booking.invoice_id }}</router-link><strong v-else>{{ booking.invoice_id }}</strong><small>{{ time(booking.created_at) }}</small></td><td>{{ booking.passenger_name }}<small>{{ booking.passenger_mobile }}</small></td><td>{{ booking.route_name || booking.departure }}<small>{{ booking.travel_date }} · {{ booking.travel_time }}</small></td><td>{{ booking.seat_numbers?.join(', ') || '—' }}</td><td><span class="ma-badge" :class="booking.status">{{ label(booking.status) }}</span></td><td class="ma-numeric">{{ money(booking.total_fare) }}</td></tr><tr v-if="!data?.recent_bookings?.length"><td colspan="6" class="ma-empty">No mobile bookings in this period.</td></tr></template></tbody></table></div>
        </div>
    </div>
</template>
<script>
import MobileFilters from './MobileFilters.vue';
import LoginActivity from './LoginActivity.vue';
import { request, today, number, money, label, displayTime } from './mobileApp';
export default {
    components: { MobileFilters, LoginActivity }, props: { options: Object },
    data: () => ({ filters: { from: today(), to: today(), environment: 'live', terminal_id: '' }, applied: null, data: null, loading: false, error: '', sequence: 0, metrics: [
        { key: 'users', label: 'App users', icon: 'fas fa-users', note: 'Accounts created in the selected period' },
        { key: 'total_bookings', label: 'Mobile bookings', icon: 'fas fa-ticket-alt', note: 'Created in the selected period' },
        { key: 'active_seats', label: 'Active seats', icon: 'fas fa-chair', note: 'Seats still held or confirmed' },
        { key: 'confirmed_sales', label: 'Confirmed sales', icon: 'fas fa-chart-line', note: 'ERP confirmed ticket fares', money: true },
        { key: 'pending_payments', label: 'Pending payments', icon: 'fas fa-clock', note: 'Awaiting payment verification' },
        { key: 'payment_exceptions', label: 'Payment exceptions', icon: 'fas fa-exclamation-triangle', note: 'Transactions requiring review' },
    ] }),
    computed: { notices() { return (this.data?.notices || []).map(item => typeof item === 'string' ? item : item.message).filter(Boolean); } },
    mounted() { this.load(true); }, beforeUnmount() { this.sequence++; },
    methods: { number, money, label, time: displayTime, async load(apply = false) { if (apply || !this.applied) this.applied = { ...this.filters }; const sequence = ++this.sequence; this.loading = true; this.error = ''; this.data = null; try { const data = await request(this, 'get', 'overview', null, this.applied); if (sequence === this.sequence) this.data = data; } catch (error) { if (sequence === this.sequence) this.error = error.message; } finally { if (sequence === this.sequence) this.loading = false; } } },
};
</script>
