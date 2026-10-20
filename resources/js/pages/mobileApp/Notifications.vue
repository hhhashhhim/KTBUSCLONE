<template>
    <div>
        <section class="ma-panel">
            <div class="ma-panel-head"><div><h2>Notifications</h2><p class="ma-muted">Send an update to app passengers and see who has read it.</p></div><button class="btn btn-outline-success" :disabled="loading" @click="load()">Refresh</button></div>
            <p class="ma-note">Announcements are saved in the app inbox. Phone alerts also require the passenger’s permission and an active app session. “Push accepted” means the provider accepted the alert; it does not confirm delivery or reading.</p>
            <p v-if="delivery" class="ma-note">{{ deliveryMessage }}<span v-if="delivery.last_run_at"> Last checked {{ time(delivery.last_run_at) }} PKT.</span></p>
            <div v-if="options.permissions.notifications_send" class="p-4">
                <h3>New announcement</h3>
                <form @submit.prevent="previewMessage"><fieldset :disabled="busy" style="border:0;padding:0;margin:0">
                    <div class="ma-form-grid">
                        <label>Send to<select v-model="form.audience" class="form-control" @change="invalidatePreview"><option v-if="options.permissions.users_view" value="selected">Selected passengers</option><option v-if="options.permissions.bookings_view" value="trip">Passengers on a departure</option><option v-if="options.permissions.notifications_broadcast" value="all">All active app users</option></select></label>
                        <label v-if="form.audience === 'selected'">Passenger IDs<input v-model="passengerText" class="form-control" placeholder="e.g. 12, 35, 48" @input="invalidatePreview"><small class="ma-muted">Use the passenger # shown in App Users. Maximum 200.</small></label>
                        <label v-if="form.audience === 'trip'">Schedule departure ID<input v-model="form.schedule_detail_id" type="number" min="1" class="form-control" required @input="invalidatePreview"><small class="ma-muted">Use the departure ID from Routes &amp; Schedules. Only passengers in your permitted routes and terminals are included.</small></label>
                    </div>
                    <label class="d-block mt-3">Title<input v-model="form.title" maxlength="120" class="form-control" required @input="invalidatePreview"></label>
                    <label class="d-block mt-3">Message<textarea v-model="form.body" maxlength="1500" rows="4" class="form-control" required @input="invalidatePreview"></textarea></label>
                    <label class="d-block mt-3">Internal reason<input v-model="form.reason" minlength="5" maxlength="500" class="form-control" required @input="invalidatePreview"><small class="ma-muted">Recorded in Activity &amp; Audit; passengers do not see this reason.</small></label>
                    <p v-if="sendError" class="ma-error" role="alert">{{ sendError }}</p><p v-if="success" class="ma-note" role="status">{{ success }}</p>
                    <button class="btn btn-success mt-3" :disabled="busy || !form.audience">{{ busy ? 'Please wait…' : 'Preview audience & message' }}</button>
                </fieldset></form>
            </div>
        </section>
        <section class="ma-panel mt-4"><div class="ma-panel-head"><h3>Sent announcements</h3></div><p v-if="error" class="ma-error p-3" role="alert">{{ error }}</p>
            <div class="ma-table-wrap"><table class="ma-table"><thead><tr><th>Sent</th><th>Message</th><th>Audience</th><th>Inboxes</th><th>Read</th><th>Push accepted</th><th>Failed devices</th></tr></thead><tbody>
                <tr v-if="loading"><td colspan="7" class="ma-empty">Loading announcements…</td></tr><template v-else><tr v-for="item in items" :key="item.id"><td>{{ time(item.created_at) }}</td><td><strong>{{ item.title }}</strong><small class="ma-notification-body">{{ item.body }}</small></td><td>{{ audienceLabel(item.audience) }}</td><td>{{ item.inbox_count }}</td><td>{{ item.read_count }}</td><td>{{ item.push_accepted }}</td><td>{{ item.push_failed }}</td></tr><tr v-if="!items.length && !error"><td colspan="7" class="ma-empty">No announcements sent yet.</td></tr></template>
            </tbody></table></div><MobilePagination :pagination="pagination" :busy="loading" @change="load" />
        </section>
        <MobileDialog v-if="preview" title="Review announcement" :busy="busy" @close="closePreview">
            <p class="ma-note">This will reach <strong>{{ preview.recipient_count }}</strong> passenger inbox{{ preview.recipient_count === 1 ? '' : 'es' }}.</p>
            <h3>{{ preview.title }}</h3><p class="ma-notification-body">{{ preview.body }}</p><p class="ma-muted">{{ preview.notice }}</p>
            <p v-if="sendError" class="ma-error" role="alert">{{ sendError }}</p>
            <button class="btn btn-success" :disabled="busy" @click="send">{{ busy ? 'Sending…' : 'Send announcement' }}</button><button class="btn btn-outline-secondary ml-2" :disabled="busy" @click="closePreview">Back to edit</button>
        </MobileDialog>
    </div>
