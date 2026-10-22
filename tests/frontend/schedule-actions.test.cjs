const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const file = 'resources/js/pages/schedule/ScheduleActions.vue';
const { descriptor, errors } = parse(fs.readFileSync(file, 'utf8'));
assert.deepEqual(errors, []);
assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: file, id: 'actions' }).errors, []);
assert.match(descriptor.template.content, /<Teleport to="body">/, 'Menu must escape the table overflow container');
assert.match(descriptor.template.content, /:aria-expanded="opened"/);
const listeners = new Map();
function target(prefix) {
    return { addEventListener: (name, handler) => listeners.set(prefix + name, handler),
        removeEventListener: name => listeners.delete(prefix + name) };
}
const document = { ...target('document:'), activeElement: null };
const window = { ...target('window:'), innerWidth: 800, innerHeight: 600 };
const context = { module: { exports: {} }, document, window };
vm.runInNewContext(descriptor.script.content.replace('export default', 'module.exports ='), context);
const component = context.module.exports;
const state = component.data();
for (const [name, method] of Object.entries(component.methods)) state[name] = method.bind(state);
const buttons = Array.from({ length: 3 }, () => ({ focus() { document.activeElement = this; } }));
const trigger = { getBoundingClientRect: () => ({ right: 790, bottom: 580 }), contains: item => item === trigger,
    focus() { document.activeElement = this; } };
const menu = { offsetWidth: 245, offsetHeight: 300, contains: item => buttons.includes(item),
    querySelector: () => buttons[0], querySelectorAll: () => buttons };
state.$refs = { trigger, menu };
state.$nextTick = async () => {};
const event = key => ({ key, preventDefault() {} });
(async () => {
    await state.show();
    assert.equal(state.opened, true);
    assert.equal(state.position.left, '545px'); assert.equal(state.position.top, '292px');
    assert.equal(document.activeElement, buttons[0]);
    state.navigate(event('ArrowUp')); assert.equal(document.activeElement, buttons[2]);
    state.navigate(event('ArrowDown')); assert.equal(document.activeElement, buttons[0]);
    state.navigate(event('End')); assert.equal(document.activeElement, buttons[2]);
    state.navigate(event('Home')); assert.equal(document.activeElement, buttons[0]);
    state.outside({ target: buttons[1] }); assert.equal(state.opened, true);
    state.escape(event('Escape')); assert.equal(state.opened, false); assert.equal(document.activeElement, trigger);
    assert.equal(listeners.size, 0);
    await state.show(); state.choose({ target: { closest: () => buttons[0] } }); assert.equal(state.opened, false);
    await state.show(); state.outside({ target: {} }); assert.equal(state.opened, false);
    await state.show(); state.scroll({ target: buttons[0] }); assert.equal(state.opened, true);
    state.scroll({ target: {} }); assert.equal(state.opened, false);
    await state.show(); component.beforeUnmount.call(state); assert.equal(listeners.size, 0);
    const source = fs.readFileSync('resources/js/pages/schedule/SchedulePage.vue', 'utf8');
    const actions = source.match(/<ScheduleActions[^>]*>([\s\S]*?)<\/ScheduleActions>/)[1];
    assert.equal((actions.match(/role="menuitem"/g) || []).length, 7);
    for (const permission of ['extend-schedule', 'edit-schedule', 'update-time', 'delete-schedule']) {
        assert.ok(actions.includes(`checkForSubmenuButtons('${permission}')`));
    }
    for (const action of ['viewSchedule(schedule.schedule.id)', 'addDays(schedule.schedule)', 'edit(schedule.schedule)',
        'openQuota(schedule.schedule)', '$refs.quotaDiscount.open(schedule.schedule)', 'editTime(schedule)', 'delId = schedule.schedule.id']) {
        assert.ok(actions.includes(action), action);
    }
    assert.match(actions, /:disabled="viewLoading"/);
    assert.match(actions, /data-target="#addDaysModal" data-toggle="modal"/);
    assert.match(actions, /data-target="#editTimeModal" data-toggle="modal"/);
    assert.match(actions, /:data-target="'#' \+ hideFormID"/);
    console.log('PASS schedule action menu, permissions, all seven actions, placement, keyboard and dismissal');
})().catch(error => { console.error(error); process.exitCode = 1; });
