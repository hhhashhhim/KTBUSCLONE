const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const { parse, compileTemplate, compileStyle } = require('@vue/compiler-sfc');

function component(file, imports = {}) {
    const source = fs.readFileSync(path.join(__dirname, '../..', file), 'utf8');
    const script = parse(source).descriptor.script.content
        .replace(/^import .*;\r?\n/gm, '')
        .replace('export default', 'module.exports =');
    const context = { module: { exports: {} }, ...imports };
    vm.runInNewContext(script, context);
    return context.module.exports;
}

(async () => {
    const sidebar = component('resources/js/layouts/company/SideBar.vue', { SidebarMenuSearch: {} });
    let calls = 0;
    const state = {
        permissions: [{ allow: true, childs: [{ name: 'online-terminal-cancellation', allow: false }] }],
        terminalCancellationPendingCount: 9,
        callApi: async () => { calls++; return { status: 200, data: { pending_count: 5 } }; },
    };
    state.checkForSubmenu = sidebar.methods.checkForSubmenu.bind(state);
    await sidebar.methods.getTerminalCancellationPendingCount.call(state);
    assert.equal(calls, 0, 'A booking-only user must not call the approval-only endpoint');
    assert.equal(state.terminalCancellationPendingCount, 0);
    state.permissions[0].childs[0].allow = true;
    state.permissions[0].allow = false;
    await sidebar.methods.getTerminalCancellationPendingCount.call(state);
    assert.equal(calls, 0, 'A disabled parent permission also prevents the request');
    state.permissions[0].allow = true;
    await sidebar.methods.getTerminalCancellationPendingCount.call(state);
    assert.equal(calls, 1);
    assert.equal(state.terminalCancellationPendingCount, 5);

    // Render the actual Ticketing template without mounting the app or making API calls.
    const { createSSRApp, compile } = require('vue');
    const { renderToString } = require('@vue/server-renderer');
    const sidebarSource = fs.readFileSync(path.join(__dirname, '../../resources/js/layouts/company/SideBar.vue'), 'utf8');
    const ticketingTemplate = sidebarSource.split('<!-- Tickting panel -->')[1].split('<!-- loyalityCard Panel -->')[0];
    async function renderTicketing(count, allowed = true) {
        const app = createSSRApp({
            render: compile(ticketingTemplate),
            data: () => ({ terminalCancellationPendingCount: count }),
            methods: { checkPermission: () => true, checkForSubmenu: () => allowed },
        });
        app.component('router-link', { template: '<a><slot /></a>' });
        return renderToString(app);
    }
    const withPending = await renderTicketing(5);
    assert.equal((withPending.match(/class="badge badge-secondary terminal-cancellation-count"/g) || []).length, 2,
        'Both Ticketing and Terminal Cancellation show the same pending count');
    assert.match(withPending, /aria-label="5 pending terminal cancellation requests"/);
    assert.match(withPending, /class="terminal-cancellation-label">Terminal Cancellation<\/span>/);
    const noPending = await renderTicketing(0);
    assert.equal((noPending.match(/class="badge badge-secondary terminal-cancellation-count"/g) || []).length, 2,
        'Both parent and child keep their count visible when nothing is pending');
    assert.match(noPending, /aria-label="0 pending terminal cancellation requests"/);
    assert.doesNotMatch(await renderTicketing(5, false), /terminal-cancellation-count/,
        'Users without cancellation access do not see its notification');

    const page = component('resources/js/pages/booking/OnlineTerminalCancellationRequestsPage.vue', { vueMask: {} });
    const view = page.data();
    view.defaultFilters = page.methods.defaultFilters;
    view.initFilterSelect2 = () => {};
    view.fetchRequests = () => { calls++; };
    view.filters.statusFilter = 'cancelled';
    view.filters.fromDateFilter = '2020-01-01';
    page.methods.selectTab.call(view, 'completed');
    assert.equal(view.activeTab, 'completed');
    assert.equal(view.filters.statusFilter, '');
    assert.equal(view.filters.fromDateFilter, '', 'Switching tabs must not retain historical date filters');
    assert.equal(view.perPage, 25);
    assert.equal(calls, 2);
    page.methods.selectTab.call(view, 'completed');
    assert.equal(calls, 2, 'Clicking the active tab does not fetch again');

    view.filters.routeFilter = '12';
    view.filters.nameFilter = 'Test passenger';
    page.methods.applyFilters.call(view);
    assert.equal(view.appliedFilters.routeFilter, '12');
    view.filters.routeFilter = '13';
    assert.equal(view.appliedFilters.routeFilter, '12', 'Draft changes do not affect paging until Apply');
    let sentFilters;
    view.destroyDataTable = () => {};
    view.initDataTable = () => {};
    view.$nextTick = callback => callback();
    view.callApi = async (method, endpoint, payload) => {
        sentFilters = payload;
        return { status: 200, data: { status: true, data: { requests: [], counts: { pending: 0, completed: 0 } } } };
    };
    await page.methods.fetchRequests.call(view, 3);
    assert.equal(sentFilters.routeFilter, '12');
    assert.equal(sentFilters.page, 3);
    assert.equal(view.loading, false);
    page.methods.resetFilters.call(view);
    assert.equal(view.filters.routeFilter, '');
    assert.equal(view.appliedFilters.routeFilter, '');

    const filename = 'resources/js/pages/booking/OnlineTerminalCancellationRequestsPage.vue';
    const { descriptor, errors } = parse(fs.readFileSync(path.join(__dirname, '../..', filename), 'utf8'));
    assert.deepEqual(errors, []);
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: 'filter-test' }).errors, []);
    assert.deepEqual(compileStyle({ source: descriptor.styles[0].content, filename, id: 'data-v-filter-test', scoped: true }).errors, []);
    assert.equal((descriptor.template.content.match(/<select /g) || []).length, 5);
    assert.equal((descriptor.template.content.match(/data-cancellation-filter=/g) || []).length, 4);
    assert.doesNotMatch(descriptor.template.content, /@keyup="fetchRequests/);

    const controls = ['routeFilter', 'terminalFilter', 'busFilter', 'statusFilter', null].map(field => ({
        dataset: { cancellationFilter: field }, value: '', initialized: false, handlers: {},
    }));
    function jquery(element) {
        return {
            off: () => { element.handlers = {}; return jquery(element); },
            hasClass: () => element.initialized,
            select2: options => { element.initialized = options !== 'destroy'; if (typeof options === 'object') element.settings = options; return jquery(element); },
            parent: () => ({}),
            val: function(value) { if (arguments.length) { element.value = value; return jquery(element); } return element.value; },
            trigger: () => jquery(element),
            on: (event, handler) => { element.handlers[event] = handler; return jquery(element); },
        };
    }
    jquery.fn = { select2: () => {} };
    const selectPage = component(filename, { vueMask: {}, $: jquery });
    const selectView = selectPage.data();
    selectView.$el = { querySelectorAll: () => controls };
    selectView.$nextTick = callback => callback();
    let filterCalls = 0;
    selectView.fetchRequests = () => { filterCalls++; };
    selectPage.methods.initFilterSelect2.call(selectView);
    controls.forEach(control => assert.equal(control.settings.minimumResultsForSearch, 0));
    controls[0].value = '42'; controls[0].handlers['change.terminalCancellationFilters']();
    assert.equal(selectView.filters.routeFilter, '42');
    assert.equal(filterCalls, 0, 'Dropdown editing waits for Apply filters');
    controls[4].value = '50'; controls[4].handlers['change.terminalCancellationFilters']();
    assert.equal(selectView.perPage, 50);
    assert.equal(filterCalls, 1, 'Page-size selection fetches the first page');
    selectView.filters.routeFilter = '';
    selectPage.methods.initFilterSelect2.call(selectView);
    assert.equal(controls[0].value, '', 'Reset clears the visible Select2 selection too');
    selectPage.methods.destroyFilterSelect2.call(selectView);
    controls.forEach(control => { assert.equal(control.initialized, false); assert.equal(Object.keys(control.handlers).length, 0); });

    view.selectedRequest = { fare: 7000, request_fare: 7000 };
    view.approveForm.deduction_percentage = 10;
    view.calculatePercentAmount = page.methods.calculatePercentAmount;
    let summary = page.computed.approvePreview.call(view);
    assert.equal(summary.refund_amount, 6300);
    view.selectedRequest.request_fare = 28000;
    summary = page.computed.approvePreview.call(view);
    assert.equal(summary.refund_amount, 25200, 'Legacy grouped approvals clearly preview the whole group');
    console.log('PASS cancellation sidebar, tabs, Select2 lifecycle, apply/reset filters, template/styles and approval previews');
})().catch(error => { console.error(error); process.exitCode = 1; });
