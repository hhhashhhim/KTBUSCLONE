<template>
    <div class="ma-monitor-grid">
        <section class="ma-panel" aria-labelledby="ma-login-title">
            <div class="ma-panel-head"><div><h2 id="ma-login-title">App logins</h2><p class="ma-muted">Successful sign-ins, including sign-in after registration.</p></div><div class="ma-actions"><label for="ma-login-days">Period</label><select id="ma-login-days" class="form-control" v-model="days" @change="load"><option :value="7">Last 7 days</option><option :value="30">Last 30 days</option></select></div></div>
            <div class="ma-panel-body" :aria-busy="loading">
                <p v-if="error" class="ma-error" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm" @click="load">Retry</button></p>
                <p v-else-if="loading" class="ma-muted" role="status">Loading login activity…</p>
                <template v-else-if="data?.available">
                    <div class="ma-login-stats"><div><strong>{{ number(data.period_logins) }}</strong><span>Logins in this period</span></div><div><strong>{{ number(data.unique_users) }}</strong><span>People signed in</span></div><div><strong>{{ number(data.first_logins) }}</strong><span>First recorded logins</span></div></div>
                    <svg class="ma-login-chart" viewBox="0 0 600 140" role="img" :aria-label="`Daily successful logins. ${data.period_logins} logins and ${data.first_logins} first recorded logins in the last ${days} days.`">
                        <line x1="20" y1="112" x2="590" y2="112" stroke="#dfe8e2" />
                        <line x1="20" y1="16" x2="590" y2="16" stroke="#eef3ef" />
                        <text x="20" y="11" class="ma-chart-label">{{ peak }}</text>
                        <g v-for="(point, index) in data.daily" :key="point.date"><title>{{ point.date }}: {{ point.logins === null ? 'Not tracked yet' : `${point.logins} logins, ${point.first_logins} first recorded` }}</title>
                            <rect :x="barX(index)" :y="112 - barHeight(point.logins)" :width="barWidth" :height="barHeight(point.logins)" rx="2" fill="#176750" />
                            <rect :x="barX(index)" :y="112 - barHeight(point.first_logins)" :width="barWidth" :height="barHeight(point.first_logins)" rx="2" fill="#9ccbb2" />
                        </g>
                        <text x="20" y="133" class="ma-chart-label">{{ data.from }}</text><text x="590" y="133" text-anchor="end" class="ma-chart-label">{{ data.to }}</text>
                    </svg>
                    <div class="ma-chart-legend"><span><i></i>All logins</span><span><i class="first"></i>First recorded login per person</span></div>
                    <p v-if="data.period_logins === 0" class="ma-muted mt-2">No logins recorded in this period.</p>
                    <p class="ma-muted mt-2">{{ number(data.total_logins) }} total logins recorded · Tracking since {{ time(data.tracking_started_at) }} PKT. Earlier history is unavailable.</p>
                    <details class="ma-daily-counts"><summary>Daily counts</summary><div class="ma-table-wrap"><table class="ma-table"><thead><tr><th>Date</th><th>Logins</th><th>First recorded</th></tr></thead><tbody><tr v-for="point in data.daily" :key="point.date"><td>{{ point.date }}</td><td>{{ number(point.logins) }}</td><td>{{ number(point.first_logins) }}</td></tr></tbody></table></div></details>
                </template>
                <p v-else class="ma-muted">Waiting for the mobile app to connect to login tracking. Previous login totals are unavailable.</p>
            </div>
        </section>
        <section class="ma-panel"><div class="ma-panel-head"><h2>New downloads</h2></div><div class="ma-panel-body"><p class="ma-download-value">—</p><p class="ma-muted">Store reports are not connected. Downloads are different from registrations and logins.</p><p class="ma-muted mt-3">Check first-time installs in your store account:</p><div class="ma-store-links"><a href="https://play.google.com/console" target="_blank" rel="noopener noreferrer">Google Play Console ↗</a><a href="https://appstoreconnect.apple.com/" target="_blank" rel="noopener noreferrer">App Store Connect ↗</a></div></div></section>
    </div>
</template>
<script>
import { request, number, displayTime } from './mobileApp';
export default {
    props: { scope: Object },
    data: () => ({ days: 7, data: null, loading: false, error: '', sequence: 0 }),
    computed: {
        peak() { return Math.max(1, ...(this.data?.daily || []).map(point => point.logins || 0)); },
        barWidth() { return Math.max(3, 570 / (this.data?.daily?.length || 7) * .55); },
    },
    watch: { scope: { deep: true, handler() { this.load(); } } },
    mounted() { this.load(); }, beforeUnmount() { this.sequence++; },
    methods: {
        number, time: displayTime,
        barX(index) { const step = 570 / (this.data?.daily?.length || 7); return 20 + index * step + (step - this.barWidth) / 2; },
        barHeight(value) { return (value || 0) / this.peak * 90; },
        async load() {
            const sequence = ++this.sequence; this.loading = true; this.error = ''; this.data = null;
            try { const data = await request(this, 'get', 'engagement', null, { days: this.days, terminal_id: this.scope?.terminal_id || '', route_id: this.scope?.route_id || '' }); if (sequence === this.sequence) this.data = data; }
            catch (error) { if (sequence === this.sequence) this.error = error.message; }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
    },
};
</script>
