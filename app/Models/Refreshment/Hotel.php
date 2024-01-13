<?php

namespace App\Models\Refreshment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Refreshment\HotelFood;
use App\Models\Refreshment\HotelFoodDeal;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function user()
    {
        return $this->hasOne( User::class, 'id', 'user_id');
    }
    
    public function foods()
    {
        return $this->hasMany( HotelFood::class, 'hotel_id', 'id');
    }
    
    public function deals()
    {
        return $this->hasMany( HotelFoodDeal::class, 'hotel_id', 'id');
    }

}
