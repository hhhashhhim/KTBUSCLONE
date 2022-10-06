<?php

namespace App\Models\Bus;

use App\Models\FareClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use HasFactory, softDeletes;

    protected $casts = [
        'seat_map' => 'array'
    ];

    protected $guarded = [];

    public function busClass()
    {
        return $this->hasOne(FareClass::class, 'id', 'fare_class_id');
    }


}
