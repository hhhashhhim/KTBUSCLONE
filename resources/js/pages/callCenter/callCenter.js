export function pakistanDate(now = new Date()) {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Karachi', year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(now);
}

export function callbackValue(value) {
    return value ? value.replace('T', ' ').slice(0, 16) + ':00' : null;
}

export function inputDateTime(value) {
    if (!value) return '';
    if (!/(Z|[+-]\d\d:\d\d)$/.test(value)) return value.replace(' ', 'T').slice(0, 16);
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Karachi', year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(new Date(value)).map(part => [part.type, part.value]));
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
}

export function apiError(response) {
    const details = Object.values(response?.data?.errors || {}).flat().filter(Boolean);
    return details.length ? details.join(' ') : response?.data?.message || 'Unable to complete this request. Please try again.';
}

export function reviewSnapshot(report, daily, shift) {
    const token = daily ? report?.snapshot_token : report?.snapshot_tokens?.[shift];
    if (typeof token !== 'string' || token.length !== 64) {
        throw new Error('The review snapshot is unavailable. Select View report to reload before saving your review.');
    }
    return token;
}

export async function request(vm, method, endpoint, payload = null, params = {}) {
    // Keep validation and access failures on this page, where the agent can act on them.
    const response = await vm.callApi(method, `call-center/${endpoint}`, payload, {
        params, headers: { Accept: 'application/json' }, validateStatus: () => true,
    });
    if (!response || response.status < 200 || response.status >= 300 || response.data?.status !== true) {
        const error = new Error(apiError(response));
        error.fields = response?.data?.errors || {};
        throw error;
    }
    return response.data.data;
}

export function canAccessCallCenter(permissions = []) {
    return permissions.some((module) => module.name === 'call-center' && Boolean(Number(module.allow)));
}

export function displayTime(value) {
    if (!value) return '—';
    // API dates without an offset are already in its advertised Asia/Karachi timezone.
    if (!/(Z|[+-]\d\d:\d\d)$/.test(value)) return value.replace('T', ' ').slice(0, 16);
    return new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Karachi', dateStyle: 'medium', timeStyle: 'short',
    }).format(new Date(value));
}

export function searchKinds(value) {
    const text = value.trim();
    const digits = text.replace(/\D/g, '');
    if (/^\d{5}-\d{7}-\d$/.test(text)) return ['cnic'];
    if (/^[+()\d\s-]+$/.test(text) && /[+()\s-]/.test(text) && digits.length >= 10 && digits.length <= 15) return ['phone'];
    if (/^\d{13}$/.test(text)) return ['cnic', 'ticket'];
    if (/^(0\d{10}|92\d{10}|\d{10})$/.test(text)) return ['phone', 'ticket'];
    if (/^\d{1,18}$/.test(text) && Number(text) > 0) return ['ticket'];
    return [];
}

export function bookingTime(value) {
    const match = String(value || '').trim().match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?$/i);
    if (!match) return '';
    let hour = Number(match[1]);
    if (Number(match[2]) > 59 || (match[3] ? hour < 1 || hour > 12 : hour > 23)) return '';
    if (match[3]) hour = hour % 12 + (match[3].toUpperCase() === 'PM' ? 12 : 0);
    return `${String(hour).padStart(2, '0')}:${match[2]}`;
}

export function callQueueParams(queue, filters, page = 1) {
    const params = { ...filters, queue, page };
    if (!params.date) delete params.date;
    if (queue === 'callbacks_due' || queue === 'open_complaints') delete params.date;
    if (queue === 'open_complaints') { delete params.category; delete params.status; }
    if (queue === 'callbacks_due') delete params.status;
    return params;
}

export function savedShift(userId) {
    try { const value = Number(sessionStorage.getItem(`cc-shift-${userId}`)); return [1, 2, 3].includes(value) ? value : ''; }
    catch { return ''; }
}

export function rememberShift(userId, shift) {
    try { sessionStorage.setItem(`cc-shift-${userId}`, String(shift)); } catch { /* Preferences are optional. */ }
}

// Screens are independent grants; a supervisor scope never grants an action.
export function callCenterScreens(permissions = {}) {
    return [
        { name: 'call-center', label: 'Call log', allowed: permissions.create || permissions.view_calls || permissions.quick_answers },
        { name: 'call-center-complaints', label: 'Complaints', allowed: permissions.view_complaints },
        { name: 'call-center-shifts', label: 'Shift summary', allowed: permissions.shift_report },
        { name: 'call-center-daily', label: 'Daily report', allowed: permissions.daily_report },
    ].filter(screen => screen.allowed === true);
}
