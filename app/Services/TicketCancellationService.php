<?php

namespace App\Services;

use App\Models\Booking\BookingCancel;
use App\Models\Booking\TicketELT;
use App\Models\Customer;
use App\Models\FareTable;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\Ticket;

class TicketCancellationService
{
    public function cancelTicket(Ticket $ticket, $percentage = 0, $reason = null, $addedBy = null)
    {
        $this->reverseLoyaltyPoints($ticket);
        $this->deleteElt($ticket);

        $previousType = $ticket->type;

        $ticket->update([
            'type' => 'canceled',
        ]);

        BookingCancel::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'percentage' => $percentage,
            'reason' => $reason,
            'type' => $previousType,
            'added_by' => $addedBy,
        ]);

        $ticket->delete();

        return $previousType;
    }

    private function deleteElt(Ticket $ticket)
    {
        $elt = TicketELT::where('ticket_id', $ticket->id)->first();

        if ($elt) {
            $elt->delete();
        }
    }

    private function reverseLoyaltyPoints(Ticket $ticket)
    {
        $customer = Customer::find($ticket->customer_id);

        if (!$customer) {
            return;
        }

        $card = CardAssign::where([
            'cnic' => $customer->cnic,
            'company_id' => $ticket->company_id,
        ])->with('cardCategory')->first();

        if (!$card || !$card->cardCategory) {
            return;
        }

        $subPoint = 0;

        if ($card->cardCategory->point_type == 'flatPoints') {
            $pointFlat = (float) $card->cardCategory->point_flat;

            if ($pointFlat > 0) {
                $subPoint = $ticket->seat_fare / $pointFlat;
            }
        } else {
            $fareTable = FareTable::where([
                'from_city_id' => $ticket->departure_city_id,
                'to_city_id' => $ticket->destination_city_id,
                'company_id' => $ticket->company_id,
            ])->first();

            $pointDistance = (float) $card->cardCategory->point_distance;

            if ($fareTable && $pointDistance > 0) {
                $subPoint = $fareTable->distance_in_km / $pointDistance;
            }
        }

        if ($ticket->type == 'booked' && $subPoint > 0) {
            $card->decrement('starting_points', $subPoint);
        }

        if ($ticket->points_usage > 0) {
            $card->increment('starting_points', $ticket->points_usage);
        }
    }
}
