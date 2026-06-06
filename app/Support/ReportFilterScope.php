<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportFilterScope
{
    public static function terminalId(Request $request, string $permission): ?int
    {
        if (self::canUseFilter($permission)) {
            return self::normalizeId($request->terminal);
        }

        return self::normalizeId(Auth::user()->terminal_id) ?? -1;
    }

    public static function userId(Request $request, string $permission): ?int
    {
        if (self::canUseFilter($permission)) {
            return self::normalizeId($request->user);
        }

        return (int) Auth::user()->id;
    }

    public static function routeIds(Request $request, string $permission): ?array
    {
        if (self::canUseFilter($permission)) {
            $routeIds = self::normalizeIds($request->route);
            return empty($routeIds) ? null : $routeIds;
        }

        return self::assignedRouteIds();
    }

    public static function terminals(string $permission)
    {
        $query = \App\Models\Terminal::where([
            'company_id' => Auth::user()->company_id,
            'hide' => 0,
        ]);

        if (!self::canUseFilter($permission)) {
            $query->where('id', Auth::user()->terminal_id);
        }

        return $query->get(['id', 'name']);
    }

    public static function routes(string $permission)
    {
        $query = \App\Models\Route\Route::where([
            'company_id' => Auth::user()->company_id,
            'hide' => 0,
        ]);

        if (!self::canUseFilter($permission)) {
            $routeIds = self::assignedRouteIds();
            if ($routeIds !== null) {
                empty($routeIds) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $routeIds);
            }
        }

        return $query->get(['id', 'name', 'via']);
    }

    public static function users(string $permission)
    {
        $query = \App\Models\User::where([
            'company_id' => Auth::user()->company_id,
            'hide' => 0,
        ]);

        if (!self::canUseFilter($permission)) {
            $query->where('id', Auth::user()->id);
        }

        return $query->get(['id', 'name']);
    }

    public static function terminalUsers(string $permission, bool $allowAllUserFilter = true)
    {
        $query = \App\Models\User::where([
            'company_id' => Auth::user()->company_id,
            'hide' => 0,
        ]);

        if (!self::canUseFilter($permission)) {
            $query->where('id', Auth::user()->id);
        } elseif (!$allowAllUserFilter || !self::canUseAllUserFilter()) {
            $terminalId = self::normalizeId(Auth::user()->terminal_id);

            if (!$terminalId) {
                return collect();
            }

            $query->where('terminal_id', $terminalId);
        }

        return $query->get(['id', 'name']);
    }

    public static function terminalUserId(Request $request, string $permission, bool $allowAllUserFilter = true): ?int
    {
        if (!self::canUseFilter($permission)) {
            return (int) Auth::user()->id;
        }

        $userId = self::normalizeId($request->user);

        if (!$userId) {
            return null;
        }

        $query = \App\Models\User::where([
            'id' => $userId,
            'company_id' => Auth::user()->company_id,
            'hide' => 0,
        ]);

        if (!$allowAllUserFilter || !self::canUseAllUserFilter()) {
            $query->where('terminal_id', Auth::user()->terminal_id);
        }

        return $query->exists() ? $userId : -1;
    }

    public static function canUseFilter(string $permission): bool
    {
        return (bool) Auth::user()->is_super_admin || (bool) checkPermissionButtons($permission);
    }

    private static function canUseAllUserFilter(): bool
    {
        return (bool) Auth::user()->is_super_admin || (bool) checkPermissionButtons('all-user-filter');
    }

    private static function assignedRouteIds(): ?array
    {
        $routeIds = Auth::user()->route_ids;
        if ($routeIds === 'all') {
            return null;
        }

        if (is_string($routeIds)) {
            $routeIds = json_decode($routeIds, true);
        }

        return self::normalizeIds($routeIds);
    }

    private static function normalizeId($id): ?int
    {
        return $id !== null && $id !== '' && (int) $id !== 0 ? (int) $id : null;
    }

    private static function normalizeIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
