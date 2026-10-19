import assert from 'node:assert/strict';
import test from 'node:test';
import { readFile } from 'node:fs/promises';

const base = new URL('../../resources/js/pages/callCenter/', import.meta.url);
const url = source => `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
const source = await readFile(new URL('QuickAnswers.vue', base), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("'./callCenter'", JSON.stringify(url(await readFile(new URL('callCenter.js', base), 'utf8'))))
    .replace("'./callCenterSelect2'", JSON.stringify(url(await readFile(new URL('callCenterSelect2.js', base), 'utf8'))));
const { default: component } = await import(url(script));
function vm() {
    const state = { ...component.data(), currentUser: { terminal_id: '2' }, info: { terminals: [{ id: 1 }, { id: 2 }] } };
    for (const [name, method] of Object.entries(component.methods)) state[name] = method.bind(state);
    state.today = () => '2026-10-19';
    return state;
}

test('Reset restores initial filters, clears results and errors, and keeps cached options', () => {
    const state = vm(), info = state.info;
    state.form = { departure_id: 1, destination_id: 3, date: '2026-10-25', terminal_id: 1 };
    state.answer = { departures: [{}] }; state.active = { ...state.form };
    state.error = 'Old error'; state.searchError = 'Search failed'; state.searching = true;
    state.reset();
    assert.deepEqual(state.form, { departure_id: '', destination_id: '', date: '2026-10-19', terminal_id: 2 });
    assert.equal(state.answer, null); assert.deepEqual(state.active, {});
    assert.equal(state.error, ''); assert.equal(state.searchError, ''); assert.equal(state.searching, false);
    assert.equal(state.info, info); assert.equal(state.sequence, 1);
});

test('Default terminal is restricted to permitted choices', () => {
    const state = vm(); state.currentUser.terminal_id = 99; state.reset(); assert.equal(state.form.terminal_id, 1);
    state.info.terminals = []; state.reset(); assert.equal(state.form.terminal_id, '');
});

test('An old response or failure cannot repopulate the cleared panel', async () => {
    for (const failed of [false, true]) {
        const state = vm(); let resolve;
        state.callApi = () => new Promise(done => { resolve = done; });
        const pending = state.search(); state.reset();
        resolve(failed ? { status: 500, data: { message: 'Old error' } } : { status: 200, data: { status: true, data: { departures: [{}] } } });
        await pending;
        assert.equal(state.answer, null); assert.equal(state.searchError, ''); assert.equal(state.searching, false);
    }
});

test('A new search after reset is not overwritten by an earlier request', async () => {
    const state = vm(); let oldResponse, newResponse;
    state.callApi = () => new Promise(done => { oldResponse = done; });
    const old = state.search(); state.reset();
    state.form.departure_id = 10;
    state.callApi = () => new Promise(done => { newResponse = done; });
    const current = state.search(); oldResponse({ status: 200, data: { status: true, data: { old: true } } });
    await old; assert.equal(state.answer, null); assert.equal(state.searching, true);
    newResponse({ status: 200, data: { status: true, data: { current: true } } });
    await current; assert.deepEqual(state.answer, { current: true }); assert.equal(state.active.departure_id, 10);
});

test('Reset does not submit the form and remains usable during a search', () => {
    const button = source.match(/<button[^>]*@click="reset"[^>]*>/)?.[0];
    assert.ok(button); assert.match(button, /type="button"/); assert.doesNotMatch(button, /disabled/);
});
