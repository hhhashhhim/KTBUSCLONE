<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static where(string $string, $company_id)
 */
class LimitedSeat extends Model
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
