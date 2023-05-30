<?php

namespace App\Models\Booking;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketsOverIssue extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function ticket(){
        return $this->hasOne( Ticket::class,'id','ticket_id' );
    }
}