</template>
<script>
import MobilePagination from './MobilePagination.vue';
import MobileDialog from './MobileDialog.vue';
import {request, displayTime} from './mobileApp';
function requestKey() { return crypto.randomUUID(); }
export default {
    components: {MobilePagination, MobileDialog}, props: {options: Object},
    data: () => ({form: {audience: '', title: '', body: '', reason: '', schedule_detail_id: ''}, passengerText: '', key: requestKey(), preview: null, prepared: null, busy: false, sendError: '', success: '', items: [], pagination: null, loading: false, error: '', delivery: null, sequence: 0}),
    computed: {deliveryMessage() { if (!this.delivery?.last_run_at) return 'The mobile notification worker has not reported yet.'; if (Date.now() - new Date(String(this.delivery.last_run_at).replace(' ', 'T') + (/[Z+-]\d\d:\d\d$|Z$/.test(this.delivery.last_run_at) ? '' : '+05:00')).getTime() > 10 * 60000) return 'The notification worker has not checked in recently. Ask your administrator to check the scheduler.'; return this.delivery.push_configured ? 'Push delivery is configured.' : 'Inbox delivery is available. Phone push delivery still needs configuration.';}},
    mounted() { this.form.audience = this.options.permissions.users_view ? 'selected' : this.options.permissions.bookings_view ? 'trip' : this.options.permissions.notifications_broadcast ? 'all' : ''; if (this.$route?.query?.passenger && this.options.permissions.users_view) { this.form.audience = 'selected'; this.passengerText = String(this.$route.query.passenger); } if (this.$route?.query?.departure && this.options.permissions.bookings_view) { this.form.audience = 'trip'; this.form.schedule_detail_id = String(this.$route.query.departure); } this.load(); },
    beforeUnmount() { this.sequence++; },
    methods: {
        time: displayTime,
        audienceLabel(a) { return a.type === 'all' ? 'All active app users' : a.type === 'trip' ? `Departure #${a.schedule_detail_id}` : 'Selected passengers'; },
        invalidatePreview() { this.preview = null; this.prepared = null; this.key = requestKey(); this.success = ''; },
        closePreview() { if (!this.busy) this.preview = null; },
        payload() { const text = this.passengerText.trim(); const ids = text ? text.split(/[,\s]+/).map(Number) : []; if (this.form.audience === 'selected' && (!ids.length || ids.length > 200 || ids.some(id => !Number.isSafeInteger(id) || id < 1))) throw new Error('Enter up to 200 valid passenger IDs.'); return {...this.form, passenger_ids: this.form.audience === 'selected' ? ids : [], schedule_detail_id: this.form.audience === 'trip' ? Number(this.form.schedule_detail_id) : null, request_key: this.key}; },
        async previewMessage() { if (this.busy) return; this.busy = true; this.sendError = ''; this.success = ''; try { this.prepared = this.payload(); this.preview = await request(this, 'post', 'notifications/preview', this.prepared); } catch (error) { this.sendError = error.message; } finally { this.busy = false; } },
        async send() { if (this.busy || !this.preview || !this.prepared) return; this.busy = true; this.sendError = ''; try { const sent = await request(this, 'post', 'notifications/send', {...this.prepared, preview_token: this.preview.preview_token}); this.preview = null; this.prepared = null; this.success = `Saved to ${sent.recipient_count} passenger inboxes.`; this.form.title = ''; this.form.body = ''; this.form.reason = ''; this.key = requestKey(); await this.load(1); } catch (error) { this.sendError = error.message; if (error.status === 409) this.preview = null; } finally { this.busy = false; } },
        async load(page = 1) { const seq = ++this.sequence; this.loading = true; this.error = ''; try { const data = await request(this, 'get', 'notifications', null, {page}); if (seq !== this.sequence) return; this.items = data.items; this.pagination = data.pagination; this.delivery = data.delivery; } catch (error) { if (seq === this.sequence) { this.error = error.message; this.items = []; this.pagination = null; } } finally { if (seq === this.sequence) this.loading = false; } },
        confirmLeave() { return !this.busy && (!(this.form.title || this.form.body) || window.confirm('Leave this unsent announcement?')); },
    },
};
</script>
<style scoped>.ma-notification-body{white-space:pre-wrap;overflow-wrap:anywhere;max-width:560px}.ma-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px}</style>
