<template>
    <li v-if="sessionKey && available" class="cc-notifications" @keydown.esc.stop.prevent="dismiss(true)">
        <button ref="bell" type="button" class="cc-notification-bell" :aria-label="unread ? `Notifications, ${unread} unread` : 'Notifications'" :aria-expanded="open" aria-controls="complaint-notifications" @click="toggle">
            <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span v-if="unread" class="cc-notification-badge" aria-hidden="true">{{ unread > 99 ? '99+' : unread }}</span>
        </button>
        <section v-if="open" id="complaint-notifications" class="cc-notification-panel" aria-label="Complaint notifications" :aria-busy="loading">
            <header><strong>Notifications <span v-if="unread">({{ unread }} unread)</span></strong><button type="button" :disabled="loading" @click="refresh()">Refresh</button></header>
            <p v-if="error" role="alert" class="cc-notification-error">{{ error }} <button type="button" :disabled="loading" @click="refresh()">Try again</button></p>
            <p v-if="loading && !items.length" role="status">Loading notifications…</p>
            <p v-else-if="!error && !items.length">No complaint assignments yet.</p>
            <ul v-if="items.length">
                <li v-for="item in items" :key="item.id">
                    <button type="button" class="cc-notification-item" :class="{ unread: !item.read_at }" :disabled="opening !== null" @click="openNotification(item)">
                        <span>{{ item.message }}</span><small>{{ time(item.created_at) }}<span v-if="!item.read_at"> · Unread</span></small>
                    </button>
                </li>
            </ul>
            <button v-if="page < lastPage" type="button" class="cc-notification-more" :disabled="loading" @click="refresh(page + 1, true)">{{ loading ? 'Loading…' : 'More notifications' }}</button>
        </section>
    </li>
</template>

<script>
import { request, displayTime, complaintNotificationRoute } from '../pages/callCenter/callCenter';

export default {
    data: () => ({ open: false, items: [], unread: 0, page: 1, lastPage: 1, loading: false, opening: null, error: '', available: false, sequence: 0, timer: null }),
    computed: {
        sessionKey() {
            const state = this.$store.state;
            // Login returns a user and role, without company modules. The API checks
            // live company/role access; cached browser permissions must not hide the bell.
            return state.token && state.user?.id ? `${state.user.id}:${state.token}` : '';
        },
    },
    watch: {
        sessionKey: { immediate: true, handler() {
            this.sequence++; this.items = []; this.unread = 0; this.open = false; this.error = ''; this.available = false; this.loading = false;
            if (this.sessionKey) this.refresh();
        } },
    },
    mounted() {
        this.timer = window.setInterval(() => { if (!document.hidden && !this.open) this.refresh(); }, 30000);
        document.addEventListener('click', this.outsideClick);
        document.addEventListener('visibilitychange', this.resume);
        window.addEventListener('focus', this.resume);
        window.addEventListener('call-center-notifications-changed', this.onChanged);
    },
    beforeUnmount() {
        this.sequence++; window.clearInterval(this.timer);
        document.removeEventListener('click', this.outsideClick);
        document.removeEventListener('visibilitychange', this.resume);
        window.removeEventListener('focus', this.resume);
        window.removeEventListener('call-center-notifications-changed', this.onChanged);
    },
    methods: {
        time: displayTime,
        dismiss(focus = false) { this.open = false; if (focus) this.$refs.bell?.focus(); },
        outsideClick(event) { if (!this.$el.contains(event.target)) this.dismiss(); },
        resume() { if (!document.hidden) this.refresh(); },
        onChanged() { this.refresh(); },
        toggle() { this.open = !this.open; if (this.open) this.refresh(); },
        async refresh(page = 1, append = false) {
            if (!this.sessionKey || this.loading) return;
            const sequence = ++this.sequence; this.loading = true; this.error = '';
            try {
                const data = await request(this, 'get', 'notifications', null, { page });
                if (sequence !== this.sequence) return;
                this.available = true;
                this.items = append ? [...this.items, ...data.notifications.data] : data.notifications.data;
                this.unread = data.unread_count; this.page = data.notifications.current_page; this.lastPage = data.notifications.last_page;
            } catch (error) {
                if (sequence !== this.sequence) return;
                if ([401, 403].includes(error.status)) { this.available = false; this.open = false; this.items = []; this.unread = 0; }
                else this.error = 'Notifications could not be loaded.';
            } finally { if (sequence === this.sequence) this.loading = false; }
        },
        async openNotification(item) {
            if (this.opening !== null) return;
            this.opening = item.id;
            try {
                const target = complaintNotificationRoute(item);
                if (this.$route.name === target.name && this.$route.query.complaint === target.query.complaint && this.$route.query.notification === target.query.notification) { this.dismiss(); return; }
                const failure = await this.$router.push(target);
                if (!failure) this.dismiss();
            } catch (error) { this.error = 'The complaint could not be opened. Please try again.'; }
            finally { this.opening = null; }
        },
    },
};
</script>

<style scoped>
.cc-notifications { position: relative; align-self: center; margin-right: 15px; color: #24483d; }
.cc-notification-bell { position: relative; display: grid; place-items: center; width: 44px; height: 44px; border: 0; border-radius: 50%; color: #246c54; background: #f0f7f3; cursor: pointer; }
.cc-notification-bell:hover { background: #e1f0e7; }
.cc-notification-badge { position: absolute; top: -3px; right: -5px; min-width: 19px; padding: 1px 5px; border-radius: 12px; background: #19804c; color: white; font-size: 11px; font-weight: 700; }
.cc-notification-panel { position: absolute; right: 0; top: 51px; width: min(380px, calc(100vw - 100px)); max-height: min(520px, calc(100vh - 110px)); overflow-y: auto; background: white; border: 1px solid #dbe7e0; border-radius: 12px; box-shadow: 0 8px 28px #153c2426; z-index: 1100; }
.cc-notification-panel header { padding: 16px; display: flex; justify-content: space-between; gap: 12px; align-items: center; border-bottom: 1px solid #e2eae5; }
.cc-notification-panel p { padding: 16px; margin: 0; color: #596e63; }
.cc-notification-panel ul { list-style: none; padding: 0; margin: 0; }
.cc-notification-panel button { font: inherit; cursor: pointer; }
.cc-notification-panel header button, .cc-notification-error button, .cc-notification-more { border: 0; background: none; color: #166549; text-decoration: underline; }
.cc-notification-item { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #e2eae5; padding: 16px; background: white; color: #24483d; line-height: 1.5; }
.cc-notification-item.unread { background: #edf7f0; border-left: 4px solid #238751; padding-left: 12px; }
.cc-notification-item:hover { background: #e3f1e8; }
.cc-notification-item small { display: block; margin-top: 5px; color: #526a5c; }
.cc-notification-more { display: block; width: 100%; padding: 13px; }
.cc-notifications button:focus-visible { outline: 2px solid #19804c; outline-offset: 2px; }
.cc-notifications button:disabled { cursor: wait; opacity: .65; }
@media print { .cc-notifications { display: none; } }
</style>
