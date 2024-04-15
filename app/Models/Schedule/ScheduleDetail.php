<?php

namespace App\Models\Schedule;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Models\City;
use App\Models\Bus\BusClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    /* Fillable
    *  Id
    * schedule_date ( Date At which bus will leave from first terminal ) 
    */

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }
    public function departure_city()
    {
        return $this->hasOne( City::class, 'id', 'departure_id' );
    }
    public function destination_city()
    {
        return $this->hasOne( City::class, 'id', 'destination_id' );
    }
    public function schedule(){
        return $this->belongsTo( Schedule::class,'schedule_id','id');
    }
    public function ticket(){
        return $this->hasMany( Ticket::class,'id','schedule_details_id');
    }
    public function bus_class(){
        return $this->hasOne( BusClass::class,'id','bus_class_id');
    }
    public function bus_class_map(){
        return $this->hasOne( BusClass::class,'id','bus_class_id');
    }



}
