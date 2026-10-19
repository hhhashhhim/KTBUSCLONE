import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { webcrypto } from 'node:crypto';

// Node 16 does not expose the browser crypto global used for request IDs.
if (!globalThis.crypto) globalThis.crypto = webcrypto;

// The legacy application is bundled by Mix and does not declare package type=module.
const source = await readFile(new URL('../../resources/js/pages/callCenter/callCenter.js', import.meta.url), 'utf8');
const { pakistanDate, inputDateTime, callbackValue, apiError, request, canAccessCallCenter, reviewSnapshot, callCenterScreens, seatChangeSummary, callQueueParams, complaintUpdateRequest, complaintNotificationRoute } =
    await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('shift business date uses Pakistan time at UTC midnight boundary', () => {
    assert.equal(pakistanDate(new Date('2026-10-13T18:59:00Z')), '2026-10-13');
    assert.equal(pakistanDate(new Date('2026-10-13T19:01:00Z')), '2026-10-14');
});

test('callbacks round-trip Pakistan wall-clock time without browser timezone conversion', () => {
    assert.equal(inputDateTime('2026-10-13 20:45:00'), '2026-10-13T20:45');
    assert.equal(inputDateTime('2026-10-13T15:45:00.000Z'), '2026-10-13T20:45');
    assert.equal(callbackValue('2026-10-13T20:45'), '2026-10-13 20:45:00');
    assert.equal(callbackValue(''), null);
    assert.equal(inputDateTime(null), '');
});

test('sidebar uses independent Call Center access without Ticketing', () => {
    const permission = allow => [{ name: 'ticketing', allow: false, childs: [] }, { name: 'call-center', allow, buttons: [] }];
    assert.equal(canAccessCallCenter(permission(true)), true);
    assert.equal(canAccessCallCenter(permission('1')), true);
    assert.equal(canAccessCallCenter(permission(false)), false);
    assert.equal(canAccessCallCenter(permission('0')), false);
    assert.equal(canAccessCallCenter([{ name: 'ticketing', allow: true, childs: [{ name: 'call-center', allow: true }] }]), false);
    assert.equal(canAccessCallCenter([]), false);
});

test('API adapter preserves exact contract, filters and JSON validation response', async () => {
    const payload = { category: 'FU', parent_call_id: 12 };
    const vm = { async callApi(method, url, body, config) {
        assert.equal(method, 'post');
        assert.equal(url, 'call-center/calls');
        assert.deepEqual(body, payload);
        assert.deepEqual(config.params, { page: 2 });
        assert.equal(config.headers.Accept, 'application/json');
        assert.equal(config.validateStatus(422), true);
        return { status: 201, data: { status: true, data: { id: 13, reference: 'CC-13' } } };
    } };
    assert.deepEqual(await request(vm, 'post', 'calls', payload, { page: 2 }), { id: 13, reference: 'CC-13' });
});

test('validation, permission and network failures remain actionable on the page', async () => {
    assert.equal(apiError({ data: { errors: { ticket_id: ['Select the confirmed booking.'], shift: ['Select a shift.'] } } }), 'Select the confirmed booking. Select a shift.');
    for (const [status, message] of [[403, 'Call center access denied.'], [409, 'Report changed. Reload it before reviewing.'], [422, 'Select the confirmed booking.'], [500, 'Unable to reach the server.']]) {
        const vm = { async callApi() { return { status, data: { message } }; } };
        await assert.rejects(request(vm, 'get', 'options'), { message });
    }
});

test('review saves bind to the daily or selected shift snapshot actually viewed', () => {
    const dailyToken = 'a'.repeat(64), shiftOne = 'b'.repeat(64), shiftTwo = 'c'.repeat(64);
    const report = { snapshot_token: dailyToken, snapshot_tokens: { 1: shiftOne, 2: shiftTwo } };
    assert.equal(reviewSnapshot(report, true, 1), dailyToken);
    assert.equal(reviewSnapshot(report, false, 1), shiftOne);
    assert.equal(reviewSnapshot(report, false, '2'), shiftTwo);
    assert.throws(() => reviewSnapshot(report, false, 3), /View report/);
    assert.throws(() => reviewSnapshot({}, true, 1), /View report/);
    assert.throws(() => reviewSnapshot({ snapshot_token: 'invalid' }, true, 1), /View report/);
});

test('one-box search asks about ambiguous numeric identifiers', async () => {
    const { searchKinds } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
    assert.deepEqual(searchKinds('03001234567'), ['phone', 'ticket']);
    assert.deepEqual(searchKinds('3520212345671'), ['cnic', 'ticket']);
    assert.deepEqual(searchKinds('35202-1234567-1'), ['cnic']);
    assert.deepEqual(searchKinds('+92 300 1234567'), ['phone']);
    assert.deepEqual(searchKinds('8100'), ['ticket']);
    assert.deepEqual(searchKinds('hello'), []);
});

