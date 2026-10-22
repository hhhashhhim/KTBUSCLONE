<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Schedule\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ScheduleQuotaDiscountController extends Controller
{
    private function authorizeEditor(): void
    {
        abort_unless(checkForSubmenu('schedules') && checkPermissionButtons('edit-schedule'), 403,
            'You are not authorized to manage quota discounts.');
        abort_unless(Schema::hasTable('schedule_quota_discounts'), 503, 'Install the quota discount migration first.');
    }

    public function show(Request $request)
    {
        $this->authorizeEditor();
        $request->validate(['id' => 'required|integer']);
        $company = $request->user()->company_id;
        $schedule = Schedule::where('company_id', $company)->findOrFail($request->id);
        return response()->json([
            // User logins are stored in email. Preserve the dropdown's response key,
            // without querying a user_name column (which only exists on companies).
            'users' => User::where('company_id', $company)->where('hide', 0)->orderBy('name')->get(['id', 'name', 'email as user_name', 'terminal_id']),
            'discounts' => DB::table('schedule_quota_discounts')->where('company_id', $company)->where('schedule_id', $schedule->id)->where('active', true)->orderBy('id')->get(['user_id', 'percentage']),
        ]);
    }

    public function save(Request $request)
    {
        $this->authorizeEditor();
        $company = $request->user()->company_id;
        $input = $request->validate([
            'id' => 'required|integer', 'discounts' => 'present|array|max:500',
            'discounts.*.user_id' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('company_id', $company)->where('hide', 0)],
            'discounts.*.percentage' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);
        return DB::transaction(function () use ($input, $request, $company) {
            $schedule = Schedule::where('company_id', $company)->whereKey($input['id'])->lockForUpdate()->firstOrFail();
            $query = DB::table('schedule_quota_discounts')->where('company_id', $company)->where('schedule_id', $schedule->id);
            $before = (clone $query)->where('active', true)->get(['user_id', 'percentage'])->toArray();
            // Keep an inactive marker so a cached discounted quote is still revalidated after removal.
            $query->update(['active' => false, 'updated_by' => $request->user()->id, 'updated_at' => now()]);
            foreach ($input['discounts'] as $row) {
                DB::table('schedule_quota_discounts')->updateOrInsert([
                    'company_id' => $company, 'schedule_id' => $schedule->id, 'user_id' => $row['user_id'],
                ], [
                    'percentage' => $row['percentage'], 'active' => true, 'updated_by' => $request->user()->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            ActivityLog::create(['company_id' => $company, 'activity_by' => $request->user()->id, 'requested_host' => $request->ip(),
                'message' => 'Schedule ' . $schedule->id . ' quota discounts changed: ' . json_encode(['before' => $before, 'after' => $input['discounts']])]);
            return response()->json(['message' => 'Quota discounts saved.']);
        });
    }
}
