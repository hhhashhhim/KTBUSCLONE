<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log($module, $action, $description, $recordId = null, array $oldValues = [], array $newValues = [], ?Request $request = null)
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $request = $request ?: request();
        $payload = [
            'module' => $module,
            'action' => strtoupper($action),
            'record_id' => $recordId,
            'description' => $description,
            'old' => self::compactValues($oldValues),
            'new' => self::compactValues($newValues),
            'user_agent' => $request ? $request->userAgent() : null,
        ];

        $message = sprintf(
            '%s | %s | %s | %s',
            $user->name,
            $payload['module'],
            $payload['action'],
            $payload['description']
        );

        if ($recordId !== null) {
            $message .= ' | Record ID: ' . $recordId;
        }

        if (!empty($payload['old'])) {
            $message .= ' | Old: ' . json_encode($payload['old']);
        }

        if (!empty($payload['new'])) {
            $message .= ' | New: ' . json_encode($payload['new']);
        }

        if (!empty($payload['user_agent'])) {
            $message .= ' | Agent: ' . $payload['user_agent'];
        }

        return ActivityLog::create([
            'activity_by' => $user->id,
            'message' => substr($message, 0, 1000),
            'requested_host' => $request ? $request->ip() : '127.0.0.1',
            'company_id' => $user->company_id,
        ]);
    }

    private static function compactValues(array $values)
    {
        return array_filter($values, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }
}
