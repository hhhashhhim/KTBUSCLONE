const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { parse, compileStyle } = require('@vue/compiler-sfc');
const { createSSRApp, compile } = require('vue');
const { renderToString } = require('@vue/server-renderer');

const source = fs.readFileSync('resources/js/layouts/company/SideBar.vue', 'utf8');
const { descriptor } = parse(source);
const context = { module: { exports: {} }, SidebarMenuSearch: {} };
vm.runInNewContext(descriptor.script.content.replace(/^import .*;\r?\n/gm, '').replace('export default', 'module.exports ='), context);
const sidebar = context.module.exports;
const template = descriptor.template.content.split('<!-- Tickting panel -->')[1].split('<li class="dropdown" v-if="canAccessCallCenter"')[0];

(async () => {
    assert.equal(sidebar.data.call({ $route: { name: 'mobile-app-users' } }).mobileAppExpanded, true);
    assert.equal(sidebar.data.call({ $route: { name: 'bookings' } }).mobileAppExpanded, false);
    const view = {
        mobileAppExpanded: true,
        $route: { name: 'mobile-app-users' },
        syncActiveMenu() {},
        checkForSubmenu: () => false,
    };
    sidebar.methods.toggleMobileAppMenu.call(view);
    assert.equal(view.mobileAppExpanded, false, 'The current Mobile App page can stay open while its sidebar is collapsed');
    sidebar.methods.toggleMobileAppMenu.call(view);
    assert.equal(view.mobileAppExpanded, true, 'Clicking again expands the links without navigation');
    view.mobileAppExpanded = false;
    sidebar.watch['$route.fullPath'].call(view);
    assert.equal(view.mobileAppExpanded, true, 'Opening another Mobile App screen reveals its active link');
    view.$route.name = 'bookings';
    sidebar.watch['$route.fullPath'].call(view);
    assert.equal(view.mobileAppExpanded, false);

    async function render(expanded, screens = [{ name: 'mobile-app', label: 'Overview', icon: 'fas fa-chart-line' }]) {
        const app = createSSRApp({
            render: compile(template),
            data: () => ({ mobileAppExpanded: expanded, mobileAppScreens: screens }),
            methods: { toggleMobileAppMenu: sidebar.methods.toggleMobileAppMenu },
        });
        // Vue reserves $-prefixed data, so provide the route as an injected global.
        app.config.globalProperties.$route = { name: 'mobile-app' };
        app.component('router-link', { props: ['to'], template: '<a><slot /></a>' });
        return renderToString(app);
    }
    const open = await render(true);
    assert.match(open, /aria-expanded="true"/);
    assert.match(open, /fa-minus/);
    assert.match(open, /aria-controls="mobile-app-sidebar-menu"/);
    assert.match(open, />Overview</);
    const closed = await render(false);
    assert.match(closed, /aria-expanded="false"/);
    assert.match(closed, /fa-plus/);
    assert.match(closed, /ma-menu-collapsed/);
    assert.match(closed, /display:none/);
    assert.doesNotMatch(await render(false, []), /Mobile App/);
    assert.doesNotMatch(template.split('</a>')[0], /has-dropdown|menu-toggle nav-link/, 'Legacy jQuery must not toggle the same control twice');
    assert.match(template, /@keydown\.space\.prevent="toggleMobileAppMenu"/);
    const style = descriptor.styles[0].content;
    assert.deepEqual(compileStyle({ source: style, id: 'sidebar', scoped: true }).errors, []);
    assert.match(style, /\.ma-mobile-menu\.ma-menu-collapsed > \.dropdown-menu\s*\{\s*display: none !important;/);
    assert.match(style, /\.ma-mobile-menu:not\(\.ma-menu-collapsed\) > \.dropdown-menu\s*\{\s*display: block !important;/);
    console.log('PASS Mobile App sidebar toggle, route activation, permissions, keyboard control and collapsed-state styles');
})().catch(error => { console.error(error); process.exitCode = 1; });
