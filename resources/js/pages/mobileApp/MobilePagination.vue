<template>
    <div class="ma-pagination" v-if="pagination">
        <span>{{ number(pagination.total) }} records · Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
        <div class="ma-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || pagination.current_page <= 1" @click="$emit('change', pagination.current_page - 1)">Previous</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || pagination.current_page >= pagination.last_page" @click="$emit('change', pagination.current_page + 1)">Next</button>
        </div>
    </div>
</template>
<script>
import { number } from './mobileApp';
export default { props: { pagination: Object, busy: Boolean }, emits: ['change'], methods: { number } };
</script>
