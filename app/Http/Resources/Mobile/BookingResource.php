<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'reference' => $this['reference'],
            'status' => $this['status'],
            'payment_status' => $this['payment_status'],
            'payment' => $this['payment'] ?? null,
            'origin_name' => $this['origin_name'],
            'destination_name' => $this['destination_name'],
            'departure_at' => $this['departure_at'],
            'seats' => $this['seats'],
            'total' => $this['total'],
            'qr_value' => $this['qr_value'],
            'passengers' => $this['passengers'] ?? [],
        ];
    }
}
