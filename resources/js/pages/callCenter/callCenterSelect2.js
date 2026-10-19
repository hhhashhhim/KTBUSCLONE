// Enhance native selects so Vue keeps its v-model types, validation and change handlers.
const controls = new WeakMap();
const optionSignature = el => JSON.stringify(Array.from(el.options, option => [option.value, option.text, option.disabled]));

function sync(el, state) {
    state.control.trigger('change.select2');
    const selection = state.control.next('.select2-container').find('.select2-selection');
    selection.attr('aria-invalid', el.getAttribute('aria-invalid') || 'false');
    selection.attr('aria-required', el.required ? 'true' : 'false');
    const label = el.getAttribute('aria-label') || Array.from(el.labels || [], item => item.textContent.trim()).join(' ');
    if (label) selection.attr('aria-label', label);
}

function mount(el) {
    const $ = typeof window !== 'undefined' && (window.jQuery || window.$);
    if (!$?.fn?.select2) return;
    const control = $(el);
    control.select2({
        width: '100%',
        minimumResultsForSearch: 0,
        // Cards clip overflow. Keep dropdowns outside cards, but inside the styled page.
        dropdownParent: $(el.closest('.cc-page') || el.parentElement),
    });
    const state = { control, signature: optionSignature(el), dispatching: false };
    controls.set(el, state);
    control.on('change.ccSelect2', event => {
        if (state.dispatching || event.originalEvent || el.disabled) return;
        state.dispatching = true;
        try {
            // Select2 emits jQuery events; Vue's native v-model needs a DOM event.
            el.dispatchEvent(new window.Event('change', { bubbles: true }));
        } finally { state.dispatching = false; }
    });
    control.on('select2:open.ccSelect2', () => {
        control.data('select2')?.$dropdown.find('.select2-search__field').trigger('focus');
    });
    sync(el, state);
}

function destroy(el) {
    const state = controls.get(el);
    if (!state) return;
    state.control.off('.ccSelect2');
    if (state.control.hasClass('select2-hidden-accessible')) state.control.select2('destroy');
    controls.delete(el);
}

export default {
    mounted: mount,
    updated(el) {
        const state = controls.get(el);
        if (!state || state.signature !== optionSignature(el)) {
            destroy(el);
            mount(el);
        } else {
            sync(el, state);
        }
    },
    beforeUnmount: destroy,
};
