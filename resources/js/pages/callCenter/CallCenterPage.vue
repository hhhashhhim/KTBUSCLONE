<template>
    <section class="section cc-page">
        <header class="cc-heading cc-no-print">
            <div><p class="cc-eyebrow">PASSENGER SUPPORT</p><h1>Call Center</h1><p class="cc-muted">Log a call, keep the next step clear, and review the day.</p></div>
            <span class="cc-timezone">Pakistan time · PKT</span>
        </header>
        <nav class="cc-tabs cc-no-print" aria-label="Call center screens">
            <router-link v-for="screen in screens" :key="screen.name" :to="{ name: screen.name }">{{ screen.label }}</router-link>
        </nav>
        <div v-if="loading" class="cc-card cc-empty" role="status">Loading your call center workspace…</div>
        <div v-else-if="error" class="cc-card cc-empty" role="alert"><h2>Workspace unavailable</h2><p>{{ error }}</p><button class="cc-button" @click="loadOptions">Try again</button></div>
        <template v-else-if="options">
            <div v-if="!canOpenScreen" class="cc-card cc-empty"><h2>Access not granted</h2><p>Your role does not include this screen. Choose an available tab or ask your administrator to update your role.</p></div>
            <CallLog v-else-if="$route.name === 'call-center'" ref="callLog" :options="options" />
            <Complaints v-else-if="$route.name === 'call-center-complaints'" ref="complaints" :options="options" />
            <CallReport v-else :key="$route.name" :options="options" :daily="$route.name === 'call-center-daily'" />
        </template>
    </section>
</template>

<script>
import CallLog from './CallLog.vue';
import CallReport from './CallReport.vue';
import Complaints from './Complaints.vue';
import { request, callCenterScreens } from './callCenter';

export default {
    components: { CallLog, CallReport, Complaints },
    data: () => ({ options: null, loading: true, error: '' }),
    computed: {
        screens() { return callCenterScreens(this.options?.permissions); },
        canOpenScreen() { return this.screens.some(screen => screen.name === this.$route.name); },
    },
    mounted() { this.loadOptions(); },
    beforeRouteLeave() { return (this.$refs.callLog || this.$refs.complaints)?.confirmLeave() ?? true; },
    beforeRouteUpdate(to, from) { return (to.name === from.name && to.query.complaint === from.query.complaint) || ((this.$refs.callLog || this.$refs.complaints)?.confirmLeave() ?? true); },
    methods: {
        async loadOptions() {
            this.loading = true;
            this.error = '';
            try {
                if (!await this.ensurePageReady()) throw new Error('Please sign in to open the call center.');
                this.options = await request(this, 'get', 'options');
                if (this.$route.name === 'call-center' && !this.canOpenScreen && this.screens.length) {
                    await this.$router.replace({ name: this.screens[0].name });
                }
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
    },
};
</script>

<style src="./callCenter.css"></style>
