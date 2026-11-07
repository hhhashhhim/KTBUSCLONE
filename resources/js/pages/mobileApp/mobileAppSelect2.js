// Preserve Vue's native v-model values, validation and @change handlers.
const controls = new WeakMap();
const optionSignature = el => JSON.stringify(Array.from(el.options, option => [
    option.value, option.text, option.disabled,
    option.parentElement?.tagName === 'OPTGROUP' ? option.parentElement.label : null,
]));

function sync(el, state) {
    state.control.trigger('change.select2');
    const selection = state.control.next('.select2-container').find('.select2-selection');
    selection.attr('aria-invalid', el.getAttribute('aria-invalid') || 'false');
    selection.attr('aria-required', el.required ? 'true' : 'false');
    selection.attr('aria-disabled', el.disabled ? 'true' : 'false');
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
        // Native dialogs are in the browser top layer: their dropdown must stay inside.
        // On normal pages escape the cards' clipped overflow.
        dropdownParent: $(el.closest('.ma-dialog') || el.closest('.ma-page') || el.parentElement),
    });
    const state = { control, signature: optionSignature(el), dispatching: false };
    controls.set(el, state);
    control.on('change.maSelect2', event => {
        if (state.dispatching || event.originalEvent || el.disabled) return;
        state.dispatching = true;
        try {
            el.dispatchEvent(new window.Event('change', { bubbles: true }));
        } finally { state.dispatching = false; }
    });
    control.on('select2:open.maSelect2', () => {
        control.data('select2')?.$dropdown.find('.select2-search__field').trigger('focus');
    });
    sync(el, state);
}

function destroy(el) {
    const state = controls.get(el);
    if (!state) return;
    state.control.off('.maSelect2');
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
