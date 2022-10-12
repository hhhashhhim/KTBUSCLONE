<?php

namespace App\Models;

use App\Models\Route\RouteFare;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FareTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function city_name()
    {
        return $this->belongsTo(City::class, 'to_city_id', 'id');
    }

    public function city_to()
    {
        return $this->belongsTo(City::class, 'to_city_id', 'id');
    }

    public function city_from()
    {
        return $this->belongsTo(City::class, 'from_city_id', 'id');
    }

    public function class()
    {
        return $this->hasOne(FareClass::class, 'id', 'fare_class');
    }

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }


    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}
