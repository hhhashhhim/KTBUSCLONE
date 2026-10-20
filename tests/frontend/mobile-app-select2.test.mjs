import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const source = await readFile(new URL('../../resources/js/pages/mobileApp/mobileAppSelect2.js', import.meta.url), 'utf8');
const { default: directive } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function fixture() {
    const calls = { mounts: 0, destroys: 0, changes: 0, redraws: 0, focused: 0, attrs: {} };
    const handlers = {};
    const page = {};
    const el = {
        options: [{ value: '', text: 'Choose shift once', disabled: false }, { value: '1', text: 'Shift 1', disabled: false }],
        value: '', disabled: false, required: true, labels: [{ textContent: 'Your shift' }],
        closest: selector => selector === '.ma-page' ? page : null,
        getAttribute: key => key === 'aria-invalid' ? el.invalid || 'false' : null,
        dispatchEvent(event) { calls.changes++; calls.value = el.value; assert.equal(event.type, 'change'); assert.equal(event.bubbles, true); handlers['change.maSelect2']?.({ originalEvent: event }); },
    };
    const selection = { attr: (key, value) => { calls.attrs[key] = value; } };
    const control = {
        select2(settings) { if (settings === 'destroy') calls.destroys++; else { calls.mounts++; calls.settings = settings; } return control; },
        on(name, handler) { handlers[name] = handler; return control; },
        off() { for (const name of Object.keys(handlers)) delete handlers[name]; return control; },
        hasClass() { return true; },
        trigger(name) { assert.equal(name, 'change.select2'); calls.redraws++; return control; },
        next() { return { find: () => selection }; },
        data() { return { $dropdown: { find: () => ({ trigger: () => calls.focused++ }) } }; },
    };
    const $ = target => target === el ? control : target;
    $.fn = { select2() {} };
    globalThis.window = { jQuery: $, Event: class { constructor(type, options) { this.type = type; Object.assign(this, options); } } };
    return { el, calls, handlers, page };
}

test('Select2 is always searchable, fits its field, stays outside clipped cards and labels its control', () => {
    const { el, calls, handlers, page } = fixture();
    directive.mounted(el);
    assert.equal(calls.settings.minimumResultsForSearch, 0);
    assert.equal(calls.settings.width, '100%');
    assert.equal(calls.settings.dropdownParent, page);
    assert.equal(calls.attrs['aria-label'], 'Your shift');
    assert.equal(calls.attrs['aria-required'], 'true');
    handlers['select2:open.maSelect2']();
    assert.equal(calls.focused, 1);
    directive.beforeUnmount(el);
});

test('Selections reach native Vue bindings once; native events and disabled fields do not double-dispatch', () => {
    const { el, calls, handlers } = fixture();
    directive.mounted(el);
    el.value = '1'; handlers['change.maSelect2']({});
    assert.equal(calls.changes, 1); assert.equal(calls.value, '1');
    handlers['change.maSelect2']({ originalEvent: {} });
    el.disabled = true; handlers['change.maSelect2']({});
    assert.equal(calls.changes, 1);
    el.value = ''; directive.updated(el);
    assert.equal(calls.changes, 1, 'Resetting a form refreshes the display without changing the model again');
    assert.equal(calls.mounts, 1, 'Ordinary form edits do not recreate dropdowns');
    directive.beforeUnmount(el);
});

test('Changing options rebuilds Select2, validation follows Vue, and unmount cleans handlers', () => {
    const { el, calls, handlers } = fixture();
    directive.mounted(el);
    el.options[1].text = 'Renamed shift'; el.invalid = 'true'; directive.updated(el);
    assert.equal(calls.mounts, 2); assert.equal(calls.destroys, 1);
    assert.equal(calls.attrs['aria-invalid'], 'true');
    el.options.push({ value: '2', text: 'Shift 2', disabled: false }); directive.updated(el);
    assert.equal(calls.mounts, 3);
    directive.beforeUnmount(el); directive.beforeUnmount(el);
    assert.equal(calls.destroys, 3); assert.deepEqual(handlers, {});
});

test('A native select remains usable if the legacy Select2 script is unavailable', () => {
    const { el, calls } = fixture(); globalThis.window = {};
    directive.mounted(el); directive.updated(el); directive.beforeUnmount(el);
    assert.equal(calls.mounts, 0);
});

test('Every Mobile App select is enhanced without replacing native model modifiers or change handlers', async () => {
    let total = 0;
    for (const name of ['Activity', 'AppControls', 'Availability', 'Bookings', 'Content', 'LoginActivity', 'MobileAppPage', 'MobileDialog', 'MobileFilters', 'MobilePagination', 'Notifications', 'Overview', 'PassengerLimits', 'PassengerWallet', 'Passengers', 'Payments', 'Reports', 'Settings']) {
        const filename = `resources/js/pages/mobileApp/${name}.vue`;
        const text = await readFile(new URL('../../' + filename, import.meta.url), 'utf8');
        const { descriptor, errors } = parse(text, { filename });
        assert.deepEqual(errors, []);
        assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: name }).errors, []);
        const tags = descriptor.template.content.match(/<select\b[^>]*>/g) || [];
        for (const tag of tags) { assert.match(tag, /v-ma-select2/); assert.match(tag, /v-model|:value=/); }
        total += tags.length;
        if (tags.length) assert.match(descriptor.script.content, /directives: \{ maSelect2: mobileAppSelect2 \}/);
    }
    assert.equal(total, 14);
});


test('Dialog selects keep the dropdown in the modal top layer', () => {
    const { el, calls } = fixture();
    const dialog = {};
    el.closest = selector => selector === '.ma-dialog' ? dialog : null;
    directive.mounted(el);
    assert.equal(calls.settings.dropdownParent, dialog);
    directive.beforeUnmount(el);
});

test('Updated optgroup labels rebuild report options', () => {
    const { el, calls } = fixture();
    el.options[1].parentElement = { tagName: 'OPTGROUP', label: 'Booking reports' };
    directive.mounted(el);
    el.options[1].parentElement.label = 'Passenger reports';
    directive.updated(el);
    assert.equal(calls.mounts, 2);
    directive.beforeUnmount(el);
});
