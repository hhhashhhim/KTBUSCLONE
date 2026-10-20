<template>
    <div class="ma-panel">
        <div class="ma-panel-head"><div><h2>Routes &amp; Schedules</h2><p class="ma-muted">See what the mobile app can show, and why a departure is unavailable.</p></div><span class="ma-badge" :class="context?.confirmed ? 'active' : 'pending'">{{ context?.confirmed ? 'App connection reported' : 'ERP preview' }}</span></div>
        <form class="ma-filters" @submit.prevent="load(true)">
            <div class="ma-field"><label for="ma-availability-date">Travel date</label><input id="ma-availability-date" type="date" class="form-control" v-model="filters.date" :min="today()" required></div>
            <div class="ma-field"><label for="ma-availability-terminal">App terminal</label><select id="ma-availability-terminal" class="form-control" v-model="filters.mobile_terminal_id"><option value="">{{ context?.confirmed ? 'Reported app terminal' : 'Choose online terminal' }}</option><option v-for="terminal in context?.terminals || []" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option></select></div>
            <div v-if="!selectedRoute" class="ma-field ma-search"><label for="ma-availability-search">Route</label><input id="ma-availability-search" class="form-control" v-model="filters.search" placeholder="Search route name" maxlength="100"></div>
            <div class="ma-field"><label for="ma-availability-status">Show</label><select id="ma-availability-status" class="form-control" v-model="filters.status"><option value="">All</option><option value="allowed">Allowed</option><option value="not_allowed">Not allowed</option><option v-if="selectedRoute" value="not_open">Not open yet</option><option value="needs_setup">Needs setup</option></select></div>
            <button class="btn btn-success" :disabled="loading">{{ loading ? 'Loading…' : 'Show availability' }}</button>
        </form>
        <p v-if="context?.booking_notice" class="ma-note ma-warning">{{ context.booking_notice }}</p>
        <div v-if="context && !context.confirmed" class="ma-panel-body ma-availability-note"><p class="ma-muted">Preview the permissions for the online terminal used by your app. Its live connection and booking-opening rules have not yet been confirmed.</p></div>
        <div v-if="error" class="ma-panel-body"><p class="ma-error" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm" @click="load()">Retry</button></p></div>
        <div v-if="selectedRoute" class="ma-panel-head"><div><button class="btn btn-outline-success btn-sm mb-2" @click="back" :disabled="loading">← All routes</button><h3>{{ selectedRoute.name }}</h3><p class="ma-muted">{{ applied?.date }} · Each row is one city-to-city departure.</p></div><span v-if="data?.summary" class="ma-muted">{{ data.summary.allowed }} allowed · {{ data.summary.not_allowed }} not allowed · {{ data.summary.not_open }} not open yet<span v-if="data.summary.needs_setup"> · {{ data.summary.needs_setup }} need setup</span></span></div>
        <div v-else-if="data?.summary" class="ma-panel-body ma-availability-note"><strong>{{ number(data.summary.allowed) }} allowed routes</strong><span class="ma-muted"> · {{ number(data.summary.not_allowed) }} not allowed · {{ number(data.summary.total) }} in your scope</span></div>
        <div class="ma-table-wrap"><table class="ma-table"><thead><tr v-if="selectedRoute"><th>Departure</th><th>Journey</th><th>App status</th><th>Reason</th></tr><tr v-else><th>Route</th><th>App access</th><th>Schedules on this date</th><th></th></tr></thead><tbody>
            <tr v-if="loading"><td colspan="4" class="ma-empty" role="status">Loading availability…</td></tr>
            <template v-else-if="!error"><tr v-for="row in data?.items || []" :key="row.id">
                <template v-if="selectedRoute"><td><strong>{{ clock(row.departure_time) }}</strong><small>{{ row.name || `Schedule #${row.schedule_id}` }} · {{ row.bus_class || 'Class not set' }} · Departure #{{ row.id }}</small></td><td>{{ row.origin || 'Unavailable city' }} → {{ row.destination || 'Unavailable city' }}</td><td><span class="ma-badge" :class="badge(row.status)">{{ statusLabel(row.status) }}</span></td><td>{{ row.reason }}<br><router-link v-if="options.permissions.notifications_send && options.permissions.bookings_view" class="btn btn-outline-success btn-sm mt-2" :to="{name:'mobile-app-notifications',query:{departure:row.id}}">Notify passengers</router-link></td></template>
                <template v-else><td><strong>{{ row.name }}</strong><small>Route #{{ row.id }}</small></td><td><span class="ma-badge" :class="badge(row.status)">{{ statusLabel(row.status) }}</span><small>{{ row.reason }}</small></td><td>{{ row.schedules ? number(row.schedules) : 'No schedules' }}</td><td><button class="btn btn-outline-success btn-sm" @click="openRoute(row)">View schedules</button></td></template>
            </tr><tr v-if="!data?.items?.length"><td colspan="4" class="ma-empty">{{ selectedRoute ? 'No departures match this date and filter.' : 'No routes match this filter.' }}</td></tr></template>
        </tbody></table></div>
        <MobilePagination v-if="!error" :pagination="data?.pagination" :busy="loading" @change="page" />
        <div class="ma-panel-body"><p class="ma-muted">Allowed means the online visibility rules permit it. Seats, fares and passenger booking controls are checked separately.</p></div>
    </div>
</template>
<script>
import MobilePagination from './MobilePagination.vue';
import { request, today, number } from './mobileApp';
export default {
    components: { MobilePagination }, props: { options: Object },
    data: () => ({ filters: { date: today(), mobile_terminal_id: '', search: '', status: '' }, applied: null, selectedRoute: null, context: null, data: null, loading: false, error: '', sequence: 0 }),
    mounted() { this.load(true); }, beforeUnmount() { this.sequence++; },
    methods: {
        today, number,
        clock(value) { if (!value) return '—'; const [h,m] = value.split(':'); return `${(+h % 12) || 12}:${m} ${+h < 12 ? 'AM' : 'PM'}`; },
        statusLabel(value) { return { allowed: 'Allowed', not_allowed: 'Not allowed', not_open: 'Not open yet', needs_setup: 'Needs setup' }[value] || 'Unknown'; },
        badge(value) { return { allowed: 'active', not_allowed: 'blocked', not_open: 'pending', needs_setup: 'pending' }[value]; },
        async openRoute(row) { this.selectedRoute = { id: row.id, name: row.name }; this.filters = { ...this.applied, status: '', search: '' }; await this.load(true); },
        async back() { this.selectedRoute = null; this.filters.status = ''; await this.load(true); },
        async page(value) { await this.load(false, value); },
        async load(apply = false, page = 1) {
            if (apply || !this.applied) this.applied = { ...this.filters };
            const sequence = ++this.sequence; this.loading = true; this.error = ''; this.data = null;
            try {
                const data = await request(this, 'get', `availability/${this.selectedRoute ? 'schedules' : 'routes'}`, null, { ...this.applied, route_id: this.selectedRoute?.id, page });
                if (sequence === this.sequence) { this.data = data; this.context = data.context; if (!this.filters.mobile_terminal_id && data.context.terminal_id) { this.filters.mobile_terminal_id = data.context.terminal_id; this.applied.mobile_terminal_id = data.context.terminal_id; } }
            } catch (error) { if (sequence === this.sequence) this.error = error.message; }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
    },
};
</script>
