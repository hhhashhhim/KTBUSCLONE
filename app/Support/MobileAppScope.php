<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileAppScope
{
    public static function isCompanyAdmin(User $user): bool
    {
        $role = $user->role;
        return $user->company_id && $role && !$user->hide && (int) $role->company_id === (int) $user->company_id
            && CallCenterPermissions::isAdminRole($role->name);
    }

    public static function allows(User $user, string $name): bool
    {
        $role = $user->role;
        if (!$role || $user->hide || (int) $role->company_id !== (int) $user->company_id) { return false; }
        $permissions = self::isCompanyAdmin($user)
            ? MobileAppPermissions::forCompanyAdmin($role->permissions ?? []) : ($role->permissions ?? []);
        $definition = collect(MobileAppPermissions::definitions())->firstWhere('name', $name);
        foreach (array_merge([$name], $definition['requires'] ?? []) as $required) {
            if (!self::granted($user->company->modules ?? [], $required) || !self::granted($permissions, $required)) {
                return false;
            }
        }
        return true;
    }

    private static function granted(array $modules, string $name): bool
    {
        $definition = collect(MobileAppPermissions::definitions())->firstWhere('name', $name);
        foreach ($modules as $module) {
            if (($module['name'] ?? '') !== 'mobile-app' || empty($module['allow'])) { continue; }
            if ($name === 'mobile-app') { return true; }
            $children = array_column($module['childs'] ?? [], 'allow', 'name');
            if (array_key_exists($name, MobileAppPermissions::submodules())) { return !empty($children[$name]); }
            if (!$definition || ($definition['submenu'] && empty($children[$definition['submenu']]))) { return false; }
            foreach ($module['buttons'] ?? [] as $button) {
                if (($button['name'] ?? '') === $name) { return !empty($button['allow']); }
            }
        }
        return false;
    }

    public static function allCompanyRoutes(User $user): bool
    {
        if ($user->route_ids === 'all') { return true; }
        $assigned = is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true);
        if (!$user->company_id || !is_array($assigned) || !$assigned || !Schema::hasTable('routes')) { return false; }
        $assigned = array_unique(array_filter(array_map(function ($id) {
            return (is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0 ? (int) $id : 0;
        }, $assigned)));
        if (!$assigned) { return false; }
        // ERP user editing persists selected IDs, including when every current company route is selected.
        // Re-evaluate current routes so newly added routes require a deliberate assignment update.
        $companyRoutes = DB::table('routes')->where('company_id', $user->company_id)->whereNull('deleted_at')->pluck('id');
        return $companyRoutes->isNotEmpty() && $companyRoutes->diff($assigned)->isEmpty();
    }

    public static function terminal(Request $request): ?int
    {
        abort_unless($request->user() instanceof User, 403, 'ERP staff access is required.');
        if (self::allows($request->user(), 'mobile-app-all-terminals')) {
            return $request->filled('terminal_id') && (int) $request->terminal_id !== 0 ? (int) $request->terminal_id : null;
        }
        abort_if($request->filled('terminal_id') && (int) $request->terminal_id !== (int) $request->user()->terminal_id, 403, 'Terminal access denied.');
        return (int) ($request->user()->terminal_id ?: -1);
    }

    public static function tickets(Request $request)
    {
        $terminal = self::terminal($request);
        $user = $request->user();
        $query = Ticket::withTrashed()->where('tickets.company_id', $user->company_id)
            ->whereExists(function ($invoice) {
                $invoice->selectRaw('1')->from('invoices')
                    ->whereColumn('invoices.id', 'tickets.invoice_id')
                    ->whereColumn('invoices.company_id', 'tickets.company_id')
                    ->whereNotNull('invoices.passenger_account_id');
            });
        if ($terminal !== null) { $query->where('tickets.terminal_id', $terminal); }
        if ($user->route_ids !== 'all') {
            $routes = is_array($user->route_ids) ? $user->route_ids : json_decode($user->route_ids ?? '[]', true);
            $query->whereIn('tickets.route_id', array_filter(array_map('intval', is_array($routes) ? $routes : [])));
        }
        return $query;
    }
}
