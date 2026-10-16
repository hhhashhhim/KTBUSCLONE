const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const parser = require('@babel/parser');

const files = ['resources/js/pages/booking/BookingPage.vue', 'resources/js/pages/schedule/SchedulePage.vue'];
const scripts = files.map(file => {
    const { descriptor, errors } = parse(fs.readFileSync(file, 'utf8'));
    assert.deepEqual(errors, []);
    parser.parse(descriptor.script.content, { sourceType: 'module' });
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: file, id: 'quota-test' }).errors, []);
    return descriptor.script.content;
});

function seatMethod(name) {
    const body = scripts[0].match(new RegExp(name + ': function \\(col\\) \\{([\\s\\S]*?)\\n        \\},'))[1];
    return vm.runInNewContext('(function(col) {' + body + '})');
}
const classes = seatMethod('getClasses');
const rescheduleClasses = seatMethod('getClassesReschedule');
assert.match(classes({ quota_reuse: true }), /quota-reuse-seat/);
assert.doesNotMatch(classes({ quota_reuse: true, selected: true }), /quota-reuse-seat/);
assert.match(classes({ quota_reuse: true, selected: true }), /selected/);
assert.doesNotMatch(classes({ quota_reuse: true, type: 'booked', gender: 0, online_terminal: 0 }), /quota-reuse-seat/);
assert.match(classes({ quota_reuse: true, type: 'booked', gender: 0, online_terminal: 0 }), /for-female-booked/);
assert.doesNotMatch(classes({ quota_reuse: true, type: 'not_for_sale' }), /quota-reuse-seat/);
assert.match(rescheduleClasses({ quota_reuse: true }), /quota-reuse-seat/);
assert.doesNotMatch(rescheduleClasses({ quota_reuse: true, alreadyBooked: true }), /quota-reuse-seat/);
assert.match(seatMethod('getTitle')({ quota_reuse: true }), /counts separately/);

const modalEvents = [];
const saveBody = scripts[1].match(/async saveQuota\(\) \{([\s\S]*?)\n        \},/)[1];
const save = vm.runInNewContext('(async function() {' + saveBody + '})', { $: selector => ({ modal: action => modalEvents.push([selector, action]) }) });
(async () => {
    let calls = 0;
    const state = {
        quotaSaving: false, quotaError: '', quotaForm: { id: 12, limit: '' }, fetchSchedule: async () => {},
        callApi: async (method, path, data) => {
            calls++;
            assert.equal(method, 'post'); assert.equal(path, 'schedule/quota'); assert.equal(data.id, 12);
            const expected = state.quotaForm.limit === '' ? null : Number(state.quotaForm.limit);
            assert.equal(data.intermediate_quota, expected);
            return { status: 200, data: { message: 'Saved' } };
        },
    };
    for (const limit of ['', '0', '10']) {
        state.quotaForm.limit = limit;
        await save.call(state);
        assert.equal(state.quotaSaving, false);
        assert.equal(state.quotaError, '');
    }
    assert.equal(calls, 3); assert.equal(modalEvents.length, 3);
    for (const limit of ['-1', '2.5', '100001', 'wrong']) {
        state.quotaForm.limit = limit;
        await save.call(state);
        assert.match(state.quotaError, /whole number/);
    }
    assert.equal(calls, 3);
    state.quotaForm.limit = 10;
    state.callApi = async () => ({ status: 403, data: { message: 'Not authorized' } });
    await save.call(state);
    assert.equal(state.quotaError, 'Not authorized');
    assert.equal(state.quotaSaving, false);
    assert.equal(modalEvents.length, 3);
    console.log('Schedule quota UI: templates, validation, saving, and seat-colour precedence passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
