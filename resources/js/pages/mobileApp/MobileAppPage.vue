<template>
    <section class="section ma-page">
        <header class="ma-heading"><div><p class="ma-eyebrow">MOBILE OPERATIONS</p><h1>Mobile App</h1><p class="ma-muted">Monitor passengers and bookings, manage app controls, and review every change.</p></div><span class="ma-timezone">Pakistan time · PKT</span></header>
        <nav class="ma-tabs" aria-label="Mobile App submodules"><router-link v-for="screen in screens" :key="screen.name" :to="{ name: screen.name }"><i :class="screen.icon"></i>{{ screen.label }}</router-link></nav>
        <div v-if="loading" class="ma-panel ma-empty" role="status"><i class="fas fa-circle-notch fa-spin"></i>Loading your Mobile App workspace…</div>
        <div v-else-if="error" class="ma-panel ma-empty" role="alert"><h2>Mobile App unavailable</h2><p>{{ error }}</p><button class="btn btn-success" @click="loadOptions">Try again</button></div>
        <template v-else-if="options">
            <p v-for="(notice,index) in visibleNotices" :key="index" class="ma-note">{{ typeof notice === 'string' ? notice : notice.message }}</p>
            <div v-if="!canOpen" class="ma-panel ma-empty"><i class="fas fa-lock"></i><h2>Access not granted</h2><p>Your role does not include this submodule. Select an available tab or ask an administrator to update your privileges.</p></div>
            <component v-else :is="component" :key="$route.name" ref="screen" :options="options" />
        </template>
    </section>
</template>
<script>
import Overview from './Overview.vue';
import Availability from './Availability.vue';
import Passengers from './Passengers.vue';
import Bookings from './Bookings.vue';
import Payments from './Payments.vue';
import Reports from './Reports.vue';
import Settings from './Settings.vue';
import Content from './Content.vue';
import Activity from './Activity.vue';
import Notifications from './Notifications.vue';
import { mobileScreens, request } from './mobileApp';
const components = { 'mobile-app': Overview, 'mobile-app-availability': Availability, 'mobile-app-users': Passengers, 'mobile-app-bookings': Bookings, 'mobile-app-payments': Payments, 'mobile-app-reports': Reports, 'mobile-app-settings': Settings, 'mobile-app-content': Content, 'mobile-app-activity': Activity, 'mobile-app-notifications': Notifications };
export default {
    data: () => ({ loading: true, error: '', options: null }),
    computed: { visibleNotices() { return (this.options?.notices || []).filter(notice => typeof notice === 'string' || !notice.screens || notice.screens.includes(this.$route.name)); }, screens() { return mobileScreens.filter(screen => this.options?.permissions?.[screen.permission] === true); }, canOpen() { return this.screens.some(screen => screen.name === this.$route.name); }, component() { return components[this.$route.name]; } },
    mounted() { this.loadOptions(); },
    beforeRouteLeave() { return this.$refs.screen?.confirmLeave?.() ?? true; },
    beforeRouteUpdate(to, from) { return to.name === from.name || (this.$refs.screen?.confirmLeave?.() ?? true); },
    methods: { async loadOptions() { this.loading = true; this.error = ''; try { if (!await this.ensurePageReady()) throw new Error('Please sign in to open Mobile App.'); this.options = await request(this, 'get', 'options'); if (this.$route.name === 'mobile-app' && !this.canOpen && this.screens.length) await this.$router.replace({ name: this.screens[0].name }); } catch (error) { this.error = error.message; } finally { this.loading = false; } } },
};
</script>
<style src="./mobileApp.css"></style>
