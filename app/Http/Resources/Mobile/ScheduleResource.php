<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'schedule_detail_id' => $this['schedule_detail_id'],
            'route_id' => $this['route_id'],
            'origin' => $this['origin'],
            'destination' => $this['destination'],
            'departure_at' => $this['departure_at'],
            'arrival_at' => $this['arrival_at'],
            'bus_class' => $this['bus_class'],
            'available_seats' => $this['available_seats'],
            'total_seats' => $this['total_seats'],
            'fares' => $this['fares'],
        ];
    }
}
