<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class City extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function fares(){
        return $this->hasMany(FareTable::class, 'from_city_id', 'id');
    }
    public function city_from(){
        return $this->belongsToMany( City::class,'city_to_city','destination_city_id','departure_city_id' );
    }
    public function city_to(){
        return $this->belongsToMany( City::class,'city_to_city','departure_city_id','destination_city_id' );
    }

    public function terminal(){
        return $this->hasMany(Terminal::class, 'city_id', 'id');
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
