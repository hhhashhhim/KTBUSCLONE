<template>
    <div class="modal fade" id="terminalTicketActivityModal" tabindex="-1" role="dialog" aria-labelledby="ticketActivityTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
            <div class="modal-header">
                <div><h5 id="ticketActivityTitle" class="modal-title">Ticket Details &amp; Activity</h5><small class="text-muted">Search an invoice to see its seats and saved history.</small></div>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body activity-body">
                <form class="invoice-search" @submit.prevent="search">
                    <div><label for="ticketActivityInvoice">Invoice number</label><input id="ticketActivityInvoice" ref="invoiceInput" v-model="invoice" class="form-control" inputmode="numeric" maxlength="20" placeholder="Enter invoice number" :disabled="loading"></div>
                    <button class="btn btn-primary" type="submit" :disabled="loading"><i class="fas fa-search mr-1"></i>{{ loading ? 'Searching...' : 'Find ticket' }}</button>
                </form>
                <div v-if="error" class="alert alert-danger mt-3" role="alert">{{ error }}</div>
                <div v-if="loading" class="py-4 text-center" role="status">Loading ticket details and activity...</div>
                <template v-if="result && !loading">
                    <div class="activity-summary"><div><small>INVOICE</small><h5 class="mb-0">#{{ result.invoice_id }}</h5></div><span>{{ result.tickets.length }} seat record(s), including history</span></div>
                    <p class="activity-note">{{ result.note }}</p>
                    <article v-for="ticket in result.tickets" :key="ticket.id" class="seat-detail">
                        <div class="seat-heading"><strong>Seat {{ ticket.seat }} <small class="text-muted">&middot; Ticket #{{ ticket.id }}</small></strong><span class="ticket-status" :class="statusClass(ticket)">{{ ticket.status }}</span></div>
                        <p v-if="ticket.historical || ticket.removed" class="text-muted mb-2">{{ ticket.historical ? 'Previous seat — rescheduled to another ticket.' : 'Archived ticket record.' }}</p>
                        <dl class="ticket-grid">
                            <div><dt>Passenger</dt><dd>{{ ticket.passenger || 'Not recorded' }}</dd></div>
                            <div><dt>CNIC / contact</dt><dd>{{ ticket.cnic || 'Not recorded' }}<br>{{ ticket.contact || 'Not recorded' }}</dd></div>
                            <div><dt>Route / departure (saved)</dt><dd>{{ ticket.route || 'Not recorded' }}<br>{{ ticket.departure || 'Not recorded' }}</dd></div>
                            <div><dt>Terminal</dt><dd>{{ ticket.terminal || 'Not recorded' }}</dd></div>
                            <div><dt>Bus / class</dt><dd>{{ ticket.bus || 'Not recorded' }}<br>{{ ticket.bus_class || 'Not recorded' }}</dd></div>
                            <div><dt>Transaction ID</dt><dd>{{ ticket.transaction_id || 'Not recorded' }}</dd></div>
                        </dl>
                        <div class="ticket-money"><span>Seat fare <b>{{ money(ticket.fare) }}</b></span><span>Discount <b>{{ money(ticket.discount) }}</b></span><span>Net ticket fare <b>{{ money(ticket.net_fare) }}</b></span><span>Recorded refund <b>{{ money(ticket.recorded_refund) }}</b></span></div>
                        <p v-if="ticket.remarks" class="mt-3 mb-0"><b>Remarks:</b> {{ ticket.remarks }}</p>
                        <p v-if="ticket.cancellation_reason" class="mt-2 mb-0"><b>Cancellation reason:</b> {{ ticket.cancellation_reason }}</p>
                    </article>
                    <h6 class="mt-4">Activity history <small class="text-muted">&middot; oldest first</small></h6>
                    <ol class="ticket-timeline"><li v-for="(event, index) in result.events" :key="index"><div class="event-heading"><strong>{{ event.action }}</strong><time>{{ event.at || 'Time not recorded' }}</time></div><div class="event-meta">Seat {{ event.seat }} &middot; {{ event.actor }}</div><p>{{ event.detail }}</p></li></ol>
                </template>
            </div>
            <div class="modal-footer">
                <form :action="$store.state.api_url + 'api/web/v1/print/pdf/terminal/ticket-activity'" method="POST" target="_blank">
                    <input type="hidden" name="token" :value="$store.state.token"><input type="hidden" name="invoice_id" :value="result ? result.invoice_id : ''">
                    <button type="submit" class="btn btn-primary" :disabled="loading || !result"><i class="fas fa-print mr-1"></i> Print details &amp; activity</button>
                </form>
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
            </div>
        </div></div>
    </div>
