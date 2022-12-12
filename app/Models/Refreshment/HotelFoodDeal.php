<?php

namespace App\Models\Refreshment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Refreshment\HotelFoodDealDetail;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelFoodDeal extends Model
{
    use HasFactory, softDeletes;
    // protected $table = "hotel_foods";
    
    protected $guarded = [];

    public function dealDetails()
    {
        return $this->hasMany( HotelFoodDealDetail::class, 'food_deal_id', 'id');
    }

}
