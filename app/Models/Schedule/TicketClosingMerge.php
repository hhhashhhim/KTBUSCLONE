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
use App\Models\User;
use App\Models\Ticket;
use App\Models\Expense\TicketMergeExpense;
use App\Models\TicketClosingShortage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketClosingMerge extends Model
{
    use HasFactory, SoftDeletes;
    //This will generate on assigning bus and on merge two records will be deleted and one record for both created
    protected $fillable = [
        "bus_id",
        "schedule_departure_date",
        "schedule_return_date",
        "schedule_complete",
        "description",
        "closing_date",
        "company_id",
        "added_by",
        "time",
    ];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }
    
    public function bus()
    {
        return $this->hasOne( Bus::class, 'id', 'bus_id' );
    }
    
    public function shortage()
    {
        return $this->hasMany(TicketClosingShortage::class, 'ticket_closing_id', 'id');
    }


    
    public function closing()
    {
        return $this->hasMany( TicketClosing::class, 'ticket_merge_id', 'id' );
    }

    public function tickets()
    {
        return $this->hasMany( Ticket::class, 'ticket_merge_id', 'id' );
    }
    
    public function expenses()
    {
        return $this->hasMany( TicketMergeExpense::class, 'ticket_merge_id', 'id' );
    }
}
