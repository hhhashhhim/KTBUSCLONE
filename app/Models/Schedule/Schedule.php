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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'route_city_terminal' => 'array',
    ];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by' );
    }

    public function updated_by()
    {
        return $this->hasOne( User::class, 'id', 'updated_by' );
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

    public function bus_class()
    {
        return $this->hasOne( BusClass::class,'id', 'bus_class_id');
    }
    public function fare_class()
    {
        return $this->hasOne(FareClass::class, 'id', 'fare_class_id');
    }
    public function route()
    {
        return $this->hasOne(Route::class, 'id', 'route_id');
    }
    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }
    public function discount()
    {
        return $this->hasOne(Discount::class, 'id', 'discount_id');
    }
    public function surcharge()
    {
        return $this->hasOne(Surcharge::class, 'id', 'surcharge_id');
    }
    public function scheduleDetail()
    {
        return $this->hasMany(ScheduleDetail::class, 'schedule_id', 'id');
    }
    public function schedule_time()
    {
        return $this->hasOne(ScheduleDetail::class, 'schedule_id', 'id');
    }

}
