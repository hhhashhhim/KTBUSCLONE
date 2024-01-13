<?php

namespace App\Models\Refreshment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Refreshment\HotelFood;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelFoodDealDetail extends Model
{
    use HasFactory, softDeletes;
    // protected $table = "hotel_foods";
    
    protected $guarded = [];

    public function food()
    {
        return $this->hasOne( HotelFood::class, 'id', 'food_id');
    }

}
