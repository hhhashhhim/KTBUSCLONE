<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $fillable = ['name','added_by'];

    public function fares(){
        return $this->hasMany(FareTable::class, 'from_city_id', 'id');
    }
    public function city_from(){
        return $this->belongsToMany( City::class,'city_to_city','destination_city_id','departure_city_id' );
    }
    public function city_to(){
        return $this->belongsToMany( City::class,'city_to_city','departure_city_id','destination_city_id' );
    }

}
