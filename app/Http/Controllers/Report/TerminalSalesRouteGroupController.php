<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\TerminalSalesRouteGroup;
use App\Support\TerminalSalesRouteGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TerminalSalesRouteGroupController extends Controller
{
    public function index()
    {
        TerminalSalesRouteGroups::authorize();
        return TerminalSalesRouteGroup::where('company_id', Auth::user()->company_id)
            ->orderBy('name')->get(['id', 'name', 'route_ids']);
    }

    public function save(Request $request)
    {
        TerminalSalesRouteGroups::authorize();
        $request->validate(['id' => ['nullable', 'integer', 'min:1']]);
        $companyId = Auth::user()->company_id;
        $group = $request->filled('id')
            ? TerminalSalesRouteGroup::where('company_id', $companyId)->findOrFail($request->id)
            : new TerminalSalesRouteGroup(['company_id' => $companyId, 'created_by' => Auth::id()]);
        $request->merge(['name' => trim((string) $request->name)]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('terminal_sales_route_groups', 'name')->where('company_id', $companyId)->ignore($group->id)],
            'route_ids' => ['required', 'array', 'min:1', 'max:500'],
            'route_ids.*' => ['required', 'integer', 'distinct', Rule::exists('routes', 'id')->where('company_id', $companyId)->where('hide', 0)->whereNull('deleted_at')],
        ]);
        $group->fill([
            'name' => $data['name'],
            'route_ids' => array_values(array_map('intval', $data['route_ids'])),
            'updated_by' => Auth::id(),
        ])->save();
        return response()->json(['message' => 'Route group saved.', 'group' => $group->only(['id', 'name', 'route_ids'])]);
    }
}
