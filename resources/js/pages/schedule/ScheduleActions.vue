<template>
    <button ref="trigger" type="button" class="btn btn-outline-secondary btn-sm schedule-actions-trigger"
            :aria-label="'Actions for ' + label" aria-haspopup="menu" :aria-expanded="opened"
            @click="toggle" @keydown.down.prevent="show">
        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
    </button>
    <Teleport to="body">
        <div v-if="opened" ref="menu" class="schedule-actions-menu" role="menu" :aria-label="label + ' actions'"
             :style="position" @click="choose" @keydown="navigate">
            <slot />
        </div>
    </Teleport>
</template>
<script>
export default {
    props: { label: { type: String, default: 'schedule' } },
    data: () => ({ opened: false, position: {} }),
    beforeUnmount() { this.close(); },
    methods: {
        toggle() { this.opened ? this.close() : this.show(); },
        async show() {
            if (this.opened) return;
            this.opened = true;
            await this.$nextTick();
            if (!this.opened || !this.$refs.menu) return;
            const rect = this.$refs.trigger.getBoundingClientRect();
            const menu = this.$refs.menu;
            this.position = {
                left: Math.max(8, Math.min(rect.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - 8)) + 'px',
                top: Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - menu.offsetHeight - 8)) + 'px',
            };
            document.addEventListener('pointerdown', this.outside);
            document.addEventListener('keydown', this.escape);
            window.addEventListener('resize', this.close);
            window.addEventListener('scroll', this.scroll, true);
            menu.querySelector('button:not(:disabled)')?.focus({ preventScroll: true });
        },
        close() {
            this.opened = false;
            document.removeEventListener('pointerdown', this.outside);
            document.removeEventListener('keydown', this.escape);
            window.removeEventListener('resize', this.close);
            window.removeEventListener('scroll', this.scroll, true);
        },
        outside(event) {
            if (!this.$refs.trigger?.contains(event.target) && !this.$refs.menu?.contains(event.target)) this.close();
        },
        scroll(event) { if (!this.$refs.menu?.contains(event.target)) this.close(); },
        escape(event) {
            if (event.key === 'Escape') { event.preventDefault(); this.close(); this.$refs.trigger?.focus(); }
        },
        choose(event) { if (event.target.closest('button:not(:disabled)')) this.close(); },
        navigate(event) {
            if (event.key === 'Tab') { this.close(); return; }
            if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const items = Array.from(this.$refs.menu.querySelectorAll('button:not(:disabled)'));
            const current = items.indexOf(document.activeElement);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                : (current + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items[next]?.focus();
        },
    },
};
</script>
<style>
.schedule-actions-trigger { width: 36px; min-height: 34px; }
.schedule-actions-menu { position: fixed; z-index: 1040; width: 245px; max-width: calc(100vw - 16px); max-height: calc(100vh - 16px); overflow-y: auto; padding: 6px; background: #fff; border: 1px solid #e4e6ef; border-radius: 8px; box-shadow: 0 6px 22px rgba(28, 39, 60, .18); }
.schedule-actions-menu button { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; border: 0; border-radius: 5px; background: transparent; color: #34395e; text-align: left; font-size: 14px; }
.schedule-actions-menu button:hover, .schedule-actions-menu button:focus-visible { background: #f0f2ff; }
.schedule-actions-menu button:disabled { opacity: .5; cursor: not-allowed; }
.schedule-actions-menu button i { width: 18px; text-align: center; }
</style>
