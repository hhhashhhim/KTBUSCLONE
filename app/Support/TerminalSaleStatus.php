<?php

namespace App\Support;

class TerminalSaleStatus
{
    public static function isUnpaidCancellation($ticket): bool
    {
        // The cancellation record preserves the ticket's type before cancellation.
        // A missing transaction reference alone does not prove a ticket was unpaid.
        return $ticket->type === 'canceled'
            && data_get($ticket, 'cancel_ticket.type') === 'advance booking';
    }

    public static function label($ticket): string
    {
        if (self::isUnpaidCancellation($ticket)) {
            return strtolower(trim((string) data_get($ticket, 'cancel_ticket.reason'))) === 'auto cancel'
                ? 'Expired reservation' : 'Cancelled reservation';
        }

        return $ticket->type === 'canceled' ? 'Cancelled' : (string) $ticket->type;
    }
}
