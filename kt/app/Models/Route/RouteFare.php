<?php

namespace App\Models\Route;

use App\Models\City;
use App\Models\FareTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteFare extends Model
{
    use HasFactory;
    protected $table = 'routes_fares';

    protected $fillable = [
        'route_id', 
        'fare_id',
        'city_from_id',
        'city_to_id',
        'company_id',
        'added_by'
    ];

    public function fare_details(){
        return $this->hasMany( FareTable::class,'id','fare_id' );
    }
    
    public function route(){
        return $this->hasOne( Route::class,'id','route_id');
    }
    
    public function city_from(){
        return $this->belongsTo(City::class, 'city_from_id',  'id');
    }

    public function city_to(){
        return $this->belongsTo(City::class, 'city_to_id',  'id');
    }
}
