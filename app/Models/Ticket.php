<?php

namespace App\Models;

use App\Models\Bus\BusClass;
use App\Models\Schedule\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory,softDeletes;
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }
    public function departure_city()
    {
        return $this->hasOne( City::class, 'id', 'departure_city_id');
    }
    public function destination_city()
    {
        return $this->hasOne( City::class, 'id', 'destination_city_id');
    }
    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

    public function bus_class(){
        return $this->hasOne( BusClass::class,'id','bus_class_id' );
    }

    public function customer(){
        return $this->hasOne( Customer::class,'id','customer_id' );
    }

    public function schedule(){
        return $this->hasOne( Schedule::class,'id','schedule_id' );
    }




}
