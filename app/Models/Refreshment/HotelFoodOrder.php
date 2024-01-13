<?php

namespace App\Models\Refreshment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelFoodOrder extends Model
{
    use HasFactory, softDeletes;
    // protected $table = "hotel_foods";
    
    protected $guarded = [];

    public function food()
    {
        return $this->hasOne( HotelFood::class, 'id', 'item_id');
    }
    public function deal()
    {
        return $this->hasOne( HotelFoodDeal::class, 'id', 'item_id');
    }
    public function hotel()
    {
        return $this->hasOne( Hotel::class, 'id', 'hotel_id');
    }
    public function bus()
    {
        return $this->hasOne( Bus::class, 'id', 'bus_id');
    }

}
