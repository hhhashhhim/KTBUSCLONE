<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Maintenance\MaintenancePart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePartLink extends Model
{
    use HasFactory, softDeletes;
    
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }
    
    public function maintenancePart()
    {
        return $this->hasOne( MaintenancePart::class, 'id', 'part_id');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

}
