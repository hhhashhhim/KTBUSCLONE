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
    public function from(){
        return $this->belongsToMany( City::class,'city_to_city','from','to' );
    }
    public function to(){
        return $this->belongsToMany( City::class,'city_to_city','to','from' );
    }
}
