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
    
}
