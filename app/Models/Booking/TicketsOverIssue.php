<?php

namespace App\Models\Booking;

use App\Models\Ticket;
use App\Models\User;
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

    public function overissue_by()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
}
