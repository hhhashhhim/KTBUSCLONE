<template>
    <section class="ma-panel ma-panel-body" aria-labelledby="ma-wallet-title">
        <div class="ma-panel-head px-0 pt-0">
            <div><h3 id="ma-wallet-title">Wallet</h3><p class="ma-muted">The customer’s existing loyalty-points balance.</p></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="loading || saving || busy" @click="reload">Reload balance</button>
        </div>
        <p v-if="loading" class="ma-empty" role="status">Loading wallet…</p>
        <div v-if="error" class="ma-error" role="alert">{{ error }}</div>
        <div v-if="notice" class="ma-error ma-success" role="status">{{ notice }}</div>
        <template v-if="wallet && !loading">
            <p v-if="!wallet.enabled || !wallet.active" class="ma-note">{{ wallet.message }}</p>
            <template v-else>
                <dl class="ma-details"><div><dt>Current balance</dt><dd><strong>{{ number(wallet.points_available) }} points</strong></dd></div><div><dt>Loyalty card</dt><dd>#{{ wallet.card_id }} · Expires {{ wallet.expires_at }}</dd></div></dl>
                <p v-if="!permissions.users_wallet" class="ma-note">View only. Adjusting points requires the wallet adjustment privilege and account-control access.</p>
                <form v-else class="ma-form" @submit.prevent="save">
                    <fieldset :disabled="saving || busy || stale"><legend>Adjust points balance</legend>
                        <div class="ma-grid">
                            <div class="ma-field"><label for="ma-wallet-points">New balance (points)</label><input id="ma-wallet-points" v-model="points" class="form-control" type="number" min="0" max="2147483647" step="1" required aria-describedby="ma-wallet-help"></div>
                            <div class="ma-field"><label for="ma-wallet-reason">Reason for adjustment</label><input id="ma-wallet-reason" v-model.trim="reason" class="form-control" minlength="3" maxlength="1000" required placeholder="Explain why the balance is changing"></div>
                        </div>
                        <p id="ma-wallet-help" class="ma-muted mt-2">Enter the total balance the customer should have after this adjustment. This also changes their linked loyalty card balance.</p>
                        <p v-if="dirty && validPoints" class="ma-note">Balance: {{ number(wallet.points_available) }} → {{ number(Number(points)) }} points ({{ Number(points) > wallet.points_available ? '+' : '' }}{{ number(Number(points) - wallet.points_available) }}).</p>
                    </fieldset>
                    <p v-if="stale" class="ma-note ma-warning">Reload and review the current balance before saving again.</p>
                    <div class="ma-actions"><p class="ma-muted">Every adjustment records the staff member, reason, and previous and new balances.</p><button type="submit" class="btn btn-success" :disabled="!canSave">{{ saving ? 'Saving…' : 'Save points balance' }}</button></div>
                </form>
            </template>
        </template>
    </section>
</template>
<script>
import { request, number } from './mobileApp';

export default {
    props: { userId: Number, permissions: Object, busy: Boolean },
    emits: ['busy', 'dirty'],
    data: () => ({ wallet: null, points: '', reason: '', loading: false, saving: false, stale: false, error: '', notice: '', sequence: 0 }),
    computed: {
        dirty() { return !!this.wallet?.active && (String(this.points) !== String(this.wallet.points_available) || this.reason !== ''); },
        validPoints() { return String(this.points).trim() !== '' && Number.isInteger(Number(this.points)) && Number(this.points) >= 0 && Number(this.points) <= 2147483647; },
        canSave() { return !!this.permissions.users_wallet && !!this.wallet?.enabled && !!this.wallet?.active && !this.loading && !this.saving && !this.busy && !this.stale && this.validPoints && Number(this.points) !== this.wallet.points_available && this.reason.trim().length >= 3; },
    },
    watch: { dirty(value) { this.$emit('dirty', value); } },
    mounted() { this.load(); },
    beforeUnmount() { this.sequence++; this.$emit('dirty', false); },
    methods: {
        number,
        confirmLeave() { return !this.saving && (!this.dirty || window.confirm('Discard the unsaved wallet adjustment?')); },
        async reload() { if (!this.busy && this.confirmLeave()) await this.load(); },
        setWallet(wallet) { this.wallet = wallet; this.points = wallet.points_available ?? ''; this.reason = ''; this.stale = false; },
        async load() {
            const sequence = ++this.sequence; this.loading = true; this.error = ''; this.notice = '';
            try { const wallet = await request(this, 'get', `users/${this.userId}/wallet`); if (sequence === this.sequence) this.setWallet(wallet); }
            catch (error) { if (sequence === this.sequence) { this.error = error.message; this.stale = true; } }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
        async save() {
            if (!this.canSave) return;
            this.saving = true; this.error = ''; this.notice = ''; this.$emit('busy', true);
            try {
                const wallet = await request(this, 'put', `users/${this.userId}/wallet`, {
                    points_available: Number(this.points), revision: this.wallet.revision, reason: this.reason.trim(),
                });
                this.setWallet(wallet); this.notice = 'Loyalty-points balance updated.';
            } catch (error) {
                this.error = error.message;
                // An uncertain response may follow a committed write. Review before retrying.
                this.stale = true;
            } finally { this.saving = false; this.$emit('busy', false); }
        },
    },
};
</script>
