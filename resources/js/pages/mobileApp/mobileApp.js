export const mobileScreens = [
    { name: 'mobile-app', path: '', label: 'Overview', icon: 'fas fa-chart-line', permission: 'overview' },
    { name: 'mobile-app-users', path: 'users', label: 'App Users', icon: 'fas fa-users', permission: 'users_view' },
    { name: 'mobile-app-bookings', path: 'bookings', label: 'Bookings & Seats', icon: 'fas fa-ticket-alt', permission: 'bookings_view' },
    { name: 'mobile-app-availability', path: 'availability', label: 'Routes & Schedules', icon: 'fas fa-route', permission: 'bookings_view' },
    { name: 'mobile-app-payments', path: 'payments', label: 'Payments', icon: 'fas fa-credit-card', permission: 'payments_view' },
    { name: 'mobile-app-reports', path: 'reports', label: 'Reports', icon: 'fas fa-chart-bar', permission: 'reports_view' },
    { name: 'mobile-app-content', path: 'content', label: 'Fleet & Content', icon: 'fas fa-images', permission: 'content_view' },
    { name: 'mobile-app-notifications', path: 'notifications', label: 'Notifications', icon: 'fas fa-bell', permission: 'notifications_view' },
    { name: 'mobile-app-settings', path: 'settings', label: 'App Settings', icon: 'fas fa-cog', permission: 'settings_view' },
    { name: 'mobile-app-activity', path: 'activity', label: 'Activity & Audit', icon: 'fas fa-history', permission: 'activity_view' },
];

export function today() {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Karachi', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
}

export function displayTime(value) {
    if (!value) return '—';
    if (!/(Z|[+-]\d\d:\d\d)$/.test(value)) return String(value).replace('T', ' ').slice(0, 16);
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Karachi', dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

export function formatCnic(value) {
    if (!value) return '—';
    const text = String(value);
    const digits = text.replace(/[-\s]/g, '');
    return /^\d{13}$/.test(digits) ? `${digits.slice(0,5)}-${digits.slice(5,12)}-${digits.slice(12)}` : text;
}

export function number(value) {
    return value === null || value === undefined ? '—' : new Intl.NumberFormat('en-PK', { maximumFractionDigits: 0 }).format(value);
}

export function money(value) {
    return value === null || value === undefined ? '—' : `PKR ${new Intl.NumberFormat('en-PK', { maximumFractionDigits: 2 }).format(value)}`;
}

export function label(value) {
    return value ? String(value).replace(/[_-]/g, ' ').replace(/\b\w/g, character => character.toUpperCase()) : '—';
}

export async function request(vm, method, endpoint, payload = null, params = {}) {
    const response = await vm.callApi(method, `mobile-app/${endpoint}`, payload, {
        params, headers: { Accept: 'application/json' }, validateStatus: () => true,
    });
    if (!response || response.status < 200 || response.status >= 300 || response.data?.status !== true) {
        const details = Object.values(response?.data?.errors || {}).flat().filter(Boolean);
        const error = new Error(details.length ? details.join(' ') : response?.data?.message || 'Unable to reach the server. Please try again.');
        error.status = response?.status;
        error.fields = response?.data?.errors || {};
        throw error;
    }
    return response.data.data;
}
