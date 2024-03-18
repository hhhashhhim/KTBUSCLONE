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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketClosingMerge extends Model
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
