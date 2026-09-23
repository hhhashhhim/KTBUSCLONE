const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const { parse } = require('@vue/compiler-sfc');

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

    const page = component('resources/js/pages/booking/OnlineTerminalCancellationRequestsPage.vue', { vueMask: {} });
    const view = page.data();
    view.defaultFilters = page.methods.defaultFilters;
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

    view.selectedRequest = { fare: 7000, request_fare: 7000 };
    view.approveForm.deduction_percentage = 10;
    view.calculatePercentAmount = page.methods.calculatePercentAmount;
    let summary = page.computed.approvePreview.call(view);
    assert.equal(summary.refund_amount, 6300);
    view.selectedRequest.request_fare = 28000;
    summary = page.computed.approvePreview.call(view);
    assert.equal(summary.refund_amount, 25200, 'Legacy grouped approvals clearly preview the whole group');
    console.log('PASS cancellation sidebar permission guard, tabs and approval previews');
})().catch(error => { console.error(error); process.exitCode = 1; });
