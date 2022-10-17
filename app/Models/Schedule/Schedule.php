<?php

namespace App\Models\Schedule;

use App\Models\Bus\Bus;
use App\Models\Bus\BusClass;
use App\Models\City;
use App\Models\Company;
use App\Models\FareClass;
use App\Models\Route\Route;
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
        'seat_map' => 'array',
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

    public function single_bus()
    {
        return $this->hasOne( Bus::class, 'id', 'bus_id' );
    }

    public function selective_bus()
    {
        return $this->hasOne(BusClass::class, 'id', 'selected_bus_class_id');
    }
    public function single_bus_class()
    {
        return $this->hasOne(FareClass::class, 'id', 'bus_class_id');
    }
    public function singleRoute()
    {
        return $this->hasOne(Route::class, 'id', 'route_id');
    }
    public function singleCity()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }
    public function singleTerminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }

}
