<?php

namespace App\Models;

use App\Models\Booking\TicketELT;
use App\Models\Booking\TicketsOverIssue;
use App\Models\Bus\BusClass;
use App\Models\Bus\Bus;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\TicketReschedule;
use App\Models\Schedule\Schedule;
use App\Models\Route\Route;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalCommission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    
    public function added_name()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    
    public function route()
    {
        return $this->hasOne(Route::class, 'id', 'route_id');
    }

    public function departure_city()
    {
        return $this->hasOne(City::class, 'id', 'departure_city_id');
    }

    public function destination_city()
    {
        return $this->hasOne(City::class, 'id', 'destination_city_id');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
    
    public function updated_name()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function seatClass()
    {
        return $this->hasOne(FareClass::class, 'id', 'bus_class_id');
    }

    public function busClass()
    {
        return $this->hasOne(BusClass::class, 'id', 'bus_class_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'id', 'schedule_id');
    }

    public function scheduleDetail()
    {
        return $this->hasOne(ScheduleDetail::class, 'id', 'schedule_details_id');
    }

    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }

    public function ticketElt()
    {
        return $this->hasOne(TicketELT::class, 'ticket_id', 'id');
    }

    public function elt()
    {
        return $this->hasOne(TicketELT::class, 'ticket_id', 'id');
    }

    public function commission()
    {
        return $this->hasOne(TerminalCommission::class,"terminal_id","terminal_id");
    }

    public function bus()
    {
        return $this->hasOne(Bus::class,"id","bus_id");
    }

    public function cancel_ticket()
    {
        return $this->hasOne(BookingCancel::class, 'ticket_id', 'id');
    }

    public function overIssueSeats()
    {
        return $this->hasOne(TicketsOverIssue::class, 'ticket_id', 'id');
    }
    
    public function reschedule_seat()
    {
        return $this->hasOne(TicketReschedule::class, 'old_ticket_id', 'id');
    }
}
