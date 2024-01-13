<?php

namespace App\Models\Route;

use App\Models\City;
use App\Models\Company;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteFare extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'routes_fares';

    protected $guarded = [];

    public function fare_details(){
        return $this->hasOne( FareTable::class,'id','fare_id' );
    }
    public function fare_class(){
        return $this->hasOne( FareClass::class,'id','fare_class_id' );
    }

    public function route(){
        return $this->hasOne( Route::class,'id','route_id');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

    public function city_from(){
        return $this->belongsTo(City::class, 'departure_city_id',  'id');
    }

    public function city_to(){
        return $this->belongsTo(City::class, 'destination_city_id',  'id');
    }
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}