test('work queues cannot hide older unfinished calls behind a date filter', async () => {
    const { callQueueParams } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
    const filters = { date: '2026-10-15', status: 'closed', category: 'GI', search: '', shift: '' };
    const due = callQueueParams('callbacks_due', filters);
    assert.equal(Object.hasOwn(due, 'date'), false); assert.equal(Object.hasOwn(due, 'status'), false);
    const open = callQueueParams('open_complaints', filters);
    assert.equal(Object.hasOwn(open, 'date'), false); assert.equal(Object.hasOwn(open, 'category'), false);
    assert.equal(Object.hasOwn(open, 'status'), false);
    assert.equal(callQueueParams('today', filters).date, '2026-10-15');
    assert.equal(Object.hasOwn(callQueueParams('all', { date: '' }), 'date'), false);
});

test('field errors survive the API adapter for inline recovery', async () => {
    const fields = { callback_at: ['Choose a future callback time.'] };
    await assert.rejects(request({ callApi: async () => ({ status: 422, data: { errors: fields } }) }, 'post', 'calls'), error => {
        assert.deepEqual(error.fields, fields); return true;
    });
});

test('complaint bus time accepts existing booking time formats without a timezone shift', async () => {
    const { bookingTime } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
    assert.equal(bookingTime('18:30:00'), '18:30');
    assert.equal(bookingTime('06:30 PM'), '18:30');
    assert.equal(bookingTime('12:00 AM'), '00:00');
    assert.equal(bookingTime('12:00 PM'), '12:00');
    assert.equal(bookingTime('9:05'), '09:05');
    assert.equal(bookingTime('25:00'), '');
    assert.equal(bookingTime(null), '');
});

test('call center screens require individual grants, independent of supervisor scope', () => {
    assert.deepEqual(callCenterScreens({ supervise: true, all_terminals: true }), []);
    assert.deepEqual(callCenterScreens({ view_complaints: true }).map(s => s.name), ['call-center-complaints']);
    assert.deepEqual(callCenterScreens({ create: true }).map(s => s.name), ['call-center']);
    assert.deepEqual(callCenterScreens({ quick_answers: true }).map(s => s.name), ['call-center']);
    assert.deepEqual(callCenterScreens({ shift_report: true, daily_report: true }).map(s => s.name), ['call-center-shifts', 'call-center-daily']);
    assert.deepEqual(callCenterScreens({ view: true, update: true, close_complaints: true }), []);
});

test('customer agreement summaries show request details without calling them completed transactions', () => {
    assert.equal(seatChangeSummary({ category: 'GI' }), '');
    assert.equal(seatChangeSummary({ category: 'RF', customer_response: 'agreed', change_seat_count: 2, proposed_refund_amount: '2500.50' }), 'Customer agreed · 2 seats · Refund discussed: PKR 2,500.50');
    assert.equal(seatChangeSummary({ category: 'RF', customer_response: 'declined', change_seat_count: 1, proposed_refund_amount: 0 }), 'Customer declined · 1 seat · Refund discussed: PKR 0.00');
    assert.equal(seatChangeSummary({ category: 'RS', customer_response: 'not_reached', proposed_travel_date: '2026-10-17', proposed_bus_time: '18:30' }), 'No answer · 1 seat · New date: 2026-10-17 · New time: 18:30');
});

test('awaiting agreement includes older work and cannot inherit closed/date filters', () => {
    const filters = { date: '2026-10-16', status: 'closed', shift: '', category: 'RF' };
    const params = callQueueParams('awaiting_agreement', filters, 2);
    assert.equal(params.date, undefined);
    assert.equal(params.status, undefined);
    assert.equal(params.category, 'RF');
    assert.equal(params.page, 2);
    assert.equal(filters.date, '2026-10-16');
});

test('complaint update retries reuse a request ID while new drafts remain separate', () => {
    const drafts = {};
    const first = complaintUpdateRequest(drafts, 7, '  Contacted the terminal.  ');
    assert.match(first.request_id, /^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/);
    assert.equal(first.remarks, 'Contacted the terminal.');
    assert.deepEqual(complaintUpdateRequest(drafts, 7, first.remarks), first);
    first.remarks = 'Changed outside the form';
    assert.equal(drafts[7].remarks, 'Contacted the terminal.');
    assert.notEqual(complaintUpdateRequest(drafts, 8, drafts[7].remarks).request_id, first.request_id);
    assert.notEqual(complaintUpdateRequest(drafts, 7, 'Terminal replied.').request_id, first.request_id);
});

