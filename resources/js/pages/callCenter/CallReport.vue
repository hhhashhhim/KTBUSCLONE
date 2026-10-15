<template>
    <div>
        <section class="cc-card cc-no-print">
            <div class="cc-card-header"><div><h2>{{ daily ? 'Daily management report' : 'Agent shift summary' }}</h2><p class="cc-muted">{{ options.permissions.supervise ? 'Review calls across your permitted team.' : 'Your own calls, grouped by shift.' }}</p></div><button class="cc-button secondary" @click="printReport" :disabled="!report || loading">Print A4</button></div>
            <form class="cc-filters" @submit.prevent="loadReport">
                <div class="cc-field"><label for="report-date">Shift date</label><input id="report-date" type="date" v-model="filters.date" required></div>
                <div v-if="!daily" class="cc-field"><label for="report-shift">Shift</label><select id="report-shift" v-model="filters.shift"><option value="">All shifts</option><option v-for="shift in options.shifts" :key="shift" :value="shift">Shift {{ shift }}</option></select></div>
                <div v-if="options.permissions.all_terminals" class="cc-field"><label for="report-terminal">Terminal</label><select id="report-terminal" v-model="filters.terminal_id"><option value="">All permitted terminals</option><option v-for="terminal in options.terminals" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option></select></div>
                <button class="cc-button" :disabled="loading">{{ loading ? 'Loading…' : 'View report' }}</button>
            </form>
        </section>
        <div v-if="error" class="cc-alert cc-no-print" role="alert">{{ error }}</div>
        <div v-if="notice" class="cc-alert success cc-no-print" role="status">{{ notice }}</div>
        <div v-if="loading" class="cc-card cc-empty" role="status">Preparing report…</div>
        <section v-else-if="report" class="cc-card cc-report" aria-label="Printable call center report">
            <div class="cc-report-title"><div><p class="cc-eyebrow">KAINAT TRAVELS</p><h2>{{ daily ? 'Call Center Daily Report' : 'Individual Call Log — Shift Tally' }}</h2><p class="cc-muted">{{ activeFilters.date }} · {{ daily || !activeFilters.shift ? 'All shifts' : 'Shift ' + activeFilters.shift }} · {{ terminalLabel }}</p></div><div class="cc-muted">Generated {{ generatedAt }}<br>Asia/Karachi (PKT)</div></div>
            <div class="cc-stats"><div class="cc-stat"><span>Total calls</span><strong>{{ report.totals.total }}</strong></div><div v-for="category in options.categories" :key="category.value" class="cc-stat"><span>{{ category.label }}</span><strong>{{ report.totals[category.value] }}</strong></div></div>
            <p class="cc-muted">Total calls = General Information + Confirmed Booking + Complaint + Follow-up</p>
            <div class="cc-report-section"><h3>Agent and shift totals</h3><div class="cc-table-wrap"><table class="cc-table"><thead><tr><th>Agent</th><th>Shift</th><th class="cc-numeric">GI</th><th class="cc-numeric">BK</th><th class="cc-numeric">C</th><th class="cc-numeric">FU</th><th class="cc-numeric">Total</th><th class="cc-numeric">Seats discussed</th></tr></thead><tbody><tr v-for="row in report.rows" :key="row.agent_id + '-' + row.shift"><td>{{ row.agent_name }}</td><td>{{ row.shift }}</td><td class="cc-numeric">{{ row.GI }}</td><td class="cc-numeric">{{ row.BK }}</td><td class="cc-numeric">{{ row.C }}</td><td class="cc-numeric">{{ row.FU }}</td><td class="cc-numeric"><strong>{{ row.total }}</strong></td><td class="cc-numeric">{{ row.seats }}</td></tr><tr v-if="!report.rows.length"><td colspan="8" class="cc-empty">No calls recorded for this selection.</td></tr></tbody><tfoot><tr><td colspan="2">Total</td><td class="cc-numeric">{{ report.totals.GI }}</td><td class="cc-numeric">{{ report.totals.BK }}</td><td class="cc-numeric">{{ report.totals.C }}</td><td class="cc-numeric">{{ report.totals.FU }}</td><td class="cc-numeric">{{ report.totals.total }}</td><td class="cc-numeric">{{ report.totals.seats }}</td></tr></tfoot></table></div><p class="cc-muted">GI: General Information · BK: Confirmed Booking · C: Complaint · FU: Follow-up</p><p class="cc-muted">Seats attached to booking calls; repeated calls may include the same seats.</p></div>

            <template v-if="daily">
                <div class="cc-report-panels"><div class="cc-report-panel"><h3>Complaints & follow-ups</h3><p class="cc-muted">Current status of calls logged on this date.</p><p><strong>Complaints:</strong> {{ report.complaints.closed }} closed / {{ report.complaints.pending }} pending</p><p><strong>Follow-ups:</strong> {{ report.followups.closed }} completed / {{ report.followups.pending }} pending</p></div><div class="cc-report-panel"><h3>Call category ratios</h3><p><strong>Confirmed Booking:</strong> {{ percent(report.ratios.booking) }}%</p><p><strong>General Information:</strong> {{ percent(report.ratios.general_information) }}%</p><p class="cc-muted">Category calls ÷ total calls × 100</p></div></div>
                <div class="cc-report-section"><h3>Seat changes by terminal</h3><div class="cc-table-wrap"><table class="cc-table"><thead><tr><th>Terminal</th><th class="cc-numeric">Shifted seats</th><th class="cc-numeric">Recorded refunds (seats)</th></tr></thead><tbody><tr v-for="row in report.seat_movements" :key="row.terminal_id"><td>{{ row.terminal_name }}</td><td class="cc-numeric">{{ row.shifted }}</td><td class="cc-numeric">{{ row.refunded }}</td></tr><tr v-if="!report.seat_movements.length"><td colspan="3">No recorded seat changes for this selection.</td></tr></tbody></table></div><p class="cc-muted">{{ report.seat_movements_note }}</p></div>
                <div class="cc-report-section"><h3>Management review</h3><div v-if="report.review" class="cc-review"><strong>{{ report.review.stale ? 'Review needs updating' : 'Reviewed' }} · {{ report.review.reviewer?.name }}</strong><p>{{ time(report.review.reviewed_at) }}</p><p v-if="report.review.stale">Report data changed after this review.</p><p><strong>Operational remarks:</strong> {{ report.review.remarks || '—' }}</p><p><strong>Next-day actions:</strong> {{ report.review.next_day_actions || '—' }}</p></div><p v-else class="cc-muted">Awaiting management review.</p></div>
            </template>
            <div v-else class="cc-report-section"><h3>Shift in-charge review</h3><div v-for="review in report.reviews" :key="review.id" class="cc-review"><strong>Shift {{ review.shift }} · {{ review.stale ? 'Review needs updating' : 'Reviewed' }}</strong><p>{{ review.reviewer?.name }} · {{ time(review.reviewed_at) }}</p><p v-if="review.stale">Calls changed after this review. Please check the updated totals.</p><p>{{ review.remarks || 'No remarks.' }}</p></div><p v-if="!report.reviews.length" class="cc-muted">No shift review recorded for this selection.</p></div>
        </section>

        <section v-if="report && !loading && options.permissions.review" class="cc-card cc-no-print">
            <div class="cc-card-header"><h2>{{ daily ? 'Record management review' : 'Record shift review' }}</h2></div>
            <form class="cc-card-body cc-form-stack" @submit.prevent="saveReview">
                <p class="cc-note">Reviewing {{ activeFilters.date }} · {{ terminalLabel }}. {{ daily ? 'Save operational remarks and the next steps for tomorrow.' : 'Choose the shift you have checked.' }}</p>
                <div v-if="!daily" class="cc-field"><label for="review-shift">Shift reviewed</label><select id="review-shift" v-model.number="reviewForm.shift" @change="prefillShiftReview" required><option v-for="shift in reviewableShifts" :key="shift" :value="shift">Shift {{ shift }}</option></select></div>
                <div class="cc-field"><label for="review-remarks">{{ daily ? 'Operational remarks' : 'In-charge remarks' }}</label><textarea id="review-remarks" v-model.trim="reviewForm.remarks" maxlength="5000" rows="3"></textarea></div>
                <div v-if="daily" class="cc-field"><label for="review-actions">Next-day actions</label><textarea id="review-actions" v-model.trim="reviewForm.next_day_actions" maxlength="5000" rows="2"></textarea></div>
                <div v-if="reviewError" class="cc-alert" role="alert">{{ reviewError }}</div>
                <div><button class="cc-button" :disabled="saving">{{ saving ? 'Saving…' : 'Save review' }}</button></div>
            </form>
        </section>
    </div>
