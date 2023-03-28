<?php

namespace App\Models\Schedule;

use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Company;
use App\Models\Discount\Discount;
use App\Models\FareClass;
use App\Models\Route\Route;
use App\Models\Surcharge\Surcharge;
use App\Models\Terminal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketClosing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }
    public function bus()
    {
        return $this->hasOne( Bus::class, 'id', 'bus_id' );
    }
    public function schedule()
    {
        return $this->hasOne( Schedule::class, 'id', 'schedule_id' );
    }
    public function members()
    {
        return $this->hasMany( TicketClosingMember::class, 'ticket_closing_id', 'id' );
    }
    public function tickets()
    {
        return $this->hasMany( Ticket::class, 'ticket_closing_id', 'id' );
    }

}