const complaintsSource = await readFile(new URL('../../resources/js/pages/callCenter/Complaints.vue', import.meta.url), 'utf8');
const select2Source = await readFile(new URL('../../resources/js/pages/callCenter/callCenterSelect2.js', import.meta.url), 'utf8');
const complaintsScript = complaintsSource.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("'./callCenterSelect2'", JSON.stringify(`data:text/javascript;base64,${Buffer.from(select2Source).toString('base64')}`))
    .replace("'./callCenter'", JSON.stringify(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`));
const { default: complaints } = await import(`data:text/javascript;base64,${Buffer.from(complaintsScript).toString('base64')}`);

function complaintVm(callApi, permissions = { update_complaints: true, close_complaints: true }) {
    const vm = { ...complaints.data(), options: { permissions }, callApi };
    for (const [name, method] of Object.entries(complaints.methods)) vm[name] = method.bind(vm);
    return vm;
}

test('complaint updates retain failed drafts and retry once without closing or replacing the description', async () => {
    const requests = []; let fail = true;
    const call = { id: 7, reference: 'CC-7', status: 'waiting', remarks: 'Original complaint' };
    const vm = complaintVm(async (method, endpoint, payload) => {
        requests.push({ method, endpoint, payload });
        if (fail) { fail = false; return { status: 503, data: { message: 'Try again' } }; }
        return { status: 200, data: { status: true, data: { ...call, latest_complaint_update: { remarks: payload.remarks }, complaint_history_count: 1 } } };
    });
    vm.drafts[7] = 'Contacted operations.';
    await vm.saveRemarks(call);
    assert.equal(vm.drafts[7], 'Contacted operations.'); assert.equal(vm.errors[7], 'Try again'); assert.equal(vm.saving, null);
    await vm.saveRemarks(call);
    assert.equal(requests.length, 2); assert.equal(requests[0].payload.request_id, requests[1].payload.request_id);
    assert.equal(requests[1].endpoint, 'call-center/calls/7/complaint-updates');
    assert.equal(call.status, 'waiting'); assert.equal(call.remarks, 'Original complaint');
    assert.equal(call.latest_complaint_update.remarks, 'Contacted operations.'); assert.equal(vm.drafts[7], undefined);
});

test('complaint closure uses its separate endpoint and clears stale history when the queue reloads', async () => {
    const requests = [];
    const vm = complaintVm(async (method, endpoint, payload) => {
        requests.push({ method, endpoint, payload });
        return { status: 200, data: { status: true, data: method === 'post' ? { status: 'closed' } : { data: [], total: 0, current_page: 1, last_page: 1 } } };
    });
    vm.drafts[7] = 'Issue resolved.'; vm.histories[7] = { items: [{ remarks: 'Old progress note' }] }; vm.historyOpen[7] = true;
    await vm.saveRemarks({ id: 7, reference: 'CC-7' }, true);
    assert.equal(requests[0].endpoint, 'call-center/calls/7/close-complaint');
    assert.deepEqual(requests[0].payload, { resolution_remarks: 'Issue resolved.' });
    assert.deepEqual(vm.histories, {}); assert.deepEqual(vm.historyOpen, {}); assert.equal(vm.drafts[7], undefined);
    const readOnly = complaintVm(async () => { throw new Error('Unexpected request'); }, {});
    readOnly.drafts[7] = 'Note'; await readOnly.saveRemarks({ id: 7 }); await readOnly.saveRemarks({ id: 7 }, true);
    assert.equal(readOnly.drafts[7], 'Note');
});



const notificationSource = await readFile(new URL('../../resources/js/components/ComplaintNotifications.vue', import.meta.url), 'utf8');
const notificationScript = notificationSource.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("'../pages/callCenter/callCenter'", JSON.stringify(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`));
const { default: notifications } = await import(`data:text/javascript;base64,${Buffer.from(notificationScript).toString('base64')}`);
function notificationVm(callApi) {
    const vm = { ...notifications.data(), sessionKey: 'user-session', callApi, $refs: {} };
    for (const [name, method] of Object.entries(notifications.methods)) vm[name] = method.bind(vm);
    return vm;
}

