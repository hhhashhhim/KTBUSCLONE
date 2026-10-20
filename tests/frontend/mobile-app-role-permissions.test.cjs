const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { parse, compileTemplate } = require('@vue/compiler-sfc');

const filename = 'resources/js/pages/roles/Permissions.vue';
const parsed = parse(fs.readFileSync(filename, 'utf8'), { filename });
assert.deepEqual(parsed.errors, []);
assert.deepEqual(compileTemplate({ source: parsed.descriptor.template.content, filename, id: 'role-permissions' }).errors, []);
const context = { module: { exports: {} } };
vm.runInNewContext(parsed.descriptor.script.content.replace('export default', 'module.exports ='), context);
const component = context.module.exports;
const definitions = [
    { name: 'mobile-app-view-users', label: 'View app users', group: 'App users', submenu: 'mobile-app-users', requires: [] },
    { name: 'mobile-app-manage-wallet', label: 'Adjust passenger wallet points', group: 'App users', submenu: 'mobile-app-users', requires: ['mobile-app-view-users'] },
    { name: 'mobile-app-view-bookings', label: 'View bookings', group: 'Bookings and seats', submenu: 'mobile-app-bookings', requires: [] },
    { name: 'mobile-app-remove-seats', label: 'Remove reserved seats', group: 'Bookings and seats', submenu: 'mobile-app-bookings', requires: ['mobile-app-view-bookings'] },
    { name: 'mobile-app-refund-seats', label: 'Cancel confirmed seats', group: 'Bookings and seats', submenu: 'mobile-app-bookings', requires: ['mobile-app-view-bookings', 'mobile-app-remove-seats'] },
];
const companyModule = () => ({
    name: 'mobile-app', allow: true,
    childs: ['mobile-app-users', 'mobile-app-bookings'].map(name => ({ name, allow: true, buttons: [] })),
    buttons: definitions.map(item => ({ name: item.name, allow: true })),
});
const payload = () => ({
    role: { id: 2, name: 'Passenger support', company: { name: 'Example company' } },
    permissions: [companyModule()], mobile_app_privileges: definitions,
    mobile_app_company_permissions: companyModule(), can_update_permissions: true,
});
function state(extra = {}) {
    const result = { ...component.data(), $route: { params: { id: 2 } }, ...extra };
    for (const [name, method] of Object.entries(component.methods)) result[name] = method.bind(result);
    for (const [name, getter] of Object.entries(component.computed)) Object.defineProperty(result, name, { get: () => getter.call(result) });
    return result;
}

(async () => {
    const editor = state({ callApi: async () => ({ status: 200, data: payload() }) });
    await editor.load();
    assert.equal(editor.loading, false); assert.equal(editor.canUpdatePermissions, true);
    const module = editor.permissions[0];
    const wallet = editor.mobileAppGroups(module.buttons)[0].items[1];
    assert.equal(wallet.label, 'Adjust passenger wallet points');
    assert.equal(wallet.requires[0], 'mobile-app-view-users');
    assert.equal(editor.mobilePrivilegeWarnings(module, wallet).length, 0);
    module.buttons[0].allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, wallet).join(' '), /also select View app users/);
    assert.equal(module.buttons[0].allow, false, 'Checking prerequisites must never grant them automatically.');
    module.childs[0].allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, wallet).join(' '), /enable the App Users submodule/);
    module.allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, wallet).join(' '), /Mobile App is disabled/);
    module.allow = true; module.childs[0].allow = true; module.buttons[0].allow = true;
    editor.mobileAppCompanyPermissions.buttons[0].allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, wallet).join(' '), /company access/);
    editor.mobileAppCompanyPermissions = companyModule();
    editor.mobileAppCompanyPermissions.childs[0].allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, wallet).join(' '), /company access/);
    wallet.button.allow = false;
    assert.equal(editor.mobilePrivilegeWarnings(module, wallet).length, 0, 'Unselected actions need no inactive warning.');
    editor.mobileAppCompanyPermissions = companyModule();
    const refund = editor.mobileAppGroups(module.buttons)[1].items[2];
    module.buttons[3].allow = false;
    assert.match(editor.mobilePrivilegeWarnings(module, refund).join(' '), /Remove reserved seats/);
    editor.role.name = ' Admin '; assert.equal(editor.isAdminRole, true);

    // A missing or denied save capability must fail closed, including old API responses.
    let writes = 0;
    for (const can_update_permissions of [false, undefined]) {
        editor.callApi = async (_method, endpoint) => {
            if (endpoint === 'role/update') writes++;
            return { status: 200, data: { ...payload(), can_update_permissions } };
        };
        await editor.load(); await editor.save();
        assert.equal(editor.canUpdatePermissions, false);
    }
    assert.equal(writes, 0);
    editor.callApi = async () => ({ status: 403, data: { Error: ['Role access denied.'] } });
    await editor.load();
    assert.equal(editor.loading, false); assert.equal(editor.permissions.length, 0);
    assert.equal(editor.error, 'Role access denied.');
    await editor.save(); assert.equal(editor.role, '');

    editor.callApi = async () => ({ status: 200, data: payload() }); await editor.load();
    const before = JSON.stringify(editor.permissions);
    let finish;
    editor.callApi = async (_method, endpoint, body) => {
        assert.equal(endpoint, 'role/update');
        assert.equal(body.id, 2);
        assert.equal(JSON.stringify(body.permissions), before);
        writes++;
        return new Promise(resolve => { finish = resolve; });
    };
    const saving = editor.save();
    assert.equal(editor.saving, true);
    await editor.save(); assert.equal(writes, 1, 'Duplicate save must not submit another role update.');
    finish({ status: 201, data: {} }); await saving;
    assert.equal(editor.saving, false); assert.match(editor.success, /updated successfully/);
    for (const response of [
        { status: 403, data: { Error: ['Your access was revoked.'] } },
        { status: 422, data: { errors: { permissions: ['Invalid permission.'] } } },
        { status: 500, data: { errors: { network: ['Unable to reach the server.'] } } },
    ]) {
        editor.callApi = async () => response; await editor.save();
        assert.equal(editor.success, ''); assert.notEqual(editor.error, '');
        assert.equal(editor.saving, false);
        assert.equal(JSON.stringify(editor.permissions), before, 'Failed saves preserve selections for correction or retry.');
    }
    editor.callApi = async () => { throw new Error('Connection lost.'); };
    await editor.save(); assert.equal(editor.error, 'Connection lost.');
    await editor.load(); assert.equal(editor.canUpdatePermissions, false);
    assert.equal(editor.loading, false); assert.equal(editor.role, '');
    console.log('PASS Roles SFC compilation, privilege dependencies/company limits, read-only and load states, guarded saves, validation/denial/network errors, and selection preservation.');
})().catch(error => { console.error(error); process.exitCode = 1; });
