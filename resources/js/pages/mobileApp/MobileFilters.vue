<template>
    <form class="ma-filters" @submit.prevent="$emit('apply')">
        <div v-if="search" class="ma-field ma-search"><label :for="id + '-search'">Search</label><input :id="id + '-search'" class="form-control" :value="modelValue.search" @input="set('search', $event.target.value)" :placeholder="search" maxlength="100"></div>
        <template v-if="dates">
            <div class="ma-field"><label :for="id + '-from'">From</label><input :id="id + '-from'" class="form-control" type="date" :value="modelValue.from" @input="set('from', $event.target.value)" :max="modelValue.to" required></div>
            <div class="ma-field"><label :for="id + '-to'">To</label><input :id="id + '-to'" class="form-control" type="date" :value="modelValue.to" @input="set('to', $event.target.value)" :min="modelValue.from" required></div>
        </template>
        <div class="ma-field" v-if="statuses.length"><label :for="id + '-status'">Status</label><select :id="id + '-status'" class="form-control" :value="modelValue.status" @change="set('status', $event.target.value)"><option value="">All statuses</option><option v-for="status in statuses" :key="status" :value="status">{{ label(status) }}</option></select></div>
        <div class="ma-field" v-if="terminals && options.permissions.all_terminals"><label :for="id + '-terminal'">Terminal</label><select :id="id + '-terminal'" class="form-control" :value="modelValue.terminal_id" @change="set('terminal_id', $event.target.value)"><option value="">All permitted terminals</option><option v-for="terminal in options.terminals" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option></select></div>
        <div class="ma-field" v-if="routes"><label :for="id + '-route'">Route</label><select :id="id + '-route'" class="form-control" :value="modelValue.route_id" @change="set('route_id', $event.target.value)"><option value="">All routes</option><option v-for="route in options.routes" :key="route.id" :value="route.id">{{ route.name }}</option></select></div>
        <div class="ma-field" v-if="environments"><label :for="id + '-environment'">Payment environment</label><select :id="id + '-environment'" class="form-control" :value="modelValue.environment" @change="set('environment', $event.target.value)"><option value="live">Live & historical</option><option value="sandbox">Sandbox</option><option value="all">All environments</option></select></div>
        <slot />
        <button class="btn btn-success" :disabled="busy"><i class="fas fa-filter mr-1"></i>{{ busy ? 'Loading…' : 'Apply filters' }}</button>
    </form>
</template>
<script>
import { label } from './mobileApp';
export default {
    props: { id: String, modelValue: Object, options: Object, busy: Boolean, search: String, dates: Boolean, terminals: Boolean, routes: Boolean, environments: Boolean, statuses: { type: Array, default: () => [] } },
    emits: ['update:modelValue', 'apply'], methods: { label, set(key, value) { this.$emit('update:modelValue', { ...this.modelValue, [key]: value }); } },
};
</script>
