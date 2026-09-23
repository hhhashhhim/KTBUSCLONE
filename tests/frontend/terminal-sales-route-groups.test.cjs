const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const file = 'resources/js/pages/Sales/TerminalSaleReportsPage.vue';
const source = fs.readFileSync(file, 'utf8');
const parsed = parse(source);
assert.deepEqual(parsed.errors, []);
assert.deepEqual(compileTemplate({ source: parsed.descriptor.template.content, filename: file, id: 'test' }).errors, []);
let modalAction;
const context = {
    module: { exports: {} },
    document: { querySelector: () => ({ getAttribute: () => '' }) },
    $: () => ({ modal: action => { modalAction = action; } }),
    swal() {},
};
vm.runInNewContext(parsed.descriptor.script.content.replace('export default', 'module.exports ='), context);
const component = context.module.exports;
const view = component.data();
Object.entries(component.methods).forEach(([key, fn]) => view[key] = fn.bind(view));
const selectIds = [];
const initState = { ...view, $nextTick: callback => callback(), setupSelect2: id => selectIds.push(id) };
component.methods.initSelect2Filters.call(initState);
for (const [, id] of source.matchAll(/<select id="([^"]+)"/g)) {
    assert.ok(selectIds.includes('#' + id), id + ' must be initialized as Select2');
}
assert.ok(source.includes('class="cancelled-tag">Cancelled</span>'));
view.$nextTick = () => {};
view.routes = [{ id: 1, name: 'Karachi - Rawalpindi', via: 'Multan' }, { id: 2, name: 'Rawalpindi - Karachi' }];
view.routeGroups = [{ id: 7, name: 'Karachi closing', route_ids: [1, 2] }];
view.filterSales.route_group_id = 7;
view.applyRouteGroup();
assert.equal(JSON.stringify(view.filterSales.route), '[1,2]');
view.openRouteGroupModal();
assert.equal(modalAction, 'show');
view.groupForm.route_ids.pop();
assert.equal(view.routeGroups[0].route_ids.length, 2, 'Editing must not mutate a saved group before save');
view.groupRouteSearch = 'multan';
assert.equal(component.computed.searchableGroupRoutes.call(view).length, 1);

(async () => {
    let calls = 0;
    view.callApi = async (method, url, payload) => {
        calls++;
        assert.equal(url, 'terminals/sales/route-groups/save');
        assert.equal(payload.id, 7);
        return { status: 200, data: { group: { id: 7, name: 'Updated closing', route_ids: [2] } } };
    };
    await view.saveRouteGroup();
    assert.equal(calls, 1);
    assert.equal(JSON.stringify(view.filterSales.route), '[2]');
    assert.equal(modalAction, 'hide');
    view.groupForm.name = '';
    await view.saveRouteGroup();
    assert.equal(calls, 1, 'Invalid groups must not be submitted');
    view.resetReportFilters();
    assert.equal(view.filterSales.route_group_id, '');
    assert.equal(view.filterSales.route.length, 0);
    assert.ok(source.includes('name="route_group_id" :value="filterSales.route_group_id"'), 'Print must submit the same saved group');
    console.log('PASS terminal sales route-group UI, save/edit, search, reset and print binding');
})().catch(error => { console.error(error); process.exitCode = 1; });
