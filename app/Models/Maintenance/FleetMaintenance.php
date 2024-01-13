<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetMaintenance extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function busName()
    {
        return $this->hasOne( Bus::class, 'id', 'bus_id');
    }
    
    public function partName()
    {
        return $this->hasOne( MaintenancePart::class, 'id', 'part_id');
    }

    // public function company(){
    //     return $this->hasOne( Company::class,'id','company_id' );
    // }

}
