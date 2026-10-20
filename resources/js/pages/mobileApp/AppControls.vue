<template>
    <div class="ma-panel mt-4">
        <div class="ma-panel-head"><div><h2>App Controls</h2><p class="ma-muted">Company-wide access, default seat allowances, and authentication request limits.</p></div><span v-if="!options.permissions.app_controls_update" class="ma-badge">View only</span></div>
        <div class="ma-panel-body">
            <div v-if="loading" class="ma-empty" role="status">Loading app controls…</div>
            <div v-if="error" class="ma-error" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm" type="button" :disabled="saving" @click="reload">Reload controls</button></div>
            <div v-if="notice" class="ma-error ma-success" role="status">{{ notice }}</div>
            <form v-if="settings && !loading" class="ma-form" @submit.prevent="save">
                <fieldset :disabled="saving || !options.permissions.app_controls_update"><legend>App access</legend><div class="ma-grid">
                    <label v-for="field in switches" :key="field.key" class="ma-check"><input type="checkbox" v-model="settings[field.key]"><span>{{ field.label }}<small>{{ field.help }}</small></span></label>
                </div></fieldset>
                <fieldset :disabled="saving || !options.permissions.app_controls_update"><legend>Default seat limits per passenger</legend><p class="ma-muted mb-3">Leave blank for unlimited seats; enter 0 to stop new seat reservations. Individual passenger limits override these defaults. Calendar periods use Pakistan time; cancellations do not restore allowance.</p><div class="ma-grid">
                    <div v-for="field in seats" :key="field.key" class="ma-field"><label :for="'ma-policy-' + field.key">{{ field.label }}</label><input :id="'ma-policy-' + field.key" class="form-control" type="number" min="0" max="10000" step="1" v-model="settings[field.key]" placeholder="Unlimited"></div>
                </div></fieldset>
                <fieldset :disabled="saving || !options.permissions.app_controls_update"><legend>OTP & sign-in protection</legend><p class="ma-muted mb-3">Per-account budgets are shared across devices. OTP requests include signup, resend, and Forgot password. Windows start with the first attempt and expire after the stated duration. Separate IP limits also apply.</p><div class="ma-grid">
                    <div v-for="field in rates" :key="field.key" class="ma-field"><label :for="'ma-policy-' + field.key">{{ field.label }}</label><input :id="'ma-policy-' + field.key" class="form-control" type="number" min="1" :max="field.max" step="1" v-model="settings[field.key]" required></div>
                </div></fieldset>
                <div v-if="options.permissions.app_controls_update"><div class="ma-field mb-3"><label for="ma-policy-reason">Reason for this change</label><input id="ma-policy-reason" class="form-control" v-model.trim="reason" minlength="3" maxlength="1000" required :disabled="saving" placeholder="Explain the app control change"></div><div class="ma-actions"><p class="ma-muted">Changes apply to new API requests and are recorded in Activity & Audit.</p><button class="btn btn-success" :disabled="saving || !dirty || reason.length < 3">{{ saving ? 'Saving…' : 'Save app controls' }}</button></div></div>
            </form>
        </div>
    </div>
</template>
<script>
import { request } from './mobileApp';
const switches = [
    { key: 'registrations_enabled', label: 'Allow new registrations', help: 'Let new passengers create accounts.' },
    { key: 'logins_enabled', label: 'Allow sign-in', help: 'Existing sessions remain active unless revoked separately.' },
    { key: 'bookings_enabled', label: 'Allow new bookings', help: 'Existing bookings remain available.' },
    { key: 'otp_enabled', label: 'Allow verification codes', help: 'Required for registration verification and password recovery.' },
];
const seats = [{ key: 'daily_seat_limit', label: 'Seats per day' }, { key: 'monthly_seat_limit', label: 'Seats per month' }];
const rates = [
    { key: 'otp_per_minute', label: 'OTP requests per minute', max: 10 },
    { key: 'otp_per_hour', label: 'OTP requests per hour', max: 100 },
    { key: 'otp_per_day', label: 'OTP requests per 24 hours', max: 1000 },
    { key: 'otp_per_month', label: 'OTP requests per 30 days', max: 10000 },
    { key: 'login_per_minute', label: 'Sign-in attempts per minute', max: 20 },
    { key: 'otp_verify_per_ten_minutes', label: 'Code checks per 10 minutes', max: 10 },
    { key: 'password_reset_per_minute', label: 'Password reset attempts per minute', max: 10 },
];
export default {
    props: { options: Object },
    data: () => ({ switches, seats, rates, settings: null, revision: '', baseline: '', reason: '', saving: false, loading: false, error: '', notice: '', sequence: 0 }),
    computed: { dirty() { return !!this.settings && JSON.stringify(this.payload()) !== this.baseline; } },
    mounted() { this.load(); }, beforeUnmount() { this.sequence++; },
    methods: {
        payload() { return { ...Object.fromEntries(switches.map(({ key }) => [key, !!this.settings[key]])), ...Object.fromEntries(seats.map(({ key }) => [key, this.settings[key] === '' || this.settings[key] == null ? null : Number(this.settings[key])])), ...Object.fromEntries(rates.map(({ key }) => [key, Number(this.settings[key])])) }; },
        confirmLeave() { return !this.saving && (!this.dirty || window.confirm('Leave without saving app controls?')); },
        async reload() { if (this.confirmLeave()) await this.load(); },
        async load() { const sequence = ++this.sequence; this.loading = true; this.error = ''; try { const data = await request(this, 'get', 'policies'); if (sequence !== this.sequence) return; this.settings = { ...data.settings }; this.revision = data.revision; this.baseline = JSON.stringify(this.payload()); this.reason = ''; } catch (error) { if (sequence === this.sequence) this.error = error.message; } finally { if (sequence === this.sequence) this.loading = false; } },
        async save() {
            if (!this.options.permissions.app_controls_update || this.saving || !this.dirty || this.reason.length < 3) return;
            const settings = this.payload();
            if (rates.some(({ key, max }) => !Number.isInteger(settings[key]) || settings[key] < 1 || settings[key] > max) || seats.some(({ key }) => settings[key] !== null && (!Number.isInteger(settings[key]) || settings[key] < 0 || settings[key] > 10000))) { this.error = 'Enter whole numbers within the limits shown in each field.'; return; }
            this.saving = true; this.error = ''; this.notice = '';
            try { await request(this, 'put', 'policies', { settings, revision: this.revision, reason: this.reason }); this.notice = 'App controls saved.'; await this.load(); } catch (error) { this.error = error.message; } finally { this.saving = false; }
        },
    },
};
</script>
