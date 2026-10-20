<template>
    <div>
        <div v-if="notice" class="ma-error ma-success" role="status">{{ notice }}</div>
        <div class="ma-panel"><div class="ma-panel-head"><div><h2>App Users</h2><p class="ma-muted">Review mobile accounts, control access, and revoke active sessions.</p></div><router-link v-if="options.permissions.reports_view" class="btn btn-outline-success" :to="{name:'mobile-app-reports',query:{report:'user_bookings'}}"><i class="fas fa-chart-bar mr-1"></i>User reports</router-link></div>
            <MobileFilters id="ma-users" v-model="filters" :options="options" :search="searchLabel" :statuses="['active', 'blocked', 'blacklisted', 'verified', 'unverified']" :busy="loading" @apply="load(1, true)" />
            <div v-if="error" class="ma-error m-3" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm ml-2" @click="load()">Retry</button></div>
            <div class="ma-table-wrap"><table class="ma-table"><thead><tr><th>Passenger</th><th>CNIC</th><th>Contact</th><th>Account</th><th>Joined</th><th>Last activity</th><th>Bookings</th><th></th></tr></thead><tbody>
                <tr v-if="loading"><td colspan="8" class="ma-empty" role="status">Loading app users…</td></tr>
                <template v-else><tr v-for="user in items" :key="user.id"><td><strong>{{ user.name }}</strong><small>#{{ user.id }}</small></td><td class="text-nowrap">{{ userCnic(user) }}</td><td>{{ user.mobile || '—' }}<small>{{ user.email || 'No email' }}</small></td><td><span class="ma-badge" :class="user.status">{{ label(user.status) }}</span><small>{{ user.mobile_verified_at ? 'Mobile verified' : 'Mobile unverified' }}</small></td><td>{{ time(user.created_at) }}</td><td>{{ time(user.last_used_at) }}</td><td>{{ number(user.booking_count) }}</td><td><button class="btn btn-outline-success btn-sm" @click="open(user.id)">View user</button><router-link v-if="options.permissions.notifications_send" class="btn btn-outline-success btn-sm ml-2" :to="{name:'mobile-app-notifications',query:{passenger:user.id}}">Send update</router-link></td></tr><tr v-if="!items.length && !error"><td colspan="8" class="ma-empty"><i class="fas fa-user-friends"></i>No matching app users.</td></tr></template>
            </tbody></table></div><MobilePagination :pagination="pagination" :busy="loading" @change="load" />
        </div>
        <MobileDialog v-if="selectedId" title="Passenger account" :busy="saving || controlsSaving || walletSaving" @close="close">
            <p v-if="opening" class="ma-empty" role="status">Loading passenger details…</p>
            <div v-if="detailError" class="ma-error" role="alert">{{ detailError }} <button v-if="!detail" class="btn btn-outline-danger btn-sm ml-2" @click="open(selectedId)">Retry</button></div>
            <template v-if="detail">
                <div class="ma-panel-head px-0 pt-0"><div><h2>{{ detail.user.name }}</h2><p class="ma-muted">Passenger #{{ detail.user.id }}</p></div><span class="ma-badge" :class="detail.user.status">{{ label(detail.user.status) }}</span></div>
                <dl class="ma-details mt-3"><div><dt>Mobile</dt><dd>{{ detail.user.mobile || '—' }}</dd></div><div><dt>Email</dt><dd>{{ detail.user.email || '—' }}</dd></div><div><dt>CNIC</dt><dd>{{ userCnic(detail.user) }}</dd></div><div><dt>Mobile verification</dt><dd>{{ time(detail.user.mobile_verified_at) }}</dd></div><div><dt>Joined</dt><dd>{{ time(detail.user.created_at) }}</dd></div><div><dt>Last activity</dt><dd>{{ time(detail.user.last_used_at) }}</dd></div></dl>
                <PassengerWallet ref="passengerWallet" :user-id="Number(detail.user.id)" :permissions="options.permissions" :busy="saving || controlsSaving" @busy="walletSaving = $event" @dirty="walletDirty = $event" />
                <p v-if="walletDirty" class="ma-note">Save or reload your wallet adjustment before changing passenger limits.</p>
                <div v-if="canChangeStatus || options.permissions.users_sessions_revoke" class="ma-panel ma-panel-body">
                    <h3>Account controls</h3><p class="ma-muted mb-3">Changes require a reason and are recorded in the Mobile App audit log.</p>
                    <form class="ma-form" @submit.prevent="saveStatus">
                        <div class="ma-grid"><div class="ma-field" v-if="canChangeStatus"><label for="ma-user-status">Account status</label><select id="ma-user-status" class="form-control" v-model="status" :disabled="saving || controlsSaving"><option value="active">Active</option><option value="blocked">Blocked</option><option v-if="options.permissions.users_blacklist" value="blacklisted">Blacklisted</option></select></div><div class="ma-field"><label for="ma-user-reason">Reason</label><input id="ma-user-reason" class="form-control" v-model.trim="reason" minlength="3" maxlength="1000" :disabled="saving || controlsSaving" required placeholder="Explain the account action"></div></div>
                        <p v-if="status === 'blocked' && detail.user.status !== 'blocked'" class="ma-note ma-warning">Blocking prevents this passenger from using the mobile account. Existing bookings remain in the ERP.</p>
                        <p v-if="status === 'blacklisted'" class="ma-note ma-warning">Blacklisting restricts this account and prevents registration with its phone number or CNIC. Only staff with blacklist privileges can remove this restriction.</p>
                        <div class="ma-actions"><button v-if="canChangeStatus" class="btn btn-success" :disabled="saving || controlsSaving || !statusAllowed || status === detail.user.status || reason.length < 3">{{ saving ? 'Saving…' : 'Save account status' }}</button><button v-if="options.permissions.users_sessions_revoke" type="button" class="btn btn-outline-danger" :disabled="saving || controlsSaving || reason.length < 3" @click="revoke">Revoke all mobile sessions</button></div>
                    </form>
                </div>
                <p v-if="!canChangeStatus && !options.permissions.users_sessions_revoke" class="ma-note">Your role or access scope does not allow changes to account status or mobile sessions. These actions require their role privilege and either company Admin access or all company terminals and routes.</p>
                <PassengerLimits v-if="detail.controls" ref="passengerLimits" :controls="detail.controls" :permissions="options.permissions" :user-id="Number(detail.user.id)" :busy="saving || walletSaving || walletDirty" @busy="controlsSaving = $event" @updated="controlsUpdated" />
                <template v-if="options.permissions.bookings_view"><h3>Recent bookings</h3><div class="ma-table-wrap"><table class="ma-table"><thead><tr><th>Booking</th><th>Journey</th><th>Seats</th><th>Status</th></tr></thead><tbody><tr v-for="booking in detail.bookings || []" :key="booking.id"><td><router-link v-if="options.permissions.bookings_view" :to="{name:'mobile-app-bookings',query:{booking:String(booking.invoice_id)}}">{{ booking.invoice_id }}</router-link><span v-else>{{ booking.invoice_id }}</span></td><td>{{ booking.route_name || booking.departure }}<small>{{ booking.travel_date }} · {{ booking.travel_time }}</small></td><td>{{ booking.seat_numbers?.join(', ') || '—' }}</td><td>{{ label(booking.status) }}</td></tr><tr v-if="!detail.bookings?.length"><td colspan="4" class="ma-empty">No mobile bookings in your permitted scope.</td></tr></tbody></table></div></template>
                <template v-if="options.permissions.users_sessions_revoke && detail.sessions"><h3 class="mt-4">Mobile sessions ({{ detail.sessions.length }})</h3><div v-for="session in detail.sessions" :key="session.id" class="ma-note"><strong>{{ session.name || 'Mobile session' }}</strong><div>Created {{ time(session.created_at) }} · Last used {{ time(session.last_used_at) }}</div></div><p v-if="!detail.sessions.length" class="ma-muted">No active mobile sessions.</p></template>
            </template>
            <div class="ma-actions mt-4"><button type="button" class="btn btn-outline-secondary" :disabled="saving || controlsSaving || walletSaving" @click="close">Close</button></div>
        </MobileDialog>
    </div>
