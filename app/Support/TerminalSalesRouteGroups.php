<?php

namespace App\Support;

use App\Models\TerminalSalesRouteGroup;
use App\Models\Route\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerminalSalesRouteGroups
{
    public static function authorize(): void
    {
        abort_unless(checkForSubmenu('terminal-sale') && ReportFilterScope::canUseFilter('terminal-sale-route-filter'), 403);
    }

    public static function routeIds(Request $request): ?array
    {
        if (!$request->filled('route_group_id')) {
            return ReportFilterScope::routeIds($request, 'terminal-sale-route-filter');
        }
        self::authorize();
        $request->validate(['route_group_id' => ['required', 'integer', 'min:1']]);
        $group = TerminalSalesRouteGroup::where('company_id', Auth::user()->company_id)->findOrFail($request->route_group_id);
        // An empty/deleted group must never silently expand to all routes.
        return Route::where('company_id', Auth::user()->company_id)
            ->whereIn('id', $group->route_ids ?? [])->pluck('id')->all();
    }
}
