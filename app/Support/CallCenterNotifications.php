<?php

namespace App\Support;

use App\Models\CallCenter\CallLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallCenterNotifications
{
    // Called inside the call's transaction, after validation and its audit entry.
    public static function assigned(CallLog $call, User $actor, array $before = []): void
    {
        if (($before['category'] ?? null) === $call->category
            && (int) ($before['assigned_to'] ?? 0) === (int) $call->assigned_to) { return; }

        $now = Carbon::now('Asia/Karachi')->format('Y-m-d H:i:s');
        DB::table('call_center_notifications')->where('call_id', $call->id)->whereNull('superseded_at')->update(['superseded_at' => $now]);
        if ($call->category !== 'C' || $call->status === 'closed' || !$call->assigned_to || (int) $call->assigned_to === (int) $actor->id) { return; }

        DB::table('call_center_notifications')->insert([
            'company_id' => $call->company_id, 'recipient_id' => $call->assigned_to,
            'actor_id' => $actor->id, 'call_id' => $call->id, 'created_at' => $now,
        ]);
    }
}