</template>
<script>
export default {
    name: 'TicketActivityModal',
    data() { return { invoice: '', loading: false, error: '', result: null }; },
    beforeUnmount() { $('#terminalTicketActivityModal').modal('hide'); },
    methods: {
        open(invoice = '') {
            if (!this.loading) { this.invoice = String(invoice || ''); this.result = null; this.error = ''; }
            $('#terminalTicketActivityModal').modal('show');
            this.$nextTick(() => this.$refs.invoiceInput?.focus());
        },
        async search() {
            if (this.loading) return;
            this.error = ''; this.result = null;
            const invoice = this.invoice.trim();
            if (!/^\d{1,20}$/.test(invoice)) { this.error = 'Enter a valid numeric invoice number.'; return; }
            this.loading = true;
            try {
                const response = await this.callApi('post', 'terminals/sales/ticket-details', { invoice_id: invoice });
                if (response.status === 200) this.result = response.data;
                else this.error = response.status === 404 ? 'No ticket found for this invoice within your permitted access.' : 'Unable to load ticket details. Please try again.';
            } catch (error) {
                this.error = error.response?.status === 404 ? 'No ticket found for this invoice within your permitted access.' : 'Unable to load ticket details. Please try again.';
            } finally { this.loading = false; }
        },
        money(value) { return value === null || value === undefined ? 'Not recorded' : 'Rs ' + Number(value).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        statusClass(ticket) { return /cancel|expired/i.test(ticket.status) ? 'status-cancelled' : ticket.historical ? 'status-history' : 'status-current'; },
    },
};
</script>
<style scoped>
.activity-body { max-height: 73vh; overflow-y: auto; }
.invoice-search { display: flex; align-items: flex-end; gap: 14px; padding: 18px; background: #f7f8fd; border-radius: 10px; }
.invoice-search > div { flex: 1; } .invoice-search .btn { min-height: 42px; }
.activity-summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 0 12px; color: #34426a; }
.activity-note { color: #6c7688; font-size: 12px; }
.seat-detail { border: 1px solid #e1e6f0; border-radius: 10px; padding: 18px; margin-top: 14px; }
.seat-heading, .event-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
.ticket-status { padding: 4px 10px; border-radius: 5px; font-size: 12px; text-transform: capitalize; }
.status-cancelled { background: #fff0ef; color: #a42d30; } .status-current { background: #eaf7f0; color: #236c49; } .status-history { background: #eef0ff; color: #534da9; }
.ticket-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 0 0 16px; }
.ticket-grid dt { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #8590a5; } .ticket-grid dd { margin: 4px 0 0; color: #354264; overflow-wrap: anywhere; }
.ticket-money { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; padding: 12px; border-radius: 7px; background: #f6f8fc; font-size: 12px; }
.ticket-money b { display: block; color: #34426a; font-size: 14px; margin-top: 5px; }
.ticket-timeline { list-style: none; padding-left: 16px; margin: 20px 0 0 7px; border-left: 2px solid #e1e5f1; }
.ticket-timeline li { position: relative; padding: 0 0 20px 8px; } .ticket-timeline li::before { content: ''; position: absolute; left: -23px; top: 5px; width: 12px; height: 12px; background: #6c6ef7; border-radius: 50%; }
.event-heading { margin-bottom: 3px; } .event-heading time, .event-meta { color: #77849b; font-size: 12px; } .ticket-timeline p { margin: 6px 0 0; overflow-wrap: anywhere; }
@media (max-width: 767px) { .ticket-grid, .ticket-money { grid-template-columns: repeat(2, minmax(0, 1fr)); } .activity-summary, .event-heading { align-items: flex-start; flex-direction: column; } .invoice-search { flex-wrap: wrap; } }
</style>
