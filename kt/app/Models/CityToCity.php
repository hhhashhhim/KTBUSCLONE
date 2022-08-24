<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityToCity extends Model
{
    use HasFactory;
    protected $table = 'city_to_city';
    protected $fillable = [
        'from','to',
    ];
}
