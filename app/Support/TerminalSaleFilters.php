<?php

namespace App\Support;

use Illuminate\Http\Request;

class TerminalSaleFilters
{
    public const STATUSES = [
        'all' => 'All report statuses', 'booked' => 'Booked', 'over-issue' => 'Over-issued',
        'canceled' => 'Cancelled', 'refunded' => 'Refund recorded (cancelled tickets)',
        'unpaid' => 'Expired / cancelled unpaid reservations',
    ];
    public const CANCELLATIONS = [
        'all' => 'All cancellations (including unpaid reservations)',
        'financial' => 'Sales / settlement entries (refund or cancellation charge)',
        'charges' => 'Cancellations with charges only',
        'refund' => 'Cancellations with a recorded refund only',
    ];

    public static function validate(Request $request): void
    {
        // JSON requests send arrays; the print form sends comma-separated values.
        // Single values from older clients remain supported.
        foreach (['ticket_status', 'cancellation_scope'] as $key) {
            $value = $request->input($key, []);
            if ($value === null || $value === '') $value = [];
            if (is_string($value)) $value = array_map('trim', explode(',', $value));
            $request->merge([$key => $value]);
        }
        $request->validate([
            'ticket_status' => ['array', 'max:6'],
            'ticket_status.*' => ['required', 'string', 'in:' . implode(',', array_keys(self::STATUSES))],
            'cancellation_scope' => ['array', 'max:4'],
            'cancellation_scope.*' => ['required', 'string', 'in:' . implode(',', array_keys(self::CANCELLATIONS))],
        ]);
    }

    private static function selections(Request $request, string $key): array
    {
        $value = $request->input($key, []);
        if (is_string($value)) $value = array_map('trim', explode(',', $value));
        return array_values(array_unique(array_filter($value ?: [])));
    }

    public static function includes($ticket, Request $request): bool
    {
        $statuses = self::selections($request, 'ticket_status');
        $scopes = self::selections($request, 'cancellation_scope');
        $unpaid = TerminalSaleStatus::isUnpaidCancellation($ticket);
        $cancelled = $ticket->type === 'canceled';
        // refund_amount is a saved amount, not a live payment-provider confirmation.
        $recordedRefund = $cancelled && !$unpaid && (float) $ticket->refund_amount > 0;
        $statusMatches = !$statuses || in_array('all', $statuses, true)
            || in_array($ticket->type, $statuses, true)
            || (in_array('refunded', $statuses, true) && $recordedRefund)
            || (in_array('unpaid', $statuses, true) && $unpaid);
        if (!$statusMatches) return false;
        // An explicit unpaid-reservation search is an audit view, not a claim view.
        if (!$cancelled || ($unpaid && in_array('unpaid', $statuses, true))) return true;
        // OR within each multi-select; the two filters are combined with AND.
        if (!$scopes || in_array('all', $scopes, true)) return true;
        return (in_array('financial', $scopes, true) && !$unpaid && ((float) $ticket->refund > 0 || (float) $ticket->cancellation_charges > 0))
            || (in_array('charges', $scopes, true) && !$unpaid && (float) $ticket->cancellation_charges > 0)
            || (in_array('refund', $scopes, true) && $recordedRefund);
    }

    public static function summary(Request $request): string
    {
        $statuses = self::selections($request, 'ticket_status');
        $scopes = self::selections($request, 'cancellation_scope');
        $labels = function ($values, $options) {
            return !$values || in_array('all', $values, true) ? $options['all']
                : implode(' OR ', array_map(fn ($value) => $options[$value] ?? $value, $values));
        };
        return $labels($statuses, self::STATUSES) . ' — ' . $labels($scopes, self::CANCELLATIONS)
            . (in_array('unpaid', $statuses, true) ? ' (Audit: explicitly selected unpaid reservations included)' : '');
    }
}
