<?php

namespace App\Models\Booking;

use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Schedule\Schedule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketELT extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function departure()
    {
        return $this->hasOne(City::class, 'id', 'departure_city');
    }

    public function destination()
    {
        return $this->hasOne(City::class, 'id', 'destination_city');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class, 'id', 'ticket_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'id', 'schedule_id');
    }

}
