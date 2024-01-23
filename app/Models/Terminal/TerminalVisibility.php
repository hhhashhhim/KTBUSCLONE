<?php

namespace App\Models\Terminal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use Illuminate\Database\Eloquent\SoftDeletes;

class TerminalVisibility extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function departure()
    {
        return $this->hasOne(City::class, 'id', 'departure_city_id');
    }
    public function destination()
    {
        return $this->hasOne(City::class, 'id', 'destination_city_id');
    }
}
