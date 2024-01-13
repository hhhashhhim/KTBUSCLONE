<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityToCity extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'city_to_city';
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function updated_by()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

}
