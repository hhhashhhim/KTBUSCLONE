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
        $request->validate([
            'ticket_status' => ['sometimes', 'required', 'in:' . implode(',', array_keys(self::STATUSES))],
            'cancellation_scope' => ['sometimes', 'required', 'in:' . implode(',', array_keys(self::CANCELLATIONS))],
        ]);
    }

    public static function includes($ticket, Request $request): bool
    {
        $status = $request->input('ticket_status', 'all');
        $unpaid = TerminalSaleStatus::isUnpaidCancellation($ticket);
        $cancelled = $ticket->type === 'canceled';
        // refund_amount is a saved amount, not a live payment-provider confirmation.
        $recordedRefund = $cancelled && !$unpaid && (float) $ticket->refund_amount > 0;
        if ($status === 'refunded' && !$recordedRefund) return false;
        if ($status === 'unpaid' && !$unpaid) return false;
        if (in_array($status, ['booked', 'over-issue', 'canceled'], true) && $ticket->type !== $status) return false;
        // An explicit unpaid-reservation search is an audit view, not a claim view.
        if (!$cancelled || $status === 'unpaid') return true;
        switch ($request->input('cancellation_scope', 'all')) {
            case 'financial': return !$unpaid && ((float) $ticket->refund > 0 || (float) $ticket->cancellation_charges > 0);
            case 'charges': return !$unpaid && (float) $ticket->cancellation_charges > 0;
            case 'refund': return $recordedRefund;
            default: return true;
        }
    }

    public static function summary(Request $request): string
    {
        $status = $request->input('ticket_status', 'all');
        return (self::STATUSES[$status] ?? self::STATUSES['all']) . ' — '
            . ($status === 'unpaid' ? 'Audit view: includes unpaid reservations' : (self::CANCELLATIONS[$request->input('cancellation_scope', 'all')] ?? self::CANCELLATIONS['all']));
    }
}
