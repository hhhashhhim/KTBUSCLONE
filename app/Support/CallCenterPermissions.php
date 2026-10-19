<?php

namespace App\Support;

class CallCenterPermissions
{
    public static function definitions(): array
    {
        $groups = [
            'Calls' => [
                'view-calls' => ['View call log', 'Read calls in your permitted scope, including complaints.'],
                'create-calls' => ['Record calls', 'Record general information, booking, complaint, refund and reschedule calls.'],
                'edit-calls' => ['Edit calls', 'Update existing calls in your permitted scope. Requires access to view the call.'],
                'follow-up' => ['Record follow-ups', 'Link a follow-up to an earlier call. Also requires Record calls and View call log; refund/reschedule follow-ups also require Edit calls.'],
                'manage-callbacks' => ['Schedule callbacks', 'Set or change callback times. Also requires Record calls or Edit calls.'],
                'assign-calls' => ['Assign to other agents', 'Choose or change another responsible person. Self-assignment is available without this privilege.'],
            ],
            'Passenger assistance' => [
                'passenger-lookup' => ['Find passengers and tickets', 'Search by phone, ticket number or CNIC and link passengers to calls.'],
                'view-history' => ['View caller history', 'Read earlier calls and complaints for a caller in your permitted scope.'],
                'quick-answers' => ['Use quick answers', 'Check fares, departures, available seats and terminal details.'],
            ],
            'Complaints' => [
                'view-complaints' => ['View complaints workspace', 'Open the Complaints tab and read open and closed complaints.'],
                'update-complaints' => ['Add complaint updates', 'Add progress remarks without closing a complaint. Also requires View complaints workspace.'],
                'close-complaints' => ['Close complaints', 'Close with required remarks, including through a follow-up, or reclassify a complaint. Also requires View complaints workspace.'],
                'reopen-complaints' => ['Reopen complaints', 'Reopen a closed complaint. Also requires View complaints workspace and Edit calls.'],
            ],
            'Reports and audit' => [
                'shift-report' => ['View shift summary', 'View and print shift totals within your permitted scope.'],
                'daily-report' => ['View daily report', 'View and print daily totals and booking movements within your permitted scope.'],
                'review-reports' => ['Review reports', 'Save a supervisor review. Also requires the relevant report and View team records.'],
                'view-audit' => ['View call change history', 'See who changed a call and when. Also requires access to view the call.'],
            ],
            'Access scope' => [
                'supervise' => ['View team records', 'Extend granted actions to other agents’ calls and report totals in permitted terminals.'],
                'all-terminals' => ['Access all company terminals', 'Extend granted actions across this company’s terminals. Assigned route limits still apply.'],
            ],
        ];
        $definitions = [];
        foreach ($groups as $group => $items) {
            foreach ($items as $key => [$label, $description]) {
                $definitions[] = ['name' => 'call-center-' . $key, 'label' => $label, 'description' => $description, 'group' => $group];
            }
        }
        return $definitions;
    }

    public static function append(array $modules, bool $available = false, bool $preserveLegacy = false): array
    {
        $clean = []; $standalone = null; $legacy = null; $position = null; $legacyPosition = null;
        foreach ($modules as $menu) {
            if (($menu['name'] ?? '') === 'call-center') {
                // An explicit standalone setting takes precedence over stale nested grants.
                if ($standalone === null) { $standalone = $menu; $position = count($clean); }
                continue;
            }
            if (($menu['name'] ?? '') === 'ticketing') {
                $children = [];
                foreach ($menu['childs'] ?? [] as $child) {
                    if (($child['name'] ?? '') === 'call-center') {
                        if ($legacy === null) {
                            $legacy = $child;
                            $legacy['allow'] = !empty($menu['allow']) && !empty($child['allow']);
                            $legacyPosition = count($clean);
                        }
                    } else { $children[] = $child; }
                }
                $menu['childs'] = $children;
            }
            $clean[] = $menu;
        }
        $module = $standalone ?? $legacy ?? ['name' => 'call-center', 'allow' => $available];
        $module['name'] = 'call-center';
        $module['childs'] = [];
        $module['buttons'] = self::buttons($module['buttons'] ?? [], $available,
            $preserveLegacy && !empty($module['allow']));
        array_splice($clean, $position ?? $legacyPosition ?? count($clean), 0, [$module]);
        return $clean;
    }

    public static function isAdminRole(?string $name): bool
    {
        return strtolower(trim($name ?? '')) === 'admin';
    }

    public static function forCompanyAdmin(array $modules): array
    {
        return array_map(function ($menu) {
            if (($menu['name'] ?? '') === 'call-center') {
                $menu['allow'] = true;
                foreach ($menu['buttons'] as &$button) { $button['allow'] = true; }
                unset($button);
            }
            return $menu;
        }, self::append($modules));
    }

    private static function buttons(array $buttons, bool $available, bool $legacy = false): array
    {
        $existing = array_column($buttons, 'allow', 'name');
        $scopes = ['call-center-supervise', 'call-center-all-terminals'];
        $actions = array_diff(array_column(self::definitions(), 'name'), $scopes);
        // Only the migration can translate the old module-wide grants. Partial or
        // already granular roles are never expanded on subsequent reads or reruns.
        $legacy = $legacy && !array_intersect(array_keys($existing), $actions);
        foreach (self::definitions() as $definition) {
            $name = $definition['name'];
            $allow = $available || ($legacy && in_array($name, $actions, true)
                && ($name !== 'call-center-review-reports' || !empty($existing['call-center-supervise'])));
            if (array_key_exists($name, $existing)) { continue; }
            $buttons[] = ['name' => $name, 'allow' => $allow];
        }
        return $buttons;
    }
}
