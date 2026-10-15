<template>
    <section aria-labelledby="complaints-title">
        <div v-if="discardVisible" class="cc-dialog-backdrop">
            <section ref="discardDialog" class="cc-dialog" role="dialog" aria-modal="true" aria-labelledby="complaint-discard-title" @keydown.esc.prevent="decideDiscard(false)" @keydown.tab.prevent="trapDialogFocus">
                <h2 id="complaint-discard-title">Keep your closing remarks?</h2><p>Your remarks have not been saved.</p>
                <div class="cc-actions"><button ref="keepEditing" class="cc-button" @click="decideDiscard(false)">Keep editing</button><button class="cc-button secondary" @click="decideDiscard(true)">Discard changes</button></div>
            </section>
        </div>
        <div v-if="notice" class="cc-alert success" role="status">{{ notice }}</div>
        <div class="cc-card">
            <div class="cc-card-header"><div><h2 id="complaints-title">Complaints</h2><p class="cc-muted">{{ options.permissions.close_complaints ? 'Add closing remarks, then close the complaint.' : 'View open complaints and completed resolutions.' }}</p></div><button class="cc-link" :disabled="loading || !!closing" @click="fetchCalls()">Refresh</button></div>
            <div class="cc-queue-tabs" aria-label="Complaint status">
                <button :class="{ selected: mode === 'open' }" :aria-pressed="mode === 'open'" :disabled="!!closing" @click="selectMode('open')">Open</button>
                <button :class="{ selected: mode === 'closed' }" :aria-pressed="mode === 'closed'" :disabled="!!closing" @click="selectMode('closed')">Closed</button>
            </div>
            <form class="cc-list-tools" @submit.prevent="fetchCalls(1)"><div class="cc-search-row"><input v-model.trim="search" type="search" aria-label="Search complaints" placeholder="Call reference, caller, or phone" maxlength="100" :disabled="!!closing"><button class="cc-button secondary small" :disabled="loading || !!closing">Find</button></div></form>
            <div v-if="error" class="cc-alert" role="alert">{{ error }} <button class="cc-link" @click="fetchCalls()">Try again</button></div>
            <div v-else-if="loading" class="cc-empty" role="status">Loading complaints…</div>
            <div v-else-if="!calls.length" class="cc-empty"><h3>{{ search ? 'No matching complaints' : mode === 'open' ? 'No open complaints' : 'No closed complaints' }}</h3><p>{{ search ? 'Try a different reference, name or phone.' : mode === 'open' ? 'Complaints recorded in the call log appear here automatically.' : 'Closed complaints and their remarks will appear here.' }}</p></div>
            <article v-for="call in loading || error ? [] : calls" :key="call.id" class="cc-work-item cc-complaint-item" :aria-label="call.reference">
                <div>
                    <div class="cc-card-header compact"><h3>{{ call.reference }} · {{ call.caller_name || 'Caller not recorded' }}</h3><span class="cc-status" :class="call.status">{{ call.status === 'closed' ? 'Closed' : 'Open' }}</span></div>
                    <p class="cc-muted">{{ call.caller_phone || 'No phone recorded' }} · {{ time(call.called_at) }}</p>
                    <p v-if="journeySummary(call)" class="cc-muted">{{ journeySummary(call) }}</p>
                    <p class="cc-complaint-text">{{ call.remarks || 'No complaint description recorded.' }}</p>
                    <p class="cc-muted">Recorded by {{ call.agent?.name || '—' }}<span v-if="call.assignee"> · Responsible: {{ call.assignee.name }}</span></p>
                    <p v-if="call.external_reference" class="cc-muted">Reference: {{ call.external_reference }}</p>
                    <p v-if="call.callback_at && call.status !== 'closed'" class="cc-muted">Callback: {{ time(call.callback_at) }}</p>
                </div>
                <form v-if="call.status !== 'closed' && options.permissions.close_complaints" class="cc-complaint-close" @submit.prevent="closeComplaint(call)">
                    <label :for="'closing-remarks-' + call.id">Closing remarks (required)</label>
                    <textarea :id="'closing-remarks-' + call.id" v-model="drafts[call.id]" rows="3" maxlength="5000" required placeholder="What was done to resolve this complaint?" :disabled="!!closing" :aria-invalid="!!errors[call.id]"></textarea>
                    <p v-if="errors[call.id]" class="cc-field-error" role="alert">{{ errors[call.id] }}</p>
                    <button class="cc-button" :disabled="!!closing || !drafts[call.id]?.trim()">{{ closing === call.id ? 'Closing…' : 'Close complaint' }}</button>
                </form>
                <div v-else-if="call.status === 'closed'" class="cc-complaint-resolution"><strong>Closing remarks</strong><p class="cc-complaint-text">{{ call.resolution_remarks || 'No closing remarks recorded for this older complaint.' }}</p><p v-if="call.resolved_at" class="cc-muted">Closed {{ time(call.resolved_at) }}<span v-if="call.resolver"> · {{ call.resolver.name }}</span></p><button v-if="options.permissions.update && options.permissions.reopen_complaints" class="cc-button secondary small" :disabled="!!closing" @click="reopenComplaint(call)">Reopen complaint</button><p v-if="errors[call.id]" class="cc-field-error" role="alert">{{ errors[call.id] }}</p></div>
            </article>
            <div class="cc-pagination"><span>{{ pagination.total }} {{ pagination.total === 1 ? 'complaint' : 'complaints' }} · Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span><div class="cc-actions"><button class="cc-button secondary small" :disabled="loading || !!closing || pagination.current_page <= 1" @click="fetchCalls(pagination.current_page - 1)">Previous</button><button class="cc-button secondary small" :disabled="loading || !!closing || pagination.current_page >= pagination.last_page" @click="fetchCalls(pagination.current_page + 1)">Next</button></div></div>
        </div>
    </section>
