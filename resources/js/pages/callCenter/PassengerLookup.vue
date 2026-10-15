<template>
    <section class="cc-lookup" aria-label="Find a passenger">
        <form @submit.prevent="search">
            <label for="passenger-search">Phone, ticket number, or CNIC <span class="cc-muted">(optional)</span></label>
            <div class="cc-search-row">
                <input id="passenger-search" v-model.trim="query" @input="clearResults" placeholder="Type or paste a number" autocomplete="off" maxlength="50" :disabled="disabled">
                <button class="cc-button secondary" :disabled="searching || disabled || !query">{{ searching ? 'Searching…' : 'Find' }}</button>
            </div>
            <div v-if="choices.length" class="cc-note cc-choice-note"><p>This number could be more than one type. Search as:</p><div class="cc-actions"><button v-for="kind in choices" :key="kind" type="button" class="cc-button secondary small" @click="searchAs(kind)" :disabled="disabled">{{ labels[kind] }}</button></div></div>
            <div v-if="searchedAs" class="cc-muted">Searched by {{ labels[searchedAs].toLowerCase() }}. <button type="button" class="cc-link" @click="choices = ['phone', 'ticket', 'cnic']">Change type</button></div>
        </form>
        <p v-if="error" class="cc-field-error" role="alert">{{ error }}</p>
        <p v-if="searched && !passengers.length" class="cc-note">No matching passenger. You can still save this call.</p>
        <div v-if="passengers.length" class="cc-results" aria-label="Passenger search results">
            <article v-for="passenger in passengers" :key="passenger.id" class="cc-passenger">
                <div class="cc-card-header compact"><div><strong>{{ passenger.name }}</strong><p class="cc-muted">{{ passenger.contact }} · {{ passenger.cnic_masked }}</p></div><button type="button" class="cc-button secondary small" @click="select(passenger)" :disabled="disabled">Use passenger</button></div>
                <details :open="category === 'BK'"><summary>Bookings ({{ passenger.tickets.length }})</summary>
                    <div v-for="ticket in passenger.tickets" :key="ticket.id" class="cc-ticket">
                        <strong>{{ ticket.booking_no || ticket.invoice_id || ticket.id }} · Seat {{ ticket.seat_no }}</strong>
                        <p>{{ ticket.departure || '—' }} → {{ ticket.destination || '—' }}</p><p>{{ ticket.schedule_date }} · {{ ticket.schedule_time }} · {{ ticket.terminal_name }}</p>
                        <p>{{ ticket.can_link_booking ? 'Confirmed booking' : ticket.type }}</p>
                        <button type="button" class="cc-button secondary small" @click="select(passenger, ticket)" :disabled="disabled || (category === 'BK' && !ticket.can_link_booking)">Use this booking</button>
                    </div>
                </details>
            </article>
        </div>
    </section>
</template>
<script>
import { request, searchKinds } from './callCenter';
export default {
    props: { category: String, disabled: Boolean }, emits: ['select', 'phone'],
    data: () => ({ query: '', choices: [], searchedAs: '', passengers: [], searching: false, searched: false, error: '', sequence: 0, labels: { phone: 'Phone number', ticket: 'Ticket number', cnic: 'CNIC' } }),
    beforeUnmount() { this.sequence++; },
    methods: {
        clearResults() { this.sequence++; this.passengers = []; this.choices = []; this.searched = false; this.searchedAs = ''; this.error = ''; this.searching = false; },
        search() {
            const kinds = searchKinds(this.query);
            this.error = '';
            if (!kinds.length) { this.error = 'Enter a complete phone number, numeric ticket number, or 13-digit CNIC.'; return; }
            if (kinds.length > 1) { this.choices = kinds; return; }
            this.searchAs(kinds[0]);
        },
        async searchAs(kind) {
            const sequence = ++this.sequence, query = this.query;
            this.choices = []; this.searching = true; this.error = ''; this.searched = false; this.passengers = []; this.searchedAs = kind;
            // Keep the caller number even when lookup is slow, fails, or finds no passenger.
            if (kind === 'phone' && /^[+()\d\s-]+$/.test(query) && query.replace(/\D/g, '').length >= 10 && query.length <= 25) this.$emit('phone', query);
            try {
                const data = await request(this, 'get', 'passengers', null, { search_by: kind, q: query });
                if (sequence !== this.sequence) return;
                this.passengers = data.passengers; this.searched = true;
            } catch (error) { if (sequence === this.sequence) this.error = error.message; }
            finally { if (sequence === this.sequence) this.searching = false; }
        },
        select(passenger, ticket = null) { this.$emit('select', { passenger, ticket }); this.clearResults(); },
    },
};
</script>
