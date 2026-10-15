<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;
use App\Models\CallCenter\CallLog;
use Illuminate\Http\Request;

class CallCenterScope
{
    public static function allows(User $user, string $name): bool
    {
        $role = $user->role;
        if (!$role || (int) $role->company_id !== (int) $user->company_id || $user->hide) {
            return false;
        }
        return self::granted($user->company->modules ?? [], $name) && self::granted($role->permissions ?? [], $name);
    }

    private static function granted(array $permissions, string $name): bool
    {
        foreach ($permissions as $menu) {
            if (($menu['name'] ?? '') !== 'call-center' || empty($menu['allow'])) { continue; }
            if ($name === 'call-center') { return true; }
            foreach ($menu['buttons'] ?? [] as $button) {
                if (($button['name'] ?? '') === $name && !empty($button['allow'])) { return true; }
            }
        }
        return false;
    }

    public static function terminal(Request $request): ?int
    {
        if (self::allows($request->user(), 'call-center-all-terminals')) {
            return $request->filled('terminal_id') && (int) $request->terminal_id !== 0 ? (int) $request->terminal_id : null;
        }
        abort_if($request->filled('terminal_id') && (int) $request->terminal_id !== (int) $request->user()->terminal_id, 403, 'Terminal access denied.');
        return (int) ($request->user()->terminal_id ?: -1);
    }

    public static function tickets(Request $request, bool $filterTerminal = true)
    {
        $user = $request->user();
        $query = Ticket::withTrashed()->where('tickets.company_id', $user->company_id);
        $terminal = $filterTerminal ? self::terminal($request) : (self::allows($user, 'call-center-all-terminals') ? null : (int) ($user->terminal_id ?: -1));
        if ($terminal !== null) { $query->where('tickets.terminal_id', $terminal); }
        // Preserve the ERP's assigned route boundary even with cross-terminal permission.
        if ($user->route_ids !== 'all') {
            $routes = is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true);
            $query->whereIn('tickets.route_id', array_filter(array_map('intval', is_array($routes) ? $routes : [])));
        }
        return $query;
    }

    public static function calls(Request $request, bool $report = false)
    {
        $user = $request->user();
        $query = CallLog::where('company_id', $user->company_id);
        $terminal = self::terminal($request);
        if ($terminal !== null) { $query->where('terminal_id', $terminal); }
        $query->where(function ($q) use ($request) {
            $q->whereNull('ticket_id')->orWhereIn('ticket_id', self::tickets($request, false)->select('tickets.id'));
        });
        if (!self::allows($user, 'call-center-supervise')) {
            $query->where(function ($q) use ($user, $report) {
                $q->where('agent_id', $user->id);
                if (!$report) { $q->orWhere('assigned_to', $user->id); }
            });
        }
        return $query;
    }
}
