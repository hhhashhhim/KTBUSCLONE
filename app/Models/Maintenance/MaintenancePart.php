<?php

namespace App\Models\Maintenance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePart extends Model
{
    use HasFactory, softDeletes;
    protected $table = "fleet_maintenance_parts";
    
    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }

    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }

}
