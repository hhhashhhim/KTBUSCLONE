<?php

namespace App\Models\Route;

use App\Models\Company;
use App\Models\User;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubRoute extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function from_city_data()
    {
        return $this->belongsTo(City::class, 'from_city', 'id');
    }
    public function to_city_data()
    {
        return $this->belongsTo(City::class, 'to_city', 'id');
    }
    public function added_by_data()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }
}
