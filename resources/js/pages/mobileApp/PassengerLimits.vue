<template>
    <div class="ma-panel ma-panel-body">
        <h3>Seat limits & verification</h3>
        <p class="ma-muted mb-3">Limits apply to this passenger account. Leave a limit blank to use the app default; enter 0 to stop new usage.</p>
        <div class="ma-grid mb-3"><div class="ma-note"><strong>{{ controls.usage.daily }} seats today</strong><div>{{ controls.usage.monthly }} seats this month</div></div><div class="ma-note">Seat limits use Pakistan calendar days and months. Created reservations count even when cancelled later.</div></div>
        <div v-if="error" class="ma-error" role="alert">{{ error }} <button type="button" class="btn btn-outline-danger btn-sm" :disabled="saving || busy" @click="reload">Reload user</button></div>
        <form class="ma-form" @submit.prevent="save">
            <fieldset :disabled="!permissions.users_limits || saving || busy"><legend>Account limits</legend>
                <div class="ma-grid">
                    <div v-for="field in fields" :key="field.key" class="ma-field"><label :for="'ma-limit-' + field.key">{{ field.label }}</label><input :id="'ma-limit-' + field.key" type="number" min="0" max="10000" step="1" class="form-control" v-model="limits[field.key]" :placeholder="'App default: ' + defaultLabel(field.default)"><small class="ma-muted">App default: {{ defaultLabel(field.default) }}</small></div>
                </div>
                <p class="ma-muted mt-3">OTP budgets cover signup and password-reset requests together, across devices. The 24-hour and 30-day windows start with the first request. App-wide minute and hour limits also apply.</p>
                <label class="ma-check mt-3"><input type="checkbox" v-model="limits.bookings_enabled"><span>Allow new bookings<small>The app-wide booking switch must also be enabled.</small></span></label>
                <label class="ma-check mt-3"><input type="checkbox" v-model="limits.otp_enabled"><span>Allow verification codes<small>Disabling this also stops password recovery for this account.</small></span></label>
            </fieldset>
            <div v-if="permissions.users_limits || permissions.users_password_reset" class="ma-field mb-3"><label for="ma-controls-reason">Reason for this action</label><input id="ma-controls-reason" class="form-control" v-model.trim="reason" minlength="3" maxlength="1000" required :disabled="saving || busy" placeholder="Explain the restriction or reset"></div>
            <div class="ma-actions"><button v-if="permissions.users_limits" class="btn btn-success" :disabled="saving || busy || !dirty || reason.length < 3">{{ saving ? 'Saving…' : 'Save passenger limits' }}</button></div>
        </form>
        <div class="mt-4"><h3>Password recovery</h3><p v-if="controls.password_reset_required" class="ma-note ma-warning">Password reset required. This passenger must complete Forgot password before signing in.</p><p class="ma-muted">Require a new password and revoke all sessions and existing verification codes. The passenger uses Forgot password and verifies their mobile number. This action does not send a message.</p>
            <button v-if="permissions.users_password_reset" type="button" class="btn btn-outline-danger" :disabled="saving || busy || dirty || reason.length < 3" @click="forceReset">Require password reset</button><p v-if="dirty && permissions.users_password_reset" class="ma-muted mt-2">Save or discard limit changes before requiring a password reset.</p>
        </div>
    </div>
</template>
<script>
import { request } from './mobileApp';
const fields = [
    { key: 'daily_seat_limit', label: 'Seats per day', default: 'daily_seat_limit' },
    { key: 'monthly_seat_limit', label: 'Seats per month', default: 'monthly_seat_limit' },
    { key: 'otp_daily_limit', label: 'OTP requests per 24 hours', default: 'otp_per_day' },
    { key: 'otp_monthly_limit', label: 'OTP requests per 30 days', default: 'otp_per_month' },
];
export default {
    props: { controls: Object, permissions: Object, userId: Number, busy: Boolean }, emits: ['updated', 'busy'],
    data() { return { fields, limits: { ...this.controls.limits }, reason: '', saving: false, error: '' }; },
    computed: { dirty() { return JSON.stringify(this.payload()) !== JSON.stringify(this.normalized(this.controls.limits)); } },
    methods: {
        normalized(value) { return { ...Object.fromEntries(fields.map(field => [field.key, value[field.key] === '' || value[field.key] == null ? null : Number(value[field.key])])), bookings_enabled: !!value.bookings_enabled, otp_enabled: !!value.otp_enabled }; },
        payload() { return this.normalized(this.limits); },
        defaultLabel(key) { return this.controls.defaults[key] == null ? 'Unlimited' : String(this.controls.defaults[key]); },
        confirmLeave() { return !this.saving && (!this.dirty || window.confirm('Discard the unsaved passenger limits?')); },
        reload() { if (this.confirmLeave()) this.$emit('updated', ''); },
        async save() {
            if (!this.permissions.users_limits || this.saving || this.busy || !this.dirty || this.reason.length < 3) return;
            const limits = this.payload();
            if (fields.some(({ key }) => limits[key] !== null && (!Number.isInteger(limits[key]) || limits[key] < 0 || limits[key] > 10000))) { this.error = 'Enter whole numbers between 0 and 10,000, or leave a limit blank.'; return; }
            await this.mutate('put', 'limits', { limits, revision: this.controls.revision, reason: this.reason }, 'Passenger limits saved.');
        },
        async forceReset() {
            if (!this.permissions.users_password_reset || this.saving || this.busy || this.dirty || this.reason.length < 3) return;
            if (!window.confirm('Sign this passenger out and require a verified password reset? Existing codes will stop working.')) return;
            await this.mutate('post', 'require-password-reset', { revision: this.controls.revision, reason: this.reason }, 'Password reset required. All mobile sessions revoked.');
        },
        async mutate(method, action, body, message) {
            this.saving = true; this.error = ''; this.$emit('busy', true);
            try { await request(this, method, `users/${this.userId}/${action}`, body); this.$emit('updated', message); }
            catch (error) { this.error = error.message; }
            finally { this.saving = false; this.$emit('busy', false); }
        },
    },
};
</script>