</template>

<script>
import { request, pakistanDate, displayTime, reviewSnapshot } from './callCenter';

export default {
    props: { options: { type: Object, required: true }, daily: Boolean },
    data() {
        return { filters: { date: pakistanDate(), shift: '', terminal_id: '' }, activeFilters: {}, report: null, loading: false, error: '', reviewError: '', notice: '', saving: false, generatedAt: '', reviewForm: { shift: 1, remarks: '', next_day_actions: '' } };
    },
    computed: {
        terminalLabel() { return this.activeFilters.terminal_id ? this.options.terminals.find(item => Number(item.id) === Number(this.activeFilters.terminal_id))?.name || 'Selected terminal' : this.options.permissions.all_terminals ? 'All permitted terminals' : this.options.terminals.find(item => Number(item.id) === Number(this.options.current_user.terminal_id))?.name || 'Permitted scope'; },
        reviewableShifts() { return this.activeFilters.shift ? [Number(this.activeFilters.shift)] : this.options.shifts; },
    },
    mounted() { this.loadReport(); },
    beforeUnmount() { document.body.classList.remove('call-center-print'); },
    methods: {
        time: displayTime,
        percent(value) { return Number(value || 0).toFixed(1); },
        async loadReport() {
            this.loading = true; this.error = ''; this.report = null; this.reviewError = '';
            const params = { ...this.filters };
            if (this.daily) delete params.shift;
            try {
                this.report = await request(this, 'get', this.daily ? 'daily-report' : 'summary', null, params);
                this.activeFilters = params;
                this.generatedAt = displayTime(new Date().toISOString());
                this.reviewForm = { shift: Number(params.shift || 1), remarks: this.daily ? this.report.review?.remarks || '' : '', next_day_actions: this.daily ? this.report.review?.next_day_actions || '' : '' };
                if (!this.daily) this.prefillShiftReview();
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        prefillShiftReview() {
            this.reviewForm.remarks = this.report?.reviews.find(review => Number(review.shift) === Number(this.reviewForm.shift))?.remarks || '';
        },
        async saveReview() {
            this.reviewError = ''; this.notice = '';
            let snapshotToken;
            try { snapshotToken = reviewSnapshot(this.report, this.daily, this.reviewForm.shift); }
            catch (error) { this.reviewError = error.message; return; }
            this.saving = true;
            const payload = { date: this.activeFilters.date, terminal_id: this.activeFilters.terminal_id || (this.options.permissions.all_terminals ? 0 : this.options.current_user.terminal_id), remarks: this.reviewForm.remarks, snapshot_token: snapshotToken };
            if (this.daily) payload.next_day_actions = this.reviewForm.next_day_actions;
            else payload.shift = this.reviewForm.shift;
            try {
                await request(this, 'post', this.daily ? 'daily-review' : 'shift-review', payload);
                this.notice = 'Review saved successfully.';
                // Reload exactly the selection just reviewed, even if the filter controls were edited.
                this.filters = { ...this.filters, ...this.activeFilters };
                await this.loadReport();
            } catch (error) { this.reviewError = error.message; }
            finally { this.saving = false; }
        },
        printReport() {
            document.body.classList.add('call-center-print');
            window.addEventListener('afterprint', () => document.body.classList.remove('call-center-print'), { once: true });
            window.print();
        },
    },
};
</script>
