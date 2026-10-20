<?php

namespace App\Support;

class MobileAppPermissions
{
    public static function submodules(): array
    {
        return [
            'mobile-app-overview' => 'Overview', 'mobile-app-users' => 'App users',
            'mobile-app-bookings' => 'Bookings and seats', 'mobile-app-payments' => 'Payment monitoring',
            'mobile-app-content' => 'Content and fleet', 'mobile-app-reports' => 'Reports',
            'mobile-app-notifications' => 'Notifications', 'mobile-app-settings' => 'App settings', 'mobile-app-activity' => 'Activity and audit',
        ];
    }

    public static function definitions(): array
    {
        $groups = [
            'Overview' => ['overview', [
                'view-dashboard' => ['View dashboard', 'View mobile booking, passenger and payment totals, plus aggregate successful-login trends within your permitted terminals and routes. Login history begins when tracking is enabled.'],
            ]],
            'App users' => ['users', [
                'view-users' => ['View app users', 'Search passenger accounts and view masked contact details within your permitted scope.'],
                'manage-wallet' => ['Adjust passenger wallet points', 'Set the existing loyalty-points balance for a visible passenger with an active linked card, while Wallet is enabled. Requires View app users and company Admin access or all company terminals/routes. Every adjustment requires a reason and records the previous and new balances.'],
                'manage-users' => ['Control app accounts', 'Suspend or reactivate passenger accounts with a required reason. Requires company Admin access or all company terminals and routes, and the passenger must be visible in your permitted scope. Does not grant access to credentials or staff roles.'],
                'blacklist-users' => ['Blacklist or release passengers', 'Blacklist an account and its mobile/CNIC identities to prevent re-registration, or release a blacklist. Requires View app users and company Admin access or all company terminals/routes. The passenger must be visible in your permitted scope. Every change requires a reason.'],
                'manage-user-limits' => ['Manage passenger limits', 'Set per-account daily/monthly seat limits, OTP delivery limits, and booking/OTP holds. Blank limits inherit app defaults; zero prevents new usage. Requires View app users and company Admin access or all company terminals/routes. The passenger must be visible in your permitted scope.'],
                'force-password-reset' => ['Require passenger password reset', 'Revoke mobile sessions and existing reset codes, then require the passenger to verify an OTP and choose a new password. Does not display or set a passenger password. Requires View app users and company Admin access or all company terminals/routes. The passenger must be visible in your permitted scope.'],
                'revoke-sessions' => ['Revoke passenger sessions', 'Sign a passenger out of all app sessions without changing their password. Requires View app users and company Admin access or all company terminals/routes. The passenger must be visible in your permitted scope.'],
                'view-sensitive-users' => ['View passenger CNIC and contact details', 'View and search full passenger CNIC, mobile and email in App Users and permitted user reports/exports. Also requires View app users; otherwise these fields remain masked.'],
            ]],
            'Bookings and seats' => ['bookings', [
                'view-bookings' => ['View mobile bookings and availability', 'Read mobile bookings and seats, plus route and schedule availability for permitted routes and online terminals. This is a read-only view; changing access or reservations requires separate privileges.'],
                'remove-seats' => ['Remove reserved seats', 'Cancel eligible mobile reservations, including individual journey segments, with a required reason through the existing cancellation service. Excludes departed journeys, closed tickets and pending gateway payments. Also requires View mobile bookings.'],
                'refund-seats' => ['Cancel confirmed seats', 'Cancel eligible confirmed mobile seats using net fare and existing cancellation controls. Approval-controlled bookings and payments awaiting review cannot be removed here. Does not send money through a payment gateway. Also requires View mobile bookings and Remove reserved seats.'],
            ]],
            'Payment monitoring' => ['payments', [
                'view-payments' => ['View mobile payments', 'Read payment states, transaction references and exceptions. Does not mark payments paid or authorize a gateway refund.'],
            ]],
            'Content and fleet' => ['content', [
                'view-content' => ['View app content', 'Read the app fleet media and published content configuration.'],
                'manage-content' => ['Manage app content', 'Upload or replace app pictures, choose their placements, set ordering, publish, hide, and remove content companywide. Also requires View app content. Company Admin can manage content regardless of booking route assignments; other roles require all company terminals and routes.'],
            ]],
            'Notifications' => ['notifications', [
                'view-notifications' => ['View notification history', 'Read your sent announcements and inbox/read/push acceptance counts. Company Admin and staff with all terminals/routes can view companywide send history. Does not reveal device tokens.'],
                'send-notifications' => ['Send passenger announcements', 'Preview and send an announcement with an audit reason to selected visible passengers or a permitted departure. Requires View notification history plus View app users for selected passengers, or View mobile bookings for trips.'],
                'broadcast-notifications' => ['Broadcast to all app users', 'Send an announcement to all active company app accounts after previewing the audience count. Also requires Send passenger announcements and company Admin access or all company terminals and routes.'],
            ]],
            'Reports' => ['reports', [
                'view-reports' => ['View mobile reports', 'Read sales, booking status, passenger and payment reports within your permitted terminals and routes. User registration, verification, restriction and booking-activity reports also require View app users; full CNIC/contact details require their separate privilege.'],
                'export-reports' => ['Export mobile reports', 'Download the report data available in your permitted scope. Also requires View mobile reports.'],
            ]],
            'App settings' => ['settings', [
                'view-settings' => ['View app settings', 'Read version, update, maintenance, store link, support and supported feature configuration.'],
                'manage-app-controls' => ['Manage app access and limits', 'Set companywide registration, sign-in, booking and OTP switches; default daily/monthly seat limits; and OTP/login/reset rate limits. Requires View app settings. Company Admin can manage these controls regardless of booking route assignments; other roles require all company terminals and routes.'],
                'manage-settings' => ['Manage app settings', 'Change supported public app configuration companywide with an audit reason. Also requires View app settings. Company Admin can edit configuration regardless of booking route assignments; other roles require all company terminals and routes. Does not manage payment credentials.'],
            ]],
            'Activity and audit' => ['activity', [
                'view-activity' => ['View mobile administration audit', 'Read staff actions, reasons and changes within your permitted terminals and routes.'],
            ]],
            'Access scope' => [null, [
                'all-terminals' => ['Access all company terminals', 'Extend granted mobile actions across company terminals. Assigned route restrictions remain in effect.'],
            ]],
        ];
        // Include every prerequisite, including transitive prerequisites, so the
        // API and role editor use the same effective access rules.
        $requires = [
            'manage-wallet' => ['view-users'], 'manage-users' => ['view-users'],
            'blacklist-users' => ['view-users'], 'manage-user-limits' => ['view-users'],
            'force-password-reset' => ['view-users'], 'revoke-sessions' => ['view-users'],
            'view-sensitive-users' => ['view-users'],
            'remove-seats' => ['view-bookings'], 'refund-seats' => ['view-bookings', 'remove-seats'],
            'manage-content' => ['view-content'], 'export-reports' => ['view-reports'],
            'manage-app-controls' => ['view-settings'], 'manage-settings' => ['view-settings'],
            'send-notifications' => ['view-notifications'],
            'broadcast-notifications' => ['view-notifications', 'send-notifications'],
        ];
        $definitions = [];
        foreach ($groups as $group => [$submenu, $items]) {
            foreach ($items as $name => [$label, $description]) {
                $definitions[] = ['name' => 'mobile-app-' . $name, 'label' => $label,
                    'description' => $description, 'group' => $group,
                    'submenu' => $submenu ? 'mobile-app-' . $submenu : null,
                    'requires' => array_map(fn ($required) => 'mobile-app-' . $required, $requires[$name] ?? [])];
            }
        }
        return $definitions;
    }

