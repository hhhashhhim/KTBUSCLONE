<template>
    <section aria-labelledby="complaints-title">
        <div v-if="discardVisible" class="cc-dialog-backdrop">
            <section ref="discardDialog" class="cc-dialog" role="dialog" aria-modal="true" aria-labelledby="complaint-discard-title" @keydown.esc.prevent="decideDiscard(false)" @keydown.tab.prevent="trapDialogFocus">
                <h2 id="complaint-discard-title">Keep your changes?</h2><p>Your changes have not been saved.</p>
                <div class="cc-actions"><button ref="keepEditing" class="cc-button" @click="decideDiscard(false)">Keep editing</button><button class="cc-button secondary" @click="decideDiscard(true)">Discard changes</button></div>
            </section>
        </div>
        <div v-if="notice" class="cc-alert success" role="status">{{ notice }}</div>
        <div v-if="targetId && calls.length" class="cc-alert success">Viewing complaint CC-{{ targetId }}. <router-link :to="{ name: 'call-center-complaints' }">All complaints</router-link></div>
        <div v-if="notificationError" class="cc-alert" role="alert">{{ notificationError }} <button class="cc-link" @click="markNotificationRead">Try again</button></div>
        <div class="cc-card">
            <div class="cc-card-header"><div><h2 id="complaints-title">Complaints</h2><p class="cc-muted">{{ instructions }}</p></div><button class="cc-link" :disabled="loading || !!saving" @click="fetchCalls()">Refresh</button></div>
            <div v-if="!targetId" class="cc-queue-tabs" aria-label="Complaint status">
                <button :class="{ selected: mode === 'open' }" :aria-pressed="mode === 'open'" :disabled="!!saving" @click="selectMode('open')">Open</button>
                <button :class="{ selected: mode === 'closed' }" :aria-pressed="mode === 'closed'" :disabled="!!saving" @click="selectMode('closed')">Closed</button>
            </div>
            <form v-if="!targetId" class="cc-list-tools" @submit.prevent="fetchCalls(1)"><div class="cc-search-row"><input v-model.trim="search" type="search" aria-label="Search complaints" placeholder="Call reference, caller, or phone" maxlength="100" :disabled="!!saving"><button class="cc-button secondary small" :disabled="loading || !!saving">Find</button></div></form>
            <div v-if="error" class="cc-alert" role="alert">{{ error }} <button class="cc-link" @click="fetchCalls()">Try again</button></div>
            <div v-else-if="loading" class="cc-empty" role="status">Loading complaints…</div>
            <div v-else-if="!calls.length" class="cc-empty"><h3>{{ search ? 'No matching complaints' : mode === 'open' ? 'No open complaints' : 'No closed complaints' }}</h3><p>{{ search ? 'Try a different reference, name or phone.' : mode === 'open' ? 'Complaints recorded in the call log appear here automatically.' : 'Closed complaints and their remarks will appear here.' }}</p></div>
            <article v-for="call in loading || error ? [] : calls" :key="call.id" :id="'complaint-' + call.id" tabindex="-1" class="cc-work-item cc-complaint-item" :aria-label="call.reference">
                <div>
                    <div class="cc-card-header compact"><h3>{{ call.reference }} · {{ call.caller_name || 'Caller not recorded' }}</h3><span class="cc-status" :class="call.status">{{ call.status === 'closed' ? 'Closed' : 'Open' }}</span></div>
                    <p class="cc-muted">{{ call.caller_phone || 'No phone recorded' }} · {{ time(call.called_at) }}</p>
                    <p v-if="journeySummary(call)" class="cc-muted">{{ journeySummary(call) }}</p>
                    <p class="cc-complaint-text">{{ call.remarks || 'No complaint description recorded.' }}</p>
                    <p class="cc-muted">Recorded by {{ call.agent?.name || '—' }}<span> · Responsible: {{ call.assignee?.name || 'Unassigned' }}</span> <button v-if="canAssign && call.status !== 'closed'" type="button" class="cc-link" :disabled="!!saving" :aria-expanded="!!assignments[call.id]" @click="toggleAssignment(call)">{{ assignments[call.id] ? 'Cancel change' : 'Change' }}</button></p>
                    <form v-if="assignments[call.id]" class="cc-complaint-assignment" @submit.prevent="assignComplaint(call)">
                        <label :for="'complaint-assignee-' + call.id">Responsible person</label>
                        <p v-if="assignments[call.id].loading" class="cc-muted" role="status">Loading people…</p>
                        <div v-else class="cc-search-row">
                            <select :id="'complaint-assignee-' + call.id" v-model="assignments[call.id].selected" :disabled="!!saving || !assignments[call.id].agents.length">
                                <option value="" disabled>Choose person</option>
                                <option v-for="agent in assignments[call.id].agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                            </select>
                            <button type="submit" class="cc-button small" :disabled="!!saving || !assignments[call.id].selected || Number(assignments[call.id].selected) === Number(call.assigned_to) || !!drafts[call.id]?.trim()">{{ saving === call.id && savingAction === 'assign' ? 'Assigning…' : 'Assign' }}</button>
                        </div>
                        <p v-if="drafts[call.id]?.trim()" class="cc-muted">Save your remarks before assigning.</p>
                        <p v-if="assignments[call.id].error" class="cc-field-error" role="alert">{{ assignments[call.id].error }}</p>
                        <button v-if="assignments[call.id].loadFailed" type="button" class="cc-link" @click="loadAssignees(call)">Try again</button>
                    </form>
                    <p v-if="call.external_reference" class="cc-muted">Reference: {{ call.external_reference }}</p>
                    <p v-if="call.callback_at && call.status !== 'closed'" class="cc-muted">Callback: {{ time(call.callback_at) }}</p>
                    <section v-if="call.latest_complaint_update" class="cc-complaint-update" aria-label="Latest complaint update">
                        <strong>Latest update</strong><p class="cc-complaint-text">{{ call.latest_complaint_update.remarks }}</p>
                        <p class="cc-muted">{{ time(call.latest_complaint_update.created_at) }} · {{ call.latest_complaint_update.actor?.name || 'Former agent' }}</p>
                    </section>
                    <button v-if="call.complaint_history_count" type="button" class="cc-link" :aria-expanded="!!historyOpen[call.id]" :aria-controls="'complaint-history-' + call.id" @click="toggleHistory(call)">{{ historyOpen[call.id] ? 'Hide history' : 'View history' }}</button>
                    <section v-if="historyOpen[call.id]" :id="'complaint-history-' + call.id" class="cc-complaint-history" aria-label="Complaint history">
                        <ol v-if="histories[call.id]?.items.length">
                            <li v-for="entry in histories[call.id].items" :key="entry.id">
                                <strong>{{ entry.kind === 'assignment' ? 'Responsibility changed' : entry.kind === 'follow_up' ? 'Follow-up call · ' + entry.reference : entry.kind === 'closed' ? 'Closed — closing remarks' : 'Update' }}</strong>
                                <p v-if="entry.kind === 'assignment'" class="cc-complaint-text">{{ entry.from_assignee?.name || 'Unassigned' }} → {{ entry.to_assignee?.name || 'Unassigned' }}</p>
                                <p v-else class="cc-complaint-text">{{ entry.remarks || 'No conversation notes recorded.' }}</p>
                                <p class="cc-muted">{{ time(entry.created_at) }} · {{ entry.actor?.name || 'Former agent' }}</p>
                                <p v-if="entry.kind === 'follow_up' && entry.callback_at" class="cc-muted">Next callback: {{ time(entry.callback_at) }}</p>
                            </li>
                        </ol>
                        <p v-if="histories[call.id]?.loading" role="status" class="cc-muted">Loading history…</p>
                        <p v-else-if="histories[call.id]?.error" role="alert" class="cc-field-error">{{ histories[call.id].error }} <button class="cc-link" @click="loadHistory(call, histories[call.id].requested_page)">Try again</button></p>
                        <button v-else-if="histories[call.id]?.page < histories[call.id]?.last_page" type="button" class="cc-link" @click="loadHistory(call, histories[call.id].page + 1)">More history</button>
                    </section>
                </div>
                <form v-if="call.status !== 'closed' && (options.permissions.update_complaints || options.permissions.close_complaints)" class="cc-complaint-close" @submit.prevent="saveRemarks(call, !options.permissions.update_complaints)">
                    <label :for="'complaint-remarks-' + call.id">{{ remarksLabel }} (required)</label>
                    <textarea :id="'complaint-remarks-' + call.id" v-model="drafts[call.id]" rows="3" maxlength="5000" required placeholder="Add a progress update or explain how the complaint was resolved." :disabled="!!saving" :aria-invalid="!!errors[call.id]"></textarea>
                    <p v-if="errors[call.id]" class="cc-field-error" role="alert">{{ errors[call.id] }}</p>
                    <div class="cc-actions">
                        <button v-if="options.permissions.update_complaints" type="submit" class="cc-button secondary" :disabled="!!saving || !drafts[call.id]?.trim()">{{ saving === call.id && savingAction === 'update' ? 'Saving…' : 'Save update' }}</button>
                        <button v-if="options.permissions.close_complaints" type="button" class="cc-button" :disabled="!!saving || !drafts[call.id]?.trim()" @click="saveRemarks(call, true)">{{ saving === call.id && savingAction === 'close' ? 'Closing…' : 'Close complaint' }}</button>
                    </div>
                </form>
                <div v-else-if="call.status === 'closed'" class="cc-complaint-resolution"><strong>Closing remarks</strong><p class="cc-complaint-text">{{ call.resolution_remarks || 'No closing remarks recorded for this older complaint.' }}</p><p v-if="call.resolved_at" class="cc-muted">Closed {{ time(call.resolved_at) }}<span v-if="call.resolver"> · {{ call.resolver.name }}</span></p><button v-if="options.permissions.update && options.permissions.reopen_complaints" class="cc-button secondary small" :disabled="!!saving" @click="reopenComplaint(call)">Reopen complaint</button><p v-if="errors[call.id]" class="cc-field-error" role="alert">{{ errors[call.id] }}</p></div>
            </article>
            <div v-if="!targetId" class="cc-pagination"><span>{{ pagination.total }} {{ pagination.total === 1 ? 'complaint' : 'complaints' }} · Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span><div class="cc-actions"><button class="cc-button secondary small" :disabled="loading || !!saving || pagination.current_page <= 1" @click="fetchCalls(pagination.current_page - 1)">Previous</button><button class="cc-button secondary small" :disabled="loading || !!saving || pagination.current_page >= pagination.last_page" @click="fetchCalls(pagination.current_page + 1)">Next</button></div></div>
        </div>
    </section>