test('bell retains unread state until complaint opens and respects cancelled navigation', async () => {
    const vm = notificationVm(async () => { throw new Error('Clicking must not mark unread prematurely'); });
    vm.open = true; vm.unread = 1; vm.$route = { name: 'call-center', query: {} };
    let cancel = true; vm.$router = { async push(target) { assert.equal(target.query.complaint, '7'); return cancel ? { type: 4 } : undefined; } };
    await vm.openNotification({ id: 9, call_id: 7 });
    assert.equal(vm.open, true); assert.equal(vm.unread, 1);
    cancel = false; await vm.openNotification({ id: 9, call_id: 7 });
    assert.equal(vm.open, false); assert.equal(vm.unread, 1); assert.equal(vm.opening, null);
});

test('notification fetch clears data when access is revoked and ignores stale session responses', async () => {
    const vm = notificationVm(async () => ({ status: 403, data: {} }));
    vm.items = [{ id: 1 }]; vm.unread = 1; vm.open = true; vm.available = true;
    await vm.refresh(); assert.equal(vm.available, false); assert.deepEqual(vm.items, []); assert.equal(vm.unread, 0); assert.equal(vm.open, false);
    const stale = notificationVm(async () => { stale.sequence++; return { status: 200, data: { status: true, data: { unread_count: 99, notifications: { data: [{ id: 2 }], current_page: 1, last_page: 1 } } } }; });
    await stale.refresh(); assert.equal(stale.unread, 0); assert.deepEqual(stale.items, []);
});

test('notification links fetch the exact complaint before marking read, including closed complaints', async () => {
    const requests = [], events = [];
    const savedDocument = globalThis.document, savedWindow = globalThis.window;
    globalThis.document = { getElementById: () => ({ focus() {} }) }; globalThis.window = { dispatchEvent: event => events.push(event.type) };
    try {
        const vm = complaintVm(async (method, endpoint) => {
            requests.push(endpoint);
            return { status: 200, data: { status: true, data: method === 'get' ? { id: 7, category: 'C', status: 'closed' } : {} } };
        });
        vm.targetId = '7'; vm.$route = { query: { complaint: '7', notification: '9' } }; vm.$nextTick = async () => {};
        await vm.fetchCalls();
        assert.deepEqual(requests, ['call-center/calls/7', 'call-center/notifications/9/read']);
        assert.equal(vm.mode, 'closed'); assert.equal(vm.calls[0].id, 7); assert.deepEqual(events, ['call-center-notifications-changed']);
        const unavailable = complaintVm(async (method, endpoint) => { assert.equal(endpoint, 'call-center/calls/8'); return { status: 404, data: { message: 'Complaint unavailable' } }; });
        unavailable.targetId = '8'; await unavailable.fetchCalls(); assert.equal(unavailable.error, 'Complaint unavailable');
    } finally { globalThis.document = savedDocument; globalThis.window = savedWindow; }
});


test('real login payload without company modules still checks the server and shows an empty bell', async () => {
    const state = { token: 'test-session', user: { id: 55, name: 'Test admin', role: { permissions: [] } }, permissions: [], companyModules: [] };
    const key = notifications.computed.sessionKey.call({ $store: { state } });
    assert.equal(key, '55:test-session');
    const vm = notificationVm(async (method, endpoint) => {
        assert.equal(endpoint, 'call-center/notifications');
        return { status: 200, data: { status: true, data: { unread_count: 0, notifications: { data: [], current_page: 1, last_page: 1 } } } };
    });
    vm.sessionKey = key; await vm.refresh();
    assert.equal(vm.available, true); assert.equal(vm.unread, 0); assert.deepEqual(vm.items, []);
    assert.deepEqual(complaintNotificationRoute({ id: 9, call_id: 7 }), { name: 'call-center-complaints', query: { complaint: '7', notification: '9' } });
    state.token = ''; assert.equal(notifications.computed.sessionKey.call({ $store: { state } }), '');
});

test('live permission grant restores the bell after an earlier denial without signing in again', async () => {
    let allow = false;
    const vm = notificationVm(async () => allow ? { status: 200, data: { status: true, data: { unread_count: 1, notifications: { data: [{ id: 1 }], current_page: 1, last_page: 1 } } } } : { status: 403, data: {} });
    await vm.refresh(); assert.equal(vm.available, false);
    allow = true; await vm.refresh(); assert.equal(vm.available, true); assert.equal(vm.unread, 1);
});