    public static function append(array $modules, bool $available = false): array
    {
        $position = null;
        foreach ($modules as $index => $module) {
            if (($module['name'] ?? '') === 'mobile-app') { $position = $index; break; }
        }
        $module = $position === null ? ['name' => 'mobile-app', 'allow' => $available] : $modules[$position];
        $buttons = $module['buttons'] ?? [];
        $names = array_column($buttons, 'name');
        foreach (self::definitions() as $definition) {
            if (!in_array($definition['name'], $names, true)) {
                $buttons[] = ['name' => $definition['name'], 'allow' => $available];
            }
        }
        $children = $module['childs'] ?? [];
        $childNames = array_column($children, 'name');
        foreach (self::submodules() as $name => $label) {
            if (!in_array($name, $childNames, true)) {
                $children[] = ['name' => $name, 'allow' => $available, 'buttons' => []];
            }
        }
        $module['buttons'] = $buttons;
        $module['childs'] = $children;
        if ($position === null) { $modules[] = $module; } else { $modules[$position] = $module; }
        return array_values($modules);
    }

    public static function forCompanyAdmin(array $modules): array
    {
        return array_map(function ($module) {
            if (($module['name'] ?? '') === 'mobile-app') {
                $module['allow'] = true;
                foreach ($module['buttons'] as &$button) { $button['allow'] = true; }
                unset($button);
                foreach ($module['childs'] as &$child) { $child['allow'] = true; }
                unset($child);
            }
            return $module;
        }, self::append($modules));
    }
}
