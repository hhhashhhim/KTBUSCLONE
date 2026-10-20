<template>
    <Teleport to="body">
        <dialog ref="dialog" class="ma-dialog ma-page" aria-labelledby="ma-dialog-title" @cancel.prevent="close" @click="backdrop">
            <header class="ma-dialog-head"><h2 id="ma-dialog-title">{{ title }}</h2><button type="button" class="ma-close" aria-label="Close dialog" :disabled="busy" @click="close">&times;</button></header>
            <div class="ma-dialog-body"><slot /></div>
        </dialog>
    </Teleport>
</template>
<script>
export default {
    props: { title: String, busy: Boolean }, emits: ['close'],
    mounted() { this.$refs.dialog.showModal(); },
    beforeUnmount() { this.$refs.dialog?.close(); },
    methods: {
        close() { if (!this.busy) this.$emit('close'); },
        backdrop(event) { if (event.target === this.$refs.dialog) { const box = event.target.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) this.close(); } },
    },
};
</script>
