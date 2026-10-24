<template>
    <div class="modal fade" id="scheduleQuotaDiscountModal" tabindex="-1" aria-labelledby="quotaDiscountTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" @submit.prevent="save">
                <div class="modal-header"><h5 id="quotaDiscountTitle" class="modal-title">Quota Discount</h5><button type="button" class="close" @click="close" :disabled="saving" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    <div class="bg-light border rounded p-3 mb-3"><strong>{{ schedule.name }}</strong><div class="text-muted">{{ schedule.route?.name || '' }}</div></div>
                    <div class="alert alert-info">All users booking through the selected terminal receive this discount on available, already-used quota seats. For example, seats 1 and 2 booked Rawalpindi to Faisalabad can receive 2% off when reused Faisalabad to Multan. Other seats keep their normal fares.</div>
                    <p class="small text-muted">Select the Website, Sasta Ticket or Bookkaru terminal to enable its API offers. APIs use the terminal assigned to their login; portal bookings use the selected booking terminal. The percentage applies after existing fare discounts, rounded to the nearest rupee. Both non-overlapping earlier and onward journeys qualify. An unlimited/blank quota disables these offers; 0% disables a terminal's discount.</p>
                    <div v-if="loading" role="status" class="text-center p-3">Loading terminals and discounts...</div>
                    <template v-else>
                        <div v-for="(row, index) in rows" :key="row.key" class="row align-items-end mb-3">
                            <div v-if="row.migration_note" class="col-12"><div class="alert alert-warning" role="alert">{{ row.migration_note }}</div></div>
                            <div class="col-md-7"><label :for="'quota-discount-terminal-' + row.key">Terminal</label><select2 :id="'quota-discount-terminal-' + row.key" v-model="row.terminal_id" :options="terminalOptions" :settings="selectSettings" :disabled="saving" /></div>
                            <div class="col-md-3"><label :for="'quota-discount-percent-' + row.key">Discount %</label><input :id="'quota-discount-percent-' + row.key" class="form-control" type="number" min="0" max="100" step="0.01" required v-model="row.percentage" :disabled="saving"></div>
                            <div class="col-md-2"><button type="button" class="btn btn-outline-danger" :disabled="saving" @click="rows.splice(index, 1)" :aria-label="'Remove discount row ' + (index + 1)">Remove</button></div>
                        </div>
                        <p v-if="loaded && !rows.length" class="text-muted">No terminal discounts configured. Add a terminal below.</p>
                        <button type="button" class="btn btn-outline-primary" :disabled="saving || !loaded" @click="addRow">+ Add Terminal</button>
                    </template>
                    <div v-if="error" class="alert alert-danger mt-3 mb-0" role="alert">{{ error }} <button v-if="!loaded && !loading" type="button" class="btn btn-sm btn-outline-danger ml-2" @click="open(schedule)">Retry</button></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="close" :disabled="saving">Close</button><button type="submit" class="btn btn-primary" :disabled="loading || saving || !loaded">{{ saving ? 'Saving...' : 'Save Discounts' }}</button></div>
            </form>
        </div>
    </div>
</template>
<script>
export default {
    data: () => ({ schedule: {}, rows: [], terminals: [], loading: false, loaded: false, saving: false, error: '', nextKey: 0, requestId: 0 }),
    computed: {
        terminalOptions() { return [{ id: '', text: 'Select terminal' }, ...this.terminals.map(terminal => ({ id: String(terminal.id), text: `${terminal.name} (#${terminal.id})` }))]; },
        selectSettings() { return { dropdownParent: '#scheduleQuotaDiscountModal', width: '100%', minimumResultsForSearch: 0 }; },
    },
    methods: {
        close() { if (!this.saving) $('#scheduleQuotaDiscountModal').modal('hide'); },
        addRow() { this.rows.push({ key: ++this.nextKey, terminal_id: '', percentage: '' }); },
        async open(schedule) {
            if (this.saving) return;
            const requestId = ++this.requestId;
            this.schedule = schedule; this.rows = []; this.terminals = []; this.error = ''; this.loading = true; this.loaded = false;
            $('#scheduleQuotaDiscountModal').modal({ backdrop: 'static', keyboard: false });
            $('#scheduleQuotaDiscountModal').modal('show');
            try {
                const response = await this.callApi('post', 'schedule/quota-discount/get', { id: schedule.id });
                if (requestId !== this.requestId) return;
                if (response?.status !== 200) throw new Error(response?.data?.message || 'Unable to load quota discounts.');
                this.terminals = response.data.terminals;
                this.rows = response.data.discounts.map(row => ({ key: ++this.nextKey, terminal_id: String(row.terminal_id), percentage: row.percentage, migration_note: row.migration_note }));
                this.loaded = true;
            } catch (error) { if (requestId === this.requestId) this.error = error.message || 'Unable to load quota discounts.'; }
            finally { if (requestId === this.requestId) this.loading = false; }
        },
        async save() {
            if (this.loading || this.saving || !this.loaded) return;
            this.error = '';
            const seen = new Set();
            for (const row of this.rows) {
                if (!row.terminal_id || seen.has(String(row.terminal_id)) || !/^\d+(\.\d{1,2})?$/.test(String(row.percentage)) || Number(row.percentage) > 100) {
                    this.error = 'Choose a different terminal for each row and enter a percentage from 0 to 100 (up to two decimal places).'; return;
                }
                seen.add(String(row.terminal_id));
            }
            this.saving = true;
            try {
                const response = await this.callApi('post', 'schedule/quota-discount/save', {
                    id: this.schedule.id, discounts: this.rows.map(row => ({ terminal_id: Number(row.terminal_id), percentage: Number(row.percentage) })),
                });
                if (response?.status !== 200) throw new Error(response?.data?.errors ? Object.values(response.data.errors).flat().join(' ') : response?.data?.message || 'Unable to save quota discounts.');
                $('#scheduleQuotaDiscountModal').modal('hide');
            } catch (error) { this.error = error.message || 'Unable to save quota discounts. Please try again.'; }
            finally { this.saving = false; }
        },
    },
};
</script>
