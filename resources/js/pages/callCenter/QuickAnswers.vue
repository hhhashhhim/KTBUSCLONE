<template>
    <details class="cc-card cc-quick" @toggle="onToggle">
        <summary>Quick answers <span class="cc-muted">Fares, seats, departures & terminal details</span></summary>
        <div class="cc-card-body">
            <p v-if="error" class="cc-alert" role="alert">{{ error }} <button type="button" class="cc-link" @click="loadOptions">Retry</button></p>
            <p v-if="loading" role="status">Loading travel information…</p>
            <template v-else-if="info">
                <details><summary>Terminal addresses & phone numbers</summary><div v-for="terminal in info.terminals" :key="terminal.id" class="cc-ticket"><strong>{{ terminal.name }}</strong><p>{{ terminal.address || 'Address not recorded' }}</p><p>{{ terminal.contact || 'Phone not recorded' }}</p></div><p v-if="!info.terminals.length">No terminal details available for your access.</p></details>
                <form class="cc-form-stack" @submit.prevent="search">
                    <div class="cc-grid"><div class="cc-field"><label for="quick-from">From</label><select v-cc-select2 id="quick-from" v-model="form.departure_id" required><option value="">Choose city</option><option v-for="city in info.cities" :key="city.id" :value="city.id">{{ city.name }}</option></select></div><div class="cc-field"><label for="quick-to">To</label><select v-cc-select2 id="quick-to" v-model="form.destination_id" required><option value="">Choose city</option><option v-for="city in info.cities" :key="city.id" :value="city.id">{{ city.name }}</option></select></div></div>
                    <div class="cc-grid"><div class="cc-field"><label for="quick-date">Travel date</label><input id="quick-date" type="date" v-model="form.date" :min="today()" required></div><div v-if="info.terminals.length > 1" class="cc-field"><label for="quick-terminal">Booking terminal</label><select v-cc-select2 id="quick-terminal" v-model="form.terminal_id" required><option value="">Choose terminal</option><option v-for="terminal in info.terminals" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option></select></div></div>
                    <div class="cc-actions">
                        <button type="submit" class="cc-button" :disabled="searching">{{ searching ? 'Checking…' : 'Check fares, departures & seats' }}</button>
                        <button type="button" class="cc-button secondary" @click="reset">Reset</button>
                    </div>
                </form>
                <p v-if="searchError" class="cc-field-error" role="alert">{{ searchError }}</p>
                <div v-if="answer" aria-live="polite">
                    <h3>{{ cityName(active.departure_id) }} → {{ cityName(active.destination_id) }} · {{ active.date }}</h3>
                    <p class="cc-muted">{{ answer.terminal.name }} · Checked {{ time(answer.checked_at) }}</p>
                    <p v-if="!answer.departures.length" class="cc-note">No upcoming departures found for this date and your permitted routes.</p>
                    <template v-else><div v-for="departure in answer.departures" :key="departure.id" class="cc-ticket"><div class="cc-departure-heading"><strong>{{ time(departure.departure_at) }}</strong><span class="cc-seat-count" :class="{ empty: departure.available_seats === 0, unknown: departure.available_seats == null }">{{ departure.available_seats == null ? 'Seat count unavailable' : departure.available_seats + (departure.available_seats === 1 ? ' seat available' : ' seats available') }}</span></div><p>{{ departure.schedule_name }} · {{ departure.bus_class }}</p></div><p class="cc-muted">{{ answer.schedule_note }}</p>
                    <h3 class="cc-spaced">Base fares</h3><p v-for="fare in answer.fares" :key="fare.class_name">{{ fare.class_name }} · PKR {{ Number(fare.base_fare).toLocaleString('en-PK') }}</p><p v-if="!answer.fares.length">Fare not recorded for this journey.</p><p class="cc-note">{{ answer.fare_note }}</p></template>
                </div>
            </template>
        </div>
    </details>
</template>
<script>
import callCenterSelect2 from './callCenterSelect2';
import { request, pakistanDate, displayTime } from './callCenter';
export default {
    directives: { ccSelect2: callCenterSelect2 },
    props: { currentUser: Object },
    data: () => ({ info: null, loading: false, error: '', searching: false, searchError: '', answer: null, active: {}, form: { departure_id: '', destination_id: '', date: pakistanDate(), terminal_id: '' }, sequence: 0 }),
    beforeUnmount() { this.sequence++; },
    methods: {
        today: pakistanDate, time: displayTime,
        defaultTerminal() {
            const terminals = this.info?.terminals || [];
            return terminals.find(t => Number(t.id) === Number(this.currentUser?.terminal_id))?.id || terminals[0]?.id || '';
        },
        reset() {
            // Ignore any outstanding search so it cannot restore results after reset.
            this.sequence++;
            this.form = { departure_id: '', destination_id: '', date: this.today(), terminal_id: this.defaultTerminal() };
            this.answer = null; this.active = {}; this.error = ''; this.searchError = ''; this.searching = false;
        },
        cityName(id) { return this.info.cities.find(city => Number(city.id) === Number(id))?.name || ''; },
        onToggle(event) { if (event.target.open && !this.info && !this.loading) this.loadOptions(); },
        async loadOptions() {
            this.loading = true; this.error = '';
            try { this.info = await request(this, 'get', 'quick-answers/options'); this.form.terminal_id = this.defaultTerminal(); }
            catch (error) { this.error = error.message; } finally { this.loading = false; }
        },
        async search() {
            const sequence = ++this.sequence, params = { ...this.form };
            this.searchError = ''; this.answer = null; this.searching = true;
            try { const answer = await request(this, 'get', 'quick-answers', null, params); if (sequence === this.sequence) { this.answer = answer; this.active = params; } }
            catch (error) { if (sequence === this.sequence) this.searchError = error.message; }
            finally { if (sequence === this.sequence) this.searching = false; }
        },
    },
};
</script>
