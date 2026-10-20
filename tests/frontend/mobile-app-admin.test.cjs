const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const parser = require('@babel/parser');

const directory = 'resources/js/pages/mobileApp';
let requestHandler = async () => { throw new Error('Unexpected request'); };
const helpers = { module: { exports: {} }, Intl, Date, URL, Blob, setTimeout };
vm.runInNewContext(fs.readFileSync(path.join(directory, 'mobileApp.js'), 'utf8').replace(/export /g, '') + '\nmodule.exports={mobileScreens,today,displayTime,formatCnic,number,money,label,request};', helpers);
const api = helpers.module.exports;

function load(name) {
    const filename = path.join(directory, name + '.vue');
    const parsed = parse(fs.readFileSync(filename, 'utf8'), { filename });
    assert.deepEqual(parsed.errors, []);
    assert.deepEqual(compileTemplate({ source: parsed.descriptor.template.content, filename, id: name }).errors, []);
    parser.parse(parsed.descriptor.script.content, { sourceType: 'module' });
    const context = {
        URL, FormData, Blob, crypto: require('node:crypto'), ...api, module: { exports: {} }, request: (...args) => requestHandler(...args),
        AppControls: {}, PassengerLimits: {}, PassengerWallet: {}, MobileFilters: {}, MobilePagination: {}, MobileDialog: {},
        Availability: {}, LoginActivity: {}, Overview: {}, Passengers: {}, Bookings: {}, Payments: {}, Reports: {}, Settings: {}, Content: {}, Activity: {}, Notifications: {},
        window: { confirm: () => true },
    };
    vm.runInNewContext(parsed.descriptor.script.content.replace(/import[\s\S]*?;/g, '').replace('export default', 'module.exports ='), context);
    return context.module.exports;
}

function state(component, extra = {}) {
    const result = { ...component.data?.call(extra), ...extra };
    for (const [name, method] of Object.entries(component.methods || {})) result[name] = method.bind(result);
    for (const [name, getter] of Object.entries(component.computed || {})) Object.defineProperty(result, name, { get: () => getter.call(result), configurable: true });
    return result;
}

