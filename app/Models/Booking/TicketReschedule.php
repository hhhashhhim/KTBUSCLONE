<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\City;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketReschedule extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];


    public function old_departure()
    {
        return $this->belongsTo(City::class, 'departure_city_id', 'id');
    }

    public function old_destination()
    {
        return $this->belongsTo(City::class, 'destination_city_id', 'id');
    }
    
    public function new_departure()
    {
        return $this->belongsTo(City::class, 'reschedule_departure_city_id', 'id');
    }

    public function new_destination()
    {
        return $this->belongsTo(City::class, 'reschedule_destination_city_id', 'id');
    }
    
    public function new_ticket()
    {
        return $this->hasOne(Ticket::class, 'id', 'new_ticket_id');
    }
}
