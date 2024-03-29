<?php

namespace App\Models\Booking;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCancel extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
    public function ticket(){
        return $this->hasOne( Ticket::class,'id','ticket_id' );
    }
    public function addedBy(){
        return $this->hasOne( User::class,'id','added_by' );
    }
    public function added_by_name(){
        return $this->hasOne( User::class,'id','added_by' );
    }
    public function updatedBy(){
        return $this->hasOne( User::class,'id','updated_by' );
    }






}