</template>
<script>
import PassengerLimits from './PassengerLimits.vue';
import PassengerWallet from './PassengerWallet.vue';
import MobileFilters from './MobileFilters.vue'; import MobilePagination from './MobilePagination.vue'; import MobileDialog from './MobileDialog.vue';
import { request, label, number, displayTime, formatCnic } from './mobileApp';
export default {
    components: { PassengerLimits, PassengerWallet, MobileFilters, MobilePagination, MobileDialog }, props: { options: Object },
    data: () => ({ filters: { search: '', status: '' }, applied: null, items: [], pagination: null, loading: false, error: '', notice: '', sequence: 0, openingSequence: 0, selectedId: null, detail: null, opening: false, detailError: '', saving: false, controlsSaving: false, walletSaving: false, walletDirty: false, status: '', reason: '' }),
    computed: {
        searchLabel() { return this.options?.permissions?.users_sensitive ? 'Name, mobile, CNIC, email or passenger ID' : 'Name, mobile, email or passenger ID'; },
        canChangeStatus() { return !!this.options.permissions.users_blacklist || (!!this.options.permissions.users_update && this.detail?.user.status !== 'blacklisted'); },
        statusAllowed() { return this.status === 'blacklisted' || this.detail?.user.status === 'blacklisted' ? !!this.options.permissions.users_blacklist : !!this.options.permissions.users_update; },
    },
    mounted() { this.load(1, true); }, beforeUnmount() { this.sequence++; this.openingSequence++; },
    methods: {
        label, number, time: displayTime,
        userCnic(user) { return formatCnic(this.options?.permissions?.users_sensitive && user.cnic ? user.cnic : user.cnic_masked); },
        async load(page = 1, apply = false) { if (apply || !this.applied) this.applied = { ...this.filters }; const sequence = ++this.sequence; this.loading = true; this.error = ''; this.items = []; try { const data = await request(this, 'get', 'users', null, { ...this.applied, page }); if (sequence === this.sequence) { this.items = data.items; this.pagination = data.pagination; } } catch (error) { if (sequence === this.sequence) { this.error = error.message; this.pagination = null; } } finally { if (sequence === this.sequence) this.loading = false; } },
        async open(id) { const sequence = ++this.openingSequence; this.selectedId = id; this.detail = null; this.detailError = ''; this.reason = ''; this.opening = true; try { const data = await request(this, 'get', `users/${id}`); if (sequence === this.openingSequence) { this.detail = data; this.status = data.user.status; } } catch (error) { if (sequence === this.openingSequence) this.detailError = error.message; } finally { if (sequence === this.openingSequence) this.opening = false; } },
        async controlsUpdated(message) { if (message) this.notice = message; await this.open(this.selectedId); },
        confirmLeave() { return !this.saving && !this.controlsSaving && !this.walletSaving && (!this.$refs?.passengerWallet || this.$refs.passengerWallet.confirmLeave()) && (!this.$refs?.passengerLimits || this.$refs.passengerLimits.confirmLeave()); },
        close() { if (!this.confirmLeave()) return; this.openingSequence++; this.selectedId = null; this.detail = null; },
        async saveStatus() { if (!this.statusAllowed || this.controlsSaving || this.saving || this.reason.length < 3 || this.status === this.detail.user.status || !this.confirmLeave()) return; this.saving = true; this.detailError = ''; try { await request(this, 'put', `users/${this.selectedId}/status`, { status: this.status, reason: this.reason, expected_status: this.detail.user.status }); this.notice = `Passenger account ${this.status === 'active' ? 'activated' : this.status}.`; await this.open(this.selectedId); await this.load(this.pagination?.current_page || 1); } catch (error) { this.detailError = error.message; } finally { this.saving = false; } },
        async revoke() { if (this.controlsSaving || this.saving || this.reason.length < 3 || !this.confirmLeave() || !window.confirm('Sign this passenger out of all mobile sessions? They will need to sign in again.')) return; this.saving = true; this.detailError = ''; try { await request(this, 'post', `users/${this.selectedId}/revoke-sessions`, { reason: this.reason }); this.notice = 'All mobile sessions revoked.'; await this.open(this.selectedId); } catch (error) { this.detailError = error.message; } finally { this.saving = false; } },
    },
};
</script>