test('complaint history retains follow-up calls alongside audit notes across pages', async () => {
    const update = { id: 2, kind: 'update', remarks: 'Terminal contacted.' };
    const followup = { id: 'follow-up-2', kind: 'follow_up', call_id: 2, reference: 'CC-2', remarks: 'Customer asked for an update.', callback_at: '2026-10-16 18:30:00', actor: { id: 1, name: 'Agent' } };
    const closure = { id: 3, kind: 'closed', remarks: 'Resolved.' };
    const vm = complaintVm(async (method, endpoint, body, config) => {
        assert.equal(method, 'get'); assert.equal(endpoint, 'call-center/calls/1/complaint-updates');
        const page = config.params.page;
        return { status: 200, data: { status: true, data: { data: page === 1 ? [update, followup] : [followup, closure], current_page: page, last_page: 2 } } };
    });
    await vm.loadHistory({ id: 1 });
    await vm.loadHistory({ id: 1 }, 2);
    assert.deepEqual(vm.histories[1].items, [update, followup, closure]);
    assert.equal(vm.histories[1].items[1].callback_at, '2026-10-16 18:30:00');
    assert.equal(vm.histories[1].page, 2); assert.equal(vm.histories[1].error, '');
});


test('complaint assignment saves only the person, preserves drafts, retries safely and refreshes history', async () => {
    const originalWindow = globalThis.window;
    globalThis.window = { dispatchEvent() {} };
    try {
        let fail = true; const requests = [];
        const call = { id: 7, reference: 'CC-7', category: 'C', status: 'waiting', assigned_to: 1, remarks: 'Original complaint' };
        const vm = complaintVm(async (method, endpoint, payload) => {
            requests.push({ method, endpoint, payload });
            if (endpoint.endsWith('complaint-assignees')) return { status: 200, data: { status: true, data: [{ id: 1, name: 'First person' }, { id: 2, name: 'Next person' }] } };
            if (endpoint.endsWith('complaint-updates')) return { status: 200, data: { status: true, data: { data: [{ id: 4, kind: 'assignment', from_assignee: { name: 'First person' }, to_assignee: { name: 'Next person' } }], current_page: 1, last_page: 1 } } };
            if (fail) { fail = false; return { status: 503, data: { message: 'Try again' } }; }
            return { status: 200, data: { status: true, data: { ...call, assigned_to: 2, assignee: { id: 2, name: 'Next person' }, can_view: true } } };
        }, { view_complaints: true, update: true, assign_calls: true });
        vm.canAssign = complaints.computed.canAssign.call(vm); vm.calls = [call];
        await vm.loadAssignees(call); assert.equal(vm.assignments[7].selected, 1);
        vm.assignments[7].selected = 2; vm.drafts[7] = 'Unsaved update';
        await vm.assignComplaint(call); assert.equal(requests.length, 1);
        assert.equal(vm.drafts[7], 'Unsaved update'); delete vm.drafts[7];
        vm.drafts[8] = 'Another complaint draft';
        assert.equal(complaints.computed.dirty.call(vm), true);
        await vm.assignComplaint(call); assert.equal(call.assigned_to, 1); assert.equal(vm.assignments[7].selected, 2); assert.equal(vm.assignments[7].error, 'Try again');
        await vm.assignComplaint(call);
        assert.deepEqual(requests[1].payload, { assigned_to: 2, expected_assigned_to: 1 });
        assert.deepEqual(requests[2].payload, requests[1].payload);
        assert.equal(call.assigned_to, 2); assert.equal(call.status, 'waiting'); assert.equal(call.remarks, 'Original complaint');
        assert.equal(vm.drafts[8], 'Another complaint draft'); assert.equal(vm.assignments[7], undefined);
        assert.equal(vm.histories[7].items[0].kind, 'assignment'); assert.equal(vm.historyOpen[7], true);
        assert.equal(vm.saving, null);
    } finally { globalThis.window = originalWindow; }
});

test('complaint reassignment removes work that left the users scope and enforces UI privileges', async () => {
    const originalWindow = globalThis.window; globalThis.window = { dispatchEvent() {} };
    try {
        const call = { id: 7, reference: 'CC-7', status: 'new', assigned_to: 1 };
        const vm = complaintVm(async () => ({ status: 200, data: { status: true, data: { ...call, assigned_to: 2, assignee: { name: 'Next person' }, can_view: false } } }));
        vm.canAssign = true; vm.calls = [call]; vm.pagination.total = 1; vm.assignments[7] = { selected: 2 };
        await vm.assignComplaint(call); assert.equal(vm.calls.length, 0); assert.equal(vm.pagination.total, 0); assert.match(vm.notice, /outside your assigned work/);
        for (const permissions of [{}, { view_complaints: true, update: true }, { view_complaints: true, assign_calls: true }]) {
            const readOnly = complaintVm(async () => { throw new Error('Must not assign'); }, permissions);
            readOnly.canAssign = complaints.computed.canAssign.call(readOnly); readOnly.assignments[7] = { selected: 2 };
            assert.equal(readOnly.canAssign, false); await readOnly.assignComplaint(call);
        }
    } finally { globalThis.window = originalWindow; }
});
