import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

// The legacy application is bundled by Mix and does not declare package type=module.
const source = await readFile(new URL('../../resources/js/pages/callCenter/callCenter.js', import.meta.url), 'utf8');
const { pakistanDate, inputDateTime, callbackValue, apiError, request, canAccessCallCenter, reviewSnapshot, callCenterScreens } =
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
