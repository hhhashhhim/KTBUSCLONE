<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PassengerSummaryService
{
    /** Same booked-ticket count as the passenger PDF, using the already loaded dated-trip tickets. */
    public function byDepartureCity(Collection $tickets): array
    {
        return $tickets->filter(function ($ticket) {
            return $ticket->type === 'booked' && !$ticket->deleted_at;
        })->groupBy('departure_city_id')->map(function ($group, $cityId) {
            return [
                'city_id' => $cityId,
                'city_name' => $group->first()->departure_city->name ?? 'Unknown city',
                'passengers' => $group->count(),
            ];
        })->sortBy('city_name')->values()->all();
    }
}