(async () => {
    for (const filename of fs.readdirSync(directory).filter(name => name.endsWith('.vue'))) load(filename.replace('.vue', ''));

    const walletEvents = [];
    const wallet = state(load('PassengerWallet'), { userId: 1, permissions: { users_wallet: true }, busy: false, $emit: (...args) => walletEvents.push(args) });
    const snapshot = { available: true, enabled: true, active: true, points_available: 100, card_id: 12, revision: 'a'.repeat(64) };
    requestHandler = async () => snapshot;
    await wallet.load(); assert.equal(wallet.points, 100); assert.equal(wallet.dirty, false);
    wallet.points = 150; wallet.reason = 'Correct credit'; assert.equal(wallet.canSave, true);
    for (const invalid of ['', ' ', -1, 1.5, 2147483648, 'abc']) { wallet.points = invalid; assert.equal(wallet.canSave, false); }
    wallet.points = 0; assert.equal(wallet.canSave, true, 'A zero balance can be saved');
    wallet.permissions.users_wallet = false; assert.equal(wallet.canSave, false); wallet.permissions.users_wallet = true;
    wallet.wallet = { ...snapshot, enabled: false }; assert.equal(wallet.canSave, false); wallet.wallet = snapshot;
    wallet.busy = true; assert.equal(wallet.canSave, false); wallet.busy = false;
    let walletCalls = [];
    requestHandler = async (_vm, method, endpoint, payload) => { walletCalls.push({method,endpoint,payload}); return {...snapshot,points_available:payload.points_available,revision:'b'.repeat(64)}; };
    await wallet.save(); assert.equal(wallet.wallet.points_available, 0); assert.equal(wallet.reason, ''); assert.equal(wallet.dirty, false);
    assert.equal(walletCalls[0].endpoint, 'users/1/wallet'); assert.equal(walletCalls[0].payload.revision, snapshot.revision);
    assert.equal(walletEvents.some(event => event[0] === 'busy' && event[1] === true), true);
    wallet.points = 30; wallet.reason = 'Balance correction';
    requestHandler = async () => { throw Object.assign(new Error('The wallet changed'), { status: 409 }); };
    await wallet.save(); assert.equal(wallet.stale, true); assert.equal(wallet.canSave, false); assert.equal(wallet.points, 30);
    requestHandler = async () => ({ ...snapshot, points_available: 20 });
    await wallet.load(); assert.equal(wallet.points, 20); assert.equal(wallet.stale, false);
    wallet.points = 50; wallet.reason = 'Correct credit';
    let finishWalletSave;
    requestHandler = () => new Promise(resolve => { finishWalletSave = resolve; });
    const pendingWalletSave = wallet.save(); assert.equal(wallet.canSave, false); assert.equal(wallet.confirmLeave(), false);
    finishWalletSave({ ...snapshot, points_available: 50 }); await pendingWalletSave;
    assert.equal(wallet.wallet.points_available, 50);
    const walletParent = state(load('Passengers'), {$refs: {passengerWallet: {confirmLeave: () => false}}});
    assert.equal(walletParent.confirmLeave(), false, 'Unsaved wallet edits participate in navigation guards');

    const notices = state(load('Notifications'), {options:{permissions:{notifications_send:true,users_view:true}}});
    notices.form={audience:'selected',title:'Update',body:'Please check your ticket',reason:'Operations update',schedule_detail_id:''}; notices.passengerText='12, 15';
    let sendAttempts=[];
    requestHandler=async (_vm,method,endpoint,payload)=>{ if(endpoint==='notifications/preview') return {recipient_count:2,title:payload.title,body:payload.body,preview_token:'proof'}; if(endpoint==='notifications/send'){sendAttempts.push(payload);throw new Error('Temporary network error');} throw new Error('Unexpected endpoint'); };
    await notices.previewMessage(); assert.equal(notices.preview.recipient_count,2); assert.equal(sendAttempts.length,0,'Preview never sends');
    await notices.send(); await notices.send(); assert.equal(sendAttempts[0].request_key,sendAttempts[1].request_key,'A retry uses the same idempotency key');
    notices.invalidatePreview(); await notices.send(); assert.equal(sendAttempts.length,2,'Edits require a new preview');
    notices.passengerText='12,wrong'; assert.throws(()=>notices.payload(),/valid passenger IDs/);
    const booking = state(load('Bookings'), { options: { permissions: { seats_refund: true } } });
    booking.detail = { active_seats: 2, tickets: [
        { id: 1, type: 'advance booking', seat_no: 7, deleted_at: null },
        { id: 2, type: 'booked', seat_no: 8, deleted_at: null },
        { id: 3, type: 'reschedule', deleted_at: null },
        { id: 4, type: 'booked', deleted_at: '2026-10-20' },
    ] };
    assert.deepEqual(Array.from(booking.availableTickets, item => item.id), [1, 2], 'Only active reserved and confirmed ERP types can be selected');
    assert.equal(booking.isConfirmed(booking.detail.tickets[0]), false);
    assert.equal(booking.isConfirmed(booking.detail.tickets[1]), true);
    booking.ticketIds = [2];
    booking.detail.seat_removal_policy = { requires_full_cancellation: true };
    assert.equal(booking.policyViolation, true, 'Online full-cancellation policy rejects a subset');
    booking.ticketIds = [1, 2];
    assert.equal(booking.policyViolation, false);
    booking.detail.seat_removal_policy = { preserve_one_seat: true };
    assert.equal(booking.policyViolation, true, 'Legacy-history policy requires one active seat to remain');
    booking.confirmed = true; booking.reason = 'Passenger requested cancellation';
    let mutations = 0; requestHandler = async () => { mutations++; };
    await booking.removeSeats();
    assert.equal(mutations, 0, 'Invalid policy selections are guarded in the handler as well as the button');

    const settings = state(load('Settings'));
    settings.settings = { latest_version: '1.0.0', minimum_supported_version: '1.0.0', force_update: false, maintenance_mode: false, maintenance_message: '', support_phone: null, payment_methods: ['historical_method'], features: { wallet: false, notifications: false, online_payments: null, future_feature: true } };
    const payload = settings.payload();
    assert.equal(payload.force_update, false);
    assert.equal(payload.maintenance_message, null);
    assert.equal(payload.features.wallet, false);
    assert.equal('payment_methods' in payload, false, 'Settings saves preserve backend-owned payment methods');
    assert.equal(Object.keys(payload.features).join(','), 'wallet,notifications', 'Settings saves only editable feature keys');

    const users = state(load('Passengers'));
    let resolveOld;
    requestHandler = () => new Promise(resolve => { resolveOld = resolve; });
    users.filters.search = 'old'; const oldRequest = users.load(1, true);
    requestHandler = async () => ({ items: [{ id: 2 }], pagination: { total: 1, current_page: 1, last_page: 1 } });
    users.filters.search = 'new'; await users.load(1, true);
    resolveOld({ items: [{ id: 1 }], pagination: { total: 1 } }); await oldRequest;
    assert.equal(users.items[0].id, 2, 'A late response cannot replace a newer passenger search');
    requestHandler = async () => { throw new Error('Unavailable'); }; await users.load();
    assert.equal(users.items.length, 0); assert.equal(users.pagination, null); assert.equal(users.error, 'Unavailable');

    assert.equal(api.formatCnic('3520212345671'), '35202-1234567-1');
    assert.equal(api.formatCnic('35202-1234567-1'), '35202-1234567-1');
    assert.equal(api.formatCnic('*********5671'), '*********5671');
    assert.equal(api.formatCnic(null), '—');
    users.options = { permissions: { users_sensitive: true } };
    assert.equal(users.userCnic({ cnic: '3520212345671', cnic_masked: '*********5671' }), '35202-1234567-1');
    users.options.permissions.users_sensitive = false;
    assert.equal(users.userCnic({ cnic: '3520212345671', cnic_masked: '*********5671' }), '*********5671');
    const reports = state(load('Reports'), { options: { permissions: { users_view: true, users_sensitive: true } } });
    reports.filters.report = 'user_verification'; reports.filters.verification = 'unverified';
    reports.filters.search = '35202-1234567-1';
    assert.equal(reports.userReport, true); assert.equal(reports.usesEnvironment, false);
    assert.equal('environment' in reports.query(), false, 'Account-only reports omit payment environment');
    let reportParams;
    requestHandler = async (_vm, _method, _endpoint, _payload, filters) => { reportParams = filters; return { items: [], columns: [], summary: {} }; };
    await reports.load(1, true);
    assert.equal(reportParams.verification, 'unverified'); assert.equal(reportParams.search, '35202-1234567-1');
    reports.filters.report = 'user_bookings'; reports.reportChanged();
    assert.equal(reports.usesEnvironment, true); assert.equal(reports.filters.status, ''); assert.equal(reports.filters.search, '');
    await reports.load(2);
    assert.equal(reportParams.report, 'user_verification', 'Pagination retains applied report, not unsubmitted filters');
    await reports.load(1, true);
    assert.equal(reportParams.report, 'user_bookings'); assert.equal(reportParams.environment, 'live');
    reports.filters.report = 'sales';
    assert.equal('verification' in reports.query(), false);
    assert.equal(reports.format('3520212345671', 'cnic'), '35202-1234567-1');
    assert.equal(reports.format('unverified', 'verification'), 'Unverified');

    const content = state(load('Content')); content.filters.status = 'inactive';
    let params;
    requestHandler = async (_vm, _method, _endpoint, _payload, filters) => { params = filters; return { items: [], pagination: null, bus_classes: [] }; };
    await content.load(1, true); assert.equal(params.is_active, 0, 'Fleet visibility filters use the confirmed API field');
    content.edit(); content.form.title = 'Home banner'; content.form.placements = ['home_banner']; content.reason = 'Publish new banner';
    let upload;
    content.file = new Blob(['fixture'], { type: 'image/png' });
    requestHandler = async (_vm, method, endpoint, payload) => {
        if (method === 'post') upload = { method, endpoint, payload };
        return { items: [], pagination: null, bus_classes: [] };
    };
    await content.save();
    assert.equal(upload.endpoint, 'content'); assert.equal(upload.payload instanceof FormData, true);
    assert.equal(upload.payload.get('is_active'), '1'); assert.equal(upload.payload.get('placements[]'), 'home_banner');
    assert.equal(upload.payload.has('image_url'), false); assert.equal(upload.payload.has('image'), true);
    content.edit({ id: 2, source: 'upload', image_url: 'https://example.test/old.jpg', placements: ['bus_guide'], revision: 'a'.repeat(64), is_active: true });
    content.reason = 'Replace picture'; content.file = new Blob(['replacement'], { type: 'image/png' });
    await content.save(); assert.equal(upload.payload.get('_method'), 'PUT'); assert.equal(upload.payload.get('revision'), 'a'.repeat(64));
    content.edit(); content.reason = 'Invalid placement'; content.form.placements = [];
    mutations = 0; requestHandler = async () => { mutations++; }; await content.save();
    assert.equal(mutations, 0); assert.match(content.formError, /placement/);
    for (const name of ['Overview', 'Bookings', 'Payments', 'Reports']) assert.equal(state(load(name)).filters.environment, 'live', name + ' excludes known sandbox transactions by default');

    const policyDefaults = { registrations_enabled: true, logins_enabled: true, bookings_enabled: true, otp_enabled: true, daily_seat_limit: null, monthly_seat_limit: null, otp_per_minute: 3, otp_per_hour: 10, otp_per_day: 20, otp_per_month: 100, login_per_minute: 5, otp_verify_per_ten_minutes: 5, password_reset_per_minute: 5 };
    const controlDefaults = { daily_seat_limit: null, monthly_seat_limit: null, otp_daily_limit: null, otp_monthly_limit: null, bookings_enabled: true, otp_enabled: true };
    let action;
    const passengerLimits = state(load('PassengerLimits'), { userId: 7, controls: { limits: controlDefaults, defaults: policyDefaults, usage: { daily: 2, monthly: 5 }, revision: 'r'.repeat(64) }, permissions: { users_limits: true, users_password_reset: true }, $emit: () => {} });
    passengerLimits.limits.daily_seat_limit = '0'; passengerLimits.limits.monthly_seat_limit = ''; passengerLimits.reason = 'Reviewed passenger limits';
    requestHandler = async (_vm, method, endpoint, body) => { action = { method, endpoint, body }; };
    await passengerLimits.save(); assert.equal(action.endpoint, 'users/7/limits'); assert.equal(action.body.limits.daily_seat_limit, 0); assert.equal(action.body.limits.monthly_seat_limit, null);
    passengerLimits.limits = { ...controlDefaults }; action = null;
    await passengerLimits.forceReset(); assert.equal(action.endpoint, 'users/7/require-password-reset'); assert.equal(Object.keys(action.body).sort().join(','), 'reason,revision');
    passengerLimits.permissions.users_password_reset = false; action = null; await passengerLimits.forceReset(); assert.equal(action, null);
    passengerLimits.limits.daily_seat_limit = '1.5'; await passengerLimits.save(); assert.equal(action, null); assert.match(passengerLimits.error, /whole numbers/);
    const appControls = state(load('AppControls'), { options: { permissions: { app_controls_update: true } } });
    requestHandler = async () => ({ settings: policyDefaults, revision: 'p'.repeat(64) }); await appControls.load();
    appControls.settings.daily_seat_limit = '0'; appControls.reason = 'Pause new seats'; action = null;
    requestHandler = async (_vm, method, endpoint, body) => { if (method === 'put') action = { endpoint, body }; return { settings: policyDefaults, revision: 'p'.repeat(64) }; };
    await appControls.save(); assert.equal(action.endpoint, 'policies'); assert.equal(action.body.settings.daily_seat_limit, 0);
    appControls.options.permissions.app_controls_update = false; appControls.settings.otp_per_day = 1; appControls.reason = 'Unauthorized'; action = null; await appControls.save(); assert.equal(action, null);
    const blacklist = state(load('Passengers'), { options: { permissions: { users_update: true, users_blacklist: false } }, detail: { user: { status: 'blacklisted' } }, status: 'active' });
    assert.equal(blacklist.canChangeStatus, false); assert.equal(blacklist.statusAllowed, false);

    const filters = load('MobileFilters'); let emitted;
    filters.methods.set.call({ modelValue: { search: 'before' }, $emit: (_event, value) => { emitted = value; } }, 'search', 'after');
    assert.equal(emitted.search, 'after');
    const shell = state(load('MobileAppPage'), { options: { permissions: { overview: true, users_view: false } }, $route: { name: 'mobile-app-users' } });
    assert.equal(shell.screens.length, 1); assert.equal(shell.canOpen, false, 'A direct route cannot render a denied submodule');
    shell.options.notices = ['Shared dependency notice', { screens: ['mobile-app-users'], message: 'Passenger scope restriction' }, { screens: ['mobile-app-settings', 'mobile-app-content'], message: 'Configuration scope restriction' }];
    shell.$route.name = 'mobile-app-settings';
    assert.equal(shell.visibleNotices.length, 2); assert.equal(shell.visibleNotices[1].message, 'Configuration scope restriction');
    shell.$route.name = 'mobile-app-users';
    assert.equal(shell.visibleNotices[1].message, 'Passenger scope restriction', 'Passenger scope notices stay on the relevant screen');

    const availability = state(load('Availability'), { options: {} });
    let availabilityParams;
    requestHandler = async (_vm, _method, endpoint, _body, params) => { availabilityParams = { endpoint, ...params }; return { context: { terminal_id: 24, terminals: [], confirmed: false }, items: [], pagination: { total: 0 } }; };
    await availability.load(true);
    const appliedDate = availability.applied.date;
    availability.filters.date = '2026-10-30';
    await availability.page(2);
    assert.equal(availabilityParams.date, appliedDate, 'Pagination uses the last applied travel date');
    assert.equal(availabilityParams.page, 2);
    assert.equal(availabilityParams.mobile_terminal_id, 24);
    await availability.openRoute({ id: 3, name: 'Example route' });
    assert.equal(availabilityParams.endpoint, 'availability/schedules');
    assert.equal(availabilityParams.route_id, 3);
    assert.equal(availabilityParams.status, '');
    requestHandler = async () => { throw new Error('Unavailable'); };
    await availability.load(); assert.equal(availability.data, null); assert.equal(availability.error, 'Unavailable');
    const logins = state(load('LoginActivity'), { scope: { terminal_id: 10, route_id: 3, from: '2020-01-01' } });
    let loginParams;
    requestHandler = async (_vm, _method, _endpoint, _body, params) => { loginParams = params; return { available: true, daily: [{ date: '2026-10-20', logins: 4, first_logins: 1 }, { date: '2026-10-19', logins: null, first_logins: null }] }; };
    await logins.load(); assert.equal(loginParams.days, 7); assert.equal(loginParams.terminal_id, 10); assert.equal(loginParams.from, undefined);
    assert.equal(logins.peak, 4); assert.equal(logins.barHeight(4), 90); assert.equal(logins.barHeight(null), 0);
    logins.days = 30; await logins.load(); assert.equal(loginParams.days, 30);
    requestHandler = async () => { throw new Error('Analytics unavailable'); }; await logins.load(); assert.equal(logins.data, null); assert.equal(logins.error, 'Analytics unavailable');

    await assert.rejects(api.request({ callApi: async () => ({ status: 422, data: { errors: { reason: ['Enter a reason.'] } } }) }, 'put', 'settings', {}), /Enter a reason/);
    console.log('PASS all Mobile App SFCs compile; CNIC display/masking and user report filters/pagination; seat types and cancellation policies, guarded mutations, settings preservation, blacklist privileges, limit payloads, forced reset, app controls, stale search/error states, fleet filters, live environment defaults, permission routing, and API errors');
})().catch(error => { console.error(error); process.exitCode = 1; });