</template>

<script>
import { request, displayTime, complaintUpdateRequest } from './callCenter';

export default {
    props: { options: { type: Object, required: true } },
    data: () => ({ mode: 'open', search: '', calls: [], drafts: {}, errors: {}, loading: false, saving: null, savingAction: '', assignments: {}, updateRequests: {}, histories: {}, historyOpen: {}, error: '', notice: '', sequence: 0, notificationError: '',
        pagination: { total: 0, current_page: 1, last_page: 1 }, discardVisible: false, discardResolve: null, returnFocus: null }),
    computed: {
        targetId() { return this.$route.query.complaint || ''; },
        canAssign() { return !!(this.options.permissions.view_complaints && this.options.permissions.update && this.options.permissions.assign_calls); },
        dirty() { return Object.values(this.drafts).some(value => value?.trim()) || this.calls.some(call => this.assignments[call.id]?.selected && Number(this.assignments[call.id].selected) !== Number(call.assigned_to)); },
        remarksLabel() { return this.options.permissions.update_complaints ? this.options.permissions.close_complaints ? 'Update / closing remarks' : 'Update' : 'Closing remarks'; },
        instructions() {
            if (this.options.permissions.update_complaints) return this.options.permissions.close_complaints ? 'Add an update, or add closing remarks and close the complaint.' : 'Add progress updates while keeping complaints open.';
            return this.options.permissions.close_complaints ? 'Add closing remarks, then close the complaint.' : 'View complaints, updates and completed resolutions.';
        },
    },
    watch: { '$route.fullPath'() { this.notice = ''; this.notificationError = ''; this.fetchCalls(1); } },
    mounted() { this.fetchCalls(); window.addEventListener('beforeunload', this.beforeUnload); },
    beforeUnmount() { this.sequence++; window.removeEventListener('beforeunload', this.beforeUnload); },
    methods: {
        time: displayTime,
        journeySummary(call) { return [call.complaint_from_city || call.complaint_to_city ? `${call.complaint_from_city || '—'} → ${call.complaint_to_city || '—'}` : '', call.complaint_bus_time ? `Bus ${call.complaint_bus_time}` : '', call.complaint_seat_number ? `Seat ${call.complaint_seat_number}` : ''].filter(Boolean).join(' · '); },
        selectMode(mode) { this.mode = mode; this.fetchCalls(1); },
        async fetchCalls(page = this.pagination.current_page) {
            const sequence = ++this.sequence; this.loading = true; this.error = '';
            try {
                if (this.targetId) {
                    if (!/^[1-9]\d*$/.test(this.targetId)) throw new Error('This complaint link is invalid.');
                    const call = await request(this, 'get', `calls/${this.targetId}`);
                    if (sequence !== this.sequence) return;
                    if (call.category !== 'C') throw new Error('This record is no longer a complaint.');
                    this.calls = [call]; this.mode = call.status === 'closed' ? 'closed' : 'open';
                    this.histories = {}; this.historyOpen = {}; this.assignments = {};
                    this.loading = false;
                    await this.$nextTick();
                    if (sequence !== this.sequence) return;
                    document.getElementById('complaint-' + call.id)?.focus({ preventScroll: true });
                    await this.markNotificationRead();
                    return;
                }
                const params = this.mode === 'open' ? { queue: 'open_complaints' } : { queue: 'all', category: 'C', status: 'closed' };
                const data = await request(this, 'get', 'calls', null, { ...params, search: this.search, page });
                if (sequence !== this.sequence) return;
                if (!data.data.length && page > 1) return this.fetchCalls(page - 1);
                this.calls = data.data;
                this.histories = {}; this.historyOpen = {}; this.assignments = {};
                this.pagination = { total: data.total, current_page: data.current_page, last_page: data.last_page };
            } catch (error) { if (sequence === this.sequence) this.error = error.message; }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
        async markNotificationRead() {
            const id = this.$route.query.notification;
            if (!/^[1-9]\d*$/.test(id || '') || !this.calls.some(call => String(call.id) === String(this.targetId))) return;
            const sequence = this.sequence;
            try {
                await request(this, 'post', `notifications/${id}/read`);
                if (sequence === this.sequence) this.notificationError = '';
                window.dispatchEvent(new Event('call-center-notifications-changed'));
            } catch (error) {
                if (sequence === this.sequence) this.notificationError = 'The complaint is open, but its notification could not be marked as read.';
            }
        },
        toggleAssignment(call) {
            if (!this.canAssign || this.saving || call.status === 'closed') return;
            if (this.assignments[call.id]) delete this.assignments[call.id];
            else this.loadAssignees(call);
        },
        async loadAssignees(call) {
            this.assignments[call.id] = { selected: call.assigned_to || '', agents: [], loading: true, error: '', loadFailed: false };
            const state = this.assignments[call.id];
            try {
                const agents = await request(this, 'get', `calls/${call.id}/complaint-assignees`);
                if (this.assignments[call.id] !== state) return;
                state.agents = agents;
                if (!agents.some(agent => Number(agent.id) === Number(state.selected))) state.selected = '';
                if (!agents.length) state.error = 'No eligible people are available for this complaint.';
            } catch (error) { if (this.assignments[call.id] === state) { state.error = error.message; state.loadFailed = true; } }
            finally { if (this.assignments[call.id] === state) state.loading = false; }
        },
        async assignComplaint(call) {
            const state = this.assignments[call.id];
            if (!this.canAssign || this.saving || call.status === 'closed' || !state?.selected || Number(state.selected) === Number(call.assigned_to) || this.drafts[call.id]?.trim()) return;
            this.saving = call.id; this.savingAction = 'assign'; state.error = ''; this.notice = '';
            try {
                const saved = await request(this, 'post', `calls/${call.id}/assign-complaint`, { assigned_to: Number(state.selected), expected_assigned_to: call.assigned_to || null });
                delete this.assignments[call.id];
                this.notice = `${call.reference} assigned to ${saved.assignee?.name || 'the selected person'}.`;
                if (saved.can_view === false) {
                    this.calls = this.calls.filter(item => item.id !== call.id);
                    this.pagination.total = Math.max(0, this.pagination.total - 1);
                    delete this.histories[call.id]; delete this.historyOpen[call.id];
                    this.notice += ' It is now outside your assigned work.';
                } else {
                    Object.assign(call, saved); this.historyOpen[call.id] = true; await this.loadHistory(call);
                }
                window.dispatchEvent(new Event('call-center-notifications-changed'));
            } catch (error) { state.error = error.message; }
            finally { this.saving = null; this.savingAction = ''; }
        },
        async reopenComplaint(call) {
            if (this.saving) return;
            this.saving = call.id; delete this.errors[call.id]; this.notice = '';
            try {
                await request(this, 'patch', `calls/${call.id}`, { status: 'new' });
                this.notice = `${call.reference} reopened. You can find it under Open.`;
                await this.fetchCalls();
            } catch (error) { this.errors[call.id] = error.message; }
            finally { this.saving = null; }
        },
        async saveRemarks(call, close = false) {
            if (this.saving || !(close ? this.options.permissions.close_complaints : this.options.permissions.update_complaints)) return;
            const remarks = (this.drafts[call.id] || '').trim();
            if (!remarks) { this.errors[call.id] = 'Enter update or closing remarks.'; return; }
            this.saving = call.id; this.savingAction = close ? 'close' : 'update'; delete this.errors[call.id]; this.notice = '';
            try {
                const endpoint = close ? `calls/${call.id}/close-complaint` : `calls/${call.id}/complaint-updates`;
                const payload = close ? { resolution_remarks: remarks } : complaintUpdateRequest(this.updateRequests, call.id, remarks);
                const saved = await request(this, 'post', endpoint, payload);
                delete this.drafts[call.id]; delete this.updateRequests[call.id];
                if (close || saved.status === 'closed') {
                    this.notice = close ? `${call.reference} closed. You can find it under Closed.` : `${call.reference} update saved. This complaint is now closed.`;
                    await this.fetchCalls();
                } else {
                    Object.assign(call, saved);
                    this.notice = `${call.reference} update saved. The complaint remains open.`;
                    if (this.historyOpen[call.id]) await this.loadHistory(call);
                }
            } catch (error) { this.errors[call.id] = error.message; }
            finally { this.saving = null; this.savingAction = ''; }
        },
        toggleHistory(call) {
            this.historyOpen[call.id] = !this.historyOpen[call.id];
            if (this.historyOpen[call.id]) this.loadHistory(call);
        },
        async loadHistory(call, page = 1) {
            const previous = this.histories[call.id];
            if (page > 1 && previous?.loading) return;
            this.histories[call.id] = { items: page > 1 ? previous.items : [], page: page > 1 ? previous.page : 0, requested_page: page, last_page: previous?.last_page || 1, loading: true, error: '' };
            const state = this.histories[call.id];
            try {
                const data = await request(this, 'get', `calls/${call.id}/complaint-updates`, null, { page });
                if (this.histories[call.id] !== state) return;
                state.items = [...new Map([...state.items, ...data.data].map(entry => [entry.id, entry])).values()];
                state.page = data.current_page; state.last_page = data.last_page;
            } catch (error) { if (this.histories[call.id] === state) state.error = error.message; }
            finally { if (this.histories[call.id] === state) state.loading = false; }
        },
        beforeUnload(event) { if (this.dirty || this.saving) { event.preventDefault(); event.returnValue = ''; } },
        confirmLeave() {
            if (this.saving || this.discardVisible) return false;
            if (!this.dirty) return true;
            this.returnFocus = document.activeElement; this.discardVisible = true;
            this.$nextTick(() => this.$refs.keepEditing?.focus());
            return new Promise(resolve => { this.discardResolve = resolve; });
        },
        decideDiscard(discard) { const resolve = this.discardResolve; this.discardResolve = null; this.discardVisible = false; if (discard) { this.drafts = {}; this.updateRequests = {}; this.assignments = {}; } resolve?.(discard); if (!discard) this.$nextTick(() => { if (this.returnFocus?.isConnected) this.returnFocus.focus(); }); },
        trapDialogFocus() { const buttons = this.$refs.discardDialog?.querySelectorAll('button'); if (buttons?.length) (document.activeElement === buttons[0] ? buttons[1] : buttons[0]).focus(); },
    },
};
</script>