</template>

<script>
import { request, displayTime } from './callCenter';

export default {
    props: { options: { type: Object, required: true } },
    data: () => ({ mode: 'open', search: '', calls: [], drafts: {}, errors: {}, loading: false, closing: null, error: '', notice: '', sequence: 0,
        pagination: { total: 0, current_page: 1, last_page: 1 }, discardVisible: false, discardResolve: null, returnFocus: null }),
    computed: { dirty() { return Object.values(this.drafts).some(value => value?.trim()); } },
    mounted() { this.fetchCalls(); window.addEventListener('beforeunload', this.beforeUnload); },
    beforeUnmount() { this.sequence++; window.removeEventListener('beforeunload', this.beforeUnload); },
    methods: {
        time: displayTime,
        journeySummary(call) { return [call.complaint_from_city || call.complaint_to_city ? `${call.complaint_from_city || '—'} → ${call.complaint_to_city || '—'}` : '', call.complaint_bus_time ? `Bus ${call.complaint_bus_time}` : '', call.complaint_seat_number ? `Seat ${call.complaint_seat_number}` : ''].filter(Boolean).join(' · '); },
        selectMode(mode) { this.mode = mode; this.fetchCalls(1); },
        async fetchCalls(page = this.pagination.current_page) {
            const sequence = ++this.sequence; this.loading = true; this.error = '';
            try {
                const params = this.mode === 'open' ? { queue: 'open_complaints' } : { queue: 'all', category: 'C', status: 'closed' };
                const data = await request(this, 'get', 'calls', null, { ...params, search: this.search, page });
                if (sequence !== this.sequence) return;
                if (!data.data.length && page > 1) return this.fetchCalls(page - 1);
                this.calls = data.data;
                this.pagination = { total: data.total, current_page: data.current_page, last_page: data.last_page };
            } catch (error) { if (sequence === this.sequence) this.error = error.message; }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
        async reopenComplaint(call) {
            if (this.closing) return;
            this.closing = call.id; delete this.errors[call.id]; this.notice = '';
            try {
                await request(this, 'patch', `calls/${call.id}`, { status: 'new' });
                this.notice = `${call.reference} reopened. You can find it under Open.`;
                await this.fetchCalls();
            } catch (error) { this.errors[call.id] = error.message; }
            finally { this.closing = null; }
        },
        async closeComplaint(call) {
            if (this.closing) return;
            const remarks = (this.drafts[call.id] || '').trim();
            if (!remarks) { this.errors[call.id] = 'Enter closing remarks.'; return; }
            this.closing = call.id; delete this.errors[call.id]; this.notice = '';
            try {
                await request(this, 'post', `calls/${call.id}/close-complaint`, { resolution_remarks: remarks });
                delete this.drafts[call.id]; this.notice = `${call.reference} closed. You can find it under Closed.`;
                await this.fetchCalls();
            } catch (error) { this.errors[call.id] = error.message; }
            finally { this.closing = null; }
        },
        beforeUnload(event) { if (this.dirty || this.closing) { event.preventDefault(); event.returnValue = ''; } },
        confirmLeave() {
            if (this.closing || this.discardVisible) return false;
            if (!this.dirty) return true;
            this.returnFocus = document.activeElement; this.discardVisible = true;
            this.$nextTick(() => this.$refs.keepEditing?.focus());
            return new Promise(resolve => { this.discardResolve = resolve; });
        },
        decideDiscard(discard) { const resolve = this.discardResolve; this.discardResolve = null; this.discardVisible = false; resolve?.(discard); if (!discard) this.$nextTick(() => { if (this.returnFocus?.isConnected) this.returnFocus.focus(); }); },
        trapDialogFocus() { const buttons = this.$refs.discardDialog?.querySelectorAll('button'); if (buttons?.length) (document.activeElement === buttons[0] ? buttons[1] : buttons[0]).focus(); },
    },
};
</script>
